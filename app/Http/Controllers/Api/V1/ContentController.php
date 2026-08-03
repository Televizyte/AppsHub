<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Support\Publishing\PublicationVisibility;

class ContentController extends Controller
{
    public function index(Request $request, string $appSlug)
    {
        $app = DB::table('apps')
            ->select(['id', 'name', 'slug'])
            ->where('slug', $appSlug)
            ->first();

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => "App not found: {$appSlug}",
            ], 404);
        }

        $bucket = $request->query('bucket');
        $status = $request->query('status', 'published');
        $featuredOnly = filter_var($request->query('featured_only', false), FILTER_VALIDATE_BOOLEAN);

        $perPage = (int) $request->query('per_page', 20);
        if ($perPage < 1) {
            $perPage = 20;
        }
        if ($perPage > 50) {
            $perPage = 50;
        }

        $qb = DB::table('content_posts')
            ->where('app_id', (int) $app->id);

        if (is_string($bucket) && trim($bucket) !== '') {
            $qb->where('bucket', trim($bucket));
        }

        if (is_string($status) && trim($status) !== '') {
            $normalizedStatus = trim($status);

            if (in_array($normalizedStatus, ['published', 'publish', 'active'], true)) {
                PublicationVisibility::apply($qb);
            } else {
                $qb->where('status', $normalizedStatus);
            }
        }

        if ($featuredOnly) {
            $qb->where('is_featured', 1);
        }

        $qb->orderByDesc('published_at')
            ->orderByDesc('publish_at')
            ->orderByDesc('id');

        $paginator = $qb->paginate($perPage)->appends($request->query());

        $items = collect($paginator->items())
            ->map(fn ($row) => $this->normalizeContentPostRow((array) $row, compact: true))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'filters' => [
                'bucket' => $bucket,
                'status' => $status,
                'featured_only' => $featuredOnly,
                'per_page' => $perPage,
            ],

            /*
             * Flutter HubStore reads decoded["items"].
             * Older backend returned only "data", so frontend Inspire buckets
             * became empty and card images never reached the app.
             * Keep both keys for backward compatibility.
             */
            'items' => $items,
            'data' => $items,

            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, string $appSlug, string $slug)
    {
        $app = DB::table('apps')
            ->select(['id', 'name', 'slug'])
            ->where('slug', $appSlug)
            ->first();

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => "App not found: {$appSlug}",
            ], 404);
        }

        $postQuery = DB::table('content_posts')
            ->where('app_id', (int) $app->id);

        PublicationVisibility::apply($postQuery);

        $post = $postQuery
            ->where(function ($query) use ($slug) {
                $query->where('slug', $slug);

                if (is_numeric($slug)) {
                    $query->orWhere('id', (int) $slug);
                }
            })
            ->first();

        if (! $post) {
            return response()->json([
                'ok' => false,
                'error' => 'CONTENT_NOT_FOUND',
                'message' => "Content not found: {$slug}",
            ], 404);
        }

        $item = $this->normalizeContentPostRow((array) $post, compact: false);

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'item' => $item,
            'data' => $item,
            'likes_count' => $item['likes_count'] ?? 0,
            'comments_count' => $item['comments_count'] ?? 0,
            'liked' => false,
            'liked_by_me' => false,
        ]);
    }

    private function normalizeContentPostRow(array $row, bool $compact): array
    {
        $id = (int) ($row['id'] ?? 0);
        $slug = trim((string) ($row['slug'] ?? ''));
        $bucket = trim((string) ($row['bucket'] ?? ''));
        $title = trim((string) ($row['title'] ?? ''));
        $subtitle = trim((string) ($row['subtitle'] ?? ''));

        $meta = $this->decodeJson($row['meta_json'] ?? null) ?? [];
        $tags = $this->decodeJson($row['tags_json'] ?? null);
        $blocks = $this->decodeJson($row['blocks_json'] ?? null);

        $coverImage = $this->resolveImageUrl($row['cover_image_url'] ?? null);
        $metaImage = $this->resolveImageUrl(data_get($meta, 'image_url'));
        $metaCover = $this->resolveImageUrl(data_get($meta, 'cover_image_url'));
        $metaBackground = $this->resolveImageUrl(data_get($meta, 'background_image_url'));

        $imageUrl = $this->firstNonEmptyString([
            $coverImage,
            $metaImage,
            $metaCover,
            $metaBackground,
        ]);

        $badge = $bucket !== ''
            ? [
                'key' => $bucket,
                'text' => $this->bucketLabel($bucket),
            ]
            : null;

        $publishedAt = $this->firstNonEmptyString([
            $row['published_at'] ?? null,
            $row['publish_at'] ?? null,
            $row['created_at'] ?? null,
        ]);

        $bodyExcerpt = $this->plainSummary($this->firstNonEmptyString([
            $row['body_html'] ?? null,
            $row['body'] ?? null,
            $row['content'] ?? null,
        ]));

        $basePayload = [
            'screen' => 'content_read',
            'content_type' => $bucket !== '' ? $bucket : null,
            'bucket' => $bucket !== '' ? $bucket : null,
            'badge' => $badge,
            'slug' => $slug !== '' ? $slug : (string) $id,
            'featured' => isset($row['is_featured']) ? (bool) $row['is_featured'] : false,
            'published_at' => $publishedAt !== '' ? $publishedAt : null,
            'excerpt' => $bodyExcerpt ?: null,
            'summary' => $bodyExcerpt ?: null,
            'description' => $bodyExcerpt ?: null,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'cover_image_url' => $imageUrl !== '' ? $imageUrl : null,
        ];

        $item = [
            'id' => $id,
            'slug' => $slug !== '' ? $slug : (string) $id,
            'type' => 'content_post',
            'bucket' => $bucket !== '' ? $bucket : null,
            'content_type' => $bucket !== '' ? $bucket : null,
            'badge' => $badge,
            'title' => $title !== '' ? $title : null,
            'subtitle' => $subtitle !== '' ? $subtitle : null,
            'excerpt' => $bodyExcerpt ?: ($subtitle !== '' ? $subtitle : null),
            'summary' => $bodyExcerpt ?: null,
            'description' => $bodyExcerpt ?: null,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
            'cover_image_url' => $imageUrl !== '' ? $imageUrl : null,
            'thumbnail_url' => $imageUrl !== '' ? $imageUrl : null,
            'featured_image' => $imageUrl !== '' ? $imageUrl : null,
            'route' => $slug !== '' ? ('/content/' . $slug) : ('/content/' . $id),
            'author_name' => $row['author_name'] ?? null,
            'is_featured' => isset($row['is_featured']) ? (bool) $row['is_featured'] : false,
            'published_at' => $publishedAt !== '' ? $publishedAt : null,
            'publish_at' => $row['publish_at'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
            'meta' => $meta,
            'payload' => $basePayload,
        ];

        if (! $compact) {
            $item['body_html'] = $row['body_html'] ?? null;
            $item['body'] = $row['body'] ?? null;
            $item['content'] = $row['content'] ?? null;
            $item['blocks'] = $blocks;
            $item['blocks_json'] = $blocks;
            $item['tags'] = $tags;
            $item['likes_count'] = (int) ($row['likes_count'] ?? 0);
            $item['comments_count'] = (int) ($row['comments_count'] ?? 0);
        }

        return $item;
    }

    private function resolveImageUrl($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $raw = trim($value);
        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, 'http://') || str_starts_with($raw, 'https://')) {
            return $raw;
        }

        $path = ltrim($raw, '/');

        if (str_starts_with($path, 'storage/')) {
            return url('/' . $path);
        }

        if (Storage::disk('public')->exists($path)) {
            return url(Storage::disk('public')->url($path));
        }

        return url('/storage/' . $path);
    }

    private function bucketLabel(string $bucket): string
    {
        $map = [
            'wordification' => 'Wordification',
            'motivation' => 'Motivation',
            'sod' => 'SOD',
            'sod_quotes' => 'SOD Quotes',
            'highlight' => 'Highlights',
            'highlights' => 'Highlights',
            'inside_dunamis' => 'Inside Dunamis',
            'articles' => 'Inside Dunamis',
        ];

        return $map[$bucket] ?? ucfirst(str_replace('_', ' ', $bucket));
    }

    private function decodeJson($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }


    private function plainSummary(?string $html): ?string
    {
        $text = trim(strip_tags((string) $html));

        if ($text === '') {
            return null;
        }

        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);

        return mb_strlen($text) > 220 ? mb_substr($text, 0, 217) . '...' : $text;
    }

    private function firstNonEmptyString(array $values, string $fallback = ''): string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return (string) $value;
            }
        }

        return $fallback;
    }
}

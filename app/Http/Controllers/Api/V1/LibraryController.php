<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ContentPost;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class LibraryController extends Controller
{
    public function quotesScripture(Request $request, string $appSlug)
    {
        $app = $this->app($appSlug);
        $category = Str::of((string) $request->query('category', ''))->snake()->toString();
        $perPage = min(max((int) $request->query('per_page', 30), 1), 100);

        $bucketMap = [
            'daily_quote' => ['daily_quotes', 'daily_quote'],
            'daily_scripture' => ['daily_scriptures', 'daily_scripture'],
            'sod_quotes' => ['sod_quotes'],
            'motivational_quotes' => ['motivational_quotes', 'motivation_quotes'],
            'paul_enenche_quotes' => ['paul_enenche_quotes', 'dr_enenche_quotes', 'enenche_quotes'],
        ];
        $allBuckets = array_values(array_unique(Arr::flatten(array_values($bucketMap))));
        $buckets = $category !== '' && isset($bucketMap[$category])
            ? $bucketMap[$category]
            : $allBuckets;

        $query = ContentPost::query()
            ->where('app_id', (int) $app->id)
            ->where('status', 'published')
            ->whereIn('bucket', $buckets)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('subtitle', 'like', "%{$search}%")
                    ->orWhere('body_html', 'like', "%{$search}%");
            });
        }

        $page = $query->paginate($perPage);
        $items = collect($page->items())->map(fn (ContentPost $post) => $this->quoteItem($post, $bucketMap))->values();

        return response()->json([
            'ok' => true,
            'items' => $items,
            'categories' => $this->quoteCategories($bucketMap),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function shortVideos(Request $request, string $appSlug)
    {
        $app = $this->app($appSlug);
        $channel = Str::of((string) $request->query('channel', ''))->snake()->toString();
        $perPage = min(max((int) $request->query('per_page', 30), 1), 100);

        $buckets = ['short_videos', 'shorts', 'home_shorts', 'inspire_shorts', 'message_highlight_shorts', 'motivation_shorts', 'bible_shorts', 'weird_facts'];
        $query = ContentPost::query()
            ->where('app_id', (int) $app->id)
            ->where('status', 'published')
            ->whereIn('bucket', $buckets)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });

        if ($channel !== '') {
            $query->where(function ($q) use ($channel) {
                $q->where('bucket', $channel)
                    ->orWhere('meta_json->channel_key', $channel)
                    ->orWhere('meta_json->channel', $channel);
            });
        }

        $page = $query->orderByDesc('published_at')->orderByDesc('id')->paginate($perPage);
        $items = collect($page->items())->map(fn (ContentPost $post) => $this->shortItem($post))->values();

        return response()->json([
            'ok' => true,
            'items' => $items,
            'channels' => collect($buckets)->map(fn ($key) => [
                'key' => $key,
                'label' => Str::of($key)->replace('_', ' ')->title()->toString(),
            ])->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    private function app(string $slug): App
    {
        return App::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();
    }

    private function quoteItem(ContentPost $post, array $bucketMap): array
    {
        $category = 'other_quotes';
        foreach ($bucketMap as $key => $buckets) {
            if (in_array($post->bucket, $buckets, true)) { $category = $key; break; }
        }
        $text = trim(strip_tags((string) ($post->body_html ?: $post->subtitle ?: $post->title)));
        return [
            'library_id' => "{$post->bucket}:{$post->id}",
            'source_id' => (int) $post->id,
            'source_type' => (string) $post->bucket,
            'category_key' => $category,
            'category_label' => Str::of($category)->replace('_', ' ')->title()->toString(),
            'title' => (string) ($post->title ?? ''),
            'text' => $text,
            'author' => (string) ($post->author_name ?? ''),
            'reference' => (string) data_get($post->meta_json, 'reference', ''),
            'image_url' => $post->cover_image_src,
            'published_at' => optional($post->published_at)->toIso8601String(),
        ];
    }

    private function shortItem(ContentPost $post): array
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];
        return [
            'library_id' => "short:{$post->id}",
            'id' => (int) $post->id,
            'channel_key' => (string) ($meta['channel_key'] ?? $meta['channel'] ?? $post->bucket),
            'channel_label' => (string) ($meta['channel_label'] ?? Str::of($post->bucket)->replace('_', ' ')->title()),
            'title' => (string) ($post->title ?? ''),
            'description' => (string) ($post->subtitle ?? ''),
            'thumbnail_url' => $post->cover_image_src,
            'video_url' => (string) ($meta['video_url'] ?? $meta['url'] ?? ''),
            'published_at' => optional($post->published_at)->toIso8601String(),
        ];
    }

    private function quoteCategories(array $bucketMap): array
    {
        return collect(array_keys($bucketMap))->map(fn ($key) => [
            'key' => $key,
            'label' => match ($key) {
                'sod_quotes' => 'SOD Quotes',
                'paul_enenche_quotes' => 'Dr Paul Enenche Quotes',
                default => Str::of($key)->replace('_', ' ')->title()->toString(),
            },
        ])->values()->all();
    }
}

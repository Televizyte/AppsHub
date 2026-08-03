<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdProfile;
use App\Models\App;
use App\Models\UserFollow;
use App\Support\Ads\AdResolver;
use App\Support\Icons\SvgIconRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\Publishing\PublicationVisibility;

class ContentRegistryController extends Controller
{
    public function index(Request $request, string $appSlug)
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            $app = (object) [
                'id' => (int) $currentApp->id,
                'name' => (string) $currentApp->name,
                'slug' => (string) $currentApp->slug,
                'is_active' => (bool) $currentApp->is_active,
            ];
        } else {
            $app = DB::table('apps')
                ->select(['id', 'name', 'slug', 'is_active'])
                ->where('slug', $appSlug)
                ->where('is_active', 1)
                ->first();
        }

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        if (! Schema::hasTable('content_posts')) {
            return response()->json([
                'ok' => false,
                'error' => 'CONTENT_TABLE_MISSING',
                'message' => 'content_posts table not found.',
            ], 500);
        }

        $viewer = auth('sanctum')->user();
        $tab = $this->cleanTab($request->query('tab', 'inspire'));
        $bucket = $this->cleanStr($request->query('bucket')) ?: $this->cleanStr($request->query('type'));
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));
        $page = max(1, (int) $request->query('page', 1));
        $cols = Schema::getColumnListing('content_posts');

        $qb = DB::table('content_posts')
            ->where('app_id', (int) $app->id);

        PublicationVisibility::apply($qb);

        if ($bucket && in_array('bucket', $cols, true)) {
            $qb->where('bucket', $bucket);
        }

        $qb->orderByDesc('published_at')
            ->orderByDesc('publish_at')
            ->orderByDesc('id');

        $total = (clone $qb)->count();
        $rows = $qb->forPage($page, $perPage)->get();

        $items = collect($rows)->map(function ($r) use ($appSlug, $viewer, $app) {
            return $this->shapePost($r, $appSlug, false, (int) $app->id, $viewer);
        })->values()->all();

        $routeKey = $bucket ? $bucket . '_list' : 'content_list';
        $ads = AdResolver::screenAdsForApp((int) $app->id, $tab, $routeKey);
        $nativeInList = $this->resolveNativeInList((int) $app->id);

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
            ],
            'scope' => [
                'tab' => $tab,
                'bucket' => $bucket,
                'route_key' => $routeKey,
            ],
            'ads' => [
                'screen' => $ads,
                'native_in_list' => $nativeInList,
            ],
            'meta' => [
                'api_version' => 'v1.2',
                'screen' => 'content_list',
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil(max(1, $total) / $perPage),
            ],
            'items' => $items,
            'data' => $items,
        ]);
    }

    public function show(Request $request, string $appSlug, string $idOrSlug)
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            $app = (object) [
                'id' => (int) $currentApp->id,
                'name' => (string) $currentApp->name,
                'slug' => (string) $currentApp->slug,
                'is_active' => (bool) $currentApp->is_active,
            ];
        } else {
            $app = DB::table('apps')
                ->select(['id', 'name', 'slug', 'is_active'])
                ->where('slug', $appSlug)
                ->where('is_active', 1)
                ->first();
        }

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        $viewer = auth('sanctum')->user();

        $qb = DB::table('content_posts')
            ->where('app_id', (int) $app->id);

        PublicationVisibility::apply($qb);

        if (ctype_digit($idOrSlug)) {
            $qb->where('id', (int) $idOrSlug);
        } else {
            $qb->where('slug', $idOrSlug);
        }

        $row = $qb->first();

        if (! $row) {
            return response()->json([
                'ok' => false,
                'error' => 'CONTENT_NOT_FOUND',
                'message' => 'Content not found.',
            ], 404);
        }

        $item = $this->shapePost($row, $appSlug, true, (int) $app->id, $viewer);
        $tab = $this->cleanTab($request->query('tab', 'inspire'));
        $bucket = $item['payload']['bucket'] ?? null;
        $routeKey = $bucket ? $bucket . '_read' : 'content_read';
        $ads = AdResolver::screenAdsForApp((int) $app->id, $tab, $routeKey);
        $nativeInList = $this->resolveNativeInList((int) $app->id);

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
            ],
            'scope' => [
                'tab' => $tab,
                'bucket' => $bucket,
                'route_key' => $routeKey,
            ],
            'ads' => [
                'screen' => $ads,
                'native_in_list' => $nativeInList,
            ],
            'meta' => [
                'api_version' => 'v1.2',
                'screen' => 'content_read',
                'detail_source' => 'content_registry',
            ],
            'item' => $item,
            'post' => $item,
            'data' => $item,
        ]);
    }

    private function resolveNativeInList(int $appId): array
    {
        $adProfile = AdProfile::query()
            ->where('app_id', $appId)
            ->first();

        $meta = $adProfile ? (is_array($adProfile->meta_json) ? $adProfile->meta_json : []) : [];
        $overrides = AdResolver::metaOverrides($meta);

        return $overrides['native_in_list'] ?? AdResolver::defaultNativeInList();
    }

    private function shapePost(object $row, string $appSlug, bool $includeBody, int $appId, $viewer): array
    {
        $a = (array) $row;

        $id = (int) ($a['id'] ?? 0);
        $slug = $a['slug'] ?? null;
        $bucket = isset($a['bucket']) && is_string($a['bucket']) ? trim($a['bucket']) : null;
        $body = $includeBody ? ($a['body_html'] ?? null) : null;
        $meta = $this->decodeJsonArray($a['meta_json'] ?? null);
        $tags = $this->decodeJsonArray($a['tags_json'] ?? null);
        $blocks = $this->decodeJsonArray($a['blocks_json'] ?? null);
        $featured = isset($a['is_featured']) ? (bool) $a['is_featured'] : false;
        $publishedAt = $this->toIsoString($a['published_at'] ?? ($a['publish_at'] ?? ($a['created_at'] ?? null)));

        $coverAssetId = $this->metaInt($meta, 'cover_asset_id');
        $coverAssetUrl = $this->metaStr($meta, 'cover_asset_url');
        $coverAssetPath = $this->metaStr($meta, 'cover_asset_path');
        $coverAssetBucket = $this->metaStr($meta, 'cover_asset_bucket');
        $coverUpdatedAt = $this->metaStr($meta, 'cover_updated_at');

        $authorUserId = isset($a['author_user_id']) && is_numeric($a['author_user_id']) ? (int) $a['author_user_id'] : null;
        $authorName = isset($a['author_name']) && is_string($a['author_name']) && trim($a['author_name']) !== ''
            ? trim((string) $a['author_name'])
            : null;

        $resolvedImage = $this->firstNonEmptyString([
            $this->resolvePublicUrl($a['cover_image_url'] ?? null),
            $this->resolvePublicUrl($meta['image_url'] ?? null),
            $this->resolvePublicUrl($meta['cover_image_url'] ?? null),
            $this->resolvePublicUrl($meta['background_image_url'] ?? null),
            $this->resolvePublicUrl($coverAssetUrl),
            $this->resolvePublicUrl($coverAssetPath),
        ]);

        $quotePayload = $this->quoteDesignerPayload($meta, $blocks, $body, $resolvedImage);
        $publisher = $this->resolvePublisher($appId, $authorUserId, $authorName, $viewer);
        $likesCount = $this->likesCount($appId, $id);
        $commentsCount = $this->commentsCount($appId, $id);
        $likedByMe = $viewer ? $this->likedByViewer($appId, $id, (int) $viewer->id) : false;
        $savesCount = $this->savesCount($appId, $id);
        $savedByMe = $viewer ? $this->savedByViewer($appId, $id, (int) $viewer->id) : false;
        $route = $slug ? '/content/' . $slug : '/content/' . $id;
        $apiUrl = "/api/v1/apps/{$appSlug}/content/" . ($slug ?: $id);

        $isQuoteCard = $this->isQuoteBucket($bucket, $quotePayload);

        $payload = [
            'screen' => $isQuoteCard ? 'quote_card' : 'content_read',
            'content_type' => $bucket,
            'bucket' => $bucket,
            'badge' => $this->contentBadge($bucket),
            'slug' => $slug,
            'featured' => $featured,
            'published_at' => $publishedAt,
            'author_name' => $authorName,
            'tags' => $tags,
            'meta' => $meta,
            'blocks' => $blocks,
            'body_html' => $body,
            'image_url' => $resolvedImage,
            'cover_image_url' => $resolvedImage,
            'thumbnail_url' => $resolvedImage,
            'featured_image' => $resolvedImage,
            'api_url' => $apiUrl,
            'cover' => [
                'image_url' => $resolvedImage,
                'asset_id' => $coverAssetId,
                'asset_url' => $coverAssetUrl,
                'asset_path' => $coverAssetPath,
                'asset_bucket' => $coverAssetBucket,
                'updated_at' => $coverUpdatedAt,
            ],
            'publisher' => $publisher,
            'engagement' => [
                'likes_count' => $likesCount,
                'comments_count' => $commentsCount,
                'saves_count' => $savesCount,
                'liked_by_me' => $likedByMe,
                'saved_by_me' => $savedByMe,
            ],
            'content_id' => $id,
            'post_id' => $id,
            'route' => $route,
            'deep_link_url' => $isQuoteCard ? $this->quoteDeepLinkForBucket($bucket, $id) : $route,
        ];

        if ($quotePayload) {
            $payload = array_merge($payload, $quotePayload);
        }

        return [
            'id' => $id,
            'type' => $isQuoteCard ? 'quote_card' : 'content_post',
            'title' => $a['title'] ?? null,
            'subtitle' => $a['subtitle'] ?? null,
            'excerpt' => $a['subtitle'] ?? null,
            'bucket' => $bucket,
            'content_type' => $bucket,
            'icon' => SvgIconRegistry::icon($bucket),
            'image_url' => $resolvedImage,
            'cover_image_url' => $resolvedImage,
            'thumbnail_url' => $resolvedImage,
            'featured_image' => $resolvedImage,
            'author_name' => $authorName,
            'author_user_id' => $authorUserId,
            'publisher' => $publisher,
            'cover_asset_id' => $coverAssetId,
            'cover_asset_url' => $coverAssetUrl,
            'cover_asset_path' => $coverAssetPath,
            'cover_asset_bucket' => $coverAssetBucket,
            'cover_updated_at' => $coverUpdatedAt,
            'likes_count' => $likesCount,
            'comments_count' => $commentsCount,
            'saves_count' => $savesCount,
            'liked_by_me' => $likedByMe,
            'saved_by_me' => $savedByMe,
            'route' => $isQuoteCard ? $this->quoteDeepLinkForBucket($bucket, $id) : $route,
            'url' => null,
            'meta' => $meta,
            'blocks' => $includeBody ? $blocks : null,
            'body_html' => $includeBody ? $body : null,
            'payload' => $payload,
        ];
    }

    private function quoteDesignerPayload(array $meta, array $blocks, ?string $body, ?string $resolvedImage): array
    {
        $designerType = $this->metaStr($meta, 'designer_type');
        $blockQuote = null;
        $blockSource = null;

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }

            if (($block['type'] ?? null) === 'quote_card') {
                $blockQuote = isset($block['quote']) && is_string($block['quote']) ? $block['quote'] : null;
                $blockSource = isset($block['source']) && is_string($block['source']) ? $block['source'] : null;
                break;
            }
        }

        $quoteText = $this->firstNonEmptyString([
            $this->metaStr($meta, 'quote_text'),
            $blockQuote,
            $body ? trim(strip_tags($body)) : null,
        ]);

        $quoteSource = $this->firstNonEmptyString([
            $this->metaStr($meta, 'quote_source'),
            $blockSource,
        ]);

        if ($designerType !== 'quote_card' && $quoteText === '') {
            return [];
        }

        $cardFormat = $this->metaStr($meta, 'card_format') ?: 'portrait';
        $formatRatio = $this->metaStr($meta, 'format_ratio') ?: $this->formatRatioFor($cardFormat);

        return [
            'designer_type' => 'quote_card',
            'designer_version' => $this->metaStr($meta, 'designer_version') ?: '2.0',
            'dxm_design_schema' => $this->metaStr($meta, 'dxm_design_schema') ?: 'quote_card_v2_1',
            'quote_text' => $quoteText !== '' ? $quoteText : null,
            'quote_source' => $quoteSource !== '' ? $quoteSource : null,
            'background_image_url' => $resolvedImage,
            'background_mode' => $this->metaStr($meta, 'background_mode') ?: ($resolvedImage ? 'image' : 'gradient'),
            'bg_color' => $this->metaStr($meta, 'bg_color') ?: '#160042',
            'bg_color_2' => $this->metaStr($meta, 'bg_color_2') ?: '#e2388a',
            'text_color' => $this->metaStr($meta, 'text_color') ?: '#ffffff',
            'accent_color' => $this->metaStr($meta, 'accent_color') ?: '#38bdf8',
            'source_color' => $this->metaStr($meta, 'source_color') ?: ($this->metaStr($meta, 'accent_color') ?: '#38bdf8'),
            'highlight_color' => $this->metaStr($meta, 'highlight_color') ?: ($this->metaStr($meta, 'accent_color') ?: '#facc15'),
            'overlay_strength' => $this->metaStr($meta, 'overlay_strength') ?: '58',
            'text_align' => $this->metaStr($meta, 'text_align') ?: 'center',
            'vertical_align' => $this->metaStr($meta, 'vertical_align') ?: 'center',
            'font_family' => $this->metaStr($meta, 'font_family') ?: 'system',
            'font_key' => $this->metaStr($meta, 'font_key') ?: ($this->metaStr($meta, 'font_family') ?: 'system'),
            'source_font_family' => $this->metaStr($meta, 'source_font_family') ?: ($this->metaStr($meta, 'source_font') ?: ($this->metaStr($meta, 'font_family') ?: 'system')),
            'source_font_key' => $this->metaStr($meta, 'source_font_key') ?: ($this->metaStr($meta, 'source_font_family') ?: ($this->metaStr($meta, 'font_family') ?: 'system')),
            'font_weight' => $this->metaStr($meta, 'font_weight') ?: '700',
            'quote_size' => $this->metaStr($meta, 'quote_size') ?: '24',
            'source_size' => $this->metaStr($meta, 'source_size') ?: '14',
            'source_weight' => $this->metaStr($meta, 'source_weight') ?: '700',
            'highlight_phrases' => $this->metaStr($meta, 'highlight_phrases') ?: '',
            'highlight_rules' => isset($meta['highlight_rules']) && is_array($meta['highlight_rules']) ? $meta['highlight_rules'] : [],
            'highlight_scale' => $this->metaStr($meta, 'highlight_scale') ?: '1.08',
            'highlight_weight' => $this->metaStr($meta, 'highlight_weight') ?: '800',
            'text_shadow' => $this->metaStr($meta, 'text_shadow') ?: 'soft',
            'line_height' => $this->metaStr($meta, 'line_height') ?: '1.35',
            'card_format' => $cardFormat,
            'format_ratio' => $formatRatio,
            'format_label' => $this->metaStr($meta, 'format_label') ?: $this->formatLabelFor($cardFormat),
            'content_width' => $this->metaStr($meta, 'content_width') ?: '86',
            'card_padding' => $this->metaStr($meta, 'card_padding') ?: '34',
            'card_padding_x' => $this->metaStr($meta, 'card_padding_x') ?: ($this->metaStr($meta, 'padding_x') ?: ($this->metaStr($meta, 'card_padding') ?: '34')),
            'card_padding_y' => $this->metaStr($meta, 'card_padding_y') ?: ($this->metaStr($meta, 'padding_y') ?: ($this->metaStr($meta, 'card_padding') ?: '34')),
            'padding_x' => $this->metaStr($meta, 'card_padding_x') ?: ($this->metaStr($meta, 'padding_x') ?: ($this->metaStr($meta, 'card_padding') ?: '34')),
            'padding_y' => $this->metaStr($meta, 'card_padding_y') ?: ($this->metaStr($meta, 'padding_y') ?: ($this->metaStr($meta, 'card_padding') ?: '34')),
            'background_color' => $this->metaStr($meta, 'bg_color') ?: '#160042',
            'gradient_start' => $this->metaStr($meta, 'bg_color') ?: '#160042',
            'gradient_end' => $this->metaStr($meta, 'bg_color_2') ?: '#e2388a',
            'text_scale_mode' => $this->metaStr($meta, 'text_scale_mode') ?: 'auto',
            'style_preset' => $this->metaStr($meta, 'style_preset') ?: 'royal',
            'show_quote_mark' => $this->metaBool($meta, 'show_quote_mark', true),
            'design' => $this->quoteDesignV21($meta, $resolvedImage, $cardFormat),
            'quote_card' => [
                'text' => $quoteText !== '' ? $quoteText : null,
                'source' => $quoteSource !== '' ? $quoteSource : null,
                'image_url' => $resolvedImage,
                'background_image_url' => $resolvedImage,
                'background_mode' => $this->metaStr($meta, 'background_mode') ?: ($resolvedImage ? 'image' : 'gradient'),
                'colors' => [
                    'bg' => $this->metaStr($meta, 'bg_color') ?: '#160042',
                    'bg2' => $this->metaStr($meta, 'bg_color_2') ?: '#e2388a',
                    'text' => $this->metaStr($meta, 'text_color') ?: '#ffffff',
                    'source' => $this->metaStr($meta, 'source_color') ?: ($this->metaStr($meta, 'accent_color') ?: '#38bdf8'),
                    'highlight' => $this->metaStr($meta, 'highlight_color') ?: ($this->metaStr($meta, 'accent_color') ?: '#facc15'),
                    'accent' => $this->metaStr($meta, 'accent_color') ?: '#38bdf8',
                ],
                'layout' => [
                    'format' => $cardFormat,
                    'ratio' => $formatRatio,
                    'align' => $this->metaStr($meta, 'text_align') ?: 'center',
                    'vertical' => $this->metaStr($meta, 'vertical_align') ?: 'center',
                    'content_width' => $this->metaStr($meta, 'content_width') ?: '86',
                    'padding' => $this->metaStr($meta, 'card_padding') ?: '34',
                    'padding_x' => $this->metaStr($meta, 'card_padding_x') ?: ($this->metaStr($meta, 'padding_x') ?: ($this->metaStr($meta, 'card_padding') ?: '34')),
                    'padding_y' => $this->metaStr($meta, 'card_padding_y') ?: ($this->metaStr($meta, 'padding_y') ?: ($this->metaStr($meta, 'card_padding') ?: '34')),
                ],
                'typography' => [
                    'family' => $this->metaStr($meta, 'font_family') ?: 'system',
                    'font_key' => $this->metaStr($meta, 'font_key') ?: ($this->metaStr($meta, 'font_family') ?: 'system'),
                    'source_family' => $this->metaStr($meta, 'source_font_family') ?: ($this->metaStr($meta, 'source_font') ?: ($this->metaStr($meta, 'font_family') ?: 'system')),
                    'source_font_key' => $this->metaStr($meta, 'source_font_key') ?: ($this->metaStr($meta, 'source_font_family') ?: ($this->metaStr($meta, 'font_family') ?: 'system')),
                    'weight' => $this->metaStr($meta, 'font_weight') ?: '700',
                    'quote_size' => $this->metaStr($meta, 'quote_size') ?: '24',
                    'source_size' => $this->metaStr($meta, 'source_size') ?: '14',
                    'source_weight' => $this->metaStr($meta, 'source_weight') ?: '700',
                    'highlight_phrases' => $this->metaStr($meta, 'highlight_phrases') ?: '',
                    'highlight_scale' => $this->metaStr($meta, 'highlight_scale') ?: '1.08',
                    'highlight_weight' => $this->metaStr($meta, 'highlight_weight') ?: '800',
                    'line_height' => $this->metaStr($meta, 'line_height') ?: '1.35',
                    'scale_mode' => $this->metaStr($meta, 'text_scale_mode') ?: 'auto',
                    'text_shadow' => $this->metaStr($meta, 'text_shadow') ?: 'soft',
                ],
            ],
        ];
    }

    private function isQuoteBucket(?string $bucket, array $quotePayload = []): bool
    {
        $bucket = strtolower(trim((string) $bucket));

        if (! empty($quotePayload)) {
            return true;
        }

        return $bucket === 'daily_quotes'
            || $bucket === 'daily_scriptures'
            || $bucket === 'sod_quotes'
            || $bucket === 'motivational_quotes'
            || $bucket === 'motivation_quotes'
            || str_starts_with($bucket, 'quote_')
            || str_ends_with($bucket, '_quotes');
    }

    private function quoteDeepLinkForBucket(?string $bucket, int $id): string
    {
        $bucket = strtolower(trim((string) $bucket));

        $path = match ($bucket) {
            'daily_quotes' => '/daily/quote',
            'daily_scriptures' => '/daily/scripture',
            'sod_quotes' => '/sod/quotes',
            'motivational_quotes', 'motivation_quotes' => '/motivation/quotes',
            default => str_starts_with($bucket, 'quote_') ? '/quotes/custom' : '/quotes',
        };

        return $path . '?' . http_build_query([
            'id' => $id,
            'quote_id' => $id,
            'post_id' => $id,
            'bucket' => $bucket,
            'channel' => $bucket,
        ]);
    }

    private function quoteDesignV21(array $meta, ?string $resolvedImage, string $cardFormat): array
    {
        $fontFamily = $this->metaStr($meta, 'font_family') ?: 'system';
        $sourceFont = $this->metaStr($meta, 'source_font_family') ?: ($this->metaStr($meta, 'source_font') ?: $fontFamily);

        return [
            'version' => 'quote_card_v2_1',
            'format' => $cardFormat,
            'background' => [
                'mode' => $this->metaStr($meta, 'background_mode') ?: ($resolvedImage ? 'image' : 'gradient'),
                'image_url' => $resolvedImage,
                'color' => $this->metaStr($meta, 'bg_color') ?: '#160042',
                'color2' => $this->metaStr($meta, 'bg_color_2') ?: '#e2388a',
                'fit' => $this->metaStr($meta, 'background_fit') ?: 'cover',
                'position' => $this->metaStr($meta, 'background_position') ?: 'center',
                'overlay_strength' => (int) ($this->metaStr($meta, 'overlay_strength') ?: 58),
                'overlay_style' => $this->metaStr($meta, 'overlay_style') ?: 'bottom',
            ],
            'text' => [
                'font_key' => $this->metaStr($meta, 'font_key') ?: $fontFamily,
                'font_family' => $fontFamily,
                'font_size' => (int) ($this->metaStr($meta, 'quote_size') ?: 24),
                'font_weight' => $this->metaStr($meta, 'font_weight') ?: '700',
                'color' => $this->metaStr($meta, 'text_color') ?: '#ffffff',
                'align' => $this->metaStr($meta, 'text_align') ?: 'center',
                'vertical_align' => $this->metaStr($meta, 'vertical_align') ?: 'center',
                'content_width' => (int) ($this->metaStr($meta, 'content_width') ?: 86),
                'padding_x' => (int) ($this->metaStr($meta, 'card_padding_x') ?: ($this->metaStr($meta, 'padding_x') ?: ($this->metaStr($meta, 'card_padding') ?: 34))),
                'padding_y' => (int) ($this->metaStr($meta, 'card_padding_y') ?: ($this->metaStr($meta, 'padding_y') ?: ($this->metaStr($meta, 'card_padding') ?: 34))),
                'offset_x' => (int) ($this->metaStr($meta, 'offset_x') ?: 0),
                'offset_y' => (int) ($this->metaStr($meta, 'offset_y') ?: 0),
                'line_height' => (float) ($this->metaStr($meta, 'line_height') ?: 1.35),
                'letter_spacing' => (float) ($this->metaStr($meta, 'letter_spacing') ?: 0),
            ],
            'source' => [
                'font_key' => $this->metaStr($meta, 'source_font_key') ?: $sourceFont,
                'font_family' => $sourceFont,
                'color' => $this->metaStr($meta, 'source_color') ?: ($this->metaStr($meta, 'accent_color') ?: '#38bdf8'),
                'font_size' => (int) ($this->metaStr($meta, 'source_size') ?: 14),
                'font_weight' => $this->metaStr($meta, 'source_weight') ?: '700',
                'spacing' => (int) ($this->metaStr($meta, 'source_spacing') ?: 18),
            ],
            'highlight' => [
                'color' => $this->metaStr($meta, 'highlight_color') ?: ($this->metaStr($meta, 'accent_color') ?: '#facc15'),
                'scale' => (float) ($this->metaStr($meta, 'highlight_scale') ?: 1.08),
                'weight' => $this->metaStr($meta, 'highlight_weight') ?: '800',
            ],
            'decorations' => [
                'quote_mark_enabled' => $this->metaBool($meta, 'show_quote_mark', true),
                'quote_mark_size' => (int) ($this->metaStr($meta, 'quote_mark_size') ?: 96),
                'quote_mark_opacity' => (int) ($this->metaStr($meta, 'quote_mark_opacity') ?: 16),
                'quote_mark_position' => $this->metaStr($meta, 'quote_mark_position') ?: 'top_left',
                'border_radius' => (int) ($this->metaStr($meta, 'border_radius') ?: 26),
            ],
        ];
    }

    private function resolvePublisher(int $appId, ?int $authorUserId, ?string $authorName, $viewer): ?array
    {
        if (! $authorUserId) {
            return $authorName ? [
                'id' => null,
                'name' => $authorName,
                'email' => null,
                'follow_route' => null,
                'followers_count' => 0,
                'following_by_me' => false,
            ] : null;
        }

        $user = DB::table('users')
            ->select(['id', 'name', 'email'])
            ->where('id', $authorUserId)
            ->first();

        if (! $user) {
            return $authorName ? [
                'id' => $authorUserId,
                'name' => $authorName,
                'email' => null,
                'follow_route' => '/users/' . $authorUserId . '/follow',
                'followers_count' => $this->followersCount($appId, $authorUserId),
                'following_by_me' => $viewer ? $this->viewerFollows($appId, (int) $viewer->id, $authorUserId) : false,
            ] : null;
        }

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'follow_route' => '/users/' . (int) $user->id . '/follow',
            'followers_count' => $this->followersCount($appId, (int) $user->id),
            'following_by_me' => $viewer ? $this->viewerFollows($appId, (int) $viewer->id, (int) $user->id) : false,
        ];
    }

    private function savesCount(int $appId, int $postId): int
    {
        if (! Schema::hasTable('content_post_saves')) {
            return 0;
        }

        return (int) DB::table('content_post_saves')
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->count();
    }

    private function savedByViewer(int $appId, int $postId, int $userId): bool
    {
        if (! Schema::hasTable('content_post_saves')) {
            return false;
        }

        return DB::table('content_post_saves')
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function likesCount(int $appId, int $postId): int
    {
        if (! Schema::hasTable('content_post_likes')) {
            return 0;
        }

        return (int) DB::table('content_post_likes')
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->count();
    }

    private function commentsCount(int $appId, int $postId): int
    {
        if (! Schema::hasTable('content_post_comments')) {
            return 0;
        }

        return (int) DB::table('content_post_comments')
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->where('status', 'published')
            ->count();
    }

    private function likedByViewer(int $appId, int $postId, int $userId): bool
    {
        if (! Schema::hasTable('content_post_likes')) {
            return false;
        }

        return DB::table('content_post_likes')
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function followersCount(int $appId, int $userId): int
    {
        if (! Schema::hasTable('user_follows')) {
            return 0;
        }

        return UserFollow::query()
            ->where('app_id', $appId)
            ->where('following_user_id', $userId)
            ->count();
    }

    private function viewerFollows(int $appId, int $viewerUserId, int $publisherUserId): bool
    {
        if (! Schema::hasTable('user_follows')) {
            return false;
        }

        return UserFollow::query()
            ->where('app_id', $appId)
            ->where('follower_user_id', $viewerUserId)
            ->where('following_user_id', $publisherUserId)
            ->exists();
    }

    private function resolvePublicUrl($val): ?string
    {
        if (! is_string($val) || trim($val) === '') {
            return null;
        }

        $raw = trim($val);

        if (Str::startsWith($raw, ['http://', 'https://'])) {
            return $raw;
        }

        $path = ltrim($raw, '/');

        if (Str::startsWith($path, 'storage/')) {
            return url('/' . $path);
        }

        if (Str::startsWith($path, 'public/')) {
            $path = Str::after($path, 'public/');
        }

        return Storage::disk('public')->url($path);
    }

    private function cleanTab($v): string
    {
        $v = strtolower(trim((string) $v));
        return in_array($v, ['home', 'watch', 'inspire', 'explore', 'more'], true) ? $v : 'inspire';
    }

    private function cleanStr($v): ?string
    {
        if (! is_string($v)) {
            return null;
        }

        $v = trim($v);
        return $v === '' ? null : $v;
    }

    private function decodeJsonArray($val): array
    {
        if (is_array($val)) {
            return $val;
        }

        if (! is_string($val)) {
            return [];
        }

        $val = trim($val);
        if ($val === '') {
            return [];
        }

        $decoded = json_decode($val, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function metaStr(array $meta, string $key): ?string
    {
        if (! array_key_exists($key, $meta)) {
            return null;
        }

        $v = $meta[$key];

        if (is_string($v)) {
            $v = trim($v);
            return $v === '' ? null : $v;
        }

        if (is_numeric($v)) {
            return (string) $v;
        }

        return null;
    }

    private function metaInt(array $meta, string $key): ?int
    {
        if (! array_key_exists($key, $meta)) {
            return null;
        }

        $v = $meta[$key];

        if (is_int($v)) {
            return $v;
        }

        if (is_string($v) && ctype_digit($v)) {
            return (int) $v;
        }

        if (is_numeric($v)) {
            return (int) $v;
        }

        return null;
    }

    private function metaBool(array $meta, string $key, bool $default = false): bool
    {
        if (! array_key_exists($key, $meta)) {
            return $default;
        }

        $value = $meta[$key];

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (bool) $value;
        }

        if (is_string($value)) {
            $value = strtolower(trim($value));

            if (in_array($value, ['1', 'true', 'yes', 'on'], true)) {
                return true;
            }

            if (in_array($value, ['0', 'false', 'no', 'off'], true)) {
                return false;
            }
        }

        return $default;
    }

    private function contentBadge(?string $bucket): ?array
    {
        $bucket = is_string($bucket) ? trim($bucket) : null;

        if (! $bucket) {
            return null;
        }

        $map = [
            'wordification' => 'Wordification',
            'motivation' => 'Motivation',
            'sod' => 'SOD',
            'sod_quotes' => 'SOD Quotes',
            'highlight' => 'Highlights',
            'highlights' => 'Highlights',
            'inside_dunamis' => 'Inside Dunamis',
            'articles' => 'Articles',
            'devotional' => 'Devotional',
            'blog' => 'Blog',
        ];

        $text = $map[$bucket] ?? $this->titleCaseFromKey($bucket);

        return [
            'key' => $bucket,
            'text' => $text,
        ];
    }

    private function titleCaseFromKey(string $key): string
    {
        $key = str_replace(['-', '_'], ' ', strtolower(trim($key)));
        $key = preg_replace('/\s+/', ' ', $key) ?: $key;
        return ucwords($key);
    }

    private function formatRatioFor(string $format): string
    {
        return match ($format) {
            'square' => '1 / 1',
            'story' => '9 / 16',
            'wide' => '16 / 9',
            'cinematic' => '21 / 9',
            'classic' => '3 / 4',
            default => '4 / 5',
        };
    }

    private function formatLabelFor(string $format): string
    {
        return match ($format) {
            'square' => 'Square 1:1',
            'story' => 'Story 9:16',
            'wide' => 'Wide 16:9',
            'cinematic' => 'Cinematic 21:9',
            'classic' => 'Classic 3:4',
            default => 'Portrait 4:5',
        };
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

    private function toIsoString($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value)->toISOString();
            } catch (\Throwable $e) {
                return trim($value);
            }
        }

        return null;
    }
}


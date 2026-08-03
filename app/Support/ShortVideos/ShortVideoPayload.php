<?php

namespace App\Support\ShortVideos;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ShortVideoPayload
{
    public const DEFAULT_BUCKET = 'short_videos';

    public static function normalizeBucket(string $bucket): string
    {
        return match (strtolower(trim($bucket))) {
            'short', 'shorts', 'short-video', 'short-videos', 'short_video', 'short_videos', 'reel', 'reels' => self::DEFAULT_BUCKET,
            default => strtolower(trim($bucket)),
        };
    }

    public static function normalizeCategoryKey(?string $value, string $fallback = 'general'): string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            $raw = $fallback;
        }

        $slug = Str::slug($raw);

        return $slug !== '' ? $slug : 'general';
    }

    public static function normalizeCategoryLabel(?string $label, ?string $key = null): string
    {
        $raw = trim((string) $label);

        if ($raw !== '') {
            return $raw;
        }

        $fallback = trim((string) $key);

        if ($fallback === '') {
            return 'General';
        }

        return Str::headline(str_replace(['-', '_'], ' ', $fallback));
    }

    public static function normalizeTags(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(
                fn ($item) => trim((string) $item),
                $value
            ), fn ($item) => $item !== ''));
        }

        $text = trim((string) $value);

        if ($text === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($item) => trim($item),
            explode(',', $text)
        ), fn ($item) => $item !== ''));
    }

    public static function metaFromRequest(array $data, array $existingMeta = []): array
    {
        $meta = $existingMeta;

        $categoryInput = trim((string) ($data['short_video_category'] ?? $data['category'] ?? ($meta['category'] ?? '')));
        $categoryLabelInput = trim((string) ($data['short_video_category_label'] ?? $data['category_label'] ?? ($meta['category_label'] ?? '')));

        $categoryKey = self::normalizeCategoryKey($categoryInput ?: $categoryLabelInput);
        $categoryLabel = self::normalizeCategoryLabel($categoryLabelInput, $categoryKey);

        $meta['content_engine'] = 'short_video_feed';
        $meta['category'] = $categoryKey;
        $meta['category_key'] = $categoryKey;
        $meta['category_slug'] = $categoryKey;
        $meta['category_label'] = $categoryLabel;
        $meta['category_name'] = $categoryLabel;
        $meta['category_title'] = $categoryLabel;

        if (array_key_exists('short_video_tags', $data) || array_key_exists('tags', $data)) {
            $meta['tags'] = self::normalizeTags($data['short_video_tags'] ?? $data['tags'] ?? []);
        }

        return $meta;
    }

    public static function postToItem(object $post, object $section, string $bucket, array $settings, callable $summaryResolver, callable $styleResolver): array
    {
        $meta = self::safeArray($post->meta_json ?? []);
        $videoUrl = trim((string) ($meta['video_url'] ?? ''));
        $duration = trim((string) ($meta['video_duration'] ?? $meta['duration'] ?? ''));
        $videoEngine = trim((string) ($meta['video_engine'] ?? 'mp4')) ?: 'mp4';
        $videoAspect = trim((string) ($meta['video_aspect'] ?? 'portrait')) ?: 'portrait';

        $categoryKey = self::normalizeCategoryKey($meta['category_key'] ?? $meta['category_slug'] ?? $meta['category'] ?? ($settings['category'] ?? 'general'));
        $categoryLabel = self::normalizeCategoryLabel(
            $meta['category_label'] ?? $meta['category_name'] ?? $meta['category_title'] ?? ($settings['category_label'] ?? null),
            $categoryKey
        );

        $tags = self::normalizeTags($meta['tags'] ?? $meta['keywords'] ?? []);

        $cover = trim((string) ($post->cover_image_url ?? ''));

        $appId = (int) ($post->app_id ?? 0);
        $postId = (int) ($post->id ?? 0);

        $likesCount = Schema::hasTable('content_post_likes')
            ? (int) DB::table('content_post_likes')
                ->where('app_id', $appId)
                ->where('content_post_id', $postId)
                ->count()
            : 0;

        $commentsCount = Schema::hasTable('content_post_comments')
            ? (int) DB::table('content_post_comments')
                ->where('app_id', $appId)
                ->where('content_post_id', $postId)
                ->count()
            : 0;

        $savesCount = Schema::hasTable('content_post_saves')
            ? (int) DB::table('content_post_saves')
                ->where('app_id', $appId)
                ->where('content_post_id', $postId)
                ->count()
            : 0;

        $payload = [
            'action' => 'open_short_video_feed',
            'bucket' => $bucket,
            'likes_count' => $likesCount,
            'comments_count' => $commentsCount,
            'saves_count' => $savesCount,
            'content_mode' => 'short_video',
            'content_engine' => 'short_video_feed',
            'content_id' => (int) $post->id,
            'post_id' => (int) $post->id,
            'slug' => $post->slug ?? null,
            'likes_count' => $likesCount,
            'comments_count' => $commentsCount,
            'saves_count' => $savesCount,

            'video_url' => $videoUrl,
            'url' => $videoUrl,
            'video_engine' => $videoEngine,
            'video_aspect' => $videoAspect,
            'video_duration' => $duration,
            'duration' => $duration,

            'category' => $categoryKey,
            'category_key' => $categoryKey,
            'category_slug' => $categoryKey,
            'category_label' => $categoryLabel,
            'category_name' => $categoryLabel,
            'category_title' => $categoryLabel,
            'tags' => $tags,

            'feed_label' => $meta['feed_label'] ?? $categoryLabel,
            'thumbnail_url' => $cover,
            'cover_image_url' => $cover,
            'image_url' => $cover,
            'route' => $settings['target_route'] ?? '/short-videos',
            'target_route' => $settings['target_route'] ?? '/short-videos',
            'status' => $post->status,
            'is_featured' => (bool) ($post->is_featured ?? false),
            'meta' => $meta,
        ];

        $style = $styleResolver([
            'card_format' => 'story',
            'background_mode' => 'image',
            'thumbnail_url' => $cover,
        ]);

        return [
            'id' => 'short_video_' . $post->id,
            'key' => 'short_video_' . $post->id,
            'section_id' => $section->id,
            'section_key' => $section->key,

            'type' => 'video',
            'layout' => 'short_video',

            'title' => $post->title,
            'subtitle' => $post->subtitle,
            'description' => $summaryResolver($post->body_html ?? ''),

            'icon' => 'play',
            'image_url' => $cover,
            'thumbnail_url' => $cover,
            'cover_image_url' => $cover,
            'poster' => $cover,
            'badge' => $duration ?: ($meta['feed_label'] ?? $categoryLabel),

            'route' => $settings['target_route'] ?? '/short-videos',
            'url' => $videoUrl,
            'video_url' => $videoUrl,
            'engine' => $videoEngine,
            'content_id' => (int) $post->id,
            'post_id' => (int) $post->id,
            'bucket' => $bucket,

            'category' => $categoryKey,
            'category_key' => $categoryKey,
            'category_slug' => $categoryKey,
            'category_label' => $categoryLabel,
            'category_name' => $categoryLabel,
            'category_title' => $categoryLabel,
            'tags' => $tags,

            'cta_label' => 'Watch',
            'cta' => [
                'label' => 'Watch',
                'route' => $settings['target_route'] ?? '/short-videos',
            ],
            'action' => [
                'type' => 'open_short_video_feed',
                'route' => $settings['target_route'] ?? '/short-videos',
                'bucket' => $bucket,
                'content_id' => (int) $post->id,
            ],
            'actions' => [],

            'style' => $style,
            'render_style' => $style,
            'design' => $style,
            'settings' => [
                'content_mode' => 'short_video',
                'content_engine' => 'short_video_feed',
                'video_aspect' => $videoAspect,
                'category' => $categoryKey,
                'category_label' => $categoryLabel,
            ],
            'payload' => $payload,

            'order' => (int) ($post->sort_order ?? 0),
            'sort_order' => (int) ($post->sort_order ?? 0),
            'enabled' => true,
            'is_enabled' => true,
        ];
    }

    public static function safeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true) ?: [];
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}

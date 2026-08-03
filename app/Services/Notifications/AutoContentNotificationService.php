<?php

namespace App\Services\Notifications;

use App\Jobs\SendPushNotificationJob;
use App\Models\App;
use App\Models\Book;
use App\Models\ContentPost;
use App\Models\PushNotification;
use App\Models\QuizSet;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoContentNotificationService
{
    public function maybeNotifyContentPost(ContentPost $post, string $trigger = 'published'): void
    {
        if ((string) ($post->status ?? '') !== 'published') {
            return;
        }

        $kind = $this->contentKind($post);
        if ($kind === null) {
            return;
        }

        $meta = is_array($post->meta_json) ? $post->meta_json : [];

        // Bulk quote imports can contain many rows. Avoid sending dozens of pushes at once.
        if ($kind === 'quote' && (bool) ($meta['imported_from_quote_engine'] ?? false)) {
            return;
        }

        $existingPush = $this->existingAutoPush($post, $kind);
        if ($existingPush && ! in_array((string) $existingPush->status, ['draft', 'scheduled'], true)) {
            return;
        }

        $app = App::query()->find((int) $post->app_id);
        if (! $app || ! $this->settingEnabled($app, $this->settingKeyForKind($kind))) {
            return;
        }

        $shortVideoChannel = $kind === 'short_video'
            ? $this->resolveShortVideoChannel($app, (int) $post->id)
            : [];

        $push = $this->createPush($app, [
            'kind' => $kind,
            'title' => $this->contentTitle($post, $kind),
            'body' => $this->contentBody($post, $kind),
            'image_url' => $this->contentImageUrl($post, $kind),
            'deep_link_url' => $this->contentDeepLink($post, $kind, $shortVideoChannel),
            'action_label' => $this->actionLabel($kind),
            'source_model' => ContentPost::class,
            'source_id' => (int) $post->id,
            'trigger' => $trigger,
            'channel_key' => (string) ($shortVideoChannel['key'] ?? ''),
            'channel_label' => (string) ($shortVideoChannel['label'] ?? ''),
            'channel_placement' => (string) ($shortVideoChannel['placement'] ?? ''),
            'scheduled_for' => $post->publish_at,
        ], $existingPush);

        $this->markSent($post, $kind, $push);
    }

    public function maybeNotifyBook(Book $book, string $trigger = 'published'): void
    {
        if ((string) ($book->status ?? '') !== 'published') {
            return;
        }

        $kind = 'book';
        if ($this->alreadySent($book, $kind)) {
            return;
        }

        $app = App::query()->find((int) $book->app_id);
        if (! $app || ! $this->settingEnabled($app, 'auto_notify_books')) {
            return;
        }

        $title = 'New Book Available';
        $body = $this->cleanText($book->title . (filled($book->author_name) ? ' by ' . $book->author_name : ''));
        if ($body === '') {
            $body = 'A new book has been added to the library.';
        }

        $push = $this->createPush($app, [
            'kind' => $kind,
            'title' => $title,
            'body' => $body,
            'image_url' => $this->absoluteUrl($book->final_cover_image_src ?: $book->cover_image_src ?: $book->cover_image_url),
            'deep_link_url' => '/tools/books/detail?id=' . (int) $book->id,
            'action_label' => 'Open Book',
            'source_model' => Book::class,
            'source_id' => (int) $book->id,
            'trigger' => $trigger,
            'scheduled_for' => $book->published_at,
        ]);

        $this->markSent($book, $kind, $push);
    }

    public function maybeNotifyQuizSet(QuizSet $quizSet, string $trigger = 'published'): void
    {
        if ((string) ($quizSet->status ?? '') !== 'published' || ! (bool) ($quizSet->is_enabled ?? true)) {
            return;
        }

        $kind = 'quiz';
        if ($this->alreadySent($quizSet, $kind, 'settings_json')) {
            return;
        }

        $app = App::query()->find((int) $quizSet->app_id);
        if (! $app || ! $this->settingEnabled($app, 'auto_notify_quizzes')) {
            return;
        }

        $body = $this->cleanText($quizSet->subtitle ?: $quizSet->title ?: 'A new Bible quiz is available.');

        $push = $this->createPush($app, [
            'kind' => $kind,
            'title' => 'New Bible Quiz Available',
            'body' => $body,
            'image_url' => $this->absoluteUrl($quizSet->image_url),
            'deep_link_url' => '/quiz/bible_quiz?set_id=' . (int) $quizSet->id,
            'action_label' => 'Start Quiz',
            'source_model' => QuizSet::class,
            'source_id' => (int) $quizSet->id,
            'trigger' => $trigger,
        ]);

        $this->markSent($quizSet, $kind, $push, 'settings_json');
    }

    private function createPush(App $app, array $payload, ?PushNotification $existing = null): PushNotification
    {
        $kind = (string) ($payload['kind'] ?? 'content');
        $deepLink = $this->normalizeDeepLink((string) ($payload['deep_link_url'] ?? '/')) ?: '/';
        $imageUrl = $this->absoluteUrl($payload['image_url'] ?? null);
        $appLogoUrl = $this->absoluteUrl($app->logo_url ?? null);

        $scheduledFor = $payload['scheduled_for'] ?? null;
        if ($scheduledFor && ! $scheduledFor instanceof \DateTimeInterface) {
            $scheduledFor = Carbon::parse($scheduledFor);
        }
        $isFuture = $scheduledFor && $scheduledFor->isFuture();

        $values = [
            'app_id' => (int) $app->id,
            'title' => Str::limit($this->cleanText($payload['title'] ?? 'New Update'), 120, ''),
            'body' => Str::limit($this->cleanText($payload['body'] ?? 'Open the app to see the latest update.'), 240, ''),
            'image_url' => $imageUrl,
            'media_asset_id' => null,
            'deep_link_url' => $deepLink,
            'click_action' => (string) config('push.default_click_action', 'FLUTTER_NOTIFICATION_CLICK'),
            'target_type' => 'topic',
            'target_value' => (string) config('push.topic_prefix', 'app-') . (string) $app->slug,
            'timezone' => (string) (config('push.default_timezone') ?: config('app.timezone') ?: 'Africa/Lagos'),
            'scheduled_for' => $isFuture ? $scheduledFor : now(),
            'recurrence_type' => 'none',
            'recurrence_weekdays' => null,
            'recurrence_month_day' => null,
            'recurrence_hour' => null,
            'recurrence_minute' => null,
            'ends_at' => null,
            'max_runs' => null,
            'runs_count' => 0,
            'status' => $isFuture ? 'scheduled' : 'queued',
            'meta_json' => [
                'campaign_type' => 'auto_' . $kind,
                'notification_kind' => $kind,
                'created_from' => 'auto_content_notification_service',
                'app_name' => (string) ($app->name ?? ''),
                'app_slug' => (string) ($app->slug ?? ''),
                'image_url' => (string) ($imageUrl ?? ''),
                'app_logo_url' => (string) ($appLogoUrl ?? ''),
                'logo_url' => (string) ($appLogoUrl ?? ''),
                'deep_link_url' => $deepLink,
                'action_type' => 'internal',
                'external_url' => '',
                'action_url' => $deepLink,
                'action_target' => 'internal',
                'action_label' => (string) ($payload['action_label'] ?? 'Open'),
                'card_type' => 'in_app_notification_card',
                'payload_version' => 'auto-v1',
                'source_model' => (string) ($payload['source_model'] ?? ''),
                'source_id' => (int) ($payload['source_id'] ?? 0),
                'trigger' => (string) ($payload['trigger'] ?? 'published'),
                'channel_key' => (string) ($payload['channel_key'] ?? ''),
                'channel_label' => (string) ($payload['channel_label'] ?? ''),
                'channel_placement' => (string) ($payload['channel_placement'] ?? ''),
            ],
        ];

        if ($existing) {
            $existing->fill($values);
            $existing->save();
            $push = $existing;
        } else {
            $push = PushNotification::query()->create($values);
        }

        if (! $isFuture) {
            SendPushNotificationJob::dispatch((int) $push->id);
        }

        return $push;
    }

    private function contentKind(ContentPost $post): ?string
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];
        $bucket = strtolower(trim((string) ($post->bucket ?? '')));
        $beginnerBucket = strtolower(trim((string) data_get($meta, 'beginner_bucket', '')));
        $combined = trim($bucket . ' ' . $beginnerBucket);

        if (in_array($bucket, ['short_videos', 'short-video', 'shorts', 'reels'], true)
            || in_array($beginnerBucket, ['short_videos', 'short-video', 'shorts', 'reels'], true)
            || Str::contains($combined, ['short_video', 'short-video', 'shorts', 'reels'])) {
            return 'short_video';
        }

        // Quote managers use dynamic bucket names such as quote_dr_eneches_quotes.
        // Treat any explicit quote bucket/channel as quote content instead of article.
        if (in_array($bucket, ['sod_quotes', 'quotes', 'quote', 'daily_quotes'], true)
            || in_array($beginnerBucket, ['sod_quotes', 'quotes', 'quote', 'daily_quotes'], true)
            || Str::contains($combined, ['quote'])) {
            return 'quote';
        }

        if (in_array($bucket, ['daily_scriptures', 'scripture', 'scriptures'], true)
            || in_array($beginnerBucket, ['daily_scriptures', 'scripture', 'scriptures'], true)) {
            return null;
        }

        return 'article';
    }

    private function settingKeyForKind(string $kind): string
    {
        return match ($kind) {
            'article' => 'auto_notify_articles',
            'quote' => 'auto_notify_quotes',
            'short_video' => 'auto_notify_short_videos',
            'book' => 'auto_notify_books',
            'quiz' => 'auto_notify_quizzes',
            default => 'auto_notify_content',
        };
    }

    private function contentTitle(ContentPost $post, string $kind): string
    {
        return match ($kind) {
            'quote' => 'New Quote Available',
            'short_video' => 'New Short Video Available',
            default => $this->cleanText($post->title ?: 'New Article Available'),
        };
    }

    private function contentBody(ContentPost $post, string $kind): string
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];

        if ($kind === 'quote') {
            $quote = data_get($meta, 'quote_text')
                ?: data_get($meta, 'text')
                ?: data_get($meta, 'content')
                ?: $post->body_html
                ?: $post->title
                ?: 'A new quote has been published.';

            return $this->cleanText($quote);
        }

        if ($kind === 'short_video') {
            return $this->cleanText($post->subtitle ?: $post->title ?: 'A new short video is available.');
        }

        $body = $this->cleanText($post->subtitle ?: $post->body_html ?: 'Open the app to read the latest article.');

        return $body !== '' ? $body : 'Open the app to read the latest article.';
    }

    private function contentImageUrl(ContentPost $post, string $kind): ?string
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];

        $value = $post->cover_image_src
            ?: $post->cover_image_url
            ?: data_get($meta, 'design_image_url')
            ?: data_get($meta, 'designed_image_url')
            ?: data_get($meta, 'rendered_image_url')
            ?: data_get($meta, 'generated_image_url')
            ?: data_get($meta, 'final_image_url')
            ?: data_get($meta, 'card_image_url')
            ?: data_get($meta, 'quote_image_url')
            ?: data_get($meta, 'preview_image_url')
            ?: data_get($meta, 'export_image_url')
            ?: data_get($meta, 'thumbnail_url')
            ?: data_get($meta, 'thumbnail')
            ?: data_get($meta, 'poster')
            ?: data_get($meta, 'poster_url')
            ?: data_get($meta, 'image_url')
            ?: data_get($meta, 'image')
            ?: data_get($meta, 'cover_url')
            ?: data_get($meta, 'cover_image_url')
            ?: data_get($meta, 'cover_image')
            ?: data_get($meta, 'media.thumbnail_url')
            ?: data_get($meta, 'media.image_url')
            ?: data_get($meta, 'media.url')
            ?: data_get($meta, 'cover.thumbnail_url')
            ?: data_get($meta, 'cover.image_url')
            ?: data_get($meta, 'design.image_url')
            ?: data_get($meta, 'design.url')
            ?: data_get($meta, 'quote_design.image_url')
            ?: data_get($meta, 'quote_design.url')
            ?: $this->firstImageFromBlocks($post->blocks_json);

        return $this->absoluteUrl($value);
    }

    private function contentDeepLink(ContentPost $post, string $kind, array $channel = []): string
    {
        $bucket = strtolower(trim((string) ($post->bucket ?? '')));
        $id = (int) $post->id;

        if ($kind === 'short_video') {
            $query = [
                'id' => $id,
                'content_id' => $id,
                'post_id' => $id,
            ];

            $channelKey = trim((string) ($channel['key'] ?? ''));
            if ($channelKey !== '') {
                $query['channel'] = $channelKey;
                $query['channel_key'] = $channelKey;
            }

            return '/short-videos?' . http_build_query($query);
        }

        if ($kind === 'quote') {
            return $this->quoteDeepLinkForBucket($bucket, $id);
        }

        return match ($bucket) {
            'highlights' => '/highlights?post_id=' . $id,
            'motivation' => '/motivation?post_id=' . $id,
            'wordification' => '/wordification?post_id=' . $id,
            'sod' => '/sod?post_id=' . $id,
            default => '/articles/detail?id=' . $id,
        };
    }

    private function quoteDeepLinkForBucket(string $bucket, int $id): string
    {
        $bucket = strtolower(trim($bucket));

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

    private function actionLabel(string $kind): string
    {
        return match ($kind) {
            'quote' => 'View Quote',
            'short_video' => 'Watch Now',
            'book' => 'Open Book',
            'quiz' => 'Start Quiz',
            default => 'Read More',
        };
    }

    private function existingAutoPush(Model $model, string $kind, string $metaColumn = 'meta_json'): ?PushNotification
    {
        $meta = is_array($model->{$metaColumn} ?? null) ? $model->{$metaColumn} : [];
        $id = (int) data_get($meta, 'auto_push_notifications.' . $kind . '.push_notification_id', 0);

        return $id > 0 ? PushNotification::query()->find($id) : null;
    }

    private function alreadySent(Model $model, string $kind, string $metaColumn = 'meta_json'): bool
    {
        $meta = is_array($model->{$metaColumn} ?? null) ? $model->{$metaColumn} : [];
        $sent = (array) ($meta['auto_push_notifications'] ?? []);

        return ! empty($sent[$kind]['push_notification_id']);
    }

    private function markSent(Model $model, string $kind, PushNotification $push, string $metaColumn = 'meta_json'): void
    {
        $meta = is_array($model->{$metaColumn} ?? null) ? $model->{$metaColumn} : [];
        $sent = (array) ($meta['auto_push_notifications'] ?? []);
        $sent[$kind] = [
            'push_notification_id' => (int) $push->id,
            'queued_at' => now()->toISOString(),
            'scheduled_for' => optional($push->scheduled_for)->toISOString(),
            'status' => (string) $push->status,
        ];
        $meta['auto_push_notifications'] = $sent;

        $model->forceFill([$metaColumn => $meta])->saveQuietly();
    }

    private function resolveShortVideoChannel(App $app, int $postId): array
    {
        if ($postId <= 0) {
            return [];
        }

        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $channels = data_get($branding, 'short_video_channels', []);

        if (! is_array($channels)) {
            return [];
        }

        $fallback = [];

        foreach ($channels as $key => $channel) {
            if (! is_array($channel)) {
                continue;
            }

            $channelKey = trim((string) ($channel['key'] ?? $key));
            if ($channelKey === '') {
                continue;
            }

            $isActive = array_key_exists('is_active', $channel) ? (bool) $channel['is_active'] : true;
            $showOnFrontend = array_key_exists('show_on_frontend', $channel) ? (bool) $channel['show_on_frontend'] : true;
            $ids = $channel['video_ids'] ?? [];

            if (! is_array($ids)) {
                $ids = [];
            }

            $ids = array_values(array_filter(array_map('intval', $ids), fn (int $id): bool => $id > 0));
            if (! in_array($postId, $ids, true)) {
                continue;
            }

            $row = [
                'key' => $channelKey,
                'label' => trim((string) ($channel['label'] ?? $channel['name'] ?? $channelKey)),
                'placement' => trim((string) ($channel['placement'] ?? '')),
                'sort_order' => (int) ($channel['sort_order'] ?? 0),
                'is_active' => $isActive,
                'show_on_frontend' => $showOnFrontend,
            ];

            if ($isActive && $showOnFrontend) {
                return $row;
            }

            if ($fallback === []) {
                $fallback = $row;
            }
        }

        return $fallback;
    }

    private function firstImageFromBlocks(mixed $blocks): ?string
    {
        if (is_string($blocks)) {
            $decoded = json_decode($blocks, true);
            $blocks = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (! is_array($blocks)) {
            return null;
        }

        $stack = [$blocks];
        while ($stack !== []) {
            $item = array_pop($stack);
            if (! is_array($item)) {
                continue;
            }

            foreach (['image_url', 'image', 'url', 'src', 'thumbnail_url', 'preview_image_url'] as $key) {
                $value = $item[$key] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }

            foreach ($item as $value) {
                if (is_array($value)) {
                    $stack[] = $value;
                }
            }
        }

        return null;
    }

    private function settingEnabled(App $app, string $key): bool
    {
        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $value = data_get($branding, 'notification_settings.' . $key);
        if ($value === null) {
            $value = data_get($branding, 'notifications.' . $key);
        }
        if ($value === null) {
            $value = data_get($branding, $key);
        }

        // Default is ON for this phase so existing apps start receiving automatic updates.
        return $value === null ? true : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function normalizeDeepLink(?string $value): ?string
    {
        $link = trim((string) $value);
        if ($link === '') {
            return null;
        }

        if (! Str::startsWith($link, ['/', 'http://', 'https://'])) {
            $link = '/' . $link;
        }

        return $link;
    }

    private function absoluteUrl(mixed $value): ?string
    {
        $url = trim((string) $value);
        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        if (Str::startsWith($url, '//')) {
            return 'https:' . $url;
        }

        if (Str::startsWith($url, '/')) {
            return url($url);
        }

        return url('/' . ltrim($url, '/'));
    }

    private function cleanText(mixed $value): string
    {
        $text = trim(strip_tags(html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $text = preg_replace('/\s+/', ' ', $text) ?: '';

        return trim($text);
    }
}

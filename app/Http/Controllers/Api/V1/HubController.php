<?php

namespace App\Http\Controllers\Api\V1;

use App\Support\Publishing\PublicationVisibility;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ContentPost;
use App\Support\Ads\AdResolver;
use App\Support\ShortVideos\ShortVideoPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HubController extends Controller
{
    public function index(Request $request, string $appSlug): JsonResponse
    {
        $app = App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->with([
                'tabs' => function ($query) {
                    $query->where('is_enabled', true)
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
                'sections' => function ($query) {
                    $query->where('is_enabled', true)
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
                'sections.items' => function ($query) {
                    $query->where('is_enabled', true)
                        ->orderBy('sort_order')
                        ->orderBy('id');
                },
            ])
            ->first();

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'The requested app was not found or is not active.',
            ], 404);
        }

        $tabs = $app->tabs->map(function ($tab) {
            $settings = $this->safeArray($tab->meta_json);

            return [
                'id' => $tab->id,
                'key' => $tab->key,
                'title' => $tab->title,
                'label' => $tab->title,
                'icon' => $tab->icon,
                'order' => (int) $tab->sort_order,
                'sort_order' => (int) $tab->sort_order,
                'enabled' => (bool) $tab->is_enabled,
                'is_enabled' => (bool) $tab->is_enabled,
                'meta' => $settings,
                'settings' => $settings,
            ];
        })->values();

        $sections = $app->sections->map(function ($section) use ($app) {
            $template = (string) $section->template;
            $settings = $this->safeArray($section->meta_json);
            $layout = $settings['layout_variant'] ?? $settings['layout'] ?? $template;

            $items = $this->sectionItems($section, $layout, (int) $app->id, $settings);

            $sourceType = $settings['source_type'] ?? $settings['content_source'] ?? null;
            $sourceChannel = $settings['source_channel'] ?? $settings['channel_key'] ?? null;
            $sourceCategory = $settings['source_category'] ?? $settings['category_key'] ?? $settings['category'] ?? null;

            // Normalize legacy/mis-saved short-video source metadata so Flutter
            // receives one consistent contract even when the builder saved a
            // channel/category alongside the generic "all shorts" source.
            if (is_string($sourceType) && str_starts_with(strtolower(trim($sourceType)), 'short_video')) {
                $normalizedChannel = $this->optionalShortVideoKey($sourceChannel ?? null);
                $normalizedCategory = $this->optionalShortVideoKey($sourceCategory ?? null);

                if (($sourceType === 'short_video_all' || $sourceType === 'short_videos' || $sourceType === '') && $normalizedChannel !== '') {
                    $sourceType = 'short_video_channel';
                    $sourceChannel = $normalizedChannel;
                } elseif (($sourceType === 'short_video_all' || $sourceType === 'short_videos' || $sourceType === '') && $normalizedCategory !== '') {
                    $sourceType = 'short_video_category';
                    $sourceCategory = $normalizedCategory;
                }
            }

            $bucket = $settings['source_bucket'] ?? $settings['bucket'] ?? null;
            $targetRoute = $settings['target_route'] ?? $settings['route'] ?? null;
            $maxItems = $settings['max_items'] ?? null;
            $contentEngine = $settings['content_engine'] ?? $settings['engine'] ?? null;
            $placement = $settings['placement'] ?? $section->tab_key ?? null;

            return [
                'id' => $section->id,
                'key' => $section->key,
                'section_key' => $section->key,

                'tab_key' => $section->tab_key,
                'route_key' => $section->route_key,

                'title' => $section->title,
                'subtitle' => $section->subtitle,

                'type' => $this->resolveSectionType($section->key, $template),
                'layout' => $layout,
                'template' => $template,

                'source_type' => $sourceType,
                'source_channel' => $sourceChannel,
                'source_category' => $sourceCategory,
                'bucket' => $bucket,
                'target_route' => $targetRoute,
                'route' => $targetRoute,
                'max_items' => $maxItems !== null ? (int) $maxItems : null,
                'content_engine' => $contentEngine,
                'placement' => $placement,
                'source' => [
                    'type' => $sourceType,
                    'channel' => $sourceChannel,
                    'category' => $sourceCategory,
                    'bucket' => $bucket,
                    'engine' => $contentEngine,
                    'placement' => $placement,
                    'target_route' => $targetRoute,
                    'max_items' => $maxItems !== null ? (int) $maxItems : null,
                ],

                'items' => $items,

                'visibility' => $this->safeArray($section->visibility_json),
                'empty_state' => $this->safeArray($section->empty_state_json),
                'meta' => $settings,
                'settings' => $settings,

                'order' => (int) $section->sort_order,
                'sort_order' => (int) $section->sort_order,
                'enabled' => (bool) $section->is_enabled,
                'is_enabled' => (bool) $section->is_enabled,
            ];
        })->values();

        $sectionsByTab = $sections
            ->groupBy('tab_key')
            ->map(fn ($items) => $items->values());

        $dailyQuoteItems = $this->dailyQuoteItems((int) $app->id);
        $dailyScriptureItems = $this->dailyScriptureItems((int) $app->id);
        $internalPromos = $this->internalPromoItems((int) $app->id);
        $shortVideoSettings = $this->shortVideoSettingsForApp($app);
        $sections = $sections
            ->concat($this->shortVideoChannelPlacementSections($app, $sections, $shortVideoSettings))
            ->values();
        $sectionsByTab = $sections
            ->groupBy('tab_key')
            ->map(fn ($items) => $items->values());
        $shortVideoDirectory = $this->shortVideoDirectory($app);
        $adsPayload = $this->adsPayload((int) $app->id);
        $brandingPayload = $this->safeArray($app->branding_json);
        $brandingPayload['short_video_settings'] = $shortVideoSettings;
        $brandingPayload['short_video_feed_settings'] = $shortVideoSettings;

        return response()->json([
            'ok' => true,

            'app' => [
                'id' => $app->id,
                'name' => $app->name,
                'slug' => $app->slug,
                'logo_url' => $app->logo_url,
                'branding' => $brandingPayload,
            ],

            'tabs' => $tabs,
            'sections' => $sections,
            'sections_by_tab' => $sectionsByTab,

            // Home daily carousel payloads consumed by Flutter.
            // Kept top-level so the existing home section array remains backward compatible.
            'daily_quote_items' => $dailyQuoteItems,
            'daily_scripture_items' => $dailyScriptureItems,
            'internal_promos' => $internalPromos,
            'short_video_categories' => $shortVideoDirectory['categories'],
            'short_video_channels' => $shortVideoDirectory['channels'],
            'short_video_settings' => $shortVideoSettings,
            'short_video_feed_settings' => $shortVideoSettings,

            'ad_policy' => $adsPayload['ad_policy'],
            'ad_formats' => $adsPayload['ad_formats'],
            'native_in_list' => $adsPayload['native_in_list'],
            'ads' => $adsPayload['ads'],

            'home' => $sectionsByTab->get('home', collect())->values(),
            'watch' => $sectionsByTab->get('watch', collect())->values(),
            'inspire' => $sectionsByTab->get('inspire', collect())->values(),
            'explore' => $sectionsByTab->get('explore', collect())->values(),
            'more' => $sectionsByTab->get('more', collect())->values(),

            'meta' => [
                'api_version' => 'v4.4',
                'source' => 'app_tabs_app_sections_app_items_content_posts',
                'app_slug' => $app->slug,
                'render_engine' => 'dynamic_flutter_tv_engine',
                'design_payload_standard' => 'dxm_render_style_v1',
                'short_video_payload' => 'category_enabled_v1',
                'daily_quote_items_payload' => 'dynamic_quote_buckets_v1',
            ],
        ]);
    }


    private function shortVideoSettingsForApp(App $app): array
    {
        $branding = $this->safeArray($app->branding_json ?? []);
        $stored = $this->safeArray($branding['short_video_settings'] ?? $branding['short_video_feed_settings'] ?? []);

        $defaults = [
            'enabled' => true,
            'default_feed' => 'latest',
            'show_category_tabs' => true,
            'show_search_filter' => true,
            'autoplay' => true,
            'loop_current_video' => false,
            'auto_scroll_next' => true,
            'smooth_scroll_speed' => 'fast',
            'show_progress_bar' => true,
            'show_like_button' => true,
            'show_comment_button' => true,
            'show_share_button' => true,
            'allow_save_favorite' => true,
            'require_login_for_interactions' => true,
            'show_banner_ads' => true,
            'show_native_ads' => true,
            'native_ads_after' => 3,
            'fallback_message' => 'No short videos available yet.',
            'target_route' => '/short-videos',
        ];

        $merged = array_merge($defaults, $stored);
        $speed = strtolower(trim((string) ($merged['smooth_scroll_speed'] ?? 'fast')));
        if (! in_array($speed, ['fast', 'normal', 'slow'], true)) {
            $speed = 'fast';
        }

        $defaultFeed = strtolower(trim((string) ($merged['default_feed'] ?? 'latest')));
        if (! in_array($defaultFeed, ['all', 'featured', 'latest', 'category'], true)) {
            $defaultFeed = 'latest';
        }

        return [
            'enabled' => (bool) ($merged['enabled'] ?? true),
            'default_feed' => $defaultFeed,
            'show_category_tabs' => (bool) ($merged['show_category_tabs'] ?? true),
            'show_search_filter' => (bool) ($merged['show_search_filter'] ?? true),
            'autoplay' => (bool) ($merged['autoplay'] ?? true),
            'loop_current_video' => (bool) ($merged['loop_current_video'] ?? false),
            'auto_scroll_next' => (bool) ($merged['auto_scroll_next'] ?? true),
            'smooth_scroll_speed' => $speed,
            'show_progress_bar' => (bool) ($merged['show_progress_bar'] ?? true),
            'show_like_button' => (bool) ($merged['show_like_button'] ?? true),
            'show_comment_button' => (bool) ($merged['show_comment_button'] ?? true),
            'show_share_button' => (bool) ($merged['show_share_button'] ?? true),
            'allow_save_favorite' => (bool) ($merged['allow_save_favorite'] ?? true),
            'require_login_for_interactions' => (bool) ($merged['require_login_for_interactions'] ?? true),
            'show_banner_ads' => (bool) ($merged['show_banner_ads'] ?? true),
            'show_native_ads' => (bool) ($merged['show_native_ads'] ?? true),
            'native_ads_after' => max(1, min(20, (int) ($merged['native_ads_after'] ?? 3))),
            'fallback_message' => trim((string) ($merged['fallback_message'] ?? 'No short videos available yet.')) ?: 'No short videos available yet.',
            'target_route' => trim((string) ($merged['target_route'] ?? '/short-videos')) ?: '/short-videos',
        ];
    }

    private function shortVideoChannelPlacementSections(App $app, $existingSections, array $feedSettings)
    {
        $branding = $this->safeArray($app->branding_json ?? []);
        $channels = $this->safeArray($branding['short_video_channels'] ?? []);
        if ($channels === []) {
            return collect();
        }

        $existingChannelKeys = [];
        foreach ($existingSections as $section) {
            $settings = $this->safeArray($section['settings'] ?? $section['meta'] ?? []);
            $channel = $this->optionalShortVideoKey($section['source_channel'] ?? $settings['source_channel'] ?? $settings['channel_key'] ?? null);
            if ($channel !== '') {
                $existingChannelKeys[$channel] = true;
            }
        }

        $out = collect();
        foreach ($channels as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! (bool) ($row['is_active'] ?? true) || ! (bool) ($row['show_on_frontend'] ?? true)) {
                continue;
            }

            $channelKey = ShortVideoPayload::normalizeCategoryKey($row['key'] ?? $key);
            if ($channelKey === '' || isset($existingChannelKeys[$channelKey])) {
                continue;
            }

            $placementKey = $this->shortVideoPlacementKey($row, $channelKey);
            if ($placementKey === 'custom' || $placementKey === '') {
                continue;
            }

            $tabKey = $this->shortVideoPlacementTab($placementKey);
            $title = trim((string) ($row['label'] ?? '')) ?: ShortVideoPayload::normalizeCategoryLabel(null, $channelKey);
            $subtitle = trim((string) ($row['description'] ?? '')) ?: 'Watch short inspirational video feeds.';
            $displayStyle = trim((string) ($row['display_style'] ?? 'carousel')) ?: 'carousel';

            $settings = array_merge($feedSettings, [
                'content_engine' => 'short_video_feed',
                'content_source' => 'short_video_channel',
                'source_type' => 'short_video_channel',
                'source_channel' => $channelKey,
                'channel_key' => $channelKey,
                'channel_label' => $title,
                'bucket' => ShortVideoPayload::DEFAULT_BUCKET,
                'source_bucket' => ShortVideoPayload::DEFAULT_BUCKET,
                'placement' => $placementKey,
                'target_page' => $placementKey,
                'target_route' => $feedSettings['target_route'] ?? '/short-videos',
                'layout_variant' => 'short_video_feed',
                'display_style' => $displayStyle,
                'scroll_behavior' => $displayStyle === 'vertical_feed' ? 'vertical' : 'horizontal',
                'max_items' => max(1, count(array_filter(array_map('intval', $row['video_ids'] ?? []))) ?: 12),
            ]);

            $section = (object) [
                'id' => 0,
                'key' => 'short_channel_' . $channelKey,
                'title' => $title,
                'subtitle' => $subtitle,
                'template' => 'short_video_feed',
                'tab_key' => $tabKey,
                'route_key' => null,
                'sort_order' => (int) ($row['sort_order'] ?? 500),
                'is_enabled' => true,
            ];

            $items = $this->shortVideoPostItems($section, 'short_video_feed', (int) $app->id, $settings);
            if ($items->isEmpty()) {
                continue;
            }

            $out->push([
                'id' => 'short_channel_' . $channelKey,
                'key' => 'short_channel_' . $channelKey,
                'section_key' => 'short_channel_' . $channelKey,
                'tab_key' => $tabKey,
                'route_key' => null,
                'title' => $title,
                'subtitle' => $subtitle,
                'type' => 'short_video',
                'layout' => 'short_video_feed',
                'template' => 'short_video_feed',
                'source_type' => 'short_video_channel',
                'source_channel' => $channelKey,
                'source_category' => null,
                'bucket' => ShortVideoPayload::DEFAULT_BUCKET,
                'target_route' => $settings['target_route'],
                'route' => $settings['target_route'],
                'max_items' => (int) ($settings['max_items'] ?? 12),
                'content_engine' => 'short_video_feed',
                'placement' => $placementKey,
                'source' => [
                    'type' => 'short_video_channel',
                    'channel' => $channelKey,
                    'category' => null,
                    'bucket' => ShortVideoPayload::DEFAULT_BUCKET,
                    'engine' => 'short_video_feed',
                    'placement' => $placementKey,
                    'target_route' => $settings['target_route'],
                    'max_items' => (int) ($settings['max_items'] ?? 12),
                ],
                'items' => $items,
                'visibility' => [],
                'empty_state' => ['message' => $feedSettings['fallback_message'] ?? 'No short videos available yet.'],
                'meta' => $settings,
                'settings' => $settings,
                'order' => (int) ($row['sort_order'] ?? 500),
                'sort_order' => (int) ($row['sort_order'] ?? 500),
                'enabled' => true,
                'is_enabled' => true,
            ]);
        }

        return $out;
    }

    private function shortVideoPlacementKey(array $row, string $channelKey): string
    {
        $placement = strtolower(trim((string) ($row['placement'] ?? '')));
        $label = strtolower(trim((string) ($row['label'] ?? '')));
        $haystack = $channelKey . ' ' . $placement . ' ' . $label;

        if (str_contains($haystack, 'home')) return 'home';
        if (str_contains($haystack, 'inspire')) return 'inspire';
        if (str_contains($haystack, 'explore') || str_contains($haystack, 'weird')) return 'explore';
        if (str_contains($haystack, 'motivation')) return 'motivation';
        if (str_contains($haystack, 'wordification')) return 'wordification';
        if (str_contains($haystack, 'teaching') || str_contains($haystack, 'message')) return 'message_highlights';
        if (str_contains($haystack, 'worship') || str_contains($haystack, 'praise')) return 'worship';
        if (str_contains($haystack, 'testimon') || str_contains($haystack, 'miracle')) return 'testimonies';
        if (str_contains($haystack, 'prayer')) return 'prayer';

        return 'custom';
    }

    private function shortVideoPlacementTab(string $placementKey): string
    {
        return match ($placementKey) {
            'home' => 'home',
            'explore' => 'explore',
            default => 'inspire',
        };
    }


    private function adsPayload(int $appId): array
    {
        $ads = AdResolver::adsForApp($appId);

        return [
            'ad_policy' => $ads['ad_policy'],
            'ad_formats' => $ads['formats'],
            'native_in_list' => $ads['native_in_list'],
            'ads' => array_merge($ads, [
                'global' => [
                    'ads_enabled' => $ads['enabled'],
                    'banner_unit_id' => $ads['units']['banner'],
                    'native_unit_id' => $ads['units']['native'],
                    'interstitial_unit_id' => $ads['units']['interstitial'],
                ],
            ]),
        ];
    }

    private function sectionItems(object $section, string $layout, int $appId, array $settings)
    {
        if ($this->isShortVideoFeedSection($section, $settings)) {
            $shortVideoItems = $this->shortVideoPostItems($section, $layout, $appId, $settings);

            if ($shortVideoItems->isNotEmpty()) {
                return $shortVideoItems;
            }
        }

        if ($this->isContentPostFeedSection($section, $settings)) {
            $contentItems = $this->contentPostFeedItems($section, $layout, $appId, $settings);

            if ($contentItems->isNotEmpty()) {
                return $contentItems;
            }
        }

        return $section->items->map(function ($item) use ($section, $layout, $appId) {
            return $this->mapAppItem($item, $section, $layout, $appId);
        })->values();
    }

    private function mapAppItem(object $item, object $section, string $layout, int $appId = 0): array
    {
        $payload = $this->safeArray($item->payload_json);
        $watchLink = $this->watchLinkForPayload($payload, $appId);
        $payload = $this->mergeWatchLinkIntoPayload($payload, $watchLink);
        $quizSet = $this->quizSetForPayload($payload, $appId);
        $payload = $this->mergeQuizIntoPayload($payload, $quizSet);
        $style = $this->renderStyleFromPayload($payload);
        $engine = $payload['engine'] ?? $payload['action']['engine'] ?? null;
        $itemLayout = $payload['layout'] ?? $payload['style']['layout'] ?? null;

        $categoryKey = ShortVideoPayload::normalizeCategoryKey(
            $payload['category_key'] ?? $payload['category_slug'] ?? $payload['category'] ?? null
        );
        $categoryLabel = ShortVideoPayload::normalizeCategoryLabel(
            $payload['category_label'] ?? $payload['category_name'] ?? $payload['category_title'] ?? null,
            $categoryKey
        );

        return [
            'id' => $item->id,
            'key' => 'item_' . $item->id,
            'section_id' => $section->id,
            'section_key' => $section->key,

            'type' => $item->type ?: 'link',
            'layout' => $itemLayout ?: $layout,

            'title' => $item->title,
            'subtitle' => $item->subtitle,
            'description' => $payload['description'] ?? null,

            'icon' => $item->icon,
            'image_url' => $item->image_url,
            'thumbnail_url' => $payload['thumbnail_url'] ?? $item->image_url,
            'badge' => $payload['badge'] ?? null,

            'route' => $item->route,
            'url' => $this->resolvedItemUrl($item, $payload),
            'engine' => $engine,
            'content_id' => $payload['content_id'] ?? null,
            'bucket' => $payload['bucket'] ?? null,

            'category' => $categoryKey,
            'category_key' => $categoryKey,
            'category_slug' => $categoryKey,
            'category_label' => $categoryLabel,
            'category_name' => $categoryLabel,
            'category_title' => $categoryLabel,
            'tags' => ShortVideoPayload::normalizeTags($payload['tags'] ?? []),

            'daily_kind' => $payload['home_kind'] ?? null,
            'quote' => $payload['quote'] ?? $payload['quote_text'] ?? null,
            'quote_source' => $payload['source'] ?? $payload['quote_source'] ?? null,
            'scripture_text' => $payload['verse'] ?? null,
            'scripture_reference' => $payload['ref'] ?? null,
            'scripture_note' => $payload['note'] ?? null,

            'cta_label' => $payload['cta_label'] ?? $payload['cta']['label'] ?? null,
            'cta' => $this->safeArray($payload['cta'] ?? []),
            'action' => $this->safeArray($payload['action'] ?? []),
            'actions' => $this->safeArray($payload['actions'] ?? []),
            'watch_link_id' => $this->watchLinkIdFromPayload($payload),
            'watch_link' => $watchLink ? $this->watchLinkPublicPayload($watchLink) : null,
            'watch_source' => $watchLink ? $this->watchLinkPublicPayload($watchLink) : null,
            'quiz_key' => $this->quizKeyFromPayload($payload),
            'quiz_source' => $quizSet ? $this->quizSetPublicPayload($quizSet) : null,

            'style' => $style,
            'render_style' => $style,
            'design' => $style,
            'settings' => $this->safeArray($payload['settings'] ?? []),
            'payload' => $payload,

            'order' => (int) $item->sort_order,
            'sort_order' => (int) $item->sort_order,
            'enabled' => (bool) $item->is_enabled,
            'is_enabled' => (bool) $item->is_enabled,
        ];
    }



    // BEGIN DROP 3.3K-B QUIZ PAYLOAD RESOLVER
    private function quizSetForPayload(array $payload, int $appId): ?array
    {
        $quizKey = $this->quizKeyFromPayload($payload);

        if ($quizKey === null || $quizKey === '' || $appId <= 0 || ! Schema::hasTable('quiz_sets')) {
            return null;
        }

        try {
            $row = DB::table('quiz_sets')
                ->where('key', $quizKey)
                ->where(function ($query) use ($appId) {
                    if (Schema::hasColumn('quiz_sets', 'app_id')) {
                        $query->whereNull('app_id')->orWhere('app_id', $appId);
                    }
                })
                ->orderByRaw('CASE WHEN app_id = ? THEN 0 ELSE 1 END', [$appId])
                ->first();

            if (! $row) {
                return null;
            }

            $data = (array) $row;
            $questionCount = 0;

            if (Schema::hasTable('quiz_questions')) {
                $questionCount = (int) DB::table('quiz_questions')
                    ->where('quiz_set_id', (int) ($data['id'] ?? 0))
                    ->when(Schema::hasColumn('quiz_questions', 'is_enabled'), fn ($query) => $query->where('is_enabled', true))
                    ->count();
            }

            $data['question_count'] = $questionCount;
            $data['settings'] = $this->safeArray($data['settings_json'] ?? null);

            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mergeQuizIntoPayload(array $payload, ?array $quizSet): array
    {
        if (! $quizSet) {
            return $payload;
        }

        $quizKey = trim((string) ($quizSet['key'] ?? ''));

        if ($quizKey === '') {
            return $payload;
        }

        $quizType = trim((string) ($quizSet['type'] ?? 'general'));
        $route = '/quiz/' . $quizKey;
        $quizSource = $this->quizSetPublicPayload($quizSet);
        $action = $this->safeArray($payload['action'] ?? []);

        $payload['quiz_key'] = $quizKey;
        $payload['quiz_type'] = $quizType;
        $payload['quiz_source'] = $quizSource;
        $payload['content_intelligence'] = array_merge(
            $this->safeArray($payload['content_intelligence'] ?? []),
            [
                'engine' => 'quiz',
                'quiz_key' => $quizKey,
                'quiz_type' => $quizType,
                'quiz_source' => $quizSource,
            ]
        );

        $action['type'] = 'route';
        $action['route_key'] = 'quiz';
        $action['page_key'] = 'quiz';
        $action['route'] = $route;
        $action['quiz_key'] = $quizKey;
        $action['quiz_type'] = $quizType;
        $action['quiz_source'] = $quizSource;
        $action['api_path'] = '/api/v1/apps/{appSlug}/quizzes/' . $quizKey;

        $payload['action'] = $action;

        return $payload;
    }

    private function quizKeyFromPayload(array $payload): ?string
    {
        $action = $this->safeArray($payload['action'] ?? []);

        foreach ([
            $payload['quiz_key'] ?? null,
            $payload['quizKey'] ?? null,
            $payload['quiz'] ?? null,
            $action['quiz_key'] ?? null,
            $action['quizKey'] ?? null,
            $action['quiz'] ?? null,
        ] as $candidate) {
            $candidate = trim((string) $candidate);

            if ($candidate !== '') {
                return str($candidate)->slug('_')->toString();
            }
        }

        return null;
    }

    private function quizSetPublicPayload(array $quizSet): array
    {
        $quizKey = trim((string) ($quizSet['key'] ?? ''));

        return [
            'id' => (int) ($quizSet['id'] ?? 0),
            'key' => $quizKey,
            'title' => trim((string) ($quizSet['title'] ?? '')),
            'subtitle' => trim((string) ($quizSet['subtitle'] ?? '')) ?: null,
            'type' => trim((string) ($quizSet['type'] ?? 'general')),
            'difficulty' => trim((string) ($quizSet['difficulty'] ?? 'easy')),
            'status' => trim((string) ($quizSet['status'] ?? 'draft')),
            'image_url' => trim((string) ($quizSet['image_url'] ?? '')) ?: null,
            'question_count' => (int) ($quizSet['question_count'] ?? 0),
            'route' => $quizKey !== '' ? '/quiz/' . $quizKey : null,
            'api_path' => $quizKey !== '' ? '/api/v1/apps/{appSlug}/quizzes/' . $quizKey : null,
            'settings' => is_array($quizSet['settings'] ?? null) ? $quizSet['settings'] : [],
        ];
    }
    // END DROP 3.3K-B QUIZ PAYLOAD RESOLVER

    // BEGIN DROP 3.3J-G WATCH LINK PAYLOAD RESOLVER
    private function watchLinkForPayload(array $payload, int $appId): ?array
    {
        $watchLinkId = $this->watchLinkIdFromPayload($payload);

        if ($watchLinkId <= 0 || $appId <= 0 || ! Schema::hasTable('watch_links')) {
            return null;
        }

        try {
            $row = DB::table('watch_links')
                ->where('id', $watchLinkId)
                ->where(function ($query) use ($appId) {
                    if (Schema::hasColumn('watch_links', 'app_id')) {
                        $query->where('app_id', $appId);
                    }
                })
                ->first();

            if (! $row) {
                return null;
            }

            if (property_exists($row, 'is_enabled') && ! (bool) $row->is_enabled) {
                return null;
            }

            $data = (array) $row;
            $meta = $this->safeArray($data['meta_json'] ?? null);

            $data['meta'] = $meta;
            $data['watch_link_id'] = (int) ($data['id'] ?? 0);
            $data['resolved_url'] = trim((string) ($data['url'] ?? ''));
            $data['resolved_player'] = $this->watchPlayerForLink($data);
            $data['resolved_type'] = $this->watchTypeForLink($data);
            $data['resolved_label'] = trim((string) ($meta['label'] ?? ''));
            $data['resolved_subtitle'] = trim((string) (($data['subtitle'] ?? '') ?: ($meta['subtitle'] ?? '')));
            $data['resolved_image_url'] = trim((string) (($data['thumbnail_url'] ?? '') ?: ($meta['image_url'] ?? '')));

            return $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mergeWatchLinkIntoPayload(array $payload, ?array $watchLink): array
    {
        if (! $watchLink) {
            return $payload;
        }

        $watchLinkId = (int) ($watchLink['watch_link_id'] ?? 0);
        $url = trim((string) ($watchLink['resolved_url'] ?? ''));
        $player = trim((string) ($watchLink['resolved_player'] ?? 'web'));
        $type = trim((string) ($watchLink['resolved_type'] ?? 'web'));
        $label = trim((string) ($watchLink['resolved_label'] ?? ''));
        $subtitle = trim((string) ($watchLink['resolved_subtitle'] ?? ''));
        $imageUrl = trim((string) ($watchLink['resolved_image_url'] ?? ''));

        $action = $this->safeArray($payload['action'] ?? []);

        $payload['watch_link_id'] = $watchLinkId;
        $payload['watch_source'] = [
            'id' => $watchLinkId,
            'title' => trim((string) ($watchLink['title'] ?? '')),
            'type' => $type,
            'player' => $player,
            'url' => $url !== '' ? $url : null,
            'label' => $label !== '' ? $label : null,
            'subtitle' => $subtitle !== '' ? $subtitle : null,
            'image_url' => $imageUrl !== '' ? $imageUrl : null,
        ];

        $action['watch_link_id'] = $watchLinkId;
        $action['watch_source'] = $payload['watch_source'];
        $action['player'] = $player;
        $action['watch_type'] = $type;

        if ($url !== '') {
            if ($player === 'youtube' || str_contains(strtolower($url), 'youtube.com') || str_contains(strtolower($url), 'youtu.be')) {
                $action['youtube'] = $url;
                $action['youtube_url'] = $url;
                $payload['youtube'] = $url;
                $payload['youtube_url'] = $url;

                if (empty($action['type']) || ! is_string($action['type'])) {
                    $action['type'] = str_contains(strtolower($url), 'playlist') || str_contains(strtolower($url), 'list=')
                        ? 'youtube_playlist'
                        : 'youtube_video';
                }
            } else {
                $action['url'] = $url;
                $payload['url'] = $url;

                if (empty($action['type']) || ! is_string($action['type'])) {
                    $action['type'] = $type === 'live_hls' ? 'live_stream' : 'webview';
                }
            }

            $action['resolved_url'] = $url;
            $payload['resolved_url'] = $url;
        }

        if ($label !== '' && empty($payload['badge'])) {
            $payload['badge'] = $label;
        }

        if ($subtitle !== '' && empty($payload['watch_subtitle'])) {
            $payload['watch_subtitle'] = $subtitle;
        }

        if ($imageUrl !== '' && empty($payload['watch_image_url'])) {
            $payload['watch_image_url'] = $imageUrl;
        }

        $payload['action'] = $action;

        return $payload;
    }

    private function watchLinkIdFromPayload(array $payload): ?int
    {
        $action = $this->safeArray($payload['action'] ?? []);

        $candidates = [
            $payload['watch_link_id'] ?? null,
            $payload['watchLinkId'] ?? null,
            $payload['watch_link'] ?? null,
            $action['watch_link_id'] ?? null,
            $action['watchLinkId'] ?? null,
            $action['watch_link'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_numeric($candidate) && (int) $candidate > 0) {
                return (int) $candidate;
            }
        }

        return null;
    }

    private function watchLinkPublicPayload(array $watchLink): array
    {
        return [
            'id' => (int) ($watchLink['watch_link_id'] ?? $watchLink['id'] ?? 0),
            'title' => trim((string) ($watchLink['title'] ?? '')),
            'type' => trim((string) ($watchLink['resolved_type'] ?? $watchLink['type'] ?? '')),
            'player' => trim((string) ($watchLink['resolved_player'] ?? 'web')),
            'url' => trim((string) ($watchLink['resolved_url'] ?? $watchLink['url'] ?? '')) ?: null,
            'label' => trim((string) ($watchLink['resolved_label'] ?? '')) ?: null,
            'subtitle' => trim((string) ($watchLink['resolved_subtitle'] ?? '')) ?: null,
            'image_url' => trim((string) ($watchLink['resolved_image_url'] ?? '')) ?: null,
        ];
    }

    private function resolvedItemUrl(object $item, array $payload): ?string
    {
        $action = $this->safeArray($payload['action'] ?? []);

        foreach ([
            $action['resolved_url'] ?? null,
            $action['url'] ?? null,
            $action['youtube'] ?? null,
            $action['youtube_url'] ?? null,
            $payload['resolved_url'] ?? null,
            $payload['url'] ?? null,
            $payload['youtube'] ?? null,
            $payload['youtube_url'] ?? null,
            $item->url ?? null,
        ] as $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function watchPlayerForLink(array $watchLink): string
    {
        $meta = $this->safeArray($watchLink['meta'] ?? $watchLink['meta_json'] ?? null);
        $player = strtolower(trim((string) ($meta['player'] ?? '')));
        $url = strtolower(trim((string) ($watchLink['url'] ?? '')));
        $type = strtolower(trim((string) ($watchLink['type'] ?? '')));

        if ($player !== '') {
            return $player;
        }

        if ($type === 'live_hls' || str_ends_with($url, '.m3u8')) {
            return 'hls';
        }

        if (str_contains($url, 'youtube.com') || str_contains($url, 'youtu.be')) {
            return 'youtube';
        }

        return 'web';
    }

    private function watchTypeForLink(array $watchLink): string
    {
        $type = strtolower(trim((string) ($watchLink['type'] ?? '')));
        $url = strtolower(trim((string) ($watchLink['url'] ?? '')));
        $player = $this->watchPlayerForLink($watchLink);

        if ($type !== '') {
            return $type;
        }

        if ($player === 'hls' || str_ends_with($url, '.m3u8')) {
            return 'live_hls';
        }

        if ($player === 'youtube') {
            return 'youtube';
        }

        return 'web';
    }
    // END DROP 3.3J-G WATCH LINK PAYLOAD RESOLVER

    private function isShortVideoFeedSection(object $section, array $settings): bool
    {
        $haystack = strtolower(implode(' ', [
            (string) ($section->key ?? ''),
            (string) ($section->title ?? ''),
            (string) ($section->template ?? ''),
            (string) ($settings['bucket'] ?? ''),
            (string) ($settings['content_engine'] ?? ''),
            (string) ($settings['layout_variant'] ?? ''),
            (string) ($settings['action'] ?? ''),
        ]));

        return str_contains($haystack, 'short_video')
            || str_contains($haystack, 'short videos')
            || str_contains($haystack, 'short-videos')
            || str_contains($haystack, 'shorts')
            || str_contains($haystack, 'reel');
    }

    private function shortVideoPostItems(object $section, string $layout, int $appId, array $settings)
    {
        if (! Schema::hasTable('content_posts')) {
            return collect();
        }

        $sourceType = strtolower(trim((string) ($settings['source_type'] ?? $settings['content_source'] ?? 'short_video_all')));
        $rawBucket = trim((string) ($settings['source_bucket'] ?? $settings['bucket'] ?? ''));
        $bucket = ShortVideoPayload::normalizeBucket($rawBucket !== '' ? $rawBucket : 'short_videos');
        $maxItems = (int) ($settings['max_items'] ?? 12);

        // Defensive source inference for older Destination Builder saves:
        // if a short-video channel/category exists, honor it even when
        // source_type was accidentally left as short_video_all.
        $configuredChannelKey = $this->optionalShortVideoKey($settings['source_channel'] ?? $settings['channel_key'] ?? null);
        $configuredCategoryKey = $this->optionalShortVideoKey($settings['source_category'] ?? $settings['category_key'] ?? $settings['category'] ?? null);

        if (in_array($sourceType, ['short_video_all', 'short_videos', 'shorts', ''], true)) {
            if ($configuredChannelKey !== '') {
                $sourceType = 'short_video_channel';
            } elseif ($configuredCategoryKey !== '') {
                $sourceType = 'short_video_category';
            }
        }
        $maxItems = $maxItems > 0 ? min($maxItems, 60) : 12;

        $channel = null;
        $channelVideoIds = [];

        if ($sourceType === 'short_video_channel') {
            $channelKey = $configuredChannelKey;
            $channel = $this->shortVideoChannelForSection($appId, $channelKey);
            $channelVideoIds = array_values(array_filter(array_map('intval', $channel['video_ids'] ?? []), fn (int $id): bool => $id > 0));

            if ($channelKey !== '' && $channel === null) {
                return collect();
            }

            if ($channel !== null && $channelVideoIds === []) {
                return collect();
            }

            $settings['source_channel'] = $channelKey;
            $settings['channel_key'] = $channelKey;
            $settings['channel_label'] = $channel['label'] ?? ShortVideoPayload::normalizeCategoryLabel(null, $channelKey);
        }

        $categoryKey = null;
        if ($sourceType === 'short_video_category') {
            $categoryKey = $configuredCategoryKey;
            if ($categoryKey !== '') {
                $settings['source_category'] = $categoryKey;
                $settings['category'] = $categoryKey;
                $settings['category_key'] = $categoryKey;
            }
        }

        $query = DB::table('content_posts')
            ->where('app_id', $appId)
            ->where('bucket', $bucket);

        PublicationVisibility::apply($query);

        if ($channelVideoIds !== []) {
            $query->whereIn('id', $channelVideoIds);
        }

        if ($categoryKey !== null && $categoryKey !== '') {
            $query->where(function ($categoryQuery) use ($categoryKey): void {
                $categoryQuery
                    ->where('meta_json->category', $categoryKey)
                    ->orWhere('meta_json->category_key', $categoryKey)
                    ->orWhere('meta_json->category_slug', $categoryKey);
            });
        }

        $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->limit($maxItems);

        return $query->get()->map(function ($post) use ($section, $bucket, $settings) {
            return ShortVideoPayload::postToItem(
                post: $post,
                section: $section,
                bucket: $bucket,
                settings: $settings,
                summaryResolver: fn ($html) => $this->plainSummary($html),
                styleResolver: fn ($payload) => $this->renderStyleFromPayload($payload),
            );
        })->values();
    }


    private function optionalShortVideoKey(mixed $value): string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return '';
        }

        return ShortVideoPayload::normalizeCategoryKey($raw);
    }

    private function shortVideoChannelForSection(int $appId, string $channelKey): ?array
    {
        if ($appId <= 0 || $channelKey === '') {
            return null;
        }

        $app = App::query()->find($appId);
        $branding = $this->safeArray($app?->branding_json ?? []);
        $channels = $this->safeArray($branding['short_video_channels'] ?? []);

        foreach ($channels as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalizedKey = ShortVideoPayload::normalizeCategoryKey($row['key'] ?? $key);
            if ($normalizedKey !== $channelKey) {
                continue;
            }

            if (! (bool) ($row['is_active'] ?? true) || ! (bool) ($row['show_on_frontend'] ?? true)) {
                return null;
            }

            $row['key'] = $normalizedKey;
            $row['video_ids'] = is_array($row['video_ids'] ?? null) ? $row['video_ids'] : [];

            return $row;
        }

        return null;
    }

    private function shortVideoDirectory(App $app): array
    {
        $branding = $this->safeArray($app->branding_json ?? []);

        return [
            'categories' => $this->shortVideoDirectoryRows($branding['short_video_categories'] ?? []),
            'channels' => $this->shortVideoDirectoryRows($branding['short_video_channels'] ?? []),
        ];
    }

    private function shortVideoDirectoryRows(mixed $rows): array
    {
        $rows = $this->safeArray($rows);
        $out = [];

        foreach ($rows as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (! (bool) ($row['is_active'] ?? true) || ! (bool) ($row['show_on_frontend'] ?? true)) {
                continue;
            }

            $normalizedKey = ShortVideoPayload::normalizeCategoryKey($row['key'] ?? $key);
            $out[] = [
                'key' => $normalizedKey,
                'label' => trim((string) ($row['label'] ?? $row['name'] ?? '')) ?: ShortVideoPayload::normalizeCategoryLabel(null, $normalizedKey),
                'description' => trim((string) ($row['description'] ?? '')) ?: null,
                'icon' => trim((string) ($row['icon'] ?? '')) ?: null,
                'color' => trim((string) ($row['color'] ?? '')) ?: null,
                'placement' => trim((string) ($row['placement'] ?? '')) ?: null,
                'display_style' => trim((string) ($row['display_style'] ?? '')) ?: null,
                'sort_order' => (int) ($row['sort_order'] ?? 0),
                'video_ids' => array_values(array_filter(array_map('intval', $row['video_ids'] ?? []), fn (int $id): bool => $id > 0)),
            ];
        }

        usort($out, fn (array $a, array $b): int => (($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0)) ?: strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? '')));

        return $out;
    }



    private function isContentPostFeedSection(object $section, array $settings): bool
    {
        $sourceType = strtolower(trim((string) ($settings['source_type'] ?? $settings['content_source'] ?? '')));
        $engine = strtolower(trim((string) ($settings['content_engine'] ?? $settings['engine'] ?? '')));
        $bucket = strtolower(trim((string) ($settings['source_bucket'] ?? $settings['bucket'] ?? '')));
        $template = strtolower(trim((string) ($section->template ?? '')));

        if (in_array($sourceType, ['content_channel', 'quote_channel', 'daily_quote', 'daily_scripture'], true)) {
            return true;
        }

        if (in_array($engine, ['content_feed', 'article_feed', 'quote_feed', 'scripture_feed'], true)) {
            return true;
        }

        return str_contains($template, 'quote')
            || str_contains($template, 'article')
            || str_contains($template, 'content')
            || str_contains($bucket, 'quote');
    }

    private function contentPostFeedItems(object $section, string $layout, int $appId, array $settings)
    {
        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            return collect();
        }

        $sourceType = strtolower(trim((string) ($settings['source_type'] ?? $settings['content_source'] ?? 'content_channel')));
        $engine = strtolower(trim((string) ($settings['content_engine'] ?? 'content_feed')));
        $bucket = trim((string) ($settings['source_bucket'] ?? $settings['bucket'] ?? ''));
        $maxItems = (int) ($settings['max_items'] ?? 12);
        $maxItems = $maxItems > 0 ? min($maxItems, 60) : 12;

        if ($bucket === '') {
            $bucket = match ($sourceType) {
                'quote_channel', 'daily_quote' => 'daily_quotes',
                'daily_scripture' => 'daily_scriptures',
                default => 'articles',
            };
        }

        $query = ContentPost::query()
            ->where('app_id', $appId);

        PublicationVisibility::apply($query);

        if ($bucket !== '*' && strtolower($bucket) !== 'all') {
            $query->where('bucket', $bucket);
        }

        $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->limit($maxItems);

        return $query->get()
            ->map(fn (ContentPost $post) => $this->mapContentPostFeedItem($post, $section, $layout, $settings, $sourceType, $engine))
            ->values();
    }

    private function mapContentPostFeedItem(ContentPost $post, object $section, string $layout, array $settings, string $sourceType, string $engine): array
    {
        $meta = $this->safeArray($post->meta_json);
        $blocks = $this->safeArray($post->blocks_json);
        $isQuote = $sourceType === 'quote_channel'
            || $sourceType === 'daily_quote'
            || $engine === 'quote_feed'
            || str_contains(strtolower((string) $post->bucket), 'quote');
        $isScripture = $sourceType === 'daily_scripture' || $engine === 'scripture_feed';

        if ($isQuote || $isScripture) {
            $kind = $isScripture ? 'daily_scripture' : 'daily_quote';
            $item = $this->mapDailyPostItem($post, $kind);
            $item['key'] = ($isScripture ? 'scripture_' : 'quote_') . $post->id;
            $item['section_id'] = $section->id ?? null;
            $item['section_key'] = $section->key ?? null;
            $item['layout'] = $settings['layout_variant'] ?? $layout;
            $item['route'] = $this->quoteRouteForPost($post, $settings);
            $item['url'] = $item['route'];
            $item['action'] = ['type' => 'route', 'route' => $item['route']];
            $item['payload'] = array_merge($item['payload'] ?? [], [
                'channel' => $post->bucket,
                'source_bucket' => $post->bucket,
                'target_route' => $settings['target_route'] ?? '/sod/quotes',
            ]);
            return $item;
        }

        $imageUrl = $this->contentPostImageUrl($post, $meta, $blocks);
        $summary = $this->plainSummary($post->body_html) ?: trim((string) ($post->subtitle ?? ''));
        $route = '/articles/detail?id=' . (int) $post->id;

        return [
            'id' => $post->id,
            'key' => 'content_' . $post->id,
            'section_id' => $section->id ?? null,
            'section_key' => $section->key ?? null,
            'type' => 'article',
            'layout' => $settings['layout_variant'] ?? $layout,
            'title' => $post->title,
            'subtitle' => $post->subtitle,
            'description' => $summary,
            'excerpt' => $summary,
            'bucket' => $post->bucket,
            'source_bucket' => $post->bucket,
            'image_url' => $imageUrl,
            'thumbnail_url' => $imageUrl,
            'cover_image_url' => $imageUrl,
            'route' => $route,
            'url' => $route,
            'content_id' => $post->id,
            'is_featured' => (bool) $post->is_featured,
            'published_at' => optional($post->published_at)->toIso8601String(),
            'publish_at' => optional($post->publish_at)->toIso8601String(),
            'action' => ['type' => 'route', 'route' => $route, 'content_id' => $post->id],
            'style' => $this->renderStyleFromPayload($meta),
            'render_style' => $this->renderStyleFromPayload($meta),
            'design' => $this->renderStyleFromPayload($meta),
            'payload' => array_merge($meta, [
                'id' => $post->id,
                'content_id' => $post->id,
                'bucket' => $post->bucket,
                'source_bucket' => $post->bucket,
            ]),
            'order' => (int) $post->sort_order,
            'sort_order' => (int) $post->sort_order,
            'enabled' => true,
            'is_enabled' => true,
        ];
    }

    private function quoteRouteForPost(ContentPost $post, array $settings): string
    {
        $base = trim((string) ($settings['target_route'] ?? '')) ?: '/sod/quotes';
        $separator = str_contains($base, '?') ? '&' : '?';

        return $base . $separator . http_build_query([
            'id' => (int) $post->id,
            'quote_id' => (int) $post->id,
            'post_id' => (int) $post->id,
            'channel' => (string) $post->bucket,
        ]);
    }

    private function contentPostImageUrl(ContentPost $post, array $meta, array $blocks = []): ?string
    {
        foreach ([
            $post->cover_image_src ?? null,
            $post->cover_image_url ?? null,
            $meta['design_image_url'] ?? null,
            $meta['designed_image_url'] ?? null,
            $meta['rendered_image_url'] ?? null,
            $meta['generated_image_url'] ?? null,
            $meta['final_image_url'] ?? null,
            $meta['card_image_url'] ?? null,
            $meta['quote_image_url'] ?? null,
            $meta['preview_image_url'] ?? null,
            $meta['export_image_url'] ?? null,
            $meta['image_url'] ?? null,
            $meta['thumbnail_url'] ?? null,
        ] as $candidate) {
            $candidate = trim((string) $candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        foreach ($blocks as $block) {
            if (! is_array($block)) {
                continue;
            }
            foreach (['image_url', 'url', 'src', 'preview_image_url'] as $key) {
                $candidate = trim((string) ($block[$key] ?? ''));
                if ($candidate !== '') {
                    return $candidate;
                }
            }
        }

        return null;
    }

    // BEGIN HOTFIX 5I DAILY QUOTE CAROUSEL HELPERS
    private function dailyQuoteItems(int $appId)
    {
        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            return collect();
        }

        return ContentPost::query()
            ->where('app_id', $appId)
            ->whereIn('status', ['published', 'publish', 'active'])
            ->where(function ($visibilityQuery) {
                $visibilityQuery->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->where(function ($query) {
                $query->where('bucket', 'daily_quotes')
                    ->orWhere(function ($q) {
                        $q->where(function ($b) {
                            $b->where('bucket', 'like', '%quote%')
                                ->orWhere('bucket', 'like', '%motivation%')
                                ->orWhere('bucket', 'sod_quotes')
                                ->orWhere('bucket', 'article_quotes')
                                ->orWhere('bucket', 'custom_quotes');
                        })
                        ->where(function ($f) {
                            $f->where('is_featured', true)
                                ->orWhere('meta_json->show_in_daily_quote', true)
                                ->orWhere('meta_json->show_in_daily_carousel', true)
                                ->orWhere('meta_json->featured_daily', true);
                        });
                    });
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn (ContentPost $post) => $this->mapDailyPostItem($post, 'daily_quote'))
            ->filter(fn (array $item) => trim((string) ($item['quote_text'] ?? '')) !== '')
            ->values();
    }

    private function dailyScriptureItems(int $appId)
    {
        if ($appId <= 0 || ! Schema::hasTable('content_posts')) {
            return collect();
        }

        return ContentPost::query()
            ->where('app_id', $appId)
            ->where('bucket', 'daily_scriptures')
            ->whereIn('status', ['published', 'publish', 'active'])
            ->where(function ($visibilityQuery) {
                $visibilityQuery->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (ContentPost $post) => $this->mapDailyPostItem($post, 'daily_scripture'))
            ->filter(fn (array $item) => trim((string) ($item['quote_text'] ?? $item['scripture_text'] ?? '')) !== '')
            ->values();
    }

    private function internalPromoItems(int $appId)
    {
        // Reserved for AppsHub-controlled internal promotion cards.
        // This key is intentionally present now so Flutter can be wired without changing the API contract later.
        return collect();
    }

    private function mapDailyPostItem(ContentPost $post, string $kind): array
    {
        $meta = $this->safeArray($post->meta_json);
        $payload = $meta;
        $payload['id'] = $post->id;
        $payload['content_id'] = $post->id;
        $payload['bucket'] = $post->bucket;
        $payload['source_bucket'] = $post->bucket;
        $payload['home_kind'] = $kind;
        $payload['daily_kind'] = $kind;

        $quoteText = $kind === 'daily_scripture'
            ? $this->firstDailyText($meta, ['verse', 'scripture', 'scripture_text', 'quote_text', 'quote', 'text', 'main_text'])
            : $this->firstDailyText($meta, ['quote_text', 'quote', 'text', 'main_text', 'body', 'content']);

        if ($quoteText === '') {
            $quoteText = trim(strip_tags((string) ($post->body_html ?? '')));
        }

        if ($quoteText === '') {
            $quoteText = trim((string) ($post->title ?? ''));
        }

        $source = $kind === 'daily_scripture'
            ? $this->firstDailyText($meta, ['ref', 'reference', 'scripture_reference', 'source', 'quote_source'])
            : $this->firstDailyText($meta, ['quote_source', 'source', 'author', 'author_name', 'credit', 'ref', 'reference']);

        if ($source === '') {
            $source = trim((string) ($post->author_name ?? ''));
        }

        $style = $this->renderStyleFromPayload($payload);

        return [
            'id' => $post->id,
            'key' => 'daily_' . $kind . '_' . $post->id,
            'type' => $kind === 'daily_scripture' ? 'scripture' : 'quote',
            'layout' => 'dynamic_quote_card',
            'title' => $post->title,
            'subtitle' => $kind === 'daily_scripture'
                ? $this->dailyScriptureSubtitleForPayload($post->subtitle, $source)
                : $post->subtitle,
            'bucket' => $post->bucket,
            'source_bucket' => $post->bucket,
            'daily_kind' => $kind,
            'home_kind' => $kind,
            'quote' => $quoteText,
            'quote_text' => $quoteText,
            'source' => $source,
            'quote_source' => $source,
            'scripture_text' => $kind === 'daily_scripture' ? $quoteText : null,
            'scripture_reference' => $kind === 'daily_scripture' ? $source : null,
            'image_url' => $post->cover_image_src,
            'cover_image_url' => $post->cover_image_src,
            'is_featured' => (bool) $post->is_featured,
            'published_at' => optional($post->published_at)->toIso8601String(),
            'publish_at' => optional($post->publish_at)->toIso8601String(),
            'style' => $style,
            'render_style' => $style,
            'design' => $style,
            'payload' => $payload,
        ];
    }

    private function dailyScriptureSubtitleForPayload(mixed $subtitle, string $source): ?string
    {
        $cleanSubtitle = trim((string) $subtitle);
        $cleanSource = trim((string) $source);

        if ($cleanSubtitle === '') {
            return null;
        }

        if ($this->sameDailyDisplayText($cleanSubtitle, $cleanSource)) {
            return null;
        }

        return $cleanSubtitle;
    }

    private function sameDailyDisplayText(string $left, string $right): bool
    {
        $normalize = static function (string $value): string {
            $value = trim($value);
            $value = preg_replace('/\s+/', ' ', $value) ?? $value;
            $value = str_replace(['–', '—'], '-', $value);
            return mb_strtolower($value);
        };

        $a = $normalize($left);
        $b = $normalize($right);

        return $a !== '' && $a === $b;
    }

    private function firstDailyText(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }
    // END HOTFIX 5I DAILY QUOTE CAROUSEL HELPERS

    private function plainSummary(?string $html): ?string
    {
        $text = trim(strip_tags((string) $html));

        if ($text === '') {
            return null;
        }

        return mb_strlen($text) > 160 ? mb_substr($text, 0, 157) . '...' : $text;
    }

    private function renderStyleFromPayload(array $payload): array
    {
        $cardFormat = (string) ($payload['card_format'] ?? 'portrait');

        return [
            'standard' => 'dxm_render_style_v1',
            'mode' => (string) ($payload['mode'] ?? 'manual'),
            'background_mode' => (string) ($payload['background_mode'] ?? 'gradient'),
            'card_format' => $cardFormat,
            'canvas_ratio' => $this->formatRatio($cardFormat),
            'font_family' => (string) ($payload['font_family'] ?? 'system'),
            'text_scale_mode' => (string) ($payload['text_scale_mode'] ?? 'auto'),
            'text_color' => (string) ($payload['text_color'] ?? '#ffffff'),
            'bg_color' => (string) ($payload['bg_color'] ?? '#160042'),
            'bg_color_2' => (string) ($payload['bg_color_2'] ?? '#e2388a'),
            'accent_color' => (string) ($payload['accent_color'] ?? '#38bdf8'),
            'font_size' => (float) ($payload['font_size'] ?? $payload['source_size'] ?? 14),
            'title_size' => (float) ($payload['title_size'] ?? $payload['quote_size'] ?? 24),
            'font_weight' => (string) ($payload['font_weight'] ?? '700'),
            'text_align' => (string) ($payload['text_align'] ?? 'center'),
            'vertical_align' => (string) ($payload['vertical_align'] ?? 'center'),
            'overlay_strength' => (int) ($payload['overlay_strength'] ?? 58),
            'content_width' => (int) ($payload['content_width'] ?? 86),
            'card_padding' => (int) ($payload['card_padding'] ?? 34),
            'line_height' => (float) ($payload['line_height'] ?? 1.35),
            'show_quote_mark' => (bool) ($payload['show_quote_mark'] ?? true),
        ];
    }

    private function formatRatio(string $format): string
    {
        return match (strtolower(trim($format))) {
            'square' => '1 / 1',
            'story' => '9 / 16',
            'landscape', 'wide' => '16 / 9',
            'cinematic' => '21 / 9',
            'classic' => '3 / 2',
            default => '4 / 5',
        };
    }

    private function safeArray(mixed $value): array
    {
        return ShortVideoPayload::safeArray($value);
    }

    private function resolveSectionType(string $key, string $template): string
    {
        $value = strtolower($key . ' ' . $template);

        if (str_contains($value, 'scripture')) {
            return 'scripture';
        }

        if (str_contains($value, 'quote')) {
            return 'quote';
        }

        if (str_contains($value, 'banner') || str_contains($value, 'hero')) {
            return 'banner';
        }

        if (str_contains($value, 'short') || str_contains($value, 'reel')) {
            return 'short_video';
        }

        if (str_contains($value, 'watch') || str_contains($value, 'video') || str_contains($value, 'live')) {
            return 'video';
        }

        if (str_contains($value, 'tool') || str_contains($value, 'explore')) {
            return 'tool';
        }

        if (str_contains($value, 'article') || str_contains($value, 'post') || str_contains($value, 'inspire')) {
            return 'article';
        }

        return $template ?: 'section';
    }
}

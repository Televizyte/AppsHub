<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\AppSection;
use App\Support\ActiveApp;
use App\Support\ShortVideos\ShortVideoPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BeginnerSectionCreateController extends Controller
{
    private array $tabs = [
        'home' => 'Home',
        'watch' => 'Watch',
        'inspire' => 'Inspire',
        'explore' => 'Explore',
        'more' => 'More',
    ];

    public function create(Request $request): View
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $prefillTab = $this->cleanTab($request->query('tab', $request->query('tab_key', 'home')));
        $prefillRouteKey = trim((string) $request->query('route_key', ''));
        $returnTo = $this->sanitizeReturnTo($request->query('return'));
        $returnUrl = $this->returnUrl($returnTo, $prefillTab);

        return view('admin.beginner.sections.create', [
            'tabs' => $this->tabs,
            'templates' => $this->templates(),
            'layoutVariants' => $this->layoutVariants(),
            'cardStyles' => $this->cardStyles(),
            'scrollModes' => $this->scrollModes(),
            'sizePresets' => $this->sizePresets(),
            'placementTypes' => $this->placementTypes(),
            'sourceOptions' => $this->sourceOptions(),
            'contentBuckets' => $this->contentBuckets($activeAppId),
            'shortVideoCategories' => $this->shortVideoCategories($activeAppId),
            'shortVideoChannels' => $this->shortVideoChannels($activeAppId),
            'prefillTab' => $prefillTab,
            'prefillRouteKey' => $prefillRouteKey,
            'currentTab' => $prefillTab,
            'returnTo' => $returnTo,
            'returnUrl' => $returnUrl,
            'meta' => [],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $validated = $this->validatedPayload($request);

        AppSection::create([
            'app_id' => $activeAppId,
            'tab_key' => $validated['tab_key'],
            'route_key' => ($validated['route_key'] ?? '') !== '' ? $validated['route_key'] : null,
            'key' => $validated['key'],
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'template' => $validated['template'],
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'meta_json' => $this->placementMetaFromValidated($validated, []),
        ]);

        $returnTo = $this->sanitizeReturnTo($validated['return'] ?? null);
        $tab = $this->cleanTab($validated['current_tab'] ?? $validated['tab_key']);

        return redirect($this->returnUrl($returnTo, $tab))
            ->with('status', 'Section created successfully.');
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'tab_key' => ['required', 'in:home,watch,inspire,explore,more'],
            'current_tab' => ['nullable', 'in:home,watch,inspire,explore,more'],
            'route_key' => ['nullable', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:140'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'key' => ['required', 'string', 'max:120'],
            'template' => ['required', 'string', 'max:120'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
            'return' => ['nullable', 'string'],

            'layout_variant' => ['nullable', 'string', 'max:80'],
            'columns' => ['nullable', 'integer', 'min:1', 'max:4'],
            'card_style' => ['nullable', 'string', 'max:80'],
            'scroll_behavior' => ['nullable', 'string', 'max:80'],
            'size_preset' => ['nullable', 'string', 'max:80'],

            'placement_type' => ['nullable', 'string', 'max:80'],
            'insert_target_bucket' => ['nullable', 'string', 'max:120'],
            'insert_after_items' => ['nullable', 'integer', 'min:0', 'max:50'],
            'parent_section_key' => ['nullable', 'string', 'max:120'],
            'insert_anchor_section_key' => ['nullable', 'string', 'max:120'],
            'builder_role' => ['nullable', 'string', 'max:80'],

            'source_type' => ['nullable', 'string', 'max:80'],
            'source_bucket' => ['nullable', 'string', 'max:120'],
            'source_category' => ['nullable', 'string', 'max:120'],
            'source_channel' => ['nullable', 'string', 'max:120'],
            'target_route' => ['nullable', 'string', 'max:160'],
            'max_items' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);
    }

    private function templates(): array
    {
        return [
            'banner_carousel' => 'Banner Carousel',
            'hero' => 'Hero / Featured Block',
            'horizontal_list' => 'Horizontal Scroll',
            'grid' => 'Grid',
            'vertical_list' => 'Vertical List',
            'featured_grid' => 'Featured Grid',
            'quick_actions' => 'Quick Actions / Buttons',
            'quote' => 'Quote Cards / Quote Feed',
            'short_video_feed' => 'Short Video Row / Reel Entry',
            'article_list' => 'Article / Content List',
            'book_carousel' => 'Book Carousel',
            'watch_row' => 'Watch / Live Stream Row',
            'text_block' => 'Text / Announcement Block',
            'mixed_content' => 'Mixed Content Block',
            'ad_slot' => 'Ad Slot',
        ];
    }

    private function layoutVariants(): array
    {
        return [
            'default' => 'Default',
            'carousel' => 'Carousel',
            'horizontal_cards' => 'Horizontal Cards',
            'quote_carousel' => 'Quote Carousel',
            'quote_grid' => 'Quote Grid',
            'short_video_feed' => 'Short Video Feed',
            'reel_entry' => 'Reel Entry',
            'article_list' => 'Article List',
            'image_grid' => 'Image Grid',
            'icon_grid' => 'Icon Grid',
            'compact_list' => 'Compact List',
            'media_list' => 'Media List',
            'hero_full' => 'Hero Full Width',
            'hero_compact' => 'Hero Compact',
            'mixed_cards' => 'Mixed Cards',
        ];
    }

    private function cardStyles(): array
    {
        return [
            'image' => 'Image Card',
            'icon' => 'Icon Card',
            'text' => 'Text Card',
            'quote' => 'Quote Design Card',
            'video' => 'Video Card',
            'book' => 'Book Cover Card',
            'live' => 'Live / Watch Card',
            'minimal' => 'Minimal',
        ];
    }

    private function scrollModes(): array
    {
        return [
            'none' => 'No Scroll',
            'horizontal' => 'Horizontal Scroll',
            'vertical' => 'Vertical List',
            'paged' => 'Paged / Carousel',
        ];
    }

    private function sizePresets(): array
    {
        return [
            'compact' => 'Compact',
            'normal' => 'Normal',
            'large' => 'Large',
            'featured' => 'Featured',
            'poster' => 'Poster / 4:5',
            'wide' => 'Wide / 16:9',
        ];
    }

    private function placementTypes(): array
    {
        return [
            'main_tab' => 'Main tab section',
            'normal' => 'Normal page section',
            'before_featured' => 'Before featured content',
            'after_featured' => 'After featured content',
            'before_list' => 'Before content list',
            'inside_content_list' => 'Insert inside article/content list',
            'after_item_number' => 'After item number',
            'inline_between_items' => 'Inline between items',
            'after_section' => 'After another section',
            'end_of_page' => 'End of page',
            'custom_page' => 'Custom page / route',
        ];
    }

    private function sourceOptions(): array
    {
        return [
            'manual_items' => 'Manual items inside this section',
            'content_channel' => 'Content channel — articles/posts by bucket',
            'quote_channel' => 'Quote channel — dynamic quote bucket',
            'short_video_all' => 'Short Videos — all published videos',
            'short_video_category' => 'Short Videos — selected category',
            'short_video_channel' => 'Short Videos — selected Short Channel',
            'book_channel' => 'Books / Library items',
            'watch_link' => 'Watch Builder / live stream links',
            'external_link' => 'External links / web pages',
            'mixed_content' => 'Mixed content block',
            'ad_slot' => 'Advertisement slot',
            'daily_scripture' => 'Daily Scripture content',
        ];
    }

    private function contentBuckets(int $appId): array
    {
        $defaults = [
            'articles' => 'Articles',
            'inside_dunamis' => 'Inside Dunamis',
            'message_highlights' => 'Message Highlights',
            'wordification' => 'Wordification',
            'motivation' => 'Motivation',
            'daily_quotes' => 'Daily Quotes',
            'sod_quotes' => 'SOD Quotes',
            'quote_dr_eneches_quotes' => 'Dr Enenche Quotes',
            'daily_scriptures' => 'Daily Scriptures',
            'short_videos' => 'Short Videos',
        ];

        try {
            DB::table('content_posts')
                ->where('app_id', $appId)
                ->whereNotNull('bucket')
                ->select('bucket')
                ->distinct()
                ->orderBy('bucket')
                ->pluck('bucket')
                ->each(function ($bucket) use (&$defaults): void {
                    $key = trim((string) $bucket);
                    if ($key !== '') {
                        $defaults[$key] = $defaults[$key] ?? str($key)->replace(['_', '-'], ' ')->title()->toString();
                    }
                });
        } catch (\Throwable $e) {
            // Keep defaults if the table is unavailable during maintenance.
        }

        return $defaults;
    }

    private function shortVideoCategories(int $appId): array
    {
        $defaults = ['motivational' => 'Motivational', 'teachings' => 'Teachings', 'music' => 'Music', 'worship' => 'Worship', 'leadership' => 'Leadership', 'testimonies' => 'Testimonies', 'announcements' => 'Announcements', 'events' => 'Events', 'youth' => 'Youth', 'prayer' => 'Prayer'];
        $app = $appId > 0 ? App::query()->find($appId) : null;
        $branding = is_array($app?->branding_json) ? $app->branding_json : [];
        foreach ($this->safeArray($branding['short_video_categories'] ?? []) as $key => $row) {
            if (! is_array($row)) continue;
            $categoryKey = ShortVideoPayload::normalizeCategoryKey($row['key'] ?? $key);
            $label = trim((string) ($row['label'] ?? $row['name'] ?? ''));
            $defaults[$categoryKey] = $label !== '' ? $label : ShortVideoPayload::normalizeCategoryLabel(null, $categoryKey);
        }
        asort($defaults);
        return $defaults;
    }

    private function shortVideoChannels(int $appId): array
    {
        $defaults = ['home-shorts' => 'Home Shorts', 'inspire-shorts' => 'Inspire Shorts', 'motivational-shorts' => 'Motivational Shorts', 'prayer-shorts' => 'Prayer Shorts'];
        $app = $appId > 0 ? App::query()->find($appId) : null;
        $branding = is_array($app?->branding_json) ? $app->branding_json : [];
        foreach ($this->safeArray($branding['short_video_channels'] ?? []) as $key => $row) {
            if (! is_array($row)) continue;
            $channelKey = ShortVideoPayload::normalizeCategoryKey($row['key'] ?? $key);
            $label = trim((string) ($row['label'] ?? $row['name'] ?? ''));
            $defaults[$channelKey] = $label !== '' ? $label : ShortVideoPayload::normalizeCategoryLabel(null, $channelKey);
        }
        asort($defaults);
        return $defaults;
    }

    private function placementMetaFromValidated(array $validated, array $existingMeta = []): array
    {
        $sourceType = trim((string) ($validated['source_type'] ?? ($existingMeta['source_type'] ?? 'manual_items'))) ?: 'manual_items';
        $bucket = trim((string) ($validated['source_bucket'] ?? ($existingMeta['bucket'] ?? '')));
        $category = trim((string) ($validated['source_category'] ?? ($existingMeta['source_category'] ?? $existingMeta['category'] ?? '')));
        $channel = trim((string) ($validated['source_channel'] ?? ($existingMeta['source_channel'] ?? $existingMeta['channel_key'] ?? '')));
        $targetRoute = trim((string) ($validated['target_route'] ?? ($existingMeta['target_route'] ?? '')));
        $maxItems = (int) ($validated['max_items'] ?? ($existingMeta['max_items'] ?? 0));
        $layout = trim((string) ($validated['layout_variant'] ?? ($existingMeta['layout_variant'] ?? 'default'))) ?: 'default';

        $meta = array_merge($existingMeta, [
            'source_type' => $sourceType,
            'content_source' => $sourceType,
            'placement_type' => trim((string) ($validated['placement_type'] ?? ($existingMeta['placement_type'] ?? 'main_tab'))) ?: 'main_tab',
            'insert_target_bucket' => trim((string) ($validated['insert_target_bucket'] ?? ($existingMeta['insert_target_bucket'] ?? ''))) ?: null,
            'insert_after_items' => max(0, min(50, (int) ($validated['insert_after_items'] ?? ($existingMeta['insert_after_items'] ?? 0)))),
            'parent_section_key' => trim((string) ($validated['parent_section_key'] ?? ($existingMeta['parent_section_key'] ?? ''))) ?: null,
            'parent_key' => trim((string) ($validated['parent_section_key'] ?? ($existingMeta['parent_section_key'] ?? $existingMeta['parent_key'] ?? ''))) ?: null,
            'insert_anchor_section_key' => trim((string) ($validated['insert_anchor_section_key'] ?? ($existingMeta['insert_anchor_section_key'] ?? ''))) ?: null,
            'builder_role' => trim((string) ($validated['builder_role'] ?? ($existingMeta['builder_role'] ?? 'section'))) ?: 'section',
            'layout_variant' => $layout,
            'columns' => max(1, min(4, (int) ($validated['columns'] ?? ($existingMeta['columns'] ?? 1)))),
            'card_style' => trim((string) ($validated['card_style'] ?? ($existingMeta['card_style'] ?? 'image'))) ?: 'image',
            'scroll_behavior' => trim((string) ($validated['scroll_behavior'] ?? ($existingMeta['scroll_behavior'] ?? 'none'))) ?: 'none',
            'size_preset' => trim((string) ($validated['size_preset'] ?? ($existingMeta['size_preset'] ?? 'normal'))) ?: 'normal',
            'target_route' => $targetRoute !== '' ? $targetRoute : null,
            'max_items' => $maxItems > 0 ? min($maxItems, 60) : 12,
        ]);

        if (str_starts_with($sourceType, 'short_video')) {
            $meta['content_engine'] = 'short_video_feed';
            $meta['bucket'] = ShortVideoPayload::normalizeBucket($bucket !== '' ? $bucket : ShortVideoPayload::DEFAULT_BUCKET);
            $meta['source_bucket'] = $meta['bucket'];
            $meta['layout_variant'] = $layout !== 'default' ? $layout : 'short_video_feed';
            $meta['card_style'] = 'video';
            $meta['scroll_behavior'] = $meta['scroll_behavior'] !== 'none' ? $meta['scroll_behavior'] : 'horizontal';
            $meta['target_route'] = $targetRoute !== '' ? $targetRoute : '/short-videos';

            if ($sourceType === 'short_video_category' && $category !== '') {
                $meta['source_category'] = ShortVideoPayload::normalizeCategoryKey($category);
                $meta['category'] = $meta['source_category'];
                $meta['category_key'] = $meta['source_category'];
            } else {
                unset($meta['source_category'], $meta['category'], $meta['category_key']);
            }

            if ($sourceType === 'short_video_channel' && $channel !== '') {
                $meta['source_channel'] = ShortVideoPayload::normalizeCategoryKey($channel);
                $meta['channel_key'] = $meta['source_channel'];
            } else {
                unset($meta['source_channel'], $meta['channel_key']);
            }
        } elseif ($sourceType === 'quote_channel') {
            $meta['content_engine'] = 'quote_feed';
            $meta['bucket'] = $bucket !== '' ? $bucket : 'daily_quotes';
            $meta['source_bucket'] = $meta['bucket'];
            $meta['layout_variant'] = $layout !== 'default' ? $layout : 'quote_carousel';
            $meta['card_style'] = 'quote';
            $meta['scroll_behavior'] = $meta['scroll_behavior'] !== 'none' ? $meta['scroll_behavior'] : 'horizontal';
            $meta['target_route'] = $targetRoute !== '' ? $targetRoute : '/sod/quotes';
        } elseif ($sourceType === 'content_channel') {
            $meta['content_engine'] = 'content_feed';
            $meta['bucket'] = $bucket !== '' ? $bucket : 'articles';
            $meta['source_bucket'] = $meta['bucket'];
            $meta['layout_variant'] = $layout !== 'default' ? $layout : 'article_list';
            $meta['card_style'] = $meta['card_style'] !== 'image' ? $meta['card_style'] : 'image';
            $meta['target_route'] = $targetRoute !== '' ? $targetRoute : '/articles';
        } elseif ($sourceType === 'book_channel') {
            $meta['content_engine'] = 'book_feed';
            $meta['bucket'] = $bucket !== '' ? $bucket : 'books';
            $meta['source_bucket'] = $meta['bucket'];
            $meta['layout_variant'] = $layout !== 'default' ? $layout : 'horizontal_cards';
            $meta['card_style'] = 'book';
            $meta['target_route'] = $targetRoute !== '' ? $targetRoute : '/tools/books';
        } elseif ($sourceType === 'watch_link') {
            $meta['content_engine'] = 'watch_feed';
            $meta['layout_variant'] = $layout !== 'default' ? $layout : 'horizontal_cards';
            $meta['card_style'] = 'live';
            $meta['target_route'] = $targetRoute !== '' ? $targetRoute : '/watch';
        } elseif ($sourceType === 'daily_scripture') {
            $meta['content_engine'] = 'scripture_feed';
            $meta['bucket'] = $bucket !== '' ? $bucket : 'daily_scriptures';
            $meta['source_bucket'] = $meta['bucket'];
            $meta['layout_variant'] = $layout !== 'default' ? $layout : 'quote_carousel';
            $meta['card_style'] = 'scripture';
        } elseif ($sourceType === 'ad_slot') {
            $meta['content_engine'] = 'ad_slot';
            $meta['layout_variant'] = 'ad_slot';
            $meta['card_style'] = 'ad';
        }

        $meta['builder_schema'] = $meta['builder_schema'] ?? 'destination_builder_v2_2';
        $meta['builder_updated_at'] = now()->toDateTimeString();

        return array_filter($meta, fn ($value) => $value !== null && $value !== '');
    }

    private function safeArray(mixed $value): array
    {
        if (is_array($value)) return $value;
        if (is_object($value)) return json_decode(json_encode($value), true) ?: [];
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function cleanTab($value): string
    {
        $value = strtolower(trim((string) $value));
        return array_key_exists($value, $this->tabs) ? $value : 'home';
    }

    private function sanitizeReturnTo(?string $value): string
    {
        return match (trim((string) $value)) {
            'destination' => 'destination',
            default => 'dashboard',
        };
    }

    private function returnUrl(string $returnTo, string $tab): string
    {
        return match ($returnTo) {
            'destination' => '/admin/destination-builder?tab=' . urlencode($tab),
            default => '/admin/beginner-dashboard?tab=' . urlencode($tab),
        };
    }
}

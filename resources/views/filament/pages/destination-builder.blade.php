<x-filament::page>
    @php
        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);
        $activeApp = $activeAppId > 0 ? \App\Models\App::query()->find($activeAppId) : null;

        $defaultTabs = [
            'home' => 'Home',
            'watch' => 'Watch',
            'inspire' => 'Inspire',
            'explore' => 'Explore',
            'more' => 'More',
        ];

        $activeTab = strtolower((string) request('tab', 'home'));
        if (! array_key_exists($activeTab, $defaultTabs)) {
            $activeTab = 'home';
        }

        $decodeArray = function ($value) {
            if (is_array($value)) return $value;
            if (is_string($value) && trim($value) !== '') {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            }
            return [];
        };

        $rawSections = collect();
        if ($activeAppId > 0) {
            $rawSections = \App\Models\AppSection::query()
                ->with(['items' => function ($query) {
                    $query->orderBy('sort_order')->orderBy('id');
                }])
                ->where('app_id', $activeAppId)
                ->orderBy('tab_key')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();
        }

        $allItems = $rawSections->flatMap(fn ($section) => $section->items ?? collect());
        $enabledSections = $rawSections->where('is_enabled', true)->values();
        $enabledItems = $allItems->where('is_enabled', true)->values();

        $sectionCount = $rawSections->count();
        $enabledSectionCount = $enabledSections->count();
        $itemCount = $allItems->count();
        $enabledItemCount = $enabledItems->count();

        $tabCounts = [];
        $tabItemCounts = [];
        foreach ($defaultTabs as $tabKey => $label) {
            $tabSections = $rawSections->where('tab_key', $tabKey);
            $tabCounts[$tabKey] = $tabSections->count();
            $tabItemCounts[$tabKey] = $tabSections->flatMap(fn ($section) => $section->items ?? collect())->count();
        }

        $payloadFor = fn ($item) => $decodeArray($item->payload_json ?? null);
        $metaFor = fn ($section) => $decodeArray($section->meta_json ?? null);

        $imageFor = function ($item) use ($payloadFor) {
            $payload = $payloadFor($item);
            return $item->image_url
                ?? $payload['image_url']
                ?? $payload['thumbnail_url']
                ?? $payload['image']
                ?? $payload['cover_image_url']
                ?? null;
        };

        $layoutFor = function ($section) use ($metaFor) {
            $meta = $metaFor($section);
            $raw = strtolower(str_replace('-', '_', (string) (
                $meta['layout_type']
                ?? $meta['layout_variant']
                ?? $meta['layout']
                ?? $section->template
                ?? 'vertical_list'
            )));

            return match ($raw) {
                'hero', 'hero_card', 'featured_large' => 'hero',
                'carousel', 'banner_slider', 'slider' => 'carousel',
                'horizontal', 'horizontal_scroll', 'horizontal_list', 'horizontal_cards' => 'horizontal_scroll',
                'grid', 'content_grid', 'image_grid' => 'grid',
                'icon_grid', 'icons', 'menu_grid' => 'icon_grid',
                'tool', 'tools', 'tool_grid', 'tool_launcher' => 'tool_grid',
                'quote', 'quote_card', 'daily_quote' => 'quote',
                'scripture', 'scripture_card', 'daily_scripture' => 'scripture',
                'video', 'videos', 'video_feed', 'banners', 'default' => 'video_feed',
                'short', 'shorts', 'short_feed', 'short_video_feed' => 'short_video_feed',
                'ad', 'ads', 'ad_block', 'advert' => 'ad_block',
                'compact', 'compact_list', 'compact_cards' => 'compact',
                default => 'vertical_list',
            };
        };

        $columnsFor = function ($section) use ($metaFor, $layoutFor) {
            $meta = $metaFor($section);
            $fallback = in_array($layoutFor($section), ['icon_grid', 'tool_grid'], true) ? 2 : 1;
            return max(1, min(6, (int) ($meta['columns'] ?? $meta['column_count'] ?? $fallback)));
        };

        $cardHeightFor = function ($section, int $fallback = 170) use ($metaFor) {
            $meta = $metaFor($section);
            $height = (int) ($meta['card_height'] ?? $meta['height'] ?? $meta['item_height'] ?? $meta['mobile_card_height'] ?? $fallback);
            return max(90, min(420, $height));
        };

        $cardWidthFor = function ($section, int $fallback = 310) use ($metaFor) {
            $meta = $metaFor($section);
            $width = (int) ($meta['card_width'] ?? $meta['width'] ?? $meta['item_width'] ?? $meta['mobile_card_width'] ?? $fallback);
            return max(140, min(520, $width));
        };

        $previewClassFor = function ($section) use ($layoutFor, $columnsFor) {
            return implode(' ', [
                'dxm-mobile-items',
                'dxm-mobile-items--' . $layoutFor($section),
                'dxm-mobile-items--cols-' . $columnsFor($section),
            ]);
        };

        $actionFor = function ($item) use ($payloadFor) {
            $payload = $payloadFor($item);
            $rawAction = $payload['action'] ?? [];
            if (! is_array($rawAction)) $rawAction = [];

            $type = $rawAction['type'] ?? null;
            $engine = $rawAction['engine'] ?? $payload['engine'] ?? null;
            $url = $rawAction['url'] ?? $rawAction['youtube'] ?? $payload['url'] ?? $item->url ?? null;
            $route = $rawAction['route'] ?? $item->route ?? null;
            $routeKey = $rawAction['route_key'] ?? null;

            if (! $type && $engine) $type = 'engine';
            if (! $type && $url) $type = 'external_url';
            if (! $type && ($route || $routeKey)) $type = 'route';
            if (! $type) $type = 'detail';

            return [
                'type' => strtolower((string) $type),
                'engine' => $engine,
                'url' => $url,
                'route' => $route,
                'route_key' => $routeKey,
            ];
        };

        $badgeFor = function ($item) use ($payloadFor) {
            $payload = $payloadFor($item);
            $text = strtolower($item->title . ' ' . $item->type . ' ' . $item->route . ' ' . $item->url . ' ' . ($payload['engine'] ?? ''));
            if (str_contains($text, 'live')) return 'LIVE';
            if (str_contains($text, 'video')) return 'VIDEO';
            if (str_contains($text, 'short')) return 'SHORT';
            if (str_contains($text, 'article') || str_contains($text, 'read')) return 'READ';
            if (str_contains($text, 'quote')) return 'QUOTE';
            if (str_contains($text, 'scripture')) return 'WORD';
            if (str_contains($text, 'bible')) return 'BIBLE';
            if (str_contains($text, 'note')) return 'NOTE';
            if (str_contains($text, 'game')) return 'GAME';
            if (str_contains($text, 'web')) return 'WEB';
            return 'OPEN';
        };

        $iconFor = function ($item) use ($payloadFor) {
            $payload = $payloadFor($item);
            $text = strtolower($item->title . ' ' . $item->type . ' ' . $item->route . ' ' . $item->url . ' ' . ($payload['engine'] ?? '') . ' ' . $item->icon);
            if (str_contains($text, 'quote')) return '❝';
            if (str_contains($text, 'note')) return '▣';
            if (str_contains($text, 'bible') || str_contains($text, 'scripture')) return '▤';
            if (str_contains($text, 'game')) return '🎮';
            if (str_contains($text, 'watch') || str_contains($text, 'live')) return '▸';
            if (str_contains($text, 'video') || str_contains($text, 'short')) return '▶';
            if (str_contains($text, 'article') || str_contains($text, 'read')) return '▦';
            if (str_contains($text, 'web') || str_contains($text, 'link')) return '◎';
            if (str_contains($text, 'prayer')) return '✦';
            return '▦';
        };

        $displayTextFor = function ($section, $item) use ($payloadFor) {
            $payload = $payloadFor($item);
            $kind = $payload['daily_kind'] ?? $payload['home_kind'] ?? '';
            if ($section->key === 'home_daily_scripture' || $kind === 'daily_scripture') {
                return [
                    'main' => $payload['scripture_text'] ?? $payload['verse'] ?? $item->title,
                    'sub' => $payload['scripture_reference'] ?? $payload['ref'] ?? $item->subtitle,
                    'note' => $payload['scripture_note'] ?? $payload['note'] ?? '',
                    'kind' => 'daily_scripture',
                ];
            }
            if ($section->key === 'home_daily_quote' || $kind === 'daily_quote') {
                return [
                    'main' => $payload['quote'] ?? $payload['quote_text'] ?? $item->title,
                    'sub' => $payload['quote_source'] ?? $payload['source'] ?? $item->subtitle,
                    'note' => '',
                    'kind' => 'daily_quote',
                ];
            }
            return [
                'main' => $item->title,
                'sub' => $item->subtitle,
                'note' => '',
                'kind' => '',
            ];
        };

        $dailyStyleFor = function ($item) use ($payloadFor, $imageFor) {
            $payload = $payloadFor($item);
            $image = $imageFor($item);
            $backgroundMode = strtolower((string) ($payload['background_mode'] ?? 'gradient'));
            $bg1 = $payload['bg_color'] ?? '#160042';
            $bg2 = $payload['bg_color_2'] ?? '#e2388a';
            $textColor = $payload['text_color'] ?? '#ffffff';
            $accent = $payload['accent_color'] ?? '#38bdf8';
            $fontSize = (int) ($payload['title_size'] ?? $payload['font_size'] ?? 24);
            $fontFamily = strtolower((string) ($payload['font_family'] ?? 'system'));
            $fontWeight = (string) ($payload['font_weight'] ?? '800');
            $align = $payload['text_align'] ?? 'center';
            $lineHeight = $payload['line_height'] ?? '1.25';
            $padding = (int) ($payload['card_padding'] ?? 34);
            $contentWidth = (int) ($payload['content_width'] ?? 86);
            $overlay = max(0, min(100, (int) ($payload['overlay_strength'] ?? 58))) / 100;
            $fontStack = $fontFamily === 'impact'
                ? 'Impact, Haettenschweiler, "Arial Narrow Bold", sans-serif'
                : 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';

            $css = 'color:' . e($textColor) . ';';
            $css .= 'border-color:' . e($accent) . ';';
            $css .= 'font-family:' . e($fontStack) . ';';
            $css .= 'font-size:' . e($fontSize) . 'px;';
            $css .= 'font-weight:' . e($fontWeight) . ';';
            $css .= 'text-align:' . e($align) . ';';
            $css .= 'line-height:' . e($lineHeight) . ';';
            $css .= '--dxm-card-padding:' . e($padding) . 'px;';
            $css .= '--dxm-content-width:' . e($contentWidth) . '%;';
            if ($backgroundMode === 'image' && $image) {
                $css .= 'background-image:linear-gradient(135deg,rgba(0,0,0,' . e($overlay) . '),rgba(0,0,0,' . e($overlay) . ')),url(' . e($image) . ');background-size:cover;background-position:center;';
            } else {
                $css .= 'background:linear-gradient(135deg,' . e($bg1) . ',' . e($bg2) . ');';
            }
            return $css;
        };

        $isDailyCard = function ($section, $item) use ($payloadFor) {
            $payload = $payloadFor($item);
            $kind = $payload['daily_kind'] ?? $payload['home_kind'] ?? '';
            return in_array($section->key, ['home_daily_scripture', 'home_daily_quote'], true)
                || in_array($kind, ['daily_scripture', 'daily_quote'], true);
        };

        $isAdSection = fn ($section) => $layoutFor($section) === 'ad_block';

        $buildAdSection = function (string $tab, int $index) {
            return (object) [
                'id' => 'virtual_' . $tab . '_ad_' . $index,
                'tab_key' => $tab,
                'route_key' => null,
                'key' => $tab . '_dynamic_ad_' . $index,
                'title' => '',
                'subtitle' => '',
                'template' => 'ad_block',
                'sort_order' => 9000 + $index,
                'is_enabled' => true,
                'meta_json' => ['layout_variant' => 'ad_block', 'placement' => $tab . '_feed'],
                'items' => collect(),
                'is_virtual' => true,
            ];
        };

        $injectAds = function ($sections, string $tab, int $interval) use ($buildAdSection) {
            $output = collect();
            foreach ($sections->values() as $index => $section) {
                $output->push($section);
                if ($index !== 0 && (($index + 1) % $interval === 0)) {
                    $output->push($buildAdSection($tab, $index));
                }
            }
            return $output;
        };

        $toolItems = $rawSections->firstWhere('key', 'explore_tools')?->items?->where('is_enabled', true)?->values() ?? collect();
        $buildToolSection = function (string $tab, int $sortOrder, string $subtitle) use ($toolItems) {
            return (object) [
                'id' => 'virtual_' . $tab . '_quick_tools',
                'tab_key' => $tab,
                'route_key' => null,
                'key' => $tab . '_quick_tools',
                'title' => 'Quick Tools',
                'subtitle' => $subtitle,
                'template' => 'grid',
                'sort_order' => $sortOrder,
                'is_enabled' => true,
                'meta_json' => [
                    'columns' => 2,
                    'layout_variant' => 'grid',
                    'card_style' => 'image',
                    'size_preset' => 'normal',
                ],
                'items' => $toolItems,
                'is_virtual' => true,
            ];
        };

        $buildPreviewSections = function (string $tabKey) use ($enabledSections, $rawSections, $toolItems, $buildToolSection, $injectAds, $isAdSection, $layoutFor) {
            $tabSections = $enabledSections->where('tab_key', $tabKey)->values();
            if ($tabKey === 'home') {
                $homeSections = $tabSections->filter(fn ($section) => $isAdSection($section) || $section->items->where('is_enabled', true)->isNotEmpty())->values();
                if ($toolItems->isNotEmpty()) {
                    $anchor = $rawSections->firstWhere('key', 'home_quick_tools');
                    $homeSections->push($buildToolSection('home', $anchor ? (int) $anchor->sort_order : 60, 'Quote Creator, Notes, Bible and Games.'));
                    $homeSections = $homeSections->sortBy('sort_order')->values();
                }
                return $homeSections;
            }
            if ($tabKey === 'watch') {
                return $injectAds($tabSections->filter(function ($section) use ($isAdSection, $layoutFor) {
                    if ($isAdSection($section)) return true;
                    if ($section->items->where('is_enabled', true)->isEmpty()) return false;
                    $lower = strtolower($section->title . ' ' . $section->subtitle . ' ' . $section->key . ' ' . $section->template);
                    return str_contains($lower, 'watch') || str_contains($lower, 'live') || str_contains($lower, 'stream') || str_contains($lower, 'video') || in_array($layoutFor($section), ['video_feed', 'short_video_feed'], true);
                })->values(), 'watch', 3);
            }
            if ($tabKey === 'inspire') {
                return $injectAds($tabSections->filter(function ($section) use ($isAdSection) {
                    if ($isAdSection($section)) return true;
                    if ($section->items->where('is_enabled', true)->isEmpty()) return false;
                    $lower = strtolower($section->title . ' ' . $section->subtitle . ' ' . $section->key . ' ' . $section->template);
                    return str_contains($lower, 'inspire') || str_contains($lower, 'article') || str_contains($lower, 'inside') || str_contains($lower, 'motivation') || str_contains($lower, 'wordification') || str_contains($lower, 'sod') || str_contains($lower, 'highlight') || str_contains($lower, 'short');
                })->values(), 'inspire', 4);
            }
            if ($tabKey === 'explore') {
                return $injectAds($tabSections->filter(function ($section) use ($isAdSection) {
                    if ($isAdSection($section)) return true;
                    if ($section->items->where('is_enabled', true)->isEmpty()) return false;
                    $lower = strtolower($section->title . ' ' . $section->subtitle . ' ' . $section->key . ' ' . $section->template);
                    return str_contains($lower, 'tool') || str_contains($lower, 'explore') || str_contains($lower, 'game') || str_contains($lower, 'quiz') || str_contains($lower, 'book') || str_contains($lower, 'bible') || str_contains($lower, 'note');
                })->values(), 'explore', 5);
            }
            return $tabSections->filter(fn ($section) => $isAdSection($section) || $section->items->where('is_enabled', true)->isNotEmpty())->values();
        };

        $previewSectionsByTab = collect();
        foreach (array_keys($defaultTabs) as $tabKey) {
            $previewSectionsByTab[$tabKey] = $buildPreviewSections($tabKey);
        }

        $sections = $rawSections->where('tab_key', $activeTab)->values();

        $placementTypeLabels = [
            'main_tab' => 'Main tab section',
            'normal' => 'Normal page section',
            'before_featured' => 'Before featured content',
            'after_featured' => 'After featured content',
            'before_list' => 'Before content list',
            'inside_content_list' => 'Inside content list',
            'after_item_number' => 'After item number',
            'inline_between_items' => 'Inline between items',
            'after_section' => 'After another section',
            'end_of_page' => 'End of page',
            'custom_page' => 'Custom page / route',
        ];

        $sourceTypeLabels = [
            'manual_items' => 'Manual items',
            'content_channel' => 'Content channel',
            'quote_channel' => 'Quote channel',
            'short_video_all' => 'Short Videos — all',
            'short_video_category' => 'Short Videos — category',
            'short_video_channel' => 'Short Videos — channel',
            'book_channel' => 'Book channel',
            'watch_link' => 'Watch / live links',
            'mixed_content' => 'Mixed content',
            'ad_slot' => 'Ad slot',
            'daily_scripture' => 'Daily Scripture',
        ];

        $layoutVariantLabels = [
            'default' => 'Default',
            'carousel' => 'Carousel',
            'horizontal_cards' => 'Horizontal cards',
            'quote_carousel' => 'Quote carousel',
            'quote_grid' => 'Quote grid',
            'short_video_feed' => 'Short video feed',
            'article_list' => 'Article list',
            'image_grid' => 'Image grid',
            'icon_grid' => 'Icon grid',
            'compact_list' => 'Compact list',
            'hero_full' => 'Hero full width',
            'mixed_cards' => 'Mixed cards',
            'ad_slot' => 'Ad slot',
        ];

        $cardStyleLabels = [
            'image' => 'Image card',
            'icon' => 'Icon card',
            'text' => 'Text card',
            'quote' => 'Quote card',
            'video' => 'Video card',
            'book' => 'Book card',
            'live' => 'Live card',
            'ad' => 'Ad block',
            'minimal' => 'Minimal',
        ];

        $sizePresetLabels = [
            'compact' => 'Compact',
            'normal' => 'Normal',
            'large' => 'Large',
            'featured' => 'Featured',
            'poster' => 'Poster / 4:5',
            'wide' => 'Wide / 16:9',
        ];

        $sourceBucketOptions = collect([
            'articles' => 'Articles',
            'inside_dunamis' => 'Inside Dunamis',
            'message_highlights' => 'Message Highlights',
            'wordification' => 'Wordification',
            'motivation' => 'Motivation',
            'daily_quotes' => 'Daily Quotes',
            'daily_scriptures' => 'Daily Scriptures',
            'sod_quotes' => 'SOD Quotes',
            'quote_dr_eneches_quotes' => 'Dr. Eneche Quotes',
            'motivational_quotes' => 'Motivation Quotes',
            'short_videos' => 'Short Videos',
            'books' => 'Books',
        ]);

        foreach ($rawSections as $bucketSection) {
            $bucketMeta = $metaFor($bucketSection);
            foreach (['bucket', 'source_bucket', 'insert_target_bucket'] as $bucketKey) {
                $bucketValue = trim((string) ($bucketMeta[$bucketKey] ?? ''));
                if ($bucketValue !== '') {
                    $sourceBucketOptions[$bucketValue] = str($bucketValue)->replace(['_', '-'], ' ')->title()->toString();
                }
            }
        }

        $sourceBucketOptions = $sourceBucketOptions->sortKeys()->all();

        $parentSectionOptions = $sections
            ->mapWithKeys(fn ($parentSection) => [(string) $parentSection->key => (string) ($parentSection->title ?: $parentSection->key)])
            ->all();

        $editUrlFor = function ($section, $item) use ($activeTab) {
            if (($section->is_virtual ?? false) === true) return '#';
            if ($section->key === 'home_daily_scripture') return route('admin.beginner.daily.edit', ['kind' => 'scripture']);
            if ($section->key === 'home_daily_quote') return route('admin.beginner.daily.edit', ['kind' => 'quote']);
            return route('admin.beginner.items.edit', $item) . '?return=destination&tab=' . urlencode($activeTab);
        };
    @endphp

    @include('filament.pages.beginner-dashboard.partials.preview-styles')

    <style>
        .dxm-destination-shell{display:grid;gap:16px}.dxm-destination-hero{border:1px solid rgba(34,211,238,.18);border-radius:24px;background:radial-gradient(circle at 6% 0%,rgba(34,211,238,.16),transparent 35%),linear-gradient(135deg,rgba(2,6,23,.95),rgba(15,23,42,.84));padding:18px}.dxm-destination-hero-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;align-items:center}.dxm-kicker{display:inline-flex;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:#dffbff;border-radius:999px;padding:6px 10px;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.08em}.dxm-destination-title{margin:10px 0 0;color:#fff;font-size:clamp(25px,4vw,38px);font-weight:950;letter-spacing:-.04em;line-height:1.02}.dxm-destination-sub{margin:8px 0 0;color:rgba(255,255,255,.66);font-size:13px;line-height:1.5;max-width:760px}.dxm-destination-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.dxm-destination-stats{display:grid;grid-template-columns:repeat(2,minmax(110px,1fr));gap:8px;min-width:250px}.dxm-destination-stat{border:1px solid rgba(255,255,255,.09);border-radius:16px;background:rgba(2,6,23,.4);padding:12px}.dxm-destination-stat strong{display:block;color:#fff;font-size:20px;line-height:1}.dxm-destination-stat span{display:block;color:rgba(255,255,255,.55);font-size:10px;font-weight:900;margin-top:5px}.dxm-destination-tabs{position:sticky;top:78px;z-index:20;border:1px solid rgba(34,211,238,.14);border-radius:20px;background:rgba(15,23,42,.92);backdrop-filter:blur(14px);padding:10px;overflow-x:auto}.dxm-destination-tabs-inner{display:flex;gap:8px;min-width:max-content}.dxm-destination-tab{display:flex;flex-direction:column;gap:4px;min-width:140px;border:1px solid rgba(255,255,255,.09);border-radius:15px;padding:10px 12px;background:rgba(255,255,255,.04);color:rgba(255,255,255,.78);text-decoration:none}.dxm-destination-tab.is-active{border-color:rgba(34,211,238,.55);background:rgba(34,211,238,.13);color:#fff}.dxm-destination-tab strong{font-size:13px;font-weight:950}.dxm-destination-tab span{font-size:10px;color:rgba(255,255,255,.58);font-weight:850}.dxm-builder-grid{display:grid;grid-template-columns:minmax(0,1fr) 370px;gap:16px;align-items:start}.dxm-builder-main{display:grid;gap:14px;min-width:0}.dxm-builder-preview{position:sticky;top:154px}.dxm-section-panel{border:1px solid rgba(255,255,255,.09);border-radius:22px;background:rgba(2,6,23,.34);overflow:hidden}.dxm-section-panel-head{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:start;padding:16px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-section-panel-title{margin:0;color:#fff;font-size:18px;font-weight:950}.dxm-section-panel-sub{margin-top:5px;color:rgba(255,255,255,.58);font-size:12px;line-height:1.45}.dxm-section-panel-actions{display:flex;gap:7px;flex-wrap:wrap;justify-content:flex-end}.dxm-section-body{display:grid;gap:12px;padding:14px}.dxm-item-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}.dxm-builder-item{border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(15,23,42,.58);overflow:hidden;display:flex;flex-direction:column;min-height:250px}.dxm-builder-img{height:96px;background:rgba(255,255,255,.055);overflow:hidden}.dxm-builder-img img{width:100%;height:100%;object-fit:cover}.dxm-builder-item-body{padding:12px;display:flex;flex-direction:column;gap:9px;flex:1}.dxm-builder-item-title{color:#fff;font-size:13px;font-weight:950;line-height:1.25}.dxm-builder-item-sub{color:rgba(255,255,255,.55);font-size:11px;line-height:1.35}.dxm-meta-row{display:flex;gap:6px;flex-wrap:wrap}.dxm-pill{display:inline-flex;align-items:center;min-height:24px;padding:4px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.055);color:rgba(255,255,255,.68);font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.03em}.dxm-status-on{border-color:rgba(34,197,94,.25);background:rgba(34,197,94,.11);color:#bbf7d0}.dxm-status-off{border-color:rgba(248,113,113,.25);background:rgba(248,113,113,.10);color:#fecaca}.dxm-btn,.dxm-danger-btn{display:inline-flex;align-items:center;justify-content:center;min-height:34px;border-radius:11px;padding:7px 11px;font-size:11px;font-weight:950;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.055);color:#fff;text-decoration:none}.dxm-btn.primary{border-color:rgba(34,211,238,.34);background:rgba(34,211,238,.12);color:#e0faff}.dxm-danger-btn{border-color:rgba(248,113,113,.35);background:rgba(248,113,113,.10);color:#fecaca}.dxm-empty-box{border:1px dashed rgba(255,255,255,.16);border-radius:18px;padding:16px;color:rgba(255,255,255,.62);background:rgba(255,255,255,.025)}.dxm-builder-actions-bottom{margin-top:auto;display:flex;gap:6px;flex-wrap:wrap}.dxm-preview-card-shell{border:1px solid rgba(34,211,238,.2);border-radius:24px;background:rgba(2,6,23,.58);padding:12px}.dxm-preview-card-head{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px}.dxm-preview-card-title{color:#fff;font-size:13px;font-weight:950}.dxm-preview-card-note{color:rgba(255,255,255,.55);font-size:10px}.dxm-phone-mini{width:100%;max-width:330px;margin:0 auto;border:1px solid rgba(255,255,255,.12);border-radius:32px;background:#050816;box-shadow:0 24px 70px rgba(0,0,0,.45);overflow:hidden}.dxm-phone-mini-top{height:28px;display:grid;place-items:center;background:rgba(255,255,255,.045)}.dxm-phone-speaker{width:72px;height:6px;border-radius:999px;background:rgba(255,255,255,.16)}.dxm-phone-mini-screen{height:600px;overflow:auto;padding:12px;background:linear-gradient(180deg,#060b1c,#030712);scrollbar-width:thin}.dxm-phone-tabs{display:grid;grid-template-columns:repeat(5,1fr);gap:4px;margin-top:10px;border-top:1px solid rgba(255,255,255,.08);padding:8px;background:rgba(15,23,42,.9)}.dxm-phone-tab{font-size:9px;text-align:center;color:rgba(255,255,255,.55);font-weight:850}.dxm-phone-tab.active{color:#67e8f9}@media(max-width:1180px){.dxm-builder-grid{grid-template-columns:1fr}.dxm-builder-preview{position:relative;top:auto}.dxm-phone-mini{max-width:360px}}@media(max-width:680px){.dxm-destination-hero-grid{grid-template-columns:1fr}.dxm-destination-stats{min-width:0}.dxm-section-panel-head{grid-template-columns:1fr}.dxm-section-panel-actions{justify-content:flex-start}.dxm-item-grid{grid-template-columns:1fr}.dxm-destination-tabs{top:64px}}
    .dxm-template-shortcuts{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;padding:14px;border-top:1px solid rgba(255,255,255,.08)}.dxm-template-shortcut{border:1px solid rgba(255,255,255,.09);border-radius:18px;background:rgba(15,23,42,.54);padding:12px;text-decoration:none;color:#fff;display:flex;gap:10px;align-items:flex-start;min-height:94px}.dxm-template-shortcut:hover{border-color:rgba(34,211,238,.38);background:rgba(34,211,238,.08)}.dxm-shortcut-icon{width:36px;height:36px;border-radius:13px;display:grid;place-items:center;background:rgba(34,211,238,.12);border:1px solid rgba(34,211,238,.22);font-weight:950;color:#a5f3fc;flex:0 0 auto}.dxm-shortcut-title{font-size:12px;font-weight:950;line-height:1.2}.dxm-shortcut-note{font-size:10px;color:rgba(255,255,255,.55);line-height:1.35;margin-top:5px}.dxm-builder-meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:7px;margin-top:10px}.dxm-builder-meta-card{border:1px solid rgba(255,255,255,.08);border-radius:13px;background:rgba(255,255,255,.035);padding:8px}.dxm-builder-meta-card span{display:block;color:rgba(255,255,255,.45);font-size:9px;font-weight:950;text-transform:uppercase;letter-spacing:.05em}.dxm-builder-meta-card strong{display:block;color:rgba(255,255,255,.86);font-size:11px;font-weight:900;margin-top:3px;word-break:break-word}.dxm-builder-template-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:10px}.dxm-item-meta-code{font-size:10px;color:rgba(255,255,255,.52);word-break:break-word;border:1px solid rgba(255,255,255,.07);border-radius:12px;background:rgba(0,0,0,.16);padding:7px;margin-top:2px}@media(max-width:980px){.dxm-template-shortcuts{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:640px){.dxm-template-shortcuts{grid-template-columns:1fr}}
    .dxm-placement-form{border:1px solid rgba(34,211,238,.12);border-radius:18px;background:rgba(8,13,32,.72);padding:12px;display:grid;gap:10px}.dxm-placement-form-head{display:flex;justify-content:space-between;align-items:center;gap:10px}.dxm-placement-form-title{color:#e0faff;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.05em}.dxm-placement-form-note{color:rgba(255,255,255,.50);font-size:10px;line-height:1.35}.dxm-placement-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:8px}.dxm-field{display:grid;gap:5px}.dxm-field label{font-size:9px;font-weight:950;text-transform:uppercase;letter-spacing:.05em;color:rgba(255,255,255,.48)}.dxm-field input,.dxm-field select{width:100%;min-height:35px;border-radius:11px;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.72);color:#fff;padding:7px 9px;font-size:11px;outline:none}.dxm-field input:focus,.dxm-field select:focus{border-color:rgba(34,211,238,.45)}.dxm-placement-actions{display:flex;justify-content:flex-end;gap:7px;flex-wrap:wrap}.dxm-logic-badge{display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.08);color:#cffafe;border-radius:999px;padding:5px 8px;font-size:9px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}
    </style>

    <div class="dxm-destination-shell" data-dxm-flow="destination-builder">
        <section class="dxm-destination-hero">
            <div class="dxm-destination-hero-grid">
                <div>
                    <div class="dxm-kicker">Official Destination Builder</div>
                    <h1 class="dxm-destination-title">{{ $activeApp?->name ?? 'No App Selected' }}</h1>
                    <div class="dxm-destination-sub">
                        This is now the only working area for Home, Watch, Inspire, Explore and More. It uses the same backend section/item parameters as the old dashboard builder so the backend preview and frontend payload stay aligned.
                    </div>
                    <div class="dxm-meta-row" style="margin-top:12px;">
                        <span class="dxm-pill">{{ $enabledSectionCount }}/{{ $sectionCount }} enabled sections</span>
                        <span class="dxm-pill">{{ $enabledItemCount }}/{{ $itemCount }} enabled items</span>
                        <span class="dxm-pill">Active tab: {{ $defaultTabs[$activeTab] }}</span>
                    </div>
                </div>

                <div>
                    <div class="dxm-destination-actions" style="margin-bottom:10px;">
                        <a href="{{ url('/admin/beginner-dashboard') }}" class="dxm-btn">Dashboard</a>
                        <a href="{{ url('/admin/content-channels') }}" class="dxm-btn">Content Channels</a>
                        <button type="button" class="dxm-btn primary" data-dxm-open-preview>Preview</button>
                    </div>
                    <div class="dxm-destination-stats">
                        <div class="dxm-destination-stat"><strong>{{ $tabCounts['home'] ?? 0 }}</strong><span>Home Sections</span></div>
                        <div class="dxm-destination-stat"><strong>{{ $tabCounts['watch'] ?? 0 }}</strong><span>Watch Sections</span></div>
                        <div class="dxm-destination-stat"><strong>{{ $tabCounts['inspire'] ?? 0 }}</strong><span>Inspire Sections</span></div>
                        <div class="dxm-destination-stat"><strong>{{ $tabCounts['explore'] ?? 0 }}</strong><span>Explore Sections</span></div>
                    </div>
                </div>
            </div>
        </section>

        <nav class="dxm-destination-tabs" aria-label="Destination tabs">
            <div class="dxm-destination-tabs-inner">
                @foreach ($defaultTabs as $key => $label)
                    <a href="{{ url('/admin/destination-builder') }}?tab={{ $key }}" class="dxm-destination-tab {{ $activeTab === $key ? 'is-active' : '' }}">
                        <strong>{{ $label }}</strong>
                        <span>{{ $tabCounts[$key] ?? 0 }} sections • {{ $tabItemCounts[$key] ?? 0 }} items</span>
                    </a>
                @endforeach
            </div>
        </nav>

        <div class="dxm-builder-grid">
            <main class="dxm-builder-main">
                <section class="dxm-section-panel">
                    <div class="dxm-section-panel-head">
                        <div>
                            <h2 class="dxm-section-panel-title">{{ $defaultTabs[$activeTab] }} Builder</h2>
                            <div class="dxm-section-panel-sub">Arrange live backend sections for {{ $defaultTabs[$activeTab] }}. This order and payload are what the Flutter render engine should receive.</div>
                        </div>
                        <div class="dxm-section-panel-actions">
                            <a href="{{ url('/admin/template-library') }}?tab=pages&target_tab={{ $activeTab }}" class="dxm-btn primary">Add From Template</a>
                            <form method="POST" action="{{ route('admin.template-library.save-tab', $activeTab) }}" onsubmit="return confirm('Save this whole {{ $defaultTabs[$activeTab] }} page as a reusable template?');">@csrf<input type="hidden" name="template_title" value="{{ ($activeApp?->name ?? 'App') . ' ' . $defaultTabs[$activeTab] . ' Page Template' }}"><button type="submit" class="dxm-btn">Save Page as Template</button></form>
                            <a href="{{ route('admin.beginner.sections.create') }}?tab={{ $activeTab }}&return=destination" class="dxm-btn primary">+ Add Section</a>
                        </div>
                    </div>
                    <div class="dxm-template-shortcuts">
                        <a class="dxm-template-shortcut" href="{{ url('/admin/template-library') }}?tab=tabs&target_tab={{ $activeTab }}">
                            <span class="dxm-shortcut-icon">TAB</span><span><span class="dxm-shortcut-title">Tab Templates</span><span class="dxm-shortcut-note">Start from complete Home, Inspire, Explore, Watch, or More structures.</span></span>
                        </a>
                        <a class="dxm-template-shortcut" href="{{ url('/admin/template-library') }}?tab=sections&target_tab={{ $activeTab }}">
                            <span class="dxm-shortcut-icon">SEC</span><span><span class="dxm-shortcut-title">Section Templates</span><span class="dxm-shortcut-note">Add quote rows, short video strips, bookshelves, banners, and grids.</span></span>
                        </a>
                        <a class="dxm-template-shortcut" href="{{ url('/admin/template-library') }}?tab=items&target_tab={{ $activeTab }}">
                            <span class="dxm-shortcut-icon">ITM</span><span><span class="dxm-shortcut-title">Item/Card Templates</span><span class="dxm-shortcut-note">Use icon cards, image cards, tool cards, CTA cards, and content cards.</span></span>
                        </a>
                        <a class="dxm-template-shortcut" href="{{ url('/admin/template-library') }}?tab=media&target_tab={{ $activeTab }}">
                            <span class="dxm-shortcut-icon">IMG</span><span><span class="dxm-shortcut-title">Media & Icons</span><span class="dxm-shortcut-note">Prepare image, icon, banner, and visual card blocks for this page.</span></span>
                        </a>
                    </div>
                </section>

                @forelse ($sections as $section)
                    <section class="dxm-section-panel" id="section-{{ $section->id }}">
                        @php
                            $sectionMeta = $metaFor($section);
                            $sectionSourceType = $sectionMeta['source_type'] ?? $sectionMeta['source'] ?? $sectionMeta['content_source'] ?? $sectionMeta['builder_source'] ?? 'manual';
                            $sectionBucket = $sectionMeta['source_bucket'] ?? $sectionMeta['bucket'] ?? $sectionMeta['content_bucket'] ?? $section->key;
                            $sectionPlacement = $sectionMeta['placement_type'] ?? $sectionMeta['placement'] ?? $sectionMeta['insert_position'] ?? 'normal';
                            $sectionInsertAfter = $sectionMeta['insert_after_items'] ?? $sectionMeta['insert_after_item'] ?? $sectionMeta['after_item'] ?? null;
                            $sectionCardStyle = $sectionMeta['card_style'] ?? $sectionMeta['card_variant'] ?? $sectionMeta['card_size'] ?? 'default';
                            $sectionParent = $sectionMeta['parent_section_key'] ?? $sectionMeta['parent_key'] ?? $sectionMeta['parent'] ?? null;
                        @endphp
                        <div class="dxm-section-panel-head">
                            <div>
                                <h3 class="dxm-section-panel-title">{{ $section->title }}</h3>
                                <div class="dxm-section-panel-sub">
                                    Key: {{ $section->key }} • Template: {{ $section->template }} • Layout: {{ $layoutFor($section) }} • Order {{ $section->sort_order }} • {{ $section->items->where('is_enabled', true)->count() }} active cards
                                </div>
                                @if ($section->subtitle)
                                    <div class="dxm-section-panel-sub">{{ $section->subtitle }}</div>
                                @endif
                                <div class="dxm-builder-meta-grid">
                                    <div class="dxm-builder-meta-card"><span>Source</span><strong>{{ $sectionSourceType }}</strong></div>
                                    <div class="dxm-builder-meta-card"><span>Bucket</span><strong>{{ $sectionBucket }}</strong></div>
                                    <div class="dxm-builder-meta-card"><span>Placement</span><strong>{{ $sectionPlacement }}</strong></div>
                                    <div class="dxm-builder-meta-card"><span>After Item</span><strong>{{ $sectionInsertAfter ?: 'Not set' }}</strong></div>
                                    <div class="dxm-builder-meta-card"><span>Card Style</span><strong>{{ $sectionCardStyle }}</strong></div>
                                    <div class="dxm-builder-meta-card"><span>Parent</span><strong>{{ $sectionParent ?: 'Root section' }}</strong></div>
                                </div>
                            </div>
                            <div class="dxm-section-panel-actions">
                                <span class="dxm-pill {{ $section->is_enabled ? 'dxm-status-on' : 'dxm-status-off' }}">{{ $section->is_enabled ? 'Enabled' : 'Disabled' }}</span>
                                <form method="POST" action="{{ route('admin.beginner.sections.move', [$section, 'up']) }}">@csrf @method('PATCH')<button type="submit" class="dxm-btn">↑</button></form>
                                <form method="POST" action="{{ route('admin.beginner.sections.move', [$section, 'down']) }}">@csrf @method('PATCH')<button type="submit" class="dxm-btn">↓</button></form>
                                <form method="POST" action="{{ route('admin.template-library.save-section', $section) }}" onsubmit="return confirm('Save this section as a reusable template?');">@csrf<input type="hidden" name="template_title" value="{{ $section->title . ' Template' }}"><input type="hidden" name="template_category" value="sections"><button type="submit" class="dxm-btn">Save Template</button></form>
                                <a href="{{ route('admin.beginner.sections.edit', $section) }}?return=destination&tab={{ $activeTab }}" class="dxm-btn">Edit Section</a>
                                <form method="POST" action="{{ route('admin.beginner.sections.destroy', $section) }}" onsubmit="return confirm('Delete this section permanently? Items under this section may also be affected. This cannot be undone.');">@csrf @method('DELETE')<button type="submit" class="dxm-danger-btn">Delete</button></form>
                            </div>
                        </div>

                        <div class="dxm-section-body">
                            <div class="dxm-section-panel-actions" style="justify-content:flex-start;">
                                <a href="{{ route('admin.beginner.items.create') }}?section_id={{ $section->id }}&tab={{ $activeTab }}&return=destination" class="dxm-btn primary">+ Add Item</a>
                                <span class="dxm-logic-badge">Builder v2 controls</span>
                            </div>

                            <form method="POST" action="{{ route('admin.beginner.sections.update', $section) }}" class="dxm-placement-form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="current_tab" value="{{ $activeTab }}">
                                <input type="hidden" name="return" value="destination">
                                <input type="hidden" name="tab_key" value="{{ $section->tab_key }}">
                                <input type="hidden" name="route_key" value="{{ $section->route_key }}">
                                <input type="hidden" name="title" value="{{ $section->title }}">
                                <input type="hidden" name="subtitle" value="{{ $section->subtitle }}">
                                <input type="hidden" name="key" value="{{ $section->key }}">
                                <input type="hidden" name="template" value="{{ $section->template }}">
                                <input type="hidden" name="sort_order" value="{{ $section->sort_order }}">
                                <input type="hidden" name="is_enabled" value="{{ $section->is_enabled ? 1 : 0 }}">

                                <div class="dxm-placement-form-head">
                                    <div>
                                        <div class="dxm-placement-form-title">Placement & Source Logic</div>
                                        <div class="dxm-placement-form-note">Update where this block appears and what backend bucket/source it should pull from. This saves to section metadata only.</div>
                                    </div>
                                    <button type="submit" class="dxm-btn primary">Save Placement</button>
                                </div>

                                <div class="dxm-placement-grid">
                                    <div class="dxm-field">
                                        <label>Placement</label>
                                        <select name="placement_type">
                                            @foreach ($placementTypeLabels as $value => $label)
                                                <option value="{{ $value }}" @selected($sectionPlacement === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="dxm-field">
                                        <label>Parent Section</label>
                                        <select name="parent_section_key">
                                            <option value="">Root section</option>
                                            @foreach ($parentSectionOptions as $value => $label)
                                                @if ($value !== $section->key)
                                                    <option value="{{ $value }}" @selected($sectionParent === $value)>{{ $label }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="dxm-field">
                                        <label>Source Type</label>
                                        <select name="source_type">
                                            @foreach ($sourceTypeLabels as $value => $label)
                                                <option value="{{ $value }}" @selected($sectionSourceType === $value || ($sectionMeta['content_source'] ?? null) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="dxm-field">
                                        <label>Source Bucket</label>
                                        <select name="source_bucket">
                                            <option value="">Manual / not set</option>
                                            @foreach ($sourceBucketOptions as $value => $label)
                                                <option value="{{ $value }}" @selected($sectionBucket === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="dxm-field">
                                        <label>Insert Target</label>
                                        <select name="insert_target_bucket">
                                            <option value="">Default page/list</option>
                                            @foreach ($sourceBucketOptions as $value => $label)
                                                <option value="{{ $value }}" @selected(($sectionMeta['insert_target_bucket'] ?? '') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="dxm-field">
                                        <label>After Item #</label>
                                        <input type="number" name="insert_after_items" min="0" max="50" value="{{ (int) ($sectionInsertAfter ?? 0) }}">
                                    </div>
                                    <div class="dxm-field">
                                        <label>Layout</label>
                                        <select name="layout_variant">
                                            @foreach ($layoutVariantLabels as $value => $label)
                                                <option value="{{ $value }}" @selected(($sectionMeta['layout_variant'] ?? $layoutFor($section)) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="dxm-field">
                                        <label>Card Style</label>
                                        <select name="card_style">
                                            @foreach ($cardStyleLabels as $value => $label)
                                                <option value="{{ $value }}" @selected($sectionCardStyle === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="dxm-field">
                                        <label>Size</label>
                                        <select name="size_preset">
                                            @foreach ($sizePresetLabels as $value => $label)
                                                <option value="{{ $value }}" @selected(($sectionMeta['size_preset'] ?? 'normal') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="dxm-field">
                                        <label>Columns</label>
                                        <input type="number" name="columns" min="1" max="4" value="{{ (int) ($sectionMeta['columns'] ?? 1) }}">
                                    </div>
                                    <div class="dxm-field">
                                        <label>Target Route</label>
                                        <input type="text" name="target_route" value="{{ $sectionMeta['target_route'] ?? '' }}" placeholder="/motivation/quotes">
                                    </div>
                                    <div class="dxm-field">
                                        <label>Max Items</label>
                                        <input type="number" name="max_items" min="1" max="60" value="{{ (int) ($sectionMeta['max_items'] ?? 12) }}">
                                    </div>
                                </div>
                            </form>

                            @if ($section->items->isNotEmpty())
                                <div class="dxm-item-grid">
                                    @foreach ($section->items as $item)
                                        @php
                                            $img = $imageFor($item);
                                            $payload = $payloadFor($item);
                                            $display = $displayTextFor($section, $item);
                                        @endphp
                                        <article class="dxm-builder-item" style="{{ $item->is_enabled ? '' : 'opacity:.55;' }}" id="item-{{ $item->id }}">
                                            <div class="dxm-builder-img">
                                                @if ($img)
                                                    <img src="{{ $img }}" alt="{{ $item->title }}">
                                                @endif
                                            </div>
                                            <div class="dxm-builder-item-body">
                                                <div>
                                                    <div class="dxm-builder-item-title">{!! nl2br(e($display['main'])) !!}</div>
                                                    @if ($display['sub'])
                                                        <div class="dxm-builder-item-sub">{!! nl2br(e($display['sub'])) !!}</div>
                                                    @endif
                                                </div>
                                                <div class="dxm-meta-row">
                                                    <span class="dxm-pill">{{ $item->type ?: 'link' }}</span>
                                                    <span class="dxm-pill">Order {{ $item->sort_order }}</span>
                                                    <span class="dxm-pill {{ $item->is_enabled ? 'dxm-status-on' : 'dxm-status-off' }}">{{ $item->is_enabled ? 'Enabled' : 'Disabled' }}</span>
                                                    @if (! empty($payload['engine']))
                                                        <span class="dxm-pill">{{ $payload['engine'] }}</span>
                                                    @endif
                                                    @if (! empty($payload['card_style']))
                                                        <span class="dxm-pill">{{ $payload['card_style'] }}</span>
                                                    @endif
                                                    @if (! empty($payload['route_key']))
                                                        <span class="dxm-pill">{{ $payload['route_key'] }}</span>
                                                    @endif
                                                </div>
                                                @if (! empty($payload['action']) || ! empty($payload['source_id']) || ! empty($payload['content_id']))
                                                    <div class="dxm-item-meta-code">
                                                        @if (! empty($payload['action'])) Action: {{ is_array($payload['action']) ? json_encode($payload['action']) : $payload['action'] }} @endif
                                                        @if (! empty($payload['source_id'])) • Source ID: {{ $payload['source_id'] }} @endif
                                                        @if (! empty($payload['content_id'])) • Content ID: {{ $payload['content_id'] }} @endif
                                                    </div>
                                                @endif
                                                <div class="dxm-builder-actions-bottom">
                                                    <form method="POST" action="{{ route('admin.beginner.items.move', [$item, 'up']) }}">@csrf @method('PATCH')<button type="submit" class="dxm-btn">↑</button></form>
                                                    <form method="POST" action="{{ route('admin.beginner.items.move', [$item, 'down']) }}">@csrf @method('PATCH')<button type="submit" class="dxm-btn">↓</button></form>
                                                    <a href="{{ $editUrlFor($section, $item) }}" class="dxm-btn">Edit</a>
                                                    <form method="POST" action="{{ route('admin.beginner.items.destroy', $item) }}" onsubmit="return confirm('Delete this item permanently? This cannot be undone.');">@csrf @method('DELETE')<button type="submit" class="dxm-danger-btn">Delete</button></form>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @else
                                <div class="dxm-empty-box">No content items inside this section yet.</div>
                            @endif
                        </div>
                    </section>
                @empty
                    <div class="dxm-empty-box">No section has been added to {{ $defaultTabs[$activeTab] }} yet. Add a section so the frontend can render this tab from AppsHub.</div>
                @endforelse
            </main>

            <aside class="dxm-builder-preview">
                <div class="dxm-preview-card-shell">
                    <div class="dxm-preview-card-head">
                        <div>
                            <div class="dxm-preview-card-title">Live Phone Preview</div>
                            <div class="dxm-preview-card-note">Uses the same section/item payload and layout helpers.</div>
                        </div>
                        <button type="button" class="dxm-btn primary" data-dxm-open-preview>Open Large</button>
                    </div>
                    <div class="dxm-phone-mini">
                        <div class="dxm-phone-mini-top"><div class="dxm-phone-speaker"></div></div>
                        <div class="dxm-phone-mini-screen">
                            @foreach (($previewSectionsByTab[$activeTab] ?? collect()) as $section)
                                @include('filament.pages.beginner-dashboard.partials.preview-section', ['section' => $section])
                            @endforeach
                        </div>
                        <div class="dxm-phone-tabs">
                            @foreach ($defaultTabs as $key => $label)
                                <div class="dxm-phone-tab {{ $activeTab === $key ? 'active' : '' }}">{{ $label }}</div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    @include('filament.pages.beginner-dashboard.partials.preview-drawer')
    @include('filament.pages.beginner-dashboard.partials.preview-scripts')
</x-filament::page>

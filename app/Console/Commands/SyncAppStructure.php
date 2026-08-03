<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\AppItem;
use App\Models\AppSection;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncAppStructure extends Command
{
    protected $signature = 'dxm:sync-structure {--items : Also sync default items/cards}';

    protected $description = 'Sync app sections and optional default items to match frontend layout safely.';

    public function handle(): int
    {
        $this->info('Starting AppsHub structure sync...');

        $apps = App::query()->orderBy('id')->get();

        foreach ($apps as $app) {
            $this->line('');
            $this->line("Processing App ID: {$app->id} ({$app->name})");

            $sectionsByKey = $this->syncSections((int) $app->id);

            if ($this->option('items')) {
                $this->syncItems((int) $app->id, $sectionsByKey);
            }
        }

        $this->info('');
        $this->info('Structure sync completed successfully.');

        return self::SUCCESS;
    }

    private function syncSections(int $appId): array
    {
        $sectionsByKey = [];

        foreach ($this->structure() as $tabKey => $sections) {
            foreach ($sections as $index => $sectionConfig) {
                $key = $sectionConfig['key'];
                $title = $sectionConfig['title'];
                $sortOrder = ($index + 1) * 10;

                $section = AppSection::query()
                    ->where('app_id', $appId)
                    ->where('key', $key)
                    ->first();

                if (! $section) {
                    $section = AppSection::query()
                        ->where('app_id', $appId)
                        ->where('tab_key', $tabKey)
                        ->where('title', $title)
                        ->first();
                }

                if (! $section) {
                    $section = AppSection::create([
                        'app_id' => $appId,
                        'tab_key' => $tabKey,
                        'route_key' => null,
                        'key' => $key,
                        'title' => $title,
                        'subtitle' => $sectionConfig['subtitle'] ?? null,
                        'template' => $sectionConfig['template'] ?? 'grid',
                        'sort_order' => $sortOrder,
                        'is_enabled' => true,
                        'meta_json' => [
                            'synced_by' => 'dxm:sync-structure',
                            'frontend_key' => $key,
                        ],
                    ]);

                    $this->info("  Created section: {$tabKey} → {$title}");
                } else {
                    $section->fill([
                        'tab_key' => $tabKey,
                        'route_key' => $section->route_key,
                        'key' => $key,
                        'title' => $title,
                        'subtitle' => $section->subtitle ?: ($sectionConfig['subtitle'] ?? null),
                        'template' => $section->template ?: ($sectionConfig['template'] ?? 'grid'),
                        'sort_order' => $sortOrder,
                    ]);

                    $section->save();

                    $this->line("  Synced section: {$tabKey} → {$title}");
                }

                $sectionsByKey[$key] = $section;
            }
        }

        return $sectionsByKey;
    }

    private function syncItems(int $appId, array $sectionsByKey): void
    {
        foreach ($this->items() as $sectionKey => $items) {
            if (! isset($sectionsByKey[$sectionKey])) {
                $this->warn("  Missing section for items: {$sectionKey}");
                continue;
            }

            $section = $sectionsByKey[$sectionKey];

            foreach ($items as $index => $itemConfig) {
                $title = $itemConfig['title'];
                $sortOrder = ($index + 1) * 10;

                $item = AppItem::query()
                    ->where('section_id', $section->id)
                    ->where('title', $title)
                    ->first();

                if (! $item) {
                    $item = AppItem::query()
                        ->whereHas('section', function ($query) use ($appId) {
                            $query->where('app_id', $appId);
                        })
                        ->where('title', $title)
                        ->first();
                }

                $payload = $itemConfig['payload_json'] ?? [
                    'action' => [
                        'type' => $itemConfig['action_type'] ?? 'route',
                        'route_key' => $itemConfig['route_key'] ?? null,
                    ],
                ];

                if ($item) {
                    $item->fill([
                        'section_id' => $section->id,
                        'type' => $itemConfig['type'] ?? $item->type ?? 'link',
                        'title' => $title,
                        'subtitle' => $item->subtitle ?: ($itemConfig['subtitle'] ?? null),
                        'icon' => $item->icon ?: ($itemConfig['icon'] ?? null),
                        'image_url' => $item->image_url ?: ($itemConfig['image_url'] ?? null),
                        'route' => $itemConfig['route'] ?? $item->route,
                        'url' => $itemConfig['url'] ?? $item->url,
                        'payload_json' => $item->payload_json ?: $payload,
                        'sort_order' => $sortOrder,
                        'is_enabled' => $item->is_enabled,
                    ]);

                    $item->save();

                    $this->line("  Synced item: {$section->title} → {$title}");
                } else {
                    AppItem::create([
                        'section_id' => $section->id,
                        'type' => $itemConfig['type'] ?? 'link',
                        'title' => $title,
                        'subtitle' => $itemConfig['subtitle'] ?? null,
                        'icon' => $itemConfig['icon'] ?? null,
                        'image_url' => $itemConfig['image_url'] ?? null,
                        'route' => $itemConfig['route'] ?? null,
                        'url' => $itemConfig['url'] ?? null,
                        'payload_json' => $payload,
                        'sort_order' => $sortOrder,
                        'is_enabled' => true,
                    ]);

                    $this->info("  Created item: {$section->title} → {$title}");
                }
            }
        }
    }

    private function structure(): array
    {
        return [
            'home' => [
                ['key' => 'home_banners', 'title' => 'Banners', 'template' => 'banner_carousel'],
                ['key' => 'home_quick_access', 'title' => 'Quick Access', 'template' => 'quick_actions'],
                ['key' => 'home_prayer_broadcast', 'title' => 'Prayer Broadcast', 'template' => 'horizontal_list'],
                ['key' => 'home_daily_scripture', 'title' => 'Daily Scripture', 'template' => 'daily'],
                ['key' => 'home_quick_tools', 'title' => 'Quick Tools', 'template' => 'quick_actions'],
                ['key' => 'home_daily_quote', 'title' => 'Daily Quote', 'template' => 'daily'],
            ],

            'watch' => [
                ['key' => 'watch_dunamis_live', 'title' => 'Dunamis Live', 'template' => 'hero'],
                ['key' => 'watch_live_stream', 'title' => 'Live Stream', 'template' => 'hero'],
                ['key' => 'watch_live_services', 'title' => 'Live Services', 'template' => 'horizontal_list'],
                ['key' => 'watch_commanding_the_day', 'title' => 'Commanding The Day', 'template' => 'horizontal_list'],
                ['key' => 'watch_healing_deliverance', 'title' => 'Healing And Deliverance Service', 'template' => 'horizontal_list'],
                ['key' => 'watch_testimonies', 'title' => 'Testimonies At Dunamis', 'template' => 'horizontal_list'],
                ['key' => 'watch_special_crusade', 'title' => 'Special Crusade', 'template' => 'horizontal_list'],
                ['key' => 'watch_other_channels', 'title' => 'Other Christian Channels', 'template' => 'grid'],
            ],

            'inspire' => [
                ['key' => 'inspire_sod', 'title' => 'Seeds Of Destiny', 'template' => 'grid'],
                ['key' => 'inspire_message_highlights', 'title' => 'Message Highlights', 'template' => 'horizontal_list'],
                ['key' => 'inspire_wordification', 'title' => 'Wordification', 'template' => 'horizontal_list'],
                ['key' => 'inspire_motivation', 'title' => 'Motivation', 'template' => 'horizontal_list'],
                ['key' => 'inspire_inside_dunamis', 'title' => 'Inside Dunamis / Articles', 'template' => 'vertical_list'],
            ],

            'explore' => [
                ['key' => 'explore_tools', 'title' => 'Tools', 'template' => 'grid'],
                ['key' => 'explore_games', 'title' => 'Games', 'template' => 'grid'],
            ],

            'more' => [
                ['key' => 'more_account', 'title' => 'Account', 'template' => 'vertical_list'],
                ['key' => 'more_saved', 'title' => 'Saved / Bookmarks', 'template' => 'vertical_list'],
                ['key' => 'more_downloads', 'title' => 'Downloads', 'template' => 'vertical_list'],
                ['key' => 'more_notifications', 'title' => 'Notifications', 'template' => 'vertical_list'],
                ['key' => 'more_support', 'title' => 'Support', 'template' => 'vertical_list'],
                ['key' => 'more_about', 'title' => 'About', 'template' => 'vertical_list'],
                ['key' => 'more_policies', 'title' => 'Policies', 'template' => 'vertical_list'],
                ['key' => 'more_settings', 'title' => 'Settings', 'template' => 'vertical_list'],
                ['key' => 'more_share_rate', 'title' => 'Share / Rate', 'template' => 'vertical_list'],
                ['key' => 'more_build_app_like_this', 'title' => 'Build app like this', 'template' => 'vertical_list'],
            ],
        ];
    }

    private function items(): array
    {
        return [
            'home_quick_access' => [
                ['title' => 'Dunamis TV Live', 'type' => 'link', 'route' => '/watch', 'route_key' => 'watch', 'payload_json' => ['home_kind' => 'live']],
                ['title' => 'Seeds Of Destiny', 'type' => 'link', 'route' => '/inspire/sod', 'route_key' => 'sod', 'payload_json' => ['home_kind' => 'sod']],
                ['title' => 'Inside Dunamis', 'type' => 'link', 'route' => '/inspire/articles', 'route_key' => 'articles', 'payload_json' => ['home_kind' => 'articles']],
                ['title' => 'Message Highlights', 'type' => 'link', 'route' => '/inspire/highlights', 'route_key' => 'highlights', 'payload_json' => ['home_kind' => 'highlights']],
            ],

            'home_quick_tools' => [
                ['title' => 'Bible', 'type' => 'tool', 'route' => '/explore/bible', 'route_key' => 'bible'],
                ['title' => 'Notes', 'type' => 'tool', 'route' => '/explore/notes', 'route_key' => 'notes'],
                ['title' => 'Quote Creator', 'type' => 'tool', 'route' => '/explore/quotes', 'route_key' => 'quote_creator'],
            ],

            'watch_other_channels' => [
                ['title' => 'Salvation TV', 'type' => 'video', 'route_key' => 'salvation_tv'],
                ['title' => 'COZA TV', 'type' => 'video', 'route_key' => 'coza_tv'],
                ['title' => 'Dove TV', 'type' => 'video', 'route_key' => 'dove_tv'],
            ],

            'inspire_sod' => [
                ['title' => 'Read SOD', 'type' => 'link', 'route' => '/inspire/sod', 'route_key' => 'sod'],
                ['title' => 'Watch SOD', 'type' => 'video', 'route' => '/watch/sod', 'route_key' => 'watch_sod'],
                ['title' => 'SOD Quotes', 'type' => 'link', 'route' => '/inspire/sod-quotes', 'route_key' => 'sod_quotes'],
            ],

            'explore_tools' => [
                ['title' => 'Quote Creator', 'type' => 'tool', 'route' => '/explore/quotes', 'route_key' => 'quote_creator'],
                ['title' => 'Notes', 'type' => 'tool', 'route' => '/explore/notes', 'route_key' => 'notes'],
                ['title' => 'Bible', 'type' => 'tool', 'route' => '/explore/bible', 'route_key' => 'bible'],
            ],

            'explore_games' => [
                ['title' => 'Dominion Match', 'type' => 'game', 'route' => '/games/dominion-match', 'route_key' => 'dominion_match'],
                ['title' => 'Race Of Faith', 'type' => 'game', 'route' => '/games/race-of-faith', 'route_key' => 'race_of_faith'],
                ['title' => 'Dominion Builder', 'type' => 'game', 'route' => '/games/dominion-builder', 'route_key' => 'dominion_builder'],
            ],

            'more_account' => [
                ['title' => 'Account', 'type' => 'link', 'route' => '/more/account', 'route_key' => 'account'],
            ],

            'more_saved' => [
                ['title' => 'Saved / Bookmarks', 'type' => 'link', 'route' => '/more/saved', 'route_key' => 'saved'],
            ],

            'more_downloads' => [
                ['title' => 'Downloads', 'type' => 'link', 'route' => '/more/downloads', 'route_key' => 'downloads'],
            ],

            'more_notifications' => [
                ['title' => 'Notifications', 'type' => 'link', 'route' => '/more/notifications', 'route_key' => 'notifications'],
            ],

            'more_support' => [
                ['title' => 'Support', 'type' => 'link', 'route' => '/more/support', 'route_key' => 'support'],
            ],

            'more_about' => [
                ['title' => 'About', 'type' => 'link', 'route' => '/more/about', 'route_key' => 'about'],
            ],

            'more_policies' => [
                ['title' => 'Policies', 'type' => 'link', 'route' => '/more/policies', 'route_key' => 'policies'],
            ],

            'more_settings' => [
                ['title' => 'Settings', 'type' => 'link', 'route' => '/more/settings', 'route_key' => 'settings'],
            ],

            'more_share_rate' => [
                ['title' => 'Share / Rate', 'type' => 'link', 'route' => '/more/share', 'route_key' => 'share_rate'],
            ],

            'more_build_app_like_this' => [
                ['title' => 'Build app like this', 'type' => 'link', 'route' => '/more/build-app-like-this', 'route_key' => 'build_app_like_this'],
            ],
        ];
    }
}

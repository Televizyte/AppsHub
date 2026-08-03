<?php

namespace App\Support;

use App\Models\App;
use Illuminate\Support\Facades\Schema;

final class EngineRoomCatalog
{
    public static function groups(): array
    {
        return [
            'overview' => [
                'label' => 'Overview',
                'description' => 'Global services status and quick control shortcuts.',
            ],
            'content_video' => [
                'label' => 'Content & Video',
                'description' => 'Engines that power app content, reading, books, watch, and short videos.',
            ],
            'growth' => [
                'label' => 'Ads / Quiz / Push',
                'description' => 'Engines for app growth, monetization, engagement, quiz, and notifications.',
            ],
            'libraries' => [
                'label' => 'Libraries',
                'description' => 'Reusable visual libraries for templates, media, icons, and app building assets.',
            ],
            'technical' => [
                'label' => 'API / AI / Usage',
                'description' => 'Owner-only monitoring for platform usage, APIs, AI, storage, and future costs.',
            ],
        ];
    }

    public static function engines(): array
    {
        return [
            [
                'group' => 'content_video',
                'name' => 'Content Engine',
                'key' => 'content_channels',
                'description' => 'Articles, SOD, motivation, wordification, highlights, and reading content.',
                'status' => 'Active',
                'route' => '/admin/content-posts',
                'manage_label' => 'Open Content',
                'icon' => '▦',
                'tone' => 'cyan',
            ],
            [
                'group' => 'content_video',
                'name' => 'Watch / Video Engine',
                'key' => 'watch_links',
                'description' => 'Live stream, HLS, YouTube, HTML embeds, playlist cards, and app watch sections.',
                'status' => 'Active',
                'route' => '/admin/watch-links',
                'manage_label' => 'Open Watch Links',
                'icon' => '▻',
                'tone' => 'blue',
            ],
            [
                'group' => 'content_video',
                'name' => 'Short Video Engine',
                'key' => 'short_videos',
                'description' => 'Short clips, reels-style sections, featured short video blocks, and future video library.',
                'status' => 'Active',
                'route' => '/admin/short-video-engine',
                'manage_label' => 'Open Engine',
                'icon' => '▶',
                'tone' => 'purple',
            ],
            [
                'group' => 'content_video',
                'name' => 'Book / Library Engine',
                'key' => 'books',
                'description' => 'Books, chapters, covers, reader payloads, and library publishing.',
                'status' => 'Active',
                'route' => '/admin/beginner/books',
                'manage_label' => 'Open Books',
                'icon' => '▤',
                'tone' => 'cyan',
            ],
            [
                'group' => 'growth',
                'name' => 'Ads Engine',
                'key' => 'ads',
                'description' => 'Per-app ad units, placement rules, native/banner/interstitial settings, and monetization controls.',
                'status' => 'Active',
                'route' => '/admin/ad-profiles',
                'manage_label' => 'Open Ads',
                'icon' => 'AD',
                'tone' => 'purple',
            ],
            [
                'group' => 'growth',
                'name' => 'Notification Engine',
                'key' => 'notifications',
                'description' => 'Instant, scheduled, repeating, image, topic, and deep-link push notifications.',
                'status' => 'Active',
                'route' => '/admin/push-notifications',
                'manage_label' => 'Open Push',
                'icon' => '◉',
                'tone' => 'cyan',
            ],
            [
                'group' => 'growth',
                'name' => 'Quiz Engine',
                'key' => 'quiz',
                'description' => 'Bible quiz, SOD quiz, article quiz, exact extraction, and future AI-assisted quiz.',
                'status' => 'Queued',
                'route' => null,
                'manage_label' => 'Coming Soon',
                'icon' => '?',
                'tone' => 'blue',
            ],
            [
                'group' => 'growth',
                'name' => 'User / Auth Engine',
                'key' => 'user_auth',
                'description' => 'Sign in for likes, comments, follow, save, profile, and protected user actions.',
                'status' => 'Active',
                'route' => null,
                'manage_label' => 'Connected',
                'icon' => '👥',
                'tone' => 'blue',
            ],
            [
                'group' => 'libraries',
                'name' => 'Template Engine',
                'key' => 'template_library',
                'description' => 'App, page, section, card, widget, and form templates with save/apply workflow.',
                'status' => 'Active',
                'route' => '/admin/template-library',
                'manage_label' => 'Open Templates',
                'icon' => '▣',
                'tone' => 'cyan',
            ],
            [
                'group' => 'libraries',
                'name' => 'Media Engine',
                'key' => 'media',
                'description' => 'Shared and app-scoped images, logos, banners, icons, thumbnails, audio, video, and files.',
                'status' => 'Active',
                'route' => '/admin/media-assets',
                'manage_label' => 'Open Media',
                'icon' => '◩',
                'tone' => 'purple',
            ],
            [
                'group' => 'libraries',
                'name' => 'Icon Preset Engine',
                'key' => 'media',
                'description' => 'Reusable icons and visual presets for cards, tools, pages, and app shortcuts.',
                'status' => 'Active',
                'route' => '/admin/icon-presets',
                'manage_label' => 'Open Icons',
                'icon' => '◇',
                'tone' => 'blue',
            ],
            [
                'group' => 'technical',
                'name' => 'API / Usage Monitor',
                'key' => 'usage_monitor',
                'description' => 'Future owner dashboard for API calls, app usage, AI usage, storage, push, and media costs.',
                'status' => 'Queued',
                'route' => null,
                'manage_label' => 'Coming Soon',
                'icon' => '▥',
                'tone' => 'purple',
            ],
            [
                'group' => 'technical',
                'name' => 'AI Engine Monitor',
                'key' => 'ai_engine',
                'description' => 'Future monitoring for AI summarization, quiz generation, text-to-speech, and media tools.',
                'status' => 'Future',
                'route' => null,
                'manage_label' => 'Future',
                'icon' => 'AI',
                'tone' => 'cyan',
            ],
        ];
    }

    public static function capabilityUsage(): array
    {
        $apps = App::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'is_active', 'branding_json']);

        $engines = self::engines();

        return collect($engines)->map(function (array $engine) use ($apps) {
            $key = (string) ($engine['key'] ?? '');
            $usingApps = collect();

            if ($key !== '') {
                $usingApps = $apps->filter(function (App $app) use ($key) {
                    return AppCapabilities::enabled($app, $key);
                })->values();
            }

            $engine['apps_count'] = $usingApps->count();
            $engine['apps'] = $usingApps->take(6)->map(fn (App $app) => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
                'is_active' => (bool) $app->is_active,
            ])->values()->all();

            return $engine;
        })->values()->all();
    }

    public static function stats(): array
    {
        $apps = App::query()->count();
        $activeApps = App::query()->where('is_active', true)->count();

        return [
            'apps' => $apps,
            'active_apps' => $activeApps,
            'engines' => count(self::engines()),
            'active_engines' => collect(self::engines())->where('status', 'Active')->count(),
            'templates' => Schema::hasTable('builder_templates') ? (int) \App\Models\BuilderTemplate::query()->count() : 0,
            'media' => Schema::hasTable('media_assets') ? (int) \App\Models\MediaAsset::query()->count() : 0,
            'posts' => Schema::hasTable('content_posts') ? (int) \App\Models\ContentPost::query()->count() : 0,
            'watch_links' => Schema::hasTable('watch_links') ? (int) \App\Models\WatchLink::query()->count() : 0,
        ];
    }
}

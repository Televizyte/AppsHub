<?php

namespace App\Support;

use App\Models\App;

final class AppCapabilities
{
    public static function defaults(): array
    {
        return [
            'destination_builder' => true,
            'home_manager' => true,
            'watch_manager' => true,
            'inspire_manager' => true,
            'explore_manager' => true,
            'content_channels' => true,
            'watch_links' => true,
            'short_videos' => true,
            'books' => true,
            'daily_scripture' => true,
            'daily_quote' => true,
            'media' => true,
            'app_settings' => true,
            'ads' => true,
            'notifications' => true,
            'analytics' => true,
            'moderation' => true,
            'feed_engine' => true,
            'quiz' => false,
            'bible' => true,
            'notes' => true,
            'quote_creator' => true,
            'games' => true,
            'template_library' => false,
            'forms' => false,
            'user_auth' => true,
        ];
    }

    public static function labels(): array
    {
        $labels = [];

        foreach (self::groups() as $group) {
            foreach (($group['items'] ?? []) as $key => $item) {
                $labels[$key] = $item['label'] ?? ucwords(str_replace('_', ' ', $key));
            }
        }

        return $labels;
    }

    public static function descriptions(): array
    {
        $descriptions = [];

        foreach (self::groups() as $group) {
            foreach (($group['items'] ?? []) as $key => $item) {
                $descriptions[$key] = $item['description'] ?? '';
            }
        }

        return $descriptions;
    }

    public static function groups(): array
    {
        return [
            'workspace' => [
                'label' => 'Workspace',
                'description' => 'Core app-building tools visible inside the selected app workspace.',
                'items' => [
                    'destination_builder' => [
                        'label' => 'Destination Builder',
                        'description' => 'Controls Home, Watch, Inspire, Explore, More sections and route/page structure.',
                    ],
                    'home_manager' => [
                        'label' => 'Home Manager',
                        'description' => 'Home banners, quick tools, daily cards, featured books/videos, and homepage blocks.',
                    ],
                    'watch_manager' => [
                        'label' => 'Watch Manager',
                        'description' => 'Watch page arrangement, live/video sections, and broadcast blocks.',
                    ],
                    'inspire_manager' => [
                        'label' => 'Inspire Manager',
                        'description' => 'SOD, articles, motivation, wordification, highlights, and inspire blocks.',
                    ],
                    'explore_manager' => [
                        'label' => 'Explore Manager',
                        'description' => 'Explore tools, Bible, notes, quote creator, games, books, and quiz entries.',
                    ],
                    'app_settings' => [
                        'label' => 'App Settings',
                        'description' => 'Identity, branding, theme, store metadata, contact, social, API and frontend config.',
                    ],
                ],
            ],

            'content' => [
                'label' => 'Content & Media',
                'description' => 'Content engines and media features available to this app.',
                'items' => [
                    'content_channels' => [
                        'label' => 'Content Channels',
                        'description' => 'Articles, SOD, motivation, wordification, highlights, and reading content.',
                    ],
                    'short_videos' => [
                        'label' => 'Short Videos',
                        'description' => 'Short clips, reels-style sections, Home/Inspire featured video blocks.',
                    ],
                    'books' => [
                        'label' => 'Books & Library',
                        'description' => 'Books, chapters, covers, reader payloads, and library publishing.',
                    ],
                    'daily_scripture' => [
                        'label' => 'Daily Scripture',
                        'description' => 'Daily scripture card, scripture bank, and future auto-rotation.',
                    ],
                    'daily_quote' => [
                        'label' => 'Daily Quote',
                        'description' => 'Daily quote card, quote bank, and future scheduled publishing.',
                    ],
                    'media' => [
                        'label' => 'Media Library',
                        'description' => 'App-scoped/shared images, logos, banners, thumbnails, audio, video, and files.',
                    ],
                ],
            ],

            'watch' => [
                'label' => 'Watch & Video',
                'description' => 'Live, video, and media playback tools.',
                'items' => [
                    'watch_links' => [
                        'label' => 'Watch Links',
                        'description' => 'Live streams, YouTube, HLS, HTML embeds, playlists, and external watch links.',
                    ],
                    'watch_manager' => [
                        'label' => 'Watch Manager',
                        'description' => 'Backend-controlled Watch tab blocks and preview arrangement.',
                    ],
                ],
            ],

            'engagement' => [
                'label' => 'Engagement',
                'description' => 'User interaction, notifications, moderation, quiz, and community features.',
                'items' => [
                    'notifications' => [
                        'label' => 'Push Notifications',
                        'description' => 'Instant, scheduled, repeating, image, and deep-link notifications.',
                    ],
                    'quiz' => [
                        'label' => 'Quiz Engine',
                        'description' => 'Bible quiz, SOD quiz, article quiz, exact extraction, and AI-assisted quiz later.',
                    ],
                    'analytics' => [
                        'label' => 'Analytics',
                        'description' => 'Views, opens, engagement, app activity, and future reports.',
                    ],
                    'moderation' => [
                        'label' => 'Moderation',
                        'description' => 'Comments, reports, user-generated content, and content review.',
                    ],
                    'feed_engine' => [
                        'label' => 'Community Feed',
                        'description' => 'Controlled app-specific community feed with posts, comments, likes, saves, shares, moderation, and deep links.',
                    ],
                    'user_auth' => [
                        'label' => 'User Sign In',
                        'description' => 'Allows sign-in for likes, comments, save, follow, profile, and protected actions.',
                    ],
                ],
            ],

            'tools' => [
                'label' => 'App Tools',
                'description' => 'Frontend tools and connected tools visible to users.',
                'items' => [
                    'bible' => [
                        'label' => 'Bible',
                        'description' => 'Bible reader/search/save and scripture opening from daily cards.',
                    ],
                    'notes' => [
                        'label' => 'Notes',
                        'description' => 'In-app notes and add-to-note flows from scripture, quotes, Bible, and content.',
                    ],
                    'quote_creator' => [
                        'label' => 'Quote Creator',
                        'description' => 'Quote design tool with prefilled scripture/quote/content payloads.',
                    ],
                    'games' => [
                        'label' => 'Games',
                        'description' => 'Dominion Match and future game features when enabled.',
                    ],
                    'forms' => [
                        'label' => 'Forms',
                        'description' => 'Prayer request, testimony, contact, event signup, and future form templates.',
                    ],
                ],
            ],

            'monetization' => [
                'label' => 'Monetization',
                'description' => 'Ads and app revenue controls.',
                'items' => [
                    'ads' => [
                        'label' => 'Ads Engine',
                        'description' => 'Per-app ad units, placements, frequency, formats, and native/banner/interstitial rules.',
                    ],
                ],
            ],

            'libraries' => [
                'label' => 'Libraries & Templates',
                'description' => 'Visual libraries used to build and reuse layouts faster.',
                'items' => [
                    'template_library' => [
                        'label' => 'Template Library',
                        'description' => 'App, page, section, card, widget, form, icon, media, and layout templates.',
                    ],
                ],
            ],
        ];
    }

    public static function forApp(?App $app): array
    {
        $defaults = self::defaults();

        if (! $app) {
            return $defaults;
        }

        $branding = is_array($app->branding_json) ? $app->branding_json : [];

        $stored = data_get($branding, 'capabilities', []);
        $stored = is_array($stored) ? $stored : [];

        $legacyFlags = data_get($branding, 'flags', []);
        $legacyFlags = is_array($legacyFlags) ? $legacyFlags : [];

        $merged = array_merge($defaults, self::fromLegacyFlags($legacyFlags), $stored);

        return self::clean($merged);
    }

    public static function enabled(?App $app, string $key): bool
    {
        $capabilities = self::forApp($app);

        return (bool) ($capabilities[$key] ?? false);
    }

    public static function clean(array $input): array
    {
        $defaults = self::defaults();
        $clean = [];

        foreach ($defaults as $key => $defaultValue) {
            $clean[$key] = array_key_exists($key, $input)
                ? filter_var($input[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                : (bool) $defaultValue;

            if ($clean[$key] === null) {
                $clean[$key] = (bool) $defaultValue;
            }
        }

        return $clean;
    }

    public static function fromLegacyFlags(array $flags): array
    {
        $map = [];

        if (array_key_exists('enable_watch', $flags)) {
            $enabled = (bool) $flags['enable_watch'];
            $map['watch_links'] = $enabled;
            $map['watch_manager'] = $enabled;
        }

        if (array_key_exists('enable_content_studio', $flags)) {
            $map['content_channels'] = (bool) $flags['enable_content_studio'];
        }

        if (array_key_exists('enable_ads', $flags)) {
            $map['ads'] = (bool) $flags['enable_ads'];
        }

        if (array_key_exists('enable_push', $flags)) {
            $map['notifications'] = (bool) $flags['enable_push'];
        }

        if (array_key_exists('enable_auth', $flags)) {
            $map['user_auth'] = (bool) $flags['enable_auth'];
        }

        return $map;
    }
}

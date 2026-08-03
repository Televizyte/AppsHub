<?php

namespace App\Support\More;

use App\Models\App;
use App\Support\Destinations\CanonicalDestinationRegistry;
use App\Support\Legal\LegalTemplateRegistry;

final class MoreInformationRegistry
{
    public static function bootstrapPayload(App $app): array
    {
        $branding = is_array($app->branding_json)
            ? $app->branding_json
            : (json_decode((string) $app->branding_json, true) ?: []);

        $configured = data_get($branding, 'more_pages', []);
        if (! is_array($configured)) $configured = [];

        $legal = LegalTemplateRegistry::bootstrapPayload($app);
        $destinations = CanonicalDestinationRegistry::bootstrapPayload($app);

        $defaults = self::defaults($app, $branding, $legal, $destinations);

        return array_replace_recursive($defaults, $configured);
    }

    public static function defaults(App $app, array $branding, array $legal, array $destinations): array
    {
        $profile = data_get($branding, 'legal.profile', []);
        if (! is_array($profile)) $profile = [];

        $officialLinks = data_get($branding, 'official_links', []);
        if (! is_array($officialLinks)) $officialLinks = [];

        $ministryLinks = [];
        foreach ($officialLinks as $row) {
            if (! is_array($row) || ($row['enabled'] ?? true) === false) continue;
            $value = trim((string) ($row['value'] ?? $row['url'] ?? ''));
            if ($value === '') continue;
            $key = trim((string) ($row['key'] ?? 'link'));
            $ministryLinks[] = [
                'key' => $key,
                'label' => (string) ($row['label'] ?? ucfirst($key)),
                'icon_key' => (string) ($row['icon'] ?? $key),
                'url' => $value,
                'open_mode' => $key === 'website' ? 'external_confirmed' : (string) ($row['open_mode'] ?? 'internal_web'),
                'enabled' => true,
                'sort_order' => (int) ($row['sort_order'] ?? 100),
            ];
        }

        return [
            'schema_version' => '1.0',
            'technical_support' => [
                'enabled' => true,
                'title' => 'Technical Support',
                'subtitle' => 'Help with the app, accounts, playback, notifications and other technical matters.',
                'route' => '/more/technical-support',
                'ads_enabled' => false,
                'knowledge_base' => [
                    'enabled' => true,
                    'title' => 'Help & Knowledge Base',
                    'articles' => [
                        ['key' => 'navigate-app', 'title' => 'Navigating Dunamis TV', 'summary' => 'Understand the five main tabs and where to find content.', 'content' => 'Use Home for highlights, Watch for live and video content, Inspire for devotionals and articles, Explore for tools and games, and More for account, support and app information.', 'icon_key' => 'navigation', 'enabled' => true, 'sort_order' => 10],
                        ['key' => 'quote-creator', 'title' => 'Using Quote Creator', 'summary' => 'Create and share faith-based quote designs.', 'content' => 'Open Explore, select Quote Creator, choose or enter a quote, adjust the available design options, preview the result, and save or share it.', 'icon_key' => 'quote', 'enabled' => true, 'sort_order' => 20],
                        ['key' => 'saved-downloads', 'title' => 'Saved Items and Downloads', 'summary' => 'Find content you bookmarked or saved for offline use.', 'content' => 'Saved contains bookmarked content linked to your app activity. Downloads contains supported items stored on the current device for offline use.', 'icon_key' => 'download', 'enabled' => true, 'sort_order' => 30],
                        ['key' => 'notifications', 'title' => 'Notifications', 'summary' => 'Manage announcements and content alerts.', 'content' => 'Open More, select Settings, and review notification options. Device-level notification permissions may also need to be enabled in Android settings.', 'icon_key' => 'notifications', 'enabled' => true, 'sort_order' => 40],
                    ],
                ],
                'request' => [
                    'enabled' => true,
                    'title' => 'Send a Support Request',
                    'form_url' => (string) data_get($legal, 'support.form_url', ''),
                    'email' => (string) data_get($profile, 'technical_support_email', data_get($branding, 'support_email', '')),
                    'phone_enabled' => false,
                ],
            ],
            'ministry' => [
                'enabled' => true,
                'title' => 'About the Ministry',
                'route' => '/more/about-ministry',
                'ads_enabled' => false,
                'name' => (string) data_get($profile, 'ministry_name', 'Dunamis International Gospel Centre'),
                'short_name' => 'Dunamis',
                'banner_url' => (string) data_get($branding, 'more_pages.ministry.banner_url', ''),
                'logo_url' => (string) data_get($branding, 'more_pages.ministry.logo_url', ''),
                'overview' => (string) data_get($branding, 'more_pages.ministry.overview', 'Dunamis International Gospel Centre is a Christian ministry committed to spreading the Gospel, building lives and helping people experience God’s purpose.'),
                'vision' => (string) data_get($branding, 'more_pages.ministry.vision', ''),
                'mission' => (string) data_get($branding, 'more_pages.ministry.mission', ''),
                'leadership' => data_get($branding, 'more_pages.ministry.leadership', []),
                'address' => (string) data_get($profile, 'ministry_address', ''),
                'emails' => array_values(array_filter([(string) data_get($profile, 'ministry_email', '')])),
                'phones' => array_values(array_filter([(string) data_get($profile, 'ministry_phone', '')])),
                'website' => [
                    'label' => 'Official Ministry Website',
                    'url' => (string) data_get($destinations, 'official.website.value', ''),
                    'open_mode' => 'external_confirmed',
                    'enabled' => (bool) data_get($destinations, 'official.website.enabled', false),
                ],
                'map_url' => (string) data_get($branding, 'more_pages.ministry.map_url', ''),
                'social_links' => $ministryLinks,
            ],
            'about_app' => [
                'enabled' => true,
                'title' => 'About '.(string) data_get($branding, 'display_name', $app->name),
                'route' => '/more/about-app',
                'ads_enabled' => false,
                'name' => (string) data_get($branding, 'display_name', $app->name),
                'tagline' => (string) data_get($branding, 'tagline', ''),
                'description' => (string) data_get($branding, 'about', ''),
                'operator_name' => (string) data_get($profile, 'operator_name', 'DigitXtra Media Services'),
                'package_name' => (string) data_get($destinations, 'app.package_name', ''),
                'version_name' => (string) data_get($destinations, 'app.version_name', ''),
                'version_code' => (string) data_get($destinations, 'app.version_code', ''),
            ],
            'legal_index' => [
                'enabled' => true,
                'title' => 'Legal & Policies',
                'route' => '/more/legal',
                'ads_enabled' => false,
                'documents' => array_values(array_filter([
                    data_get($destinations, 'legal.privacy'),
                    data_get($destinations, 'legal.terms'),
                    data_get($destinations, 'legal.account_deletion'),
                    data_get($destinations, 'legal.support'),
                    data_get($legal, 'documents.community'),
                    data_get($legal, 'documents.copyright'),
                    data_get($legal, 'documents.content-usage'),
                    data_get($legal, 'documents.disclaimer'),
                    data_get($legal, 'documents.data-safety'),
                ], fn ($v) => is_array($v))),
            ],
            'build_with_dxm' => [
                'enabled' => true,
                'title' => 'Build an App Like This',
                'route' => '/more/build-with-dxm',
                'ads_enabled' => false,
                'hero_title' => 'Build Your Next Digital Platform with DigitXtra Media',
                'intro' => 'We design and build mobile applications, websites, streaming platforms and reusable digital systems for ministries, media organizations and growing businesses.',
                'services' => ['Mobile app development', 'Website and platform development', 'Streaming and media systems', 'AppsHub-powered TV and ministry apps', 'Custom digital product development'],
                'portfolio' => data_get($branding, 'more_pages.build_with_dxm.portfolio', []),
                'email_only' => true,
                'public_email' => (string) data_get($branding, 'more_pages.build_with_dxm.public_email', data_get($profile, 'technical_support_email', 'apps.dxm@gmail.com')),
                'form' => [
                    'enabled' => true,
                    'endpoint' => '/api/v1/apps/'.$app->slug.'/business-enquiries',
                    'phone_enabled' => false,
                    'whatsapp_enabled' => false,
                ],
            ],
        ];
    }
}

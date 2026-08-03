<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AdProfile;
use App\Models\App;
use App\Support\Ads\AdResolver;
use App\Support\AppBranding;
use App\Support\AppCapabilities;
use App\Support\Legal\LegalTemplateRegistry;
use App\Support\Destinations\CanonicalDestinationRegistry;
use App\Support\More\MoreInformationRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BootstrapController extends Controller
{
    public function show(Request $request, string $appSlug)
    {
        $app = App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $tabs = $this->resolveTabsForApp((int) $app->id);

        $brandingJson = is_array($app->branding_json) ? $app->branding_json : [];

        $branding = [
            'raw' => $brandingJson,
            'assets' => AppBranding::assetsPayload($app),
            'theme' => AppBranding::themePayload($app),
            'profile' => [
                'display_name' => data_get($brandingJson, 'display_name', $app->name),
                'tagline' => data_get($brandingJson, 'tagline'),
                'about' => data_get($brandingJson, 'about'),
                'support_email' => data_get($brandingJson, 'support_email'),
                'support_phone' => data_get($brandingJson, 'support_phone'),
                'website_url' => data_get($brandingJson, 'website_url'),
                'social' => data_get($brandingJson, 'social', []),
            ],
        ];

        $flags = data_get($brandingJson, 'flags', []);
        $features = data_get($brandingJson, 'features', []);
        $capabilities = AppCapabilities::forApp($app);

        $featureFlags = array_merge(
            is_array($features) ? $features : [],
            is_array($flags) ? $flags : [],
            $capabilities
        );

        $branding['capabilities'] = $capabilities;

        $adProfile = AdProfile::query()
            ->where('app_id', (int) $app->id)
            ->first();

        $meta = $adProfile ? (is_array($adProfile->meta_json) ? $adProfile->meta_json : []) : [];
        $overrides = AdResolver::metaOverrides($meta);

        $tabsAds = AdResolver::tabsAdsForApp((int) $app->id);

        $adPolicy = [];
        foreach ($tabsAds as $tabKey => $cfg) {
            $adPolicy[$tabKey] = (bool) ($cfg['enabled'] ?? false);
        }

        if (isset($overrides['ad_policy']) && is_array($overrides['ad_policy'])) {
            $adPolicy = array_merge($adPolicy, $overrides['ad_policy']);
        }

        $adFormats = $overrides['ad_formats'] ?? AdResolver::defaultFormats();
        $nativeInList = $overrides['native_in_list'] ?? AdResolver::defaultNativeInList();
        $interstitial = $overrides['interstitial'] ?? AdResolver::defaultInterstitial();

        $routes = $this->resolveRoutesForApp((int) $app->id);
        $hub = $this->resolveHubForApp((int) $app->id);
        $links = $this->resolveLinksForApp((int) $app->id);
        $watch = $this->resolveWatchForApp((int) $app->id);
        $home = $this->resolveHomeForApp((int) $app->id, $branding, $watch);
        $inspire = $this->resolveInspireForApp((int) $app->id, $branding, $links);
        $presetsCatalog = $this->presetsCatalog();

        return response()->json([
            'ok' => true,
            'api_version' => 'v1.2',

            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
                'is_active' => (bool) $app->is_active,
            ],

            'branding' => $branding,
            'capabilities' => $capabilities,
            'feature_flags' => $featureFlags,
            'legal' => LegalTemplateRegistry::bootstrapPayload($app),
            'destinations' => CanonicalDestinationRegistry::bootstrapPayload($app),
            'more_pages' => MoreInformationRegistry::bootstrapPayload($app),
            'support' => data_get(
                LegalTemplateRegistry::bootstrapPayload($app),
                'support'
            ),

            'tabs' => $tabs,
            'routes' => $routes,

            'hub' => $hub,
            'home' => $home,
            'inspire' => $inspire,

            'ad_policy' => $adPolicy,
            'ad_formats' => $adFormats,
            'native_in_list' => $nativeInList,
            'interstitial' => $interstitial,

            'ads' => [
                'enabled' => $adProfile ? (bool) $adProfile->ads_enabled : true,
                'units' => [
                    'banner' => $adProfile?->banner_unit_id,
                    'native' => $adProfile?->native_unit_id,
                    'interstitial' => $adProfile?->interstitial_unit_id,
                ],
                'global' => [
                    'ads_enabled' => $adProfile ? (bool) $adProfile->ads_enabled : true,
                    'banner_unit_id' => $adProfile?->banner_unit_id,
                    'native_unit_id' => $adProfile?->native_unit_id,
                    'interstitial_unit_id' => $adProfile?->interstitial_unit_id,
                ],
                'formats' => $adFormats,
                'native_in_list' => $nativeInList,
                'interstitial' => $interstitial,
                'policy_version' => 2,
                'tabs' => $tabsAds,
            ],

            'watch' => $watch,
            'links' => $links,
            'presets_catalog' => $presetsCatalog,
        ]);
    }

    private function decodeJson($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $s = trim($value);
            if ($s === '' || strtolower($s) === 'null') {
                return null;
            }

            $decoded = json_decode($s, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function resolveTabsForApp(int $appId): array
    {
        $fallbackOrder = ['home', 'watch', 'inspire', 'explore', 'more'];
        $fallbackLabels = [
            'home' => 'Home',
            'watch' => 'Watch',
            'inspire' => 'Inspire',
            'explore' => 'Explore',
            'more' => 'More',
        ];

        if (! Schema::hasTable('app_tabs')) {
            return $this->fallbackTabs($fallbackOrder, $fallbackLabels);
        }

        $cols = ['id', 'key', 'title', 'icon', 'sort_order', 'is_enabled', 'meta_json'];

        $rows = DB::table('app_tabs')
            ->select($cols)
            ->where('app_id', $appId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return $this->fallbackTabs($fallbackOrder, $fallbackLabels);
        }

        $out = [];
        foreach ($rows as $r) {
            $a = (array) $r;

            $out[] = [
                'key' => (string) ($a['key'] ?? ''),
                'title' => (string) ($a['title'] ?? ''),
                'icon' => $a['icon'] ?? null,
                'sort_order' => (int) ($a['sort_order'] ?? 0),
                'is_enabled' => (bool) ($a['is_enabled'] ?? true),
                'meta' => $this->decodeJson($a['meta_json'] ?? null),
            ];
        }

        return array_values(array_filter($out, fn ($t) => ! empty($t['key'])));
    }

    private function fallbackTabs(array $order, array $labels): array
    {
        $out = [];
        $i = 0;

        foreach ($order as $key) {
            $out[] = [
                'key' => (string) $key,
                'title' => (string) ($labels[$key] ?? ucfirst($key)),
                'icon' => null,
                'sort_order' => $i,
                'is_enabled' => true,
                'meta' => null,
            ];
            $i++;
        }

        return $out;
    }

    private function resolveRoutesForApp(int $appId): array
    {
        if (! Schema::hasTable('app_routes')) {
            return [];
        }

        $cols = ['id', 'key', 'title', 'path', 'route', 'tab_key', 'is_enabled', 'meta_json'];

        $rows = DB::table('app_routes')
            ->select($cols)
            ->where('app_id', $appId)
            ->orderBy('tab_key')
            ->orderBy('key')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $a = (array) $r;

            $out[] = [
                'key' => (string) ($a['key'] ?? ''),
                'title' => (string) ($a['title'] ?? ''),
                'path' => $a['path'] ?? null,
                'route' => $a['route'] ?? null,
                'tab_key' => $a['tab_key'] ?? null,
                'is_enabled' => (bool) ($a['is_enabled'] ?? true),
                'meta' => $this->decodeJson($a['meta_json'] ?? null),
            ];
        }

        return array_values(array_filter($out, fn ($x) => ! empty($x['key'])));
    }

    private function resolveHubForApp(int $appId): array
    {
        if (! Schema::hasTable('app_sections') || ! Schema::hasTable('app_items')) {
            return [
                'sections' => [],
                'items' => [],
                'grouped' => [],
            ];
        }

        $sections = DB::table('app_sections')
            ->where('app_id', $appId)
            ->where('is_enabled', 1)
            ->orderBy('tab_key')
            ->orderBy('route_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $sectionIds = $sections->pluck('id')->all();

        $items = [];
        if (! empty($sectionIds)) {
            $items = DB::table('app_items')
                ->whereIn('section_id', $sectionIds)
                ->where('is_enabled', 1)
                ->orderBy('section_id')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->all();
        }

        $sectionsOut = [];
        foreach ($sections as $s) {
            $a = (array) $s;

            $sectionsOut[] = [
                'id' => (int) ($a['id'] ?? 0),
                'tab_key' => (string) ($a['tab_key'] ?? ''),
                'route_key' => $a['route_key'] ?? null,
                'key' => (string) ($a['key'] ?? ''),
                'title' => (string) ($a['title'] ?? ''),
                'subtitle' => $a['subtitle'] ?? null,
                'template' => (string) ($a['template'] ?? 'blocks'),
                'sort_order' => (int) ($a['sort_order'] ?? 0),
                'meta' => $this->decodeJson($a['meta_json'] ?? null),
                'visibility' => $this->decodeJson($a['visibility_json'] ?? null),
                'empty_state' => $this->decodeJson($a['empty_state_json'] ?? null),
            ];
        }

        $itemsOut = [];
        foreach ($items as $i) {
            $a = (array) $i;

            $itemsOut[] = [
                'id' => (int) ($a['id'] ?? 0),
                'section_id' => (int) ($a['section_id'] ?? 0),
                'type' => (string) ($a['type'] ?? ''),
                'title' => (string) ($a['title'] ?? ''),
                'subtitle' => $a['subtitle'] ?? null,
                'icon' => $a['icon'] ?? null,
                'image_url' => $a['image_url'] ?? null,
                'route' => $a['route'] ?? null,
                'url' => $a['url'] ?? null,
                'sort_order' => (int) ($a['sort_order'] ?? 0),
                'payload' => $this->decodeJson($a['payload_json'] ?? null),
            ];
        }

        $grouped = [];
        foreach ($sectionsOut as $sec) {
            $tabKey = $sec['tab_key'] ?: 'home';
            $routeKey = $sec['route_key'] ?: '__tab__';

            $grouped[$tabKey] ??= [];
            $grouped[$tabKey][$routeKey] ??= [];

            $secItems = array_values(array_filter(
                $itemsOut,
                fn ($it) => (int) $it['section_id'] === (int) $sec['id']
            ));

            $secWithItems = $sec;
            $secWithItems['items'] = $secItems;

            $grouped[$tabKey][$routeKey][] = $secWithItems;
        }

        return [
            'sections' => $sectionsOut,
            'items' => $itemsOut,
            'grouped' => $grouped,
        ];
    }

    private function resolveWatchForApp(int $appId): array
    {
        $out = [
            'endpoint' => '/api/v1/apps/{appSlug}/watch',
            'default_ads_off' => true,
            'primary_stream_url' => null,
            'live_hls' => null,
            'live_youtube' => null,
            'commanding_day' => null,
            'videos' => [],
            'other_channels' => [],
            'groups' => [],
            'items' => [],
        ];

        $sectionWatch = $this->resolveWatchFromBuilderSections($appId);

        if (! empty($sectionWatch['items'])) {
            return array_merge($out, $sectionWatch);
        }

        return $this->resolveWatchFromLegacyWatchLinks($appId, $out);
    }

    private function resolveWatchFromBuilderSections(int $appId): array
    {
        $out = [
            'endpoint' => '/api/v1/apps/{appSlug}/watch',
            'default_ads_off' => true,
            'primary_stream_url' => null,
            'live_hls' => null,
            'live_youtube' => null,
            'commanding_day' => null,
            'videos' => [],
            'other_channels' => [],
            'groups' => [],
            'items' => [],
        ];

        if (! Schema::hasTable('app_sections') || ! Schema::hasTable('app_items')) {
            return $out;
        }

        $sections = DB::table('app_sections')
            ->where('app_id', $appId)
            ->where('tab_key', 'watch')
            ->where('is_enabled', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($sections->isEmpty()) {
            return $out;
        }

        $sectionIds = $sections->pluck('id')->all();

        $itemsBySection = DB::table('app_items')
            ->whereIn('section_id', $sectionIds)
            ->where('is_enabled', 1)
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_id');

        $groups = [];
        $flatItems = [];

        foreach ($sections as $section) {
            $sectionKey = trim((string) ($section->key ?? ''));
            $sectionTitle = trim((string) ($section->title ?? ''));
            $sectionSubtitle = trim((string) ($section->subtitle ?? ''));
            $sectionItems = $itemsBySection[$section->id] ?? collect();

            if ($sectionItems->isEmpty()) {
                $fallbackCard = $this->watchFallbackCardForSection($sectionKey, $sectionTitle, $sectionSubtitle);

                if ($fallbackCard !== null) {
                    $this->assignWatchCardToBucket($out, $groups, $flatItems, $sectionKey, $fallbackCard);
                }

                continue;
            }

            foreach ($sectionItems as $item) {
                $payload = $this->decodeJson($item->payload_json ?? null) ?? [];

                $itemTitle = trim((string) ($item->title ?? ''));
                $title = $this->watchDisplayTitleForItem($sectionKey, $sectionTitle, $itemTitle);

                $subtitle = trim((string) ($item->subtitle ?? ''));
                if ($subtitle === '') {
                    $subtitle = $sectionSubtitle;
                }

                if ($subtitle === '') {
                    $subtitle = $this->defaultWatchSubtitleForSection($sectionKey, $title);
                }

                $imageUrl = trim((string) ($item->image_url ?? ''));
                $rawType = strtolower(trim((string) ($item->type ?? '')));
                $rawUrl = trim((string) ($item->url ?? ''));

                $route = trim((string) ($item->route ?? ''));
                $payloadUrl = $this->watchUrlFromPayload($payload);
                $payloadRoute = $this->watchRouteFromPayload($payload);

                $url = $this->firstNonEmptyString([
                    $rawUrl,
                    $payloadUrl,
                    $this->watchUrlFromRouteKey($payloadRoute),
                    $this->watchUrlFromRouteKey($route),
                    $this->defaultWatchUrlForSection($sectionKey, $title),
                ]);

                $type = $this->watchTypeForSection($sectionKey, $rawType, $url, $title);
                $group = $this->watchGroupForSection($sectionKey, $type, $title);

                $card = [
                    'id' => (int) ($item->id ?? 0),
                    'section_id' => (int) ($section->id ?? 0),
                    'section_key' => $sectionKey,
                    'section_title' => $sectionTitle,
                    'item_title' => $itemTitle !== '' ? $itemTitle : null,
                    'group' => $group,
                    'raw_type' => $rawType !== '' ? $rawType : null,
                    'type' => $type,
                    'title' => $title,
                    'subtitle' => $subtitle !== '' ? $subtitle : null,
                    'thumbnail_url' => $imageUrl !== '' ? $imageUrl : null,
                    'image_url' => $imageUrl !== '' ? $imageUrl : null,
                    'url' => $url !== '' ? $url : null,
                    'route' => $route !== '' ? $route : null,
                    'sort_order' => (int) ($item->sort_order ?? 0),
                    'badge' => $this->watchBadgeForSection($sectionKey, $sectionTitle, $title),
                    'meta' => $payload ?: null,
                ];

                $this->assignWatchCardToBucket($out, $groups, $flatItems, $sectionKey, $card);
            }
        }

        $out['items'] = $flatItems;
        $out['groups'] = $groups;
        $out['videos'] = $groups['video'] ?? [];
        $out['other_channels'] = $groups['other_channels'] ?? [];

        if ($out['live_hls'] && empty($out['primary_stream_url'])) {
            $out['primary_stream_url'] = $out['live_hls']['url'] ?? null;
        }

        return $out;
    }

    private function assignWatchCardToBucket(array &$out, array &$groups, array &$flatItems, string $sectionKey, array $card): void
    {
        $key = strtolower(trim($sectionKey));
        $type = strtolower(trim((string) ($card['type'] ?? '')));
        $title = trim((string) ($card['title'] ?? ''));
        $groupKey = $this->watchGroupForSection($key, $type, $title);

        $isDunamisLive = str_contains($key, 'dunamis_live');
        $isLiveStream = str_contains($key, 'live_stream');
        $isLiveServices = str_contains($key, 'live_services');
        $isCommandingDay = str_contains($key, 'commanding');

        $isPrimaryBucket = $isDunamisLive || $isLiveStream || $isCommandingDay;

        if (! $isPrimaryBucket) {
            $this->appendUniqueWatchCard($flatItems, $card);
        }

        if ($isDunamisLive && $out['live_hls'] === null) {
            $out['live_hls'] = $this->withWatchTitle($card, 'Dunamis Live');
            $out['primary_stream_url'] = $card['url'] ?? null;
            return;
        }

        if ($isLiveStream && $out['live_youtube'] === null) {
            $out['live_youtube'] = $this->withWatchTitle($card, 'Live Stream');
            return;
        }

        if ($isLiveServices) {
            $serviceCard = $this->withWatchTitle($card, 'Live Services');
            $groups['video'] ??= [];
            $this->appendUniqueWatchCard($groups['video'], $serviceCard);
            $this->appendUniqueWatchCard($flatItems, $serviceCard);
            return;
        }

        if ($isCommandingDay && $out['commanding_day'] === null) {
            $out['commanding_day'] = $this->withWatchTitle($card, 'Commanding The Day');
            return;
        }

        $groups[$groupKey] ??= [];
        $this->appendUniqueWatchCard($groups[$groupKey], $card);
    }

    private function appendUniqueWatchCard(array &$target, array $card): void
    {
        $signature = strtolower(trim((string) ($card['title'] ?? ''))) . '|' . strtolower(trim((string) ($card['url'] ?? '')));

        foreach ($target as $existing) {
            $existingSignature = strtolower(trim((string) ($existing['title'] ?? ''))) . '|' . strtolower(trim((string) ($existing['url'] ?? '')));
            if ($existingSignature === $signature) {
                return;
            }
        }

        $target[] = $card;
    }

    private function withWatchTitle(array $card, string $title): array
    {
        $card['title'] = $title;
        return $card;
    }

    private function watchDisplayTitleForItem(string $sectionKey, string $sectionTitle, string $itemTitle): string
    {
        $key = strtolower(trim($sectionKey));
        $sectionTitle = trim($sectionTitle);
        $itemTitle = trim($itemTitle);

        if (str_contains($key, 'dunamis_live')) {
            return 'Dunamis Live';
        }

        if (str_contains($key, 'live_stream')) {
            return 'Live Stream';
        }

        if (str_contains($key, 'live_services')) {
            return 'Live Services';
        }

        if (str_contains($key, 'commanding')) {
            return 'Commanding The Day';
        }

        if (str_contains($key, 'healing')) {
            return $sectionTitle !== '' ? $sectionTitle : 'Healing And Deliverance Service';
        }

        if (str_contains($key, 'testimon')) {
            return $sectionTitle !== '' ? $sectionTitle : 'Testimonies At Dunamis';
        }

        if (str_contains($key, 'crusade')) {
            return $sectionTitle !== '' ? $sectionTitle : 'Special Crusade';
        }

        if (str_contains($key, 'other_channels')) {
            return $itemTitle !== '' ? $itemTitle : ($sectionTitle !== '' ? $sectionTitle : 'Other Christian Channels');
        }

        if ($itemTitle !== '') {
            return $itemTitle;
        }

        if ($sectionTitle !== '') {
            return $sectionTitle;
        }

        return 'Watch';
    }

    private function watchFallbackCardForSection(string $sectionKey, string $sectionTitle, string $sectionSubtitle): ?array
    {
        $key = strtolower(trim($sectionKey));
        $title = trim($sectionTitle) !== '' ? trim($sectionTitle) : 'Watch';
        $subtitle = trim($sectionSubtitle);

        $url = $this->defaultWatchUrlForSection($key, $title);
        if ($url === '') {
            return null;
        }

        $type = $this->watchTypeForSection($key, '', $url, $title);
        $group = $this->watchGroupForSection($key, $type, $title);

        return [
            'id' => 0,
            'section_id' => 0,
            'section_key' => $sectionKey,
            'section_title' => $sectionTitle,
            'item_title' => null,
            'group' => $group,
            'raw_type' => null,
            'type' => $type,
            'title' => $title,
            'subtitle' => $subtitle !== '' ? $subtitle : $this->defaultWatchSubtitleForSection($key, $title),
            'thumbnail_url' => null,
            'image_url' => null,
            'url' => $url,
            'route' => null,
            'sort_order' => 0,
            'badge' => $this->watchBadgeForSection($key, $sectionTitle, $title),
            'meta' => null,
        ];
    }

    private function defaultWatchSubtitleForSection(string $sectionKey, string $title): string
    {
        $key = strtolower(trim($sectionKey));
        $lowerTitle = strtolower(trim($title));

        if (str_contains($key, 'dunamis_live')) {
            return 'Watch Dunamis TV live broadcast';
        }

        if (str_contains($key, 'live_stream') || $lowerTitle === 'live stream') {
            return 'Open the live stream broadcast';
        }

        if (str_contains($key, 'live_services') || str_contains($lowerTitle, 'service')) {
            return 'Watch live and previous services';
        }

        if (str_contains($key, 'commanding')) {
            return 'Watch Commanding The Day prayer broadcast';
        }

        if (str_contains($key, 'healing')) {
            return 'Watch healing and deliverance services';
        }

        if (str_contains($key, 'testimon')) {
            return 'Watch testimonies at Dunamis';
        }

        if (str_contains($key, 'crusade')) {
            return 'Watch special crusade broadcasts';
        }

        if (str_contains($key, 'other_channels')) {
            return 'Open available Christian TV channels';
        }

        return 'Open available watch content';
    }

    private function watchUrlFromPayload(array $payload): string
    {
        $candidates = [
            data_get($payload, 'url'),
            data_get($payload, 'link'),
            data_get($payload, 'action.url'),
            data_get($payload, 'action.link'),
            data_get($payload, 'action.href'),
            data_get($payload, 'media.url'),
            data_get($payload, 'video.url'),
            data_get($payload, 'watch.url'),
        ];

        return $this->firstNonEmptyString($candidates);
    }

    private function watchRouteFromPayload(array $payload): string
    {
        return $this->firstNonEmptyString([
            data_get($payload, 'route_key'),
            data_get($payload, 'route'),
            data_get($payload, 'action.route_key'),
            data_get($payload, 'action.route'),
            data_get($payload, 'action.key'),
        ]);
    }

    private function watchUrlFromRouteKey(?string $routeKey): string
    {
        $key = strtolower(trim((string) $routeKey));

        if ($key === '') {
            return '';
        }

        $known = [
            'salvation_tv' => 'https://iframe.viewmedia.tv/?channel=017',
            'coza_tv' => 'https://iframe.viewmedia.tv/?channel=097',
            'dove_tv' => 'https://iframe.viewmedia.tv/?channel=093',
            'dunamis_live' => 'https://atechgroupuk.com/DTV/DTV.html',
            'dunamis_tv_live' => 'https://atechgroupuk.com/DTV/DTV.html',
            'live_stream' => 'https://www.youtube.com/@dunamistvworldwide/live',
            'live_services' => 'https://youtube.com/playlist?list=PLsFcFNo2Ku199zMhBztn8Kf5L_q5NSkdG&si=t8Q_1hIly-uqHgg8',
            'commanding_the_day' => 'https://www.youtube.com/watch?v=h5dBu2pf99s&list=PLsFcFNo2Ku18Yev7LYduuw7h6nql2PPf7',
            'healing_deliverance' => 'https://www.youtube.com/watch?v=vkjf-wBj9UA&list=PLsFcFNo2Ku1-7z6_ztR6Zs-jKElGZhVY_',
            'testimonies' => 'https://www.youtube.com/watch?v=zBEidHApzf4&list=PLsFcFNo2Ku1_roRwhIPQeRzHsnaVvuHBA',
        ];

        if (isset($known[$key])) {
            return $known[$key];
        }

        if (Schema::hasTable('app_routes')) {
            $route = DB::table('app_routes')
                ->where(function ($q) use ($key) {
                    $q->whereRaw('LOWER(`key`) = ?', [$key]);

                    if (Schema::hasColumn('app_routes', 'route')) {
                        $q->orWhereRaw('LOWER(`route`) = ?', [$key]);
                    }

                    if (Schema::hasColumn('app_routes', 'path')) {
                        $q->orWhereRaw('LOWER(`path`) = ?', [$key]);
                    }
                })
                ->first();

            if ($route) {
                foreach (['url', 'route', 'path'] as $field) {
                    if (isset($route->{$field}) && is_string($route->{$field}) && trim($route->{$field}) !== '') {
                        $value = trim($route->{$field});
                        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
                            return $value;
                        }
                    }
                }
            }
        }

        return '';
    }

    private function defaultWatchUrlForSection(string $sectionKey, string $title): string
    {
        $key = strtolower(trim($sectionKey));
        $text = strtolower(trim($sectionKey . ' ' . $title));

        if (str_contains($key, 'dunamis_live')) {
            return 'https://atechgroupuk.com/DTV/DTV.html';
        }

        if (str_contains($key, 'live_stream')) {
            return 'https://www.youtube.com/@dunamistvworldwide/live';
        }

        if (str_contains($key, 'live_services')) {
            return 'https://youtube.com/playlist?list=PLsFcFNo2Ku199zMhBztn8Kf5L_q5NSkdG&si=t8Q_1hIly-uqHgg8';
        }

        if (str_contains($key, 'commanding') || str_contains($text, 'commanding')) {
            return 'https://www.youtube.com/watch?v=h5dBu2pf99s&list=PLsFcFNo2Ku18Yev7LYduuw7h6nql2PPf7';
        }

        if (str_contains($key, 'healing') || str_contains($text, 'healing')) {
            return 'https://www.youtube.com/watch?v=vkjf-wBj9UA&list=PLsFcFNo2Ku1-7z6_ztR6Zs-jKElGZhVY_';
        }

        if (str_contains($key, 'testimon') || str_contains($text, 'testimon')) {
            return 'https://www.youtube.com/watch?v=zBEidHApzf4&list=PLsFcFNo2Ku1_roRwhIPQeRzHsnaVvuHBA';
        }

        if (str_contains($key, 'crusade') || str_contains($text, 'crusade')) {
            return 'https://www.youtube.com/@DunamisTVLive/playlists';
        }

        if (str_contains($text, 'salvation')) {
            return 'https://iframe.viewmedia.tv/?channel=017';
        }

        if (str_contains($text, 'coza')) {
            return 'https://iframe.viewmedia.tv/?channel=097';
        }

        if (str_contains($text, 'dove')) {
            return 'https://iframe.viewmedia.tv/?channel=093';
        }

        return '';
    }

    private function watchTypeForSection(string $sectionKey, string $rawType, string $url, string $title): string
    {
        $key = strtolower(trim($sectionKey));
        $type = strtolower(trim($rawType));
        $u = strtolower(trim($url));
        $text = strtolower(trim($sectionKey . ' ' . $title));

        if (str_contains($key, 'dunamis_live')) {
            return 'live_hls';
        }

        if (str_contains($key, 'live_stream')) {
            return 'live_youtube';
        }

        if (str_contains($key, 'live_services')) {
            return 'video';
        }

        if (str_contains($key, 'commanding')) {
            return 'commanding_day';
        }

        if (str_contains($key, 'other_channels') || str_contains($text, 'salvation') || str_contains($text, 'coza') || str_contains($text, 'dove')) {
            return 'channel';
        }

        if (str_contains($key, 'healing') || str_contains($key, 'testimon') || str_contains($key, 'crusade')) {
            return 'video';
        }

        if ($type === 'video' || $type === 'youtube_video' || $type === 'vod' || $type === 'playlist') {
            return 'video';
        }

        if ($type === 'link' && (str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be'))) {
            return 'youtube';
        }

        if ($type !== '') {
            return $type;
        }

        return 'web';
    }

    private function watchGroupForSection(string $sectionKey, string $type, string $title): string
    {
        $key = strtolower(trim($sectionKey));
        $t = strtolower(trim($type));

        if (str_contains($key, 'dunamis_live')) {
            return 'live_hls';
        }

        if (str_contains($key, 'live_stream')) {
            return 'live_youtube';
        }

        if (str_contains($key, 'commanding')) {
            return 'commanding_day';
        }

        if (str_contains($key, 'other_channels') || $t === 'channel') {
            return 'other_channels';
        }

        if (str_contains($key, 'live_services') || str_contains($key, 'healing') || str_contains($key, 'testimon') || str_contains($key, 'crusade')) {
            return 'video';
        }

        if ($t === 'video' || $t === 'youtube') {
            return 'video';
        }

        return $key !== '' ? $key : 'other_channels';
    }

    private function watchBadgeForSection(string $sectionKey, string $sectionTitle, string $title): string
    {
        $text = strtolower(trim($sectionKey . ' ' . $sectionTitle . ' ' . $title));

        if (str_contains($text, 'commanding') || str_contains($text, 'prayer')) {
            return 'PRAYER';
        }

        if (str_contains($text, 'healing')) {
            return 'HEALING';
        }

        if (str_contains($text, 'testimon')) {
            return 'TESTIMONY';
        }

        if (str_contains($text, 'crusade')) {
            return 'CRUSADE';
        }

        if (str_contains($text, 'service')) {
            return 'SERVICE';
        }

        if (str_contains($text, 'channel') || str_contains($text, 'tv')) {
            return 'CHANNEL';
        }

        if (str_contains($text, 'live') || str_contains($text, 'stream')) {
            return 'LIVE';
        }

        return 'WATCH';
    }

    private function resolveWatchFromLegacyWatchLinks(int $appId, array $out): array
    {
        if (! Schema::hasTable('watch_links')) {
            return $out;
        }

        $cols = [];
        foreach (['id', 'app_id', 'group', 'type', 'title', 'subtitle', 'thumbnail_url', 'url', 'sort_order', 'is_enabled', 'meta_json'] as $c) {
            if (Schema::hasColumn('watch_links', $c)) {
                $cols[] = $c;
            }
        }

        if (! in_array('url', $cols, true) || ! in_array('title', $cols, true)) {
            return $out;
        }

        $query = DB::table('watch_links')->select($cols);

        if (in_array('app_id', $cols, true)) {
            $query->where('app_id', $appId);
        }

        if (in_array('is_enabled', $cols, true)) {
            $query->where('is_enabled', 1);
        }

        $query->orderBy(in_array('sort_order', $cols, true) ? 'sort_order' : 'id');

        $rows = $query->get();

        $items = [];
        $groups = [];

        foreach ($rows as $r) {
            $a = (array) $r;
            $meta = $this->decodeJson($a['meta_json'] ?? null) ?? [];

            $title = trim((string) ($a['title'] ?? ''));
            $url = trim((string) ($a['url'] ?? ''));
            $subtitle = trim((string) ($a['subtitle'] ?? ($meta['subtitle'] ?? '')));
            $rawType = strtolower(trim((string) ($a['type'] ?? '')));
            $player = strtolower(trim((string) ($meta['player'] ?? '')));
            $group = trim((string) ($a['group'] ?? ($meta['group'] ?? '')));
            $imageUrl = trim((string) ($a['thumbnail_url'] ?? ($meta['image_url'] ?? '')));

            $type = $this->normalizeWatchType($rawType, $player, $url);

            $item = [
                'id' => (int) ($a['id'] ?? 0),
                'group' => $group !== '' ? $group : null,
                'raw_type' => $rawType !== '' ? $rawType : null,
                'type' => $type,
                'title' => $title,
                'subtitle' => $subtitle !== '' ? $subtitle : null,
                'thumbnail_url' => $imageUrl !== '' ? $imageUrl : null,
                'image_url' => $imageUrl !== '' ? $imageUrl : null,
                'url' => $url !== '' ? $url : null,
                'sort_order' => (int) ($a['sort_order'] ?? 0),
                'meta' => $meta ?: null,
            ];

            $items[] = $item;

            if ($title === '' || $url === '') {
                continue;
            }

            $simple = [
                'title' => $title,
                'subtitle' => $subtitle,
                'type' => $type,
                'url' => $url,
                'image_url' => $imageUrl,
            ];

            if ($type === 'live_hls' && $out['live_hls'] === null) {
                $out['live_hls'] = $simple;
                $out['primary_stream_url'] = $url;
                continue;
            }

            if ($type === 'live_youtube' && $out['live_youtube'] === null) {
                $out['live_youtube'] = $simple;
                continue;
            }

            if ($type === 'commanding_day' && $out['commanding_day'] === null) {
                $out['commanding_day'] = $simple;
                continue;
            }

            $groupKey = $group !== '' ? $group : $this->inferWatchGroupKey($rawType, $type, $title);

            $groups[$groupKey] ??= [];
            $groups[$groupKey][] = $simple;
        }

        $out['items'] = $items;
        $out['groups'] = $groups;
        $out['videos'] = $groups['video'] ?? [];
        $out['other_channels'] = $groups['other_channels'] ?? [];

        if ($out['live_hls'] && empty($out['primary_stream_url'])) {
            $out['primary_stream_url'] = $out['live_hls']['url'] ?? null;
        }

        return $out;
    }

    private function normalizeWatchType(string $rawType, string $player, string $url): string
    {
        $t = strtolower(trim($rawType));
        $p = strtolower(trim($player));
        $u = strtolower(trim($url));

        if ($t === 'commanding_day' || str_contains($t, 'commanding')) {
            return 'commanding_day';
        }

        if ($t === 'live_hls' || $p === 'hls' || str_ends_with($u, '.m3u8')) {
            return 'live_hls';
        }

        if ($t === 'live_youtube') {
            return 'live_youtube';
        }

        if ($t === 'channel') {
            return 'channel';
        }

        if (in_array($t, ['video', 'youtube_video', 'vod', 'playlist'], true)) {
            return 'video';
        }

        if ($p === 'youtube' || str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be')) {
            return 'youtube';
        }

        return $t !== '' ? $t : 'web';
    }

    private function inferWatchGroupKey(string $rawType, string $type, string $title): string
    {
        $t = strtolower(trim($rawType));
        $s = strtolower(trim($title));

        if ($type === 'channel' || $t === 'channel') {
            return 'other_channels';
        }

        if ($type === 'video' || in_array($t, ['video', 'youtube_video', 'vod', 'playlist'], true)) {
            return 'video';
        }

        if ($type === 'commanding_day' || str_contains($s, 'commanding the day')) {
            return 'commanding_day';
        }

        if ($type === 'live_hls') {
            return 'live_hls';
        }

        if ($type === 'live_youtube') {
            return 'live_youtube';
        }

        return $type !== '' ? $type : 'other_channels';
    }

    private function resolveHomeForApp(int $appId, array $branding, array $watch): array
    {
        $bannerUrl = trim((string) data_get($branding, 'assets.banner_url', ''));
        $appName = trim((string) data_get($branding, 'profile.display_name', 'Dunamis TV'));
        $tagline = trim((string) data_get($branding, 'profile.tagline', 'Watch, learn and get inspired'));

        $sections = $this->getHomeSectionsWithItems($appId);

        $bannerSection = $sections['home_banners'] ?? null;
        $shortcutSection = $sections['home_quick_access'] ?? $sections['home_shortcuts'] ?? null;
        $legacyDailySection = $sections['home_daily'] ?? null;
        $scriptureSection = $sections['home_daily_scripture'] ?? null;
        $quoteSection = $sections['home_daily_quote'] ?? null;
        $prayerSection = $sections['home_prayer_broadcast'] ?? null;

        $heroSlides = $this->mapHomeBannerSlides($bannerSection, $appName, $tagline, $bannerUrl);
        $daily = $this->mapHomeDaily($legacyDailySection, $scriptureSection, $quoteSection);
        $shortcuts = $this->mapHomeShortcuts($shortcutSection, $watch, $bannerUrl);
        $prayerBroadcast = $this->mapHomePrayerBroadcast($prayerSection, $watch, $bannerUrl);

        $dailyQuoteItems = $this->resolveDailyQuoteItemsForHome($appId, $daily['daily_quote']);
        $dailyScriptureItems = $this->resolveDailyScriptureItemsForHome($appId, $daily['daily_scripture']);

        return [
            'hero' => [
                'slides' => $heroSlides,
            ],
            'shortcuts' => $shortcuts,
            'prayer_broadcast' => $prayerBroadcast,
            'daily_scripture' => $daily['daily_scripture'],
            'daily_quote' => $daily['daily_quote'],
            'daily_scripture_items' => $dailyScriptureItems,
            'daily_quote_items' => $dailyQuoteItems,
            'internal_promos' => [],
        ];
    }

    private function getHomeSectionsWithItems(int $appId): array
    {
        if (! Schema::hasTable('app_sections') || ! Schema::hasTable('app_items')) {
            return [];
        }

        $sections = DB::table('app_sections')
            ->where('app_id', $appId)
            ->where('tab_key', 'home')
            ->where('is_enabled', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($sections->isEmpty()) {
            return [];
        }

        $sectionIds = $sections->pluck('id')->all();

        $items = DB::table('app_items')
            ->whereIn('section_id', $sectionIds)
            ->where('is_enabled', 1)
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('section_id');

        $out = [];
        foreach ($sections as $section) {
            $a = (array) $section;
            $sectionItems = [];

            foreach (($items[$section->id] ?? collect()) as $item) {
                $i = (array) $item;
                $sectionItems[] = [
                    'id' => (int) ($i['id'] ?? 0),
                    'title' => (string) ($i['title'] ?? ''),
                    'subtitle' => $i['subtitle'] ?? null,
                    'icon' => $i['icon'] ?? null,
                    'image_url' => $i['image_url'] ?? null,
                    'image' => $i['image_url'] ?? null,
                    'route' => $i['route'] ?? null,
                    'url' => $i['url'] ?? null,
                    'sort_order' => (int) ($i['sort_order'] ?? 0),
                    'payload' => $this->decodeJson($i['payload_json'] ?? null),
                ];
            }

            $key = (string) ($a['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $out[$key] = [
                'id' => (int) ($a['id'] ?? 0),
                'key' => $key,
                'title' => (string) ($a['title'] ?? ''),
                'subtitle' => $a['subtitle'] ?? null,
                'template' => (string) ($a['template'] ?? ''),
                'items' => $sectionItems,
            ];
        }

        return $out;
    }

    private function mapHomeBannerSlides(?array $section, string $appName, string $tagline, string $bannerUrl): array
    {
        $items = $section['items'] ?? [];

        $slides = [];
        foreach ($items as $item) {
            $imageUrl = trim((string) ($item['image_url'] ?? ''));
            if ($imageUrl === '') {
                continue;
            }

            $slides[] = [
                'title' => trim((string) ($item['title'] ?? '')) ?: $appName,
                'subtitle' => trim((string) ($item['subtitle'] ?? '')) ?: $tagline,
                'image_url' => $imageUrl,
                'image' => $imageUrl,
                'route' => $item['route'] ?? null,
                'url' => $item['url'] ?? null,
                'payload' => $item['payload'] ?? null,
            ];
        }

        if (! empty($slides)) {
            return $slides;
        }

        return $bannerUrl !== '' ? [[
            'title' => $appName,
            'subtitle' => $tagline,
            'image_url' => $bannerUrl,
            'image' => $bannerUrl,
            'route' => null,
            'url' => null,
            'payload' => null,
        ]] : [];
    }

    private function mapHomeDaily(?array $legacyDailySection, ?array $scriptureSection, ?array $quoteSection): array
    {
        $emptyDesign = [
            'mode' => 'manual',
            'text_color' => '#ffffff',
            'bg_color' => '#5b0aa8',
            'background_color' => '#5b0aa8',
            'accent_color' => '#ff4fb8',
            'font_size' => '18',
            'title_size' => '24',
            'font_weight' => '700',
            'text_align' => 'center',
            'overlay_strength' => '55',
            'use_solid_bg' => false,
            'use_solid_background' => false,
        ];

        $empty = [
            'daily_scripture' => array_merge($emptyDesign, [
                'title' => 'Daily Scripture',
                'ref' => '',
                'reference' => '',
                'verse' => '',
                'note' => '',
                'image_url' => '',
                'image' => '',
            ]),
            'daily_quote' => array_merge($emptyDesign, [
                'title' => 'Daily Quote',
                'quote' => '',
                'source' => '',
                'image_url' => '',
                'image' => '',
            ]),
        ];

        $scripture = $empty['daily_scripture'];
        $quote = $empty['daily_quote'];

        $sectionsToScan = array_filter([$legacyDailySection, $scriptureSection, $quoteSection]);

        foreach ($sectionsToScan as $section) {
            $items = collect($section['items'] ?? [])->sortByDesc('id')->values();

            foreach ($items as $item) {
                $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
                $homeKind = strtolower(trim((string) ($payload['home_kind'] ?? '')));
                $title = strtolower(trim((string) ($item['title'] ?? '')));
                $sectionKey = strtolower(trim((string) ($section['key'] ?? '')));
                $imageUrl = trim((string) ($item['image_url'] ?? ''));

                $design = [
                    'mode' => trim((string) ($payload['mode'] ?? 'manual')),
                    'text_color' => trim((string) ($payload['text_color'] ?? '#ffffff')),
                    'bg_color' => trim((string) ($payload['bg_color'] ?? '#5b0aa8')),
                    'background_color' => trim((string) ($payload['bg_color'] ?? $payload['background_color'] ?? '#5b0aa8')),
                    'accent_color' => trim((string) ($payload['accent_color'] ?? '#ff4fb8')),
                    'font_size' => (string) ($payload['font_size'] ?? '18'),
                    'title_size' => (string) ($payload['title_size'] ?? '24'),
                    'font_weight' => (string) ($payload['font_weight'] ?? '700'),
                    'text_align' => trim((string) ($payload['text_align'] ?? 'center')),
                    'overlay_strength' => (string) ($payload['overlay_strength'] ?? '55'),
                    'use_solid_bg' => (bool) ($payload['use_solid_bg'] ?? false),
                    'use_solid_background' => (bool) ($payload['use_solid_bg'] ?? $payload['use_solid_background'] ?? false),
                ];

                if (
                    $homeKind === 'daily_scripture'
                    || str_contains($title, 'scripture')
                    || str_contains($sectionKey, 'scripture')
                ) {
                    $scripture = array_merge($design, [
                        'title' => trim((string) ($item['title'] ?? 'Daily Scripture')) ?: 'Daily Scripture',
                        'ref' => trim((string) ($payload['ref'] ?? $payload['reference'] ?? '')),
                        'reference' => trim((string) ($payload['ref'] ?? $payload['reference'] ?? '')),
                        'verse' => trim((string) ($payload['verse'] ?? $payload['text'] ?? $payload['content'] ?? '')),
                        'note' => trim((string) ($payload['note'] ?? $payload['caption'] ?? ($item['subtitle'] ?? ''))),
                        'image_url' => $imageUrl,
                        'image' => $imageUrl,
                    ]);

                    continue;
                }

                if (
                    $homeKind === 'daily_quote'
                    || str_contains($title, 'quote')
                    || str_contains($sectionKey, 'quote')
                ) {
                    $quote = array_merge($design, [
                        'title' => trim((string) ($item['title'] ?? 'Daily Quote')) ?: 'Daily Quote',
                        'quote' => trim((string) ($payload['quote'] ?? $payload['text'] ?? $payload['content'] ?? '')),
                        'source' => trim((string) ($payload['source'] ?? $payload['author'] ?? ($item['subtitle'] ?? ''))),
                        'image_url' => $imageUrl,
                        'image' => $imageUrl,
                    ]);
                }
            }
        }

        return [
            'daily_scripture' => $scripture,
            'daily_quote' => $quote,
        ];
    }


    private function resolveDailyQuoteItemsForHome(int $appId, array $fallbackQuote): array
    {
        if (! Schema::hasTable('content_posts')) {
            return $this->normalizeDailyItemsFallback($fallbackQuote, 'daily_quote');
        }

        $fixedBuckets = [
            'daily_quotes',
            'sod_quotes',
            'motivational_quotes',
            'motivation_quotes',
            'article_quotes',
            'custom_quotes',
        ];

        $posts = \App\Models\ContentPost::query()
            ->where('app_id', $appId)
            ->where('status', 'published')
            ->where(function ($q) use ($fixedBuckets) {
                $q->whereIn('bucket', $fixedBuckets)
                  ->orWhere('bucket', 'like', 'quote_%');
            })
            ->where(function ($q) {
                $q->whereNull('publish_at')
                  ->orWhere('publish_at', '<=', now());
            })
            ->latest('id')
            ->limit(80)
            ->get();

        $items = [];

        foreach ($posts as $post) {
            $bucket = strtolower(trim((string) $post->bucket));
            $isDailyBucket = $bucket === 'daily_quotes';

            if (! $isDailyBucket && ! $this->postShouldFeatureInDailyQuote($post)) {
                continue;
            }

            $card = $this->contentPostToDailyQuoteCard($post);
            if (trim((string) ($card['quote'] ?? '')) === '') {
                continue;
            }

            $this->appendUniqueDailyCard($items, $card);

            if (count($items) >= 10) {
                break;
            }
        }

        if (empty($items)) {
            return $this->normalizeDailyItemsFallback($fallbackQuote, 'daily_quote');
        }

        return $items;
    }

    private function resolveDailyScriptureItemsForHome(int $appId, array $fallbackScripture): array
    {
        if (! Schema::hasTable('content_posts')) {
            return $this->normalizeDailyItemsFallback($fallbackScripture, 'daily_scripture');
        }

        $posts = \App\Models\ContentPost::query()
            ->where('app_id', $appId)
            ->where('bucket', 'daily_scriptures')
            ->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('publish_at')
                  ->orWhere('publish_at', '<=', now());
            })
            ->orderByDesc('is_featured')
            ->latest('id')
            ->limit(10)
            ->get();

        $items = [];

        foreach ($posts as $post) {
            $card = $this->contentPostToDailyScriptureCard($post);
            if (trim((string) ($card['verse'] ?? '')) === '') {
                continue;
            }

            $this->appendUniqueDailyCard($items, $card);
        }

        if (empty($items)) {
            return $this->normalizeDailyItemsFallback($fallbackScripture, 'daily_scripture');
        }

        return $items;
    }

    private function postShouldFeatureInDailyQuote(\App\Models\ContentPost $post): bool
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];

        return (bool) $post->is_featured
            || $this->truthy(data_get($meta, 'show_in_daily_quote'))
            || $this->truthy(data_get($meta, 'show_in_daily_carousel'))
            || $this->truthy(data_get($meta, 'featured_daily'))
            || $this->truthy(data_get($meta, 'daily_featured'));
    }

    private function contentPostToDailyQuoteCard(\App\Models\ContentPost $post): array
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];
        $quote = $this->firstNonEmptyString([
            data_get($meta, 'quote_text'),
            data_get($meta, 'quote'),
            data_get($meta, 'text'),
            data_get($meta, 'content'),
            trim(strip_tags((string) $post->body_html)),
            $post->title,
        ]);

        $source = $this->firstNonEmptyString([
            data_get($meta, 'quote_source'),
            data_get($meta, 'source'),
            data_get($meta, 'author'),
            data_get($meta, 'reference'),
            $post->subtitle,
        ]);

        $card = $this->dailyDesignFromPostMeta($post, $meta);

        return array_merge($card, [
            'id' => (int) $post->id,
            'post_id' => (int) $post->id,
            'bucket' => (string) $post->bucket,
            'type' => 'daily_quote',
            'title' => $this->firstNonEmptyString([data_get($meta, 'display_title'), $post->title, 'Daily Quote']),
            'quote' => $quote,
            'text' => $quote,
            'source' => $source,
            'quote_source' => $source,
            'source_title' => $source,
            'payload' => array_merge($meta, [
                'id' => (int) $post->id,
                'post_id' => (int) $post->id,
                'bucket' => (string) $post->bucket,
                'quote' => $quote,
                'quote_text' => $quote,
                'source' => $source,
                'quote_source' => $source,
            ]),
        ]);
    }

    private function contentPostToDailyScriptureCard(\App\Models\ContentPost $post): array
    {
        $meta = is_array($post->meta_json) ? $post->meta_json : [];
        $verse = $this->firstNonEmptyString([
            data_get($meta, 'quote_text'),
            data_get($meta, 'verse'),
            data_get($meta, 'scripture'),
            data_get($meta, 'text'),
            data_get($meta, 'content'),
            trim(strip_tags((string) $post->body_html)),
            $post->title,
        ]);

        $reference = $this->firstNonEmptyString([
            data_get($meta, 'reference'),
            data_get($meta, 'ref'),
            data_get($meta, 'quote_source'),
            data_get($meta, 'source'),
            $post->subtitle,
        ]);

        $card = $this->dailyDesignFromPostMeta($post, $meta);

        return array_merge($card, [
            'id' => (int) $post->id,
            'post_id' => (int) $post->id,
            'bucket' => (string) $post->bucket,
            'type' => 'daily_scripture',
            'title' => $this->firstNonEmptyString([data_get($meta, 'display_title'), $post->title, 'Daily Scripture']),
            'verse' => $verse,
            'quote' => $verse,
            'text' => $verse,
            'ref' => $reference,
            'reference' => $reference,
            'source' => $reference,
            'payload' => array_merge($meta, [
                'id' => (int) $post->id,
                'post_id' => (int) $post->id,
                'bucket' => (string) $post->bucket,
                'verse' => $verse,
                'quote_text' => $verse,
                'reference' => $reference,
                'ref' => $reference,
            ]),
        ]);
    }

    private function dailyDesignFromPostMeta(\App\Models\ContentPost $post, array $meta): array
    {
        $imageUrl = $this->firstNonEmptyString([
            data_get($meta, 'image_url'),
            data_get($meta, 'background_image_url'),
            data_get($meta, 'background_image'),
            data_get($meta, 'design.background_image_url'),
            $post->cover_image_src,
        ]);

        $gradientStart = $this->firstNonEmptyString([
            data_get($meta, 'gradient_start'),
            data_get($meta, 'gradient_from'),
            data_get($meta, 'design.gradient_start'),
        ]);

        $gradientEnd = $this->firstNonEmptyString([
            data_get($meta, 'gradient_end'),
            data_get($meta, 'gradient_to'),
            data_get($meta, 'design.gradient_end'),
        ]);

        $backgroundColor = $this->firstNonEmptyString([
            data_get($meta, 'background_color'),
            data_get($meta, 'bg_color'),
            data_get($meta, 'solid_color'),
            data_get($meta, 'design.background_color'),
            '#5b0aa8',
        ]);

        return [
            'mode' => (string) $this->firstNonEmptyString([data_get($meta, 'mode'), data_get($meta, 'design.mode'), 'manual']),
            'background_mode' => (string) $this->firstNonEmptyString([data_get($meta, 'background_mode'), data_get($meta, 'design.background_mode'), $imageUrl !== '' ? 'image' : ($gradientStart !== '' && $gradientEnd !== '' ? 'gradient' : 'solid')]),
            'image_url' => $imageUrl,
            'image' => $imageUrl,
            'background_image_url' => $imageUrl,
            'bg_color' => $backgroundColor,
            'background_color' => $backgroundColor,
            'gradient_start' => $gradientStart,
            'gradient_end' => $gradientEnd,
            'accent_color' => (string) $this->firstNonEmptyString([data_get($meta, 'accent_color'), data_get($meta, 'highlight_color'), '#ff4fb8']),
            'text_color' => (string) $this->firstNonEmptyString([data_get($meta, 'text_color'), data_get($meta, 'quote_color'), '#ffffff']),
            'source_color' => (string) $this->firstNonEmptyString([data_get($meta, 'source_color'), data_get($meta, 'reference_color'), data_get($meta, 'text_color'), '#ffffff']),
            'highlight_phrases' => data_get($meta, 'highlight_phrases') ?? data_get($meta, 'highlight_words') ?? [],
            'highlight_color' => (string) $this->firstNonEmptyString([data_get($meta, 'highlight_color'), data_get($meta, 'accent_color'), '#ff4fb8']),
            'highlight_size' => (string) $this->firstNonEmptyString([data_get($meta, 'highlight_size'), data_get($meta, 'highlight_size_boost'), '0']),
            'highlight_size_boost' => (string) $this->firstNonEmptyString([data_get($meta, 'highlight_size_boost'), data_get($meta, 'highlight_size'), '0']),
            'font_family' => (string) $this->firstNonEmptyString([data_get($meta, 'font_family'), data_get($meta, 'font'), '']),
            'font_size' => (string) $this->firstNonEmptyString([data_get($meta, 'font_size'), data_get($meta, 'quote_size'), '18']),
            'quote_size' => (string) $this->firstNonEmptyString([data_get($meta, 'quote_size'), data_get($meta, 'font_size'), '18']),
            'source_size' => (string) $this->firstNonEmptyString([data_get($meta, 'source_size'), data_get($meta, 'reference_size'), '13']),
            'title_size' => (string) $this->firstNonEmptyString([data_get($meta, 'title_size'), data_get($meta, 'quote_size'), '24']),
            'font_weight' => (string) $this->firstNonEmptyString([data_get($meta, 'font_weight'), data_get($meta, 'quote_weight'), '700']),
            'source_weight' => (string) $this->firstNonEmptyString([data_get($meta, 'source_weight'), data_get($meta, 'reference_weight'), '600']),
            'text_align' => (string) $this->firstNonEmptyString([data_get($meta, 'text_align'), data_get($meta, 'align'), 'center']),
            'vertical_align' => (string) $this->firstNonEmptyString([data_get($meta, 'vertical_align'), data_get($meta, 'content_align'), 'center']),
            'overlay_strength' => (string) $this->firstNonEmptyString([data_get($meta, 'overlay_strength'), '0']),
            'use_solid_bg' => $this->truthy(data_get($meta, 'use_solid_bg')),
            'use_solid_background' => $this->truthy(data_get($meta, 'use_solid_background')),
            'card_padding' => (string) $this->firstNonEmptyString([data_get($meta, 'card_padding'), data_get($meta, 'padding'), '34']),
            'card_padding_x' => (string) $this->firstNonEmptyString([data_get($meta, 'card_padding_x'), data_get($meta, 'padding_x'), data_get($meta, 'card_padding'), '34']),
            'card_padding_y' => (string) $this->firstNonEmptyString([data_get($meta, 'card_padding_y'), data_get($meta, 'padding_y'), data_get($meta, 'card_padding'), '34']),
            'padding_x' => (string) $this->firstNonEmptyString([data_get($meta, 'padding_x'), data_get($meta, 'card_padding_x'), data_get($meta, 'card_padding'), '34']),
            'padding_y' => (string) $this->firstNonEmptyString([data_get($meta, 'padding_y'), data_get($meta, 'card_padding_y'), data_get($meta, 'card_padding'), '34']),
            'content_width' => (string) $this->firstNonEmptyString([data_get($meta, 'content_width'), '88']),
            'text_shadow' => (string) $this->firstNonEmptyString([data_get($meta, 'text_shadow'), 'none']),
        ];
    }

    private function normalizeDailyItemsFallback(array $card, string $type): array
    {
        $text = $type === 'daily_scripture'
            ? trim((string) ($card['verse'] ?? $card['quote'] ?? $card['text'] ?? ''))
            : trim((string) ($card['quote'] ?? $card['text'] ?? ''));

        if ($text === '') {
            return [];
        }

        $card['type'] = $type;
        $card['id'] = $card['id'] ?? 0;
        return [$card];
    }

    private function appendUniqueDailyCard(array &$items, array $card): void
    {
        $signature = strtolower(trim((string) ($card['bucket'] ?? ''))) . '|' . (string) ($card['id'] ?? '') . '|' . strtolower(trim((string) ($card['quote'] ?? $card['verse'] ?? $card['text'] ?? '')));

        foreach ($items as $existing) {
            $existingSignature = strtolower(trim((string) ($existing['bucket'] ?? ''))) . '|' . (string) ($existing['id'] ?? '') . '|' . strtolower(trim((string) ($existing['quote'] ?? $existing['verse'] ?? $existing['text'] ?? '')));
            if ($existingSignature === $signature) {
                return;
            }
        }

        $items[] = $card;
    }

    private function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on', 'enabled', 'active'], true);
        }

        return false;
    }

    private function mapHomeShortcuts(?array $section, array $watch, string $bannerUrl): array
    {
        $primaryLiveTitle = trim((string) data_get($watch, 'live_hls.title', 'Dunamis TV Live'));

        $fallback = [
            'live' => [
                'title' => $primaryLiveTitle !== '' ? $primaryLiveTitle : 'Dunamis TV Live',
                'subtitle' => 'Watch live broadcast and streaming content',
                'image_url' => $bannerUrl,
                'image' => $bannerUrl,
                'route' => '/watch',
            ],
            'sod' => [
                'title' => 'Seeds Of Destiny',
                'subtitle' => 'Read today’s devotional and key spiritual insights',
                'image_url' => $bannerUrl,
                'image' => $bannerUrl,
                'route' => '/inspire/sod',
            ],
            'articles' => [
                'title' => 'Inside Dunamis',
                'subtitle' => 'Read featured articles and ministry updates',
                'image_url' => $bannerUrl,
                'image' => $bannerUrl,
                'route' => '/inspire/articles',
            ],
            'highlights' => [
                'title' => 'Message Highlight',
                'subtitle' => 'Catch short message summaries and spiritual takeaways',
                'image_url' => $bannerUrl,
                'image' => $bannerUrl,
                'route' => '/inspire/highlights',
            ],
        ];

        if (! $section || empty($section['items'])) {
            return $fallback;
        }

        $mapped = [];

        foreach ($section['items'] as $item) {
            $payload = is_array($item['payload'] ?? null) ? $item['payload'] : [];
            $kind = strtolower(trim((string) ($payload['home_kind'] ?? '')));
            $title = strtolower(trim((string) ($item['title'] ?? '')));
            $route = strtolower(trim((string) ($item['route'] ?? '')));

            if ($kind === '') {
                if (str_contains($title, 'seed') || str_contains($title, 'sod') || str_contains($route, 'sod')) {
                    $kind = 'sod';
                } elseif (str_contains($title, 'article') || str_contains($title, 'inside') || str_contains($route, 'article')) {
                    $kind = 'articles';
                } elseif (str_contains($title, 'highlight') || str_contains($route, 'highlight')) {
                    $kind = 'highlights';
                } elseif (str_contains($title, 'live') || $route === '/watch' || str_contains($route, 'watch')) {
                    $kind = 'live';
                }
            }

            if (! in_array($kind, ['live', 'sod', 'articles', 'highlights'], true)) {
                continue;
            }

            $imageUrl = trim((string) ($item['image_url'] ?? ''));

            $mapped[$kind] = [
                'title' => trim((string) ($item['title'] ?? '')) ?: $fallback[$kind]['title'],
                'subtitle' => trim((string) ($item['subtitle'] ?? '')) ?: $fallback[$kind]['subtitle'],
                'image_url' => $imageUrl !== '' ? $imageUrl : $fallback[$kind]['image_url'],
                'image' => $imageUrl !== '' ? $imageUrl : $fallback[$kind]['image_url'],
                'route' => trim((string) ($item['route'] ?? '')) ?: $fallback[$kind]['route'],
                'url' => $item['url'] ?? null,
            ];
        }

        return [
            'live' => $mapped['live'] ?? $fallback['live'],
            'sod' => $mapped['sod'] ?? $fallback['sod'],
            'articles' => $mapped['articles'] ?? $fallback['articles'],
            'highlights' => $mapped['highlights'] ?? $fallback['highlights'],
        ];
    }

    private function mapHomePrayerBroadcast(?array $section, array $watch, string $bannerUrl): array
    {
        $fallback = [
            'title' => (string) data_get($watch, 'commanding_day.title', 'Commanding The Day Prayer Broadcast'),
            'subtitle' => 'Open the current prayer stream from Watch.',
            'image_url' => (string) data_get($watch, 'commanding_day.image_url', $bannerUrl),
            'image' => (string) data_get($watch, 'commanding_day.image_url', $bannerUrl),
            'route' => '/watch',
            'url' => data_get($watch, 'commanding_day.url'),
        ];

        if (! $section || empty($section['items'])) {
            return $fallback;
        }

        $item = $section['items'][0];
        $imageUrl = trim((string) ($item['image_url'] ?? ''));

        return [
            'title' => trim((string) ($item['title'] ?? '')) ?: $fallback['title'],
            'subtitle' => trim((string) ($item['subtitle'] ?? '')) ?: $fallback['subtitle'],
            'image_url' => $imageUrl !== '' ? $imageUrl : $fallback['image_url'],
            'image' => $imageUrl !== '' ? $imageUrl : $fallback['image_url'],
            'route' => trim((string) ($item['route'] ?? '')) ?: $fallback['route'],
            'url' => $item['url'] ?? $fallback['url'],
        ];
    }

    private function resolveInspireForApp(int $appId, array $branding, array $links): array
    {
        $bannerUrl = trim((string) data_get($branding, 'assets.banner_url', ''));

        $brandingInspire = data_get($branding, 'raw.inspire', []);
        $brandingInspire = is_array($brandingInspire) ? $brandingInspire : [];

        $sodReadUrl = $this->firstNonEmptyString([
            data_get($brandingInspire, 'sod_read_url'),
            data_get($brandingInspire, 'read_sod_url'),
            data_get($links, 'website.url'),
            'https://seedsofdestinyonline.org/',
        ]);

        $sodWatchUrl = $this->firstNonEmptyString([
            data_get($brandingInspire, 'sod_watch_url'),
            data_get($brandingInspire, 'watch_sod_url'),
            data_get($links, 'youtube.url'),
            'https://www.youtube.com/@DunamisTVLive/playlists',
        ]);

        return [
            'titles' => [
                'featured_title' => (string) data_get($brandingInspire, 'featured_title', 'Featured'),
                'featured_subtitle' => (string) data_get($brandingInspire, 'featured_subtitle', 'Browse featured content'),
                'all_title' => (string) data_get($brandingInspire, 'all_title', 'All Content'),
                'all_subtitle' => (string) data_get($brandingInspire, 'all_subtitle', 'Browse all available content'),
            ],

            'sod_banner' => [
                'title' => (string) data_get($brandingInspire, 'sod_banner.title', 'Seed of Destiny'),
                'subtitle' => (string) data_get($brandingInspire, 'sod_banner.subtitle', 'Daily devotion • Watch • Quotes'),
                'image_url' => (string) data_get($brandingInspire, 'sod_banner.image_url', $bannerUrl),
                'pill_text' => (string) data_get($brandingInspire, 'sod_banner.pill_text', 'SEED OF DESTINY'),
            ],

            'sod_read_url' => $sodReadUrl,
            'sod_watch_url' => $sodWatchUrl,

            'sod_cards' => [
                [
                    'id' => 'sod-read',
                    'title' => 'Read Seed of Destiny',
                    'subtitle' => 'Open today’s devotional reading',
                    'badge' => 'READ',
                    'type' => 'web',
                    'url' => $sodReadUrl,
                    'image_url' => (string) data_get($brandingInspire, 'sod_cards.read.image_url', $bannerUrl),
                ],
                [
                    'id' => 'sod-watch',
                    'title' => 'Watch Seed of Destiny',
                    'subtitle' => 'Open the available video or playlist',
                    'badge' => 'WATCH',
                    'type' => 'youtube',
                    'url' => $sodWatchUrl,
                    'image_url' => (string) data_get($brandingInspire, 'sod_cards.watch.image_url', $bannerUrl),
                ],
                [
                    'id' => 'sod-quotes',
                    'title' => 'SOD Quote',
                    'subtitle' => 'Browse quotes and written inspiration',
                    'badge' => 'QUOTE',
                    'type' => 'route',
                    'bucket' => 'sod_quotes',
                    'route' => '/sod/quotes',
                    'image_url' => (string) data_get($brandingInspire, 'sod_cards.quote.image_url', $bannerUrl),
                ],
            ],
        ];
    }

    private function firstNonEmptyString(array $values, string $fallback = ''): string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return $fallback;
    }

    private function presetsCatalog(): array
    {
        return [
            'section_templates' => [
                'carousel',
                'featured_grid',
                'horizontal_cards',
                'vertical_list',
                'quote_strip',
                'highlights_list',
                'cta',
                'mixed_feed',
            ],
            'content_blocks' => [
                'paragraph',
                'heading',
                'image',
                'quote',
                'divider',
                'list',
                'embed',
                'scripture',
                'keypoints',
                'callout',
            ],
        ];
    }

    private function resolveLinksForApp(int $appId): array
    {
        if (! Schema::hasTable('app_sections') || ! Schema::hasTable('app_items')) {
            return [];
        }

        if (! Schema::hasColumn('app_sections', 'id') || ! Schema::hasColumn('app_sections', 'app_id')) {
            return [];
        }

        if (! Schema::hasColumn('app_items', 'section_id') || ! Schema::hasColumn('app_items', 'type')) {
            return [];
        }

        $itemsOrderCol = Schema::hasColumn('app_items', 'sort_order')
            ? 'sort_order'
            : (Schema::hasColumn('app_items', 'order') ? 'order' : 'id');

        $q = DB::table('app_items')
            ->join('app_sections', 'app_sections.id', '=', 'app_items.section_id')
            ->where('app_sections.app_id', $appId)
            ->where('app_items.type', 'link');

        if (Schema::hasColumn('app_items', 'is_enabled')) {
            $q->where('app_items.is_enabled', 1);
        }

        $rows = $q->orderBy('app_items.' . $itemsOrderCol)
            ->select([
                'app_items.id',
                'app_items.title',
                'app_items.subtitle',
                'app_items.route',
                'app_items.url',
                'app_items.payload_json',
            ])
            ->get();

        $out = [];

        foreach ($rows as $r) {
            $a = (array) $r;

            $title = (string) ($a['title'] ?? '');
            $route = (string) ($a['route'] ?? '');
            $url = (string) ($a['url'] ?? '');

            $key = $this->guessLinkKey($title, $route, $url);
            if ($key === '') {
                $key = $this->slugKey($title);
            }
            if ($key === '') {
                continue;
            }

            $out[$key] = [
                'title' => $title !== '' ? $title : ucfirst(str_replace('_', ' ', $key)),
                'url' => $url !== '' ? $url : null,
                'route' => $route !== '' ? $route : null,
            ];
        }

        unset($out['donation'], $out['giving'], $out['give']);

        return $out;
    }

    private function guessLinkKey(string $title, string $route, string $url): string
    {
        $hay = strtolower(trim($title . ' ' . $route . ' ' . $url));

        if (str_contains($hay, 'privacy')) return 'privacy';
        if (str_contains($hay, 'terms') || str_contains($hay, 'conditions')) return 'terms';
        if (str_contains($hay, 'support') || str_contains($hay, 'help')) return 'support';
        if (str_contains($hay, 'website') || ($url !== '' && str_contains($url, 'http'))) return 'website';
        if (str_contains($hay, 'facebook')) return 'facebook';
        if (str_contains($hay, 'instagram')) return 'instagram';
        if (str_contains($hay, 'youtube')) return 'youtube';
        if (str_contains($hay, 'x.com') || str_contains($hay, 'twitter')) return 'x';
        if (str_contains($hay, 'contact')) return 'contact';

        return '';
    }

    private function slugKey(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/i', '_', $s);
        $s = trim((string) $s, '_');

        return $s ?: '';
    }
}

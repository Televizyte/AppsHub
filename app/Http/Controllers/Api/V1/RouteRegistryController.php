<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Support\Ads\AdResolver;
use App\Support\Icons\SvgIconRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RouteRegistryController extends Controller
{
    public function index(Request $request, string $appSlug)
    {
        $app = DB::table('apps')
            ->select(['id', 'name', 'slug', 'is_active'])
            ->where('slug', $appSlug)
            ->where('is_active', 1)
            ->first();

        if (!$app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive for slug: ' . $appSlug,
            ], 404);
        }

        if (!Schema::hasTable('app_tabs')) {
            return response()->json([
                'ok' => false,
                'error' => 'TABS_TABLE_MISSING',
                'message' => 'app_tabs table not found.',
            ], 500);
        }

        if (!Schema::hasTable('app_routes')) {
            return response()->json([
                'ok' => false,
                'error' => 'ROUTES_TABLE_MISSING',
                'message' => 'app_routes table not found.',
            ], 500);
        }

        /**
         * DB tabs are source of truth:
         * - key, title, icon, sort_order, is_enabled
         */
        $tabSelect = ['id'];
        foreach (['app_id', 'key', 'title', 'icon', 'sort_order', 'is_enabled', 'meta_json'] as $c) {
            if (Schema::hasColumn('app_tabs', $c)) {
                $tabSelect[] = $c;
            }
        }

        $tabRows = DB::table('app_tabs')
            ->select($tabSelect)
            ->where('app_id', (int) $app->id)
            ->orderBy(Schema::hasColumn('app_tabs', 'sort_order') ? 'sort_order' : 'id')
            ->orderBy('id')
            ->get();

        // Build tab payload indexed by key
        $tabPayload = [];

        // DB-driven ads map (truth). Watch is OFF by default, but can be enabled later.
        $tabsAds = AdResolver::tabsAdsForApp((int) $app->id);

        foreach ($tabRows as $t) {
            $a = (array) $t;

            $tabKey = $this->cleanStr($a['key'] ?? null);
            if (!$tabKey) {
                continue;
            }

            $title = $this->cleanStr($a['title'] ?? null) ?? ucfirst($tabKey);

            // DB icon column may contain: "home", "watch", "inspire", etc.
            $iconKey = $this->cleanStr($a['icon'] ?? null) ?? $tabKey;

            $tabAdCfg = $tabsAds[$tabKey] ?? null;
            $adsEnabled = $tabAdCfg ? (bool) ($tabAdCfg['enabled'] ?? true) : true;

            $tabPayload[$tabKey] = [
                'key' => $tabKey,
                'title' => $title,
                'icon' => SvgIconRegistry::get($iconKey) ?? SvgIconRegistry::get($tabKey),
                // "allow_ads" reflects current admin-configured enablement
                'allow_ads' => $adsEnabled,
                'enabled' => isset($a['is_enabled']) ? (bool) $a['is_enabled'] : true,
                'sort_order' => isset($a['sort_order']) ? (int) $a['sort_order'] : null,
                'items' => [],
            ];
        }

        // If for any reason tabs are empty, fallback to stable defaults
        if (empty($tabPayload)) {
            foreach (['home', 'watch', 'inspire', 'explore', 'more'] as $fallbackKey) {
                $tabAdCfg = $tabsAds[$fallbackKey] ?? null;
                $adsEnabled = $tabAdCfg ? (bool) ($tabAdCfg['enabled'] ?? true) : true;

                $tabPayload[$fallbackKey] = [
                    'key' => $fallbackKey,
                    'title' => ucfirst($fallbackKey),
                    'icon' => SvgIconRegistry::get($fallbackKey),
                    'allow_ads' => $adsEnabled,
                    'enabled' => true,
                    'sort_order' => null,
                    'items' => [],
                ];
            }
        }

        /**
         * Routes
         * Table: id, app_id, key, title, path, route, tab_key, is_enabled, meta_json
         */
        $routeSelect = ['id'];
        foreach ([
            'app_id',
            'key',
            'title',
            'path',
            'route',
            'tab_key',
            'is_enabled',
            'meta_json',
            'created_at',
            'updated_at',
        ] as $c) {
            if (Schema::hasColumn('app_routes', $c)) {
                $routeSelect[] = $c;
            }
        }

        $rows = DB::table('app_routes')
            ->select($routeSelect)
            ->where('app_id', (int) $app->id)
            ->orderByRaw("CASE WHEN tab_key IS NULL OR tab_key = '' THEN 999 ELSE 0 END ASC")
            ->orderBy('tab_key')
            ->orderBy('key')
            ->get();

        foreach ($rows as $r) {
            $a = (array) $r;

            $tabKey = $this->cleanStr($a['tab_key'] ?? null);
            if (!$tabKey || !isset($tabPayload[$tabKey])) {
                continue;
            }

            $meta = $this->decodeMeta($a['meta_json'] ?? null);

            // Icon priority:
            // 1) meta_json.icon_key
            // 2) route key mapping
            // 3) tab icon
            $overrideKey = SvgIconRegistry::norm($this->cleanStr($meta['icon_key'] ?? null));
            $mappedKey = $this->routeKeyToIconKey($this->cleanStr($a['key'] ?? null), $tabKey);

            $icon =
                ($overrideKey ? SvgIconRegistry::get($overrideKey) : null)
                ?? ($mappedKey ? SvgIconRegistry::get($mappedKey) : null)
                ?? ($tabPayload[$tabKey]['icon'] ?? SvgIconRegistry::get($tabKey));

            $path = $this->cleanStr($a['path'] ?? null) ?? $this->cleanStr($a['route'] ?? null);
            $route = $this->cleanStr($a['route'] ?? null) ?? $path;

            // Screen ads can be overridden by route rules in ad_rules (scope_type=route)
            $screenAds = AdResolver::screenAdsForApp((int) $app->id, $tabKey, $this->cleanStr($a['key'] ?? null));
            $allowAds = (bool) ($screenAds['enabled'] ?? ($tabPayload[$tabKey]['allow_ads'] ?? true));

            $tabPayload[$tabKey]['items'][] = [
                'id' => (int) ($a['id'] ?? 0),
                'key' => $this->cleanStr($a['key'] ?? null),
                'title' => $this->cleanStr($a['title'] ?? null),
                'tab_key' => $tabKey,
                'path' => $path,
                'route' => $route,
                'enabled' => isset($a['is_enabled']) ? (bool) $a['is_enabled'] : true,
                'type' => null,
                'icon' => $icon,
                // DB-driven: on/off per tab + route overrides
                'allow_ads' => $allowAds,
                'meta' => !empty($meta) ? $meta : null,
                'sort_order' => null,
            ];
        }

        // Preserve DB tab ordering in output (sort_order then key)
        $tabsOut = array_values($tabPayload);
        usort($tabsOut, function ($a, $b) {
            $ao = $a['sort_order'];
            $bo = $b['sort_order'];
            if (is_int($ao) && is_int($bo) && $ao !== $bo) return $ao <=> $bo;
            if (is_int($ao) && !is_int($bo)) return -1;
            if (!is_int($ao) && is_int($bo)) return 1;
            return strcmp((string) $a['key'], (string) $b['key']);
        });

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'tabs' => $tabsOut,
        ]);
    }

    private function routeKeyToIconKey(?string $routeKey, string $tabKey): ?string
    {
        $routeKey = is_string($routeKey) ? trim($routeKey) : null;
        if (!$routeKey) return $tabKey;

        $map = [
            'watch_live' => 'watch',
            'watch_backup' => 'watch',

            'inspire_articles_list' => 'articles',
            'inspire_sod_list' => 'sod',
            'inspire_highlights_list' => 'highlights',
            'inspire_wordification_list' => 'wordification',
            'inspire_motivation_list' => 'motivation',

            'explore_quote_creator' => 'quote',
            'explore_notes' => 'notes',
            'explore_bible' => 'bible',

            'more_account' => 'user',
            'more_settings' => 'settings',
        ];

        return $map[$routeKey] ?? $tabKey;
    }

    /**
     * Handles:
     *  - null / "" / "null"
     *  - proper JSON: {"icon_key":"play"}
     *  - double-encoded JSON: "{\"icon_key\":\"play\"}"
     */
    private function decodeMeta($v): array
    {
        if (is_array($v)) return $v;
        if (!is_string($v)) return [];

        $s = trim($v);
        if ($s === '' || strtolower($s) === 'null') return [];

        $first = json_decode($s, true);

        // Normal case: already decoded to array
        if (is_array($first)) {
            return $first;
        }

        // Double-encoded case: decoded to string that itself is JSON
        if (is_string($first)) {
            $inner = trim($first);
            if ($inner !== '' && ($inner[0] ?? '') === '{') {
                $second = json_decode($inner, true);
                return is_array($second) ? $second : [];
            }
        }

        return [];
    }

    private function cleanStr($v): ?string
    {
        if (!is_string($v)) return null;
        $v = trim($v);
        return $v === '' ? null : $v;
    }
}

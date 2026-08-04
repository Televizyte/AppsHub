<?php

namespace App\Support\Ads;

use Illuminate\Support\Facades\DB;

class AdResolver
{
    public static function defaultTabPolicy(): array
    {
        return [
            'home'    => true,
            'watch'   => true,  // Watch browsing root is monetizable; active players remain protected
            'inspire' => true,
            'explore' => true,
            'more'    => true,

            // Virtual parent scopes consumed by Flutter game/quiz engines.
            // These are not bottom tabs, but AdsService resolves nested keys
            // such as game.race_of_faith from these parent policies.
            'games'   => true,
            'game'    => true,
            'quiz'    => true,
        ];
    }

    public static function defaultRoutePlacementPolicies(): array
    {
        return [
            // Home root placement surfaces. These are separate from action
            // policies so AppsHub can independently control native inventory.
            'home.native.after_quick_access' => ['enabled' => true, 'banner' => false, 'native' => true, 'interstitial' => false, 'safe' => 'recommended'],
            'home.native.quick_tools' => ['enabled' => true, 'banner' => false, 'native' => true, 'interstitial' => false, 'safe' => 'recommended'],

            // Home navigation actions. Flutter uses these exact keys before
            // falling back to the broader Home tab policy.
            'home.action.watch' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'home.action.articles' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'home.action.message_highlights' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'home.action.short_videos' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'medium'],
            'home.action.daily_scripture.quote_creator' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'home.action.daily_scripture.bible' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'home.action.daily_quote.quote_creator' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'home.action.daily_quote.notes' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'home.action.quick_tools.quote_creator' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'home.action.quick_tools.notes' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'home.action.quick_tools.bible' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'home.action.quick_tools.books' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'medium'],
            'home.action.other' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],

            // Watch browsing and navigation policies.
            'watch.native.channel_list' => ['enabled' => true, 'banner' => false, 'native' => true, 'interstitial' => false, 'safe' => 'recommended'],
            'watch.channels' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'recommended'],
            'watch.video_list' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'watch.playlist_list' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'watch.player.hls' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'watch.player.youtube' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'watch.player.web_embed' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'watch.player.live' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'watch.action.open_channels' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'watch.action.open_video_collection' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'medium'],
            'watch.action.open_playlist' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'medium'],
            'watch.action.open_live_hls' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'watch.action.open_youtube' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'watch.action.open_web_embed' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'watch.action.other' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],

            // Canonical Shorts engine policy. Source-specific routes such as
            // home:short_videos and inspire:short_videos may still override it.
            'shorts' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'shorts.general' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],

            // Canonical Inspire screens and actions consumed by Flutter.
            'inspire.sod' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'inspire.sod.quotes' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.sod.read' => ['enabled' => true, 'banner' => true, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'inspire.sod.watch' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'inspire.highlights' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.articles' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.motivation' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.wordification' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.short_videos' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'inspire.action.sod_quotes.share' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.action.article.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.action.article.share' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.action.highlight.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.action.motivation.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.action.wordification.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],

            // Explore/tool/list screens consumed by Flutter.
            'explore.quote_library' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'recommended'],
            'explore.quick_tools' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'more.saved' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'more.downloads' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'more.notifications' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'more.notifications.message' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'more.settings' => ['enabled' => true, 'banner' => true, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'more.support' => ['enabled' => true, 'banner' => true, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'more.about' => ['enabled' => true, 'banner' => true, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'more.privacy' => ['enabled' => true, 'banner' => true, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'more.terms' => ['enabled' => true, 'banner' => true, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'more.saved.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'more.downloads.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'more.notifications.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'more.settings.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'more.rate.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'notification.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'notification.action.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],

            // Generic game engine scopes used by standalone game screens.
            'game' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'game.race_of_faith' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'game.dominion_match' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'game.dominion_builder' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'game.future_games' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],

            // Quiz engine scopes. Reading pages stay cautious, but gameplay/result screens can be controlled here.
            'quiz' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'quiz.bible' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'quiz.bible_quiz' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'quiz.sod' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'quiz.sod_quiz' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'quiz.article' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'quiz.general' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],

            // Canonical Inspire hierarchy.
            'inspire.message_highlights' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.message_highlights.detail' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'inspire.inside_dunamis' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'recommended'],
            'inspire.inside_dunamis.detail' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'inspire.wordification.detail' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'inspire.motivation.detail' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'inspire.sod.quiz' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],

            // Canonical Explore hierarchy.
            'explore.quote_creator' => ['enabled' => true, 'banner' => true, 'native' => false, 'interstitial' => false, 'safe' => 'medium'],
            'explore.notes' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'explore.bible' => ['enabled' => true, 'banner' => true, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution'],
            'explore.books' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'explore.books.library' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'explore.books.reader' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution', 'settings' => ['banner' => ['placement' => 'disabled']]],
            'explore.games' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'explore.games.bible_quiz' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'explore.games.dominion_match' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'explore.games.race_of_faith' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'explore.games.kingdom_builder' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => true, 'safe' => 'medium'],
            'explore.shorts' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'explore.shorts.player' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'high_caution'],

            // Protected active contexts inherit explicit backend OFF rules.
            'game.race_of_faith.active' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution', 'settings' => ['banner' => ['placement' => 'disabled']]],
            'game.dominion_match.active' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution', 'settings' => ['banner' => ['placement' => 'disabled']]],
            'game.kingdom_builder.active' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution', 'settings' => ['banner' => ['placement' => 'disabled']]],
            'quiz.active' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution', 'settings' => ['banner' => ['placement' => 'disabled']]],

            // Canonical More hierarchy and safe actions.
            'more.account' => ['enabled' => false, 'banner' => false, 'native' => false, 'interstitial' => false, 'safe' => 'high_caution', 'settings' => ['banner' => ['placement' => 'disabled']]],
            'more.rate' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'more.share' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'more.item.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'saved.item.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'saved.item.share' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'download.item.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'support.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
        ];
    }

    public static function defaultFormats(): array
    {
        return [
            'banner'       => true,
            'native'       => true,
            'interstitial' => true,
        ];
    }

    public static function defaultNativeInList(): array
    {
        return [
            'enabled'     => true,
            'every'       => 4,
            'start_after' => 4,
            'max_per_list' => 0,
        ];
    }

    public static function defaultInterstitial(): array
    {
        return [
            'cooldown_seconds' => 120,
            'every_n_safe_actions' => 4,
            'minimum_launch_delay_seconds' => 20,
            'maximum_per_session' => 4,
            'minimum_page_dwell_seconds' => 0,
        ];
    }

    /** Canonical advertising contract for one app. */
    public static function adsForApp(int $appId): array
    {
        $profile = DB::table('ad_profiles')->where('app_id', $appId)->first();
        $rules = DB::table('ad_rules')
            ->where('app_id', $appId)
            ->whereIn('scope_type', ['tab', 'route'])
            ->orderBy('id')
            ->get()
            ->all();

        return self::resolveContract($profile ? (array) $profile : [], $rules);
    }

    /** Side-effect-free resolver used by the API and focused contract tests. */
    public static function resolveContract(array $profileRow, iterable $ruleRows): array
    {
        $masterEnabled = array_key_exists('ads_enabled', $profileRow)
            ? (bool) $profileRow['ads_enabled']
            : true;
        $profile = self::metaOverrides(self::decodeJsonArray($profileRow['meta_json'] ?? null));
        $formats = self::normalizeFormats($profile['ad_formats']);
        foreach ($formats as $format => $enabled) {
            $formats[$format] = $masterEnabled && $enabled;
        }

        $nativeInList = self::normalizeNativeConfig($profile['native_in_list']);
        $nativeInList['enabled'] = $formats['native'] && $nativeInList['enabled'];
        $globalInterstitial = self::normalizeInterstitialConfig($profile['interstitial']);

        $orderedRules = is_array($ruleRows) ? array_values($ruleRows) : iterator_to_array($ruleRows, false);
        usort($orderedRules, static fn ($left, $right): int =>
            ((int) (((object) $left)->id ?? 0)) <=> ((int) (((object) $right)->id ?? 0))
        );

        $tabRules = [];
        $routeRules = [];
        foreach ($orderedRules as $rawRule) {
            $rule = (object) $rawRule;
            $key = self::canonicalKey((string) ($rule->scope_key ?? ''));
            if ($key === null) {
                continue;
            }
            if (($rule->scope_type ?? null) === 'tab' && in_array($key, self::mainTabs(), true)) {
                $tabRules[$key] = $rule;
            } elseif (($rule->scope_type ?? null) === 'route') {
                $routeRules[$key] = $rule;
            }
        }

        $makePolicy = static function (
            string $key,
            bool $enabled,
            bool $banner,
            bool $native,
            bool $interstitial,
            string $source,
            array $settings = [],
            ?int $typedCooldown = null
        ) use ($masterEnabled, $formats, $nativeInList, $globalInterstitial): array {
            $enabled = $masterEnabled && $enabled;
            $banner = $enabled && $formats['banner'] && $banner;
            $native = $enabled && $formats['native'] && $native;
            $interstitial = $enabled && $formats['interstitial'] && $interstitial;

            $bannerConfig = array_merge([
                'placement' => str_contains($key, '.') ? 'page_bottom' : 'shell_bottom',
                'hide_on_failure' => true,
                'reserve_space_before_load' => false,
            ], is_array($settings['banner'] ?? null) ? $settings['banner'] : []);
            $nativeConfig = self::normalizeNativeConfig(array_merge(
                $nativeInList,
                is_array($settings['native'] ?? null) ? $settings['native'] : []
            ));

            // Cooldown precedence: settings JSON, typed column, profile, resolver default.
            $interstitialOverrides = is_array($settings['interstitial'] ?? null) ? $settings['interstitial'] : [];
            if (! array_key_exists('cooldown_seconds', $interstitialOverrides) && $typedCooldown !== null) {
                $interstitialOverrides['cooldown_seconds'] = $typedCooldown;
            }
            $interstitialConfig = self::normalizeInterstitialConfig(array_merge($globalInterstitial, $interstitialOverrides));

            if (($bannerConfig['placement'] ?? null) === 'disabled') {
                $banner = false;
            }
            if (! $nativeConfig['enabled']) {
                $native = false;
            }
            if (array_key_exists('enabled', $interstitialConfig) && ! $interstitialConfig['enabled']) {
                $interstitial = false;
            }

            $bannerConfig['placement'] = $banner ? $bannerConfig['placement'] : 'disabled';
            $nativeConfig['enabled'] = $native;
            $interstitialConfig['enabled'] = $interstitial;

            return [
                'enabled' => $enabled,
                'banner' => $banner,
                'native' => $native,
                'interstitial' => $interstitial,
                'banner_config' => $bannerConfig,
                'native_config' => $nativeConfig,
                'interstitial_config' => $interstitialConfig,
                'cooldown' => $interstitialConfig['cooldown_seconds'],
                'source' => $source,
                'scope_key' => $key,
            ];
        };

        $policies = [];
        foreach (self::mainTabs() as $tab) {
            $rule = $tabRules[$tab] ?? null;
            $defaultEnabled = (bool) (self::defaultTabPolicy()[$tab] ?? false);
            $policies[$tab] = $rule
                ? $makePolicy(
                    $tab,
                    (bool) ($rule->is_enabled ?? false),
                    (bool) ($rule->banner_enabled ?? false),
                    (bool) ($rule->native_enabled ?? false),
                    (bool) ($rule->interstitial_enabled ?? false),
                    'ad_rules.tab.' . $tab,
                    self::decodeJsonArray($rule->settings_json ?? null),
                    isset($rule->interstitial_cooldown_seconds) ? (int) $rule->interstitial_cooldown_seconds : null
                )
                : $makePolicy($tab, $defaultEnabled, $defaultEnabled, $defaultEnabled, false, 'default.tab.' . $tab);
        }

        $routeDefaults = [];
        foreach (self::defaultRoutePlacementPolicies() as $rawKey => $default) {
            $key = self::canonicalKey((string) $rawKey);
            $parentKey = $key !== null ? self::parentTabForKey($key) : null;
            if ($key === null || $parentKey === null || ! isset($policies[$parentKey])) {
                continue;
            }
            $parent = $policies[$parentKey];
            $policy = $makePolicy(
                $key,
                $parent['enabled'] && (bool) ($default['enabled'] ?? true),
                $parent['banner'] && (bool) ($default['banner'] ?? false),
                $parent['native'] && (bool) ($default['native'] ?? false),
                $parent['interstitial'] && (bool) ($default['interstitial'] ?? false),
                'default.route.' . $key,
                is_array($default['settings'] ?? null) ? $default['settings'] : []
            );
            $policy['parent_key'] = $parentKey;
            $routeDefaults[$key] = $policy;
            $policies[$key] = $policy;
        }

        foreach ($routeRules as $key => $rule) {
            $parentKey = self::parentTabForKey($key);
            if ($parentKey === null || ! isset($policies[$parentKey])) {
                continue;
            }
            $parent = $policies[$parentKey];
            $base = $routeDefaults[$key] ?? $parent;
            $settings = self::decodeJsonArray($rule->settings_json ?? null);
            $override = is_array($settings['override'] ?? null) ? $settings['override'] : [];

            $enabled = ($override['placement'] ?? false) === true
                ? $parent['enabled'] && (bool) ($rule->is_enabled ?? false)
                : (bool) $base['enabled'];
            $banner = ($override['banner'] ?? false) === true
                ? $parent['banner'] && (bool) ($rule->banner_enabled ?? false)
                : (bool) $base['banner'];
            $native = ($override['native'] ?? false) === true
                ? $parent['native'] && (bool) ($rule->native_enabled ?? false)
                : (bool) $base['native'];
            $interstitial = ($override['interstitial'] ?? false) === true
                ? $parent['interstitial'] && (bool) ($rule->interstitial_enabled ?? false)
                : (bool) $base['interstitial'];

            $defaultSettings = self::defaultRoutePlacementPolicies()[$key]['settings'] ?? [];
            $policy = $makePolicy(
                $key,
                $enabled,
                $banner,
                $native,
                $interstitial,
                'ad_rules.route.' . $key,
                array_replace_recursive(is_array($defaultSettings) ? $defaultSettings : [], $settings),
                isset($rule->interstitial_cooldown_seconds) ? (int) $rule->interstitial_cooldown_seconds : null
            );
            $policy['parent_key'] = $parentKey;
            $policy['inheritance'] = [
                'placement' => ($override['placement'] ?? false) !== true,
                'banner' => ($override['banner'] ?? false) !== true,
                'native' => ($override['native'] ?? false) !== true,
                'interstitial' => ($override['interstitial'] ?? false) !== true,
            ];
            $policies[$key] = $policy;
        }

        // Safety-only contexts may not belong to a navigation tab, but they
        // must still resolve as explicit valid policies rather than falling
        // through to an advertising-enabled tab policy.
        foreach (self::protectedKeys() as $key) {
            if (! isset($policies[$key])) {
                $policies[$key] = $makePolicy($key, true, false, false, false, 'safety.protected');
            }
        }

        foreach ($policies as $key => $policy) {
            if (! self::isProtectedKey($key)) {
                continue;
            }
            $policy['banner'] = false;
            $policy['native'] = false;
            $policy['interstitial'] = false;
            $policy['banner_config']['placement'] = 'disabled';
            $policy['native_config']['enabled'] = false;
            $policy['interstitial_config']['enabled'] = false;
            $policy['source'] = 'safety.protected';
            $policy['protected'] = true;
            $policies[$key] = $policy;
        }

        $adPolicy = [];
        foreach ($policies as $key => $policy) {
            $adPolicy[$key] = (bool) $policy['enabled'];
        }

        return [
            'enabled' => $masterEnabled,
            'formats' => $formats,
            'units' => [
                'banner' => self::nullableString($profileRow['banner_unit_id'] ?? null),
                'native' => self::nullableString($profileRow['native_unit_id'] ?? null),
                'interstitial' => self::nullableString($profileRow['interstitial_unit_id'] ?? null),
            ],
            'tabs' => $policies,
            'native_in_list' => $nativeInList,
            'interstitial' => $globalInterstitial,
            'ad_policy' => $adPolicy,
        ];
    }

    public static function globalInterstitialForApp(int $appId): array
    {
        return self::adsForApp($appId)['interstitial'];
    }

    /**
     * 🔥 TAB / ENGINE LEVEL CONTROL
     *
     * The Flutter AdsService currently reads policies from ads.tabs.
     * For that reason, this payload intentionally includes:
     * - real app tabs: home, watch, inspire, explore, more
     * - virtual parent scopes: games, game, quiz
     * - route/engine scopes: game.race_of_faith, game.dominion_match, quiz.bible, etc.
     */
    public static function tabsAdsForApp(int $appId): array
    {
        return self::adsForApp($appId)['tabs'];


    }

    /**
     * Resolve the effective policy for one screen or action.
     */
    public static function screenAdsForApp(int $appId, string $tabKey, ?string $routeKey): array
    {
        return self::resolveScreenContract(self::tabsAdsForApp($appId), $tabKey, $routeKey);
    }

    /** Side-effect-free screen resolver used by focused contract tests. */
    public static function resolveScreenContract(array $policies, string $tabKey, ?string $routeKey): array
    {
        $tabKey = strtolower(trim($tabKey));
        $routeKey = self::canonicalKey((string) $routeKey);

        if ($routeKey !== null && self::isProtectedKey($routeKey)) {
            if (isset($policies[$routeKey]) && is_array($policies[$routeKey])) {
                return array_merge($policies[$routeKey], ['resolved_key' => $routeKey]);
            }

            $policy = self::disabledPolicy('safety.protected');
            $policy['enabled'] = (bool) ($policies[$tabKey]['enabled'] ?? false);
            $policy['native_config']['enabled'] = false;
            $policy['interstitial_config']['enabled'] = false;
            $policy['protected'] = true;
            $policy['scope_key'] = $routeKey;
            $policy['resolved_key'] = $routeKey;

            return $policy;
        }

        $candidates = [];
        if ($routeKey !== null && $routeKey !== '') {
            $candidates[] = $routeKey;

            if (! str_starts_with($routeKey, $tabKey . '.')) {
                $candidates[] = $tabKey . '.' . $routeKey;
            }

            $walk = $routeKey;
            while (str_contains($walk, '.')) {
                $walk = substr($walk, 0, strrpos($walk, '.'));
                $candidates[] = $walk;
            }
        }
        $candidates[] = $tabKey;

        foreach (array_values(array_unique(array_filter($candidates))) as $candidate) {
            if (isset($policies[$candidate]) && is_array($policies[$candidate])) {
                return array_merge($policies[$candidate], ['resolved_key' => $candidate]);
            }
        }

        return self::disabledPolicy('safe.missing_policy');
    }

    private static function mainTabs(): array
    {
        return ['home', 'watch', 'inspire', 'explore', 'more'];
    }

    private static function canonicalKey(string $key): ?string
    {
        $key = strtolower(trim($key));
        if ($key === '' || str_contains($key, ':')) {
            return null;
        }

        return preg_replace('/\.+/', '.', trim($key, '.')) ?: null;
    }

    private static function parentTabForKey(string $key): ?string
    {
        foreach (self::mainTabs() as $tab) {
            if ($key === $tab || str_starts_with($key, $tab . '.')) {
                return $tab;
            }
        }

        if ($key === 'game' || $key === 'quiz'
            || str_starts_with($key, 'game.')
            || str_starts_with($key, 'quiz.')
            || str_starts_with($key, 'shorts')) {
            return 'explore';
        }
        if (str_starts_with($key, 'notification.') || str_starts_with($key, 'saved.') || str_starts_with($key, 'download.') || str_starts_with($key, 'support.')) {
            return 'more';
        }

        return null;
    }

    private static function isProtectedKey(string $key): bool
    {
        if (in_array($key, self::protectedKeys(), true)) {
            return true;
        }

        return preg_match('/^game\.[a-z0-9_-]+\.active$/', $key) === 1;
    }

    private static function protectedKeys(): array
    {
        return [
            'watch.player.live',
            'watch.player.hls',
            'watch.player.youtube',
            'watch.player.web_embed',
            'inspire.sod.watch',
            'explore.shorts.player',
            'explore.books.reader',
            'quiz.active',
            'webview.active',
            'form.active',
            'authentication.active',
        ];
    }

    private static function disabledPolicy(string $source): array
    {
        $interstitial = self::defaultInterstitial();

        return [
            'enabled' => false,
            'banner' => false,
            'native' => false,
            'interstitial' => false,
            'banner_config' => [
                'placement' => 'disabled',
                'hide_on_failure' => true,
                'reserve_space_before_load' => false,
            ],
            'native_config' => self::defaultNativeInList(),
            'interstitial_config' => $interstitial,
            'cooldown' => $interstitial['cooldown_seconds'],
            'source' => $source,
            'resolved_key' => null,
        ];
    }

    /**
     * 🔥 META OVERRIDES (NO CHANGE)
     */
    public static function metaOverrides(?array $metaJson): array
    {
        $metaJson = is_array($metaJson) ? $metaJson : [];

        return [
            'ad_policy'      => array_merge(self::defaultTabPolicy(), is_array($metaJson['ad_policy'] ?? null) ? $metaJson['ad_policy'] : []),
            'ad_formats'     => self::normalizeFormats(array_merge(self::defaultFormats(), is_array($metaJson['ad_formats'] ?? null) ? $metaJson['ad_formats'] : [])),
            'native_in_list' => self::normalizeNativeConfig(array_merge(self::defaultNativeInList(), is_array($metaJson['native_in_list'] ?? null) ? $metaJson['native_in_list'] : [])),
            'interstitial'   => self::normalizeInterstitialConfig(array_merge(self::defaultInterstitial(), is_array($metaJson['interstitial'] ?? null) ? $metaJson['interstitial'] : [])),
        ];
    }

    private static function normalizeFormats(array $formats): array
    {
        return [
            'banner' => (bool) ($formats['banner'] ?? false),
            'native' => (bool) ($formats['native'] ?? false),
            'interstitial' => (bool) ($formats['interstitial'] ?? false),
        ];
    }

    private static function normalizeNativeConfig(array $config): array
    {
        return [
            'enabled' => (bool) ($config['enabled'] ?? true),
            'every' => max(1, min(1000, (int) ($config['every'] ?? 4))),
            'start_after' => max(0, min(1000, (int) ($config['start_after'] ?? 4))),
            'max_per_list' => max(0, min(1000, (int) ($config['max_per_list'] ?? 0))),
        ];
    }

    private static function normalizeInterstitialConfig(array $config): array
    {
        $normalized = [
            'cooldown_seconds' => max(1, min(86400, (int) ($config['cooldown_seconds'] ?? 120))),
            'every_n_safe_actions' => max(1, min(1000, (int) ($config['every_n_safe_actions'] ?? 4))),
            'minimum_launch_delay_seconds' => max(0, min(86400, (int) ($config['minimum_launch_delay_seconds'] ?? 20))),
            'maximum_per_session' => max(0, min(1000, (int) ($config['maximum_per_session'] ?? 4))),
            'minimum_page_dwell_seconds' => max(0, min(86400, (int) ($config['minimum_page_dwell_seconds'] ?? 0))),
        ];
        if (array_key_exists('enabled', $config)) {
            $normalized['enabled'] = (bool) $config['enabled'];
        }

        return $normalized;
    }

    private static function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private static function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

}

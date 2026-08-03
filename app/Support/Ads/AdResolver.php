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

            // Explore game surfaces
            'explore:games' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'explore:dominion_match' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'explore:future_games' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],
            'explore:quiz' => ['enabled' => true, 'banner' => true, 'native' => true, 'interstitial' => false, 'safe' => 'medium'],

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
            'notification.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
            'notification.action.open' => ['enabled' => true, 'banner' => false, 'native' => false, 'interstitial' => true, 'safe' => 'recommended'],
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

    public static function globalInterstitialForApp(int $appId): array
    {
        $meta = DB::table('ad_profiles')
            ->where('app_id', $appId)
            ->value('meta_json');

        $decoded = self::decodeJsonArray($meta);
        $defaults = self::defaultInterstitial();
        $configured = is_array($decoded['interstitial'] ?? null)
            ? $decoded['interstitial']
            : [];

        return [
            'cooldown_seconds' => max(1, (int) ($configured['cooldown_seconds'] ?? $defaults['cooldown_seconds'])),
            'every_n_safe_actions' => max(1, (int) ($configured['every_n_safe_actions'] ?? $defaults['every_n_safe_actions'])),
            'minimum_launch_delay_seconds' => max(0, (int) ($configured['minimum_launch_delay_seconds'] ?? $defaults['minimum_launch_delay_seconds'])),
            'maximum_per_session' => max(0, (int) ($configured['maximum_per_session'] ?? $defaults['maximum_per_session'])),
            'minimum_page_dwell_seconds' => max(0, (int) ($configured['minimum_page_dwell_seconds'] ?? $defaults['minimum_page_dwell_seconds'])),
        ];
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
        $profileMeta = self::decodeJsonArray(
            DB::table('ad_profiles')->where('app_id', $appId)->value('meta_json')
        );
        $profile = self::metaOverrides($profileMeta);
        $globalFormats = $profile['ad_formats'];
        $globalNative = $profile['native_in_list'];
        $globalInterstitial = $profile['interstitial'];

        $makePolicy = static function (
            string $key,
            bool $enabled,
            bool $banner,
            bool $native,
            bool $interstitial,
            string $source,
            array $settings = []
        ) use ($globalFormats, $globalNative, $globalInterstitial): array {
            $bannerSettings = array_merge([
                'placement' => str_contains($key, '.') ? 'page_bottom' : 'shell_bottom',
                'hide_on_failure' => true,
                'reserve_space_before_load' => false,
            ], is_array($settings['banner'] ?? null) ? $settings['banner'] : []);

            $nativeSettings = array_merge(
                $globalNative,
                is_array($settings['native'] ?? null) ? $settings['native'] : []
            );
            $interstitialSettings = array_merge(
                $globalInterstitial,
                is_array($settings['interstitial'] ?? null) ? $settings['interstitial'] : []
            );

            $banner = $banner && (bool) ($globalFormats['banner'] ?? false);
            $native = $native && (bool) ($globalFormats['native'] ?? false);
            $interstitial = $interstitial && (bool) ($globalFormats['interstitial'] ?? false);

            if (! $enabled) {
                $banner = $native = $interstitial = false;
            }

            return [
                'enabled' => $enabled,
                'banner' => $banner,
                'native' => $native,
                'interstitial' => $interstitial,
                'banner_config' => $bannerSettings,
                'native_config' => $nativeSettings,
                'interstitial_config' => $interstitialSettings,
                'cooldown' => (int) ($interstitialSettings['cooldown_seconds'] ?? 120),
                'source' => $source,
                'scope_key' => $key,
            ];
        };

        $rules = DB::table('ad_rules')
            ->where('app_id', $appId)
            ->whereIn('scope_type', ['tab', 'route'])
            ->orderBy('id')
            ->get();

        $tabRules = [];
        $routeRules = [];

        foreach ($rules as $rule) {
            $key = self::canonicalKey((string) $rule->scope_key);
            if ($key === null) {
                continue; // Legacy colon-style hierarchy is intentionally ignored.
            }

            if ((string) $rule->scope_type === 'tab' && in_array($key, self::mainTabs(), true)) {
                $tabRules[$key] = $rule;
                continue;
            }

            if ((string) $rule->scope_type === 'route') {
                $routeRules[$key] = $rule;
            }
        }

        $policies = [];

        // Main tabs are the authoritative parent controls. Their format switches
        // always apply; global profile switches still act as an app-wide gate.
        foreach (self::mainTabs() as $tab) {
            $rule = $tabRules[$tab] ?? null;
            if ($rule !== null) {
                $settings = self::decodeJsonArray($rule->settings_json ?? null);
                $policies[$tab] = $makePolicy(
                    $tab,
                    (bool) $rule->is_enabled,
                    (bool) $rule->banner_enabled,
                    (bool) $rule->native_enabled,
                    (bool) $rule->interstitial_enabled,
                    'ad_rules.tab.' . $tab,
                    $settings
                );
            } else {
                $enabled = (bool) (self::defaultTabPolicy()[$tab] ?? false);
                $policies[$tab] = $makePolicy(
                    $tab,
                    $enabled,
                    $enabled,
                    $enabled,
                    false,
                    'default.tab.' . $tab
                );
            }
        }

        // All canonical child pages/actions inherit from their parent tab unless
        // settings_json contains an explicit override marker for that format.
        foreach ($routeRules as $key => $rule) {
            $parentTab = self::parentTabForKey($key);
            if ($parentTab === null || ! isset($policies[$parentTab])) {
                continue;
            }

            $parent = $policies[$parentTab];
            $settings = self::decodeJsonArray($rule->settings_json ?? null);
            $override = is_array($settings['override'] ?? null) ? $settings['override'] : [];

            $enabled = (bool) $parent['enabled'];
            $banner = (bool) $parent['banner'];
            $native = (bool) $parent['native'];
            $interstitial = (bool) $parent['interstitial'];
            $sources = [
                'enabled' => $parent['source'],
                'banner' => $parent['source'],
                'native' => $parent['source'],
                'interstitial' => $parent['source'],
            ];

            if (($override['placement'] ?? false) === true) {
                $enabled = (bool) $rule->is_enabled;
                $sources['enabled'] = 'ad_rules.route.' . $key;
            }
            if (($override['banner'] ?? false) === true) {
                $banner = (bool) $rule->banner_enabled;
                $sources['banner'] = 'ad_rules.route.' . $key;
            }
            if (($override['native'] ?? false) === true) {
                $native = (bool) $rule->native_enabled;
                $sources['native'] = 'ad_rules.route.' . $key;
            }
            if (($override['interstitial'] ?? false) === true) {
                $interstitial = (bool) $rule->interstitial_enabled;
                $sources['interstitial'] = 'ad_rules.route.' . $key;
            }

            $policy = $makePolicy(
                $key,
                $enabled,
                $banner,
                $native,
                $interstitial,
                'inherited.' . $parentTab,
                $settings
            );
            $policy['parent_key'] = $parentTab;
            $policy['sources'] = $sources;
            $policy['inheritance'] = [
                'placement' => ! (($override['placement'] ?? false) === true),
                'banner' => ! (($override['banner'] ?? false) === true),
                'native' => ! (($override['native'] ?? false) === true),
                'interstitial' => ! (($override['interstitial'] ?? false) === true),
            ];

            if (self::isProtectedKey($key)) {
                $policy['enabled'] = true;
                $policy['banner'] = false;
                $policy['native'] = false;
                $policy['interstitial'] = false;
                $policy['banner_config']['placement'] = 'disabled';
                $policy['source'] = 'safety.protected';
                $policy['sources'] = [
                    'enabled' => 'safety.protected',
                    'banner' => 'safety.protected',
                    'native' => 'safety.protected',
                    'interstitial' => 'safety.protected',
                ];
                $policy['protected'] = true;
            }

            $policies[$key] = $policy;
        }

        return $policies;
    }

    /**
     * Resolve the effective policy for one screen or action.
     */
    public static function screenAdsForApp(int $appId, string $tabKey, ?string $routeKey): array
    {
        $policies = self::tabsAdsForApp($appId);
        $tabKey = strtolower(trim($tabKey));
        $routeKey = self::canonicalKey((string) $routeKey);

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

        if (str_starts_with($key, 'game.') || str_starts_with($key, 'quiz.') || str_starts_with($key, 'shorts')) {
            return 'explore';
        }
        if (str_starts_with($key, 'notification.') || str_starts_with($key, 'saved.') || str_starts_with($key, 'download.') || str_starts_with($key, 'support.')) {
            return 'more';
        }

        return null;
    }

    private static function isProtectedKey(string $key): bool
    {
        $exact = [
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

        if (in_array($key, $exact, true)) {
            return true;
        }

        return preg_match('/^game\.[a-z0-9_-]+\.active$/', $key) === 1;
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
            'ad_policy'      => array_merge(self::defaultTabPolicy(), $metaJson['ad_policy'] ?? []),
            'ad_formats'     => array_merge(self::defaultFormats(), $metaJson['ad_formats'] ?? []),
            'native_in_list' => array_merge(self::defaultNativeInList(), $metaJson['native_in_list'] ?? []),
            'interstitial'   => array_merge(self::defaultInterstitial(), $metaJson['interstitial'] ?? []),
        ];
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

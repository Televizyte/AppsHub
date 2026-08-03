<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\ActiveApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BeginnerAdPlacementController extends Controller
{
    public function syncDefaults(Request $request): RedirectResponse
    {
        $appId = $this->activeAppId();

        foreach ($this->defaultRules() as $rule) {
            $this->ensureRule($appId, $rule['scope_type'], $rule['scope_key'], $rule['defaults']);
        }

        return redirect('/admin/ads-monetization?tab=inner&group=all')
            ->with('status', 'Default ad placement rules refreshed for this app.');
    }

    public function toggleMaster(Request $request): RedirectResponse
    {
        $appId = $this->activeAppId();
        $profile = DB::table('ad_profiles')->where('app_id', $appId)->latest('id')->first();

        if (! $profile) {
            $this->createDefaultProfile($appId, 'admob');
            return redirect('/admin/ads-monetization?tab=setup')->with('status', 'Ad setup created and master ads enabled.');
        }

        DB::table('ad_profiles')->where('id', $profile->id)->where('app_id', $appId)->update([
            'ads_enabled' => ! (bool) $profile->ads_enabled,
            'updated_at' => now(),
        ]);

        return redirect('/admin/ads-monetization?tab=setup')->with('status', 'Master ads switch updated.');
    }

    public function updateProvider(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'in:admob,applovin,unity,iron_source,custom'],
        ]);

        $appId = $this->activeAppId();
        $profile = DB::table('ad_profiles')->where('app_id', $appId)->latest('id')->first();

        if (! $profile) {
            $this->createDefaultProfile($appId, $data['provider']);
            return redirect('/admin/ads-monetization?tab=setup')->with('status', 'Ad provider created for this app.');
        }

        $meta = json_decode((string) ($profile->meta_json ?? '{}'), true);
        $meta = is_array($meta) ? $meta : [];
        $meta['provider'] = $data['provider'];

        DB::table('ad_profiles')->where('id', $profile->id)->where('app_id', $appId)->update([
            'meta_json' => json_encode($meta, JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);

        return redirect('/admin/ads-monetization?tab=setup')->with('status', 'Ad provider updated.');
    }

    public function updateGlobalSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'banner_unit_id' => ['nullable', 'string', 'max:190'],
            'native_unit_id' => ['nullable', 'string', 'max:190'],
            'interstitial_unit_id' => ['nullable', 'string', 'max:190'],
            'allow_banner' => ['nullable', 'boolean'],
            'allow_native' => ['nullable', 'boolean'],
            'allow_interstitial' => ['nullable', 'boolean'],
            'native_list_enabled' => ['nullable', 'boolean'],
            'native_start_after' => ['required', 'integer', 'min:1', 'max:100'],
            'native_every' => ['required', 'integer', 'min:1', 'max:100'],
            'native_max_per_list' => ['required', 'integer', 'min:0', 'max:100'],
            'interstitial_cooldown_seconds' => ['required', 'integer', 'min:1', 'max:86400'],
            'interstitial_every_n_safe_actions' => ['required', 'integer', 'min:1', 'max:100'],
            'interstitial_min_launch_delay_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'interstitial_max_per_session' => ['required', 'integer', 'min:0', 'max:100'],
            'interstitial_min_page_dwell_seconds' => ['required', 'integer', 'min:0', 'max:3600'],
        ]);

        $appId = $this->activeAppId();
        $profile = DB::table('ad_profiles')->where('app_id', $appId)->latest('id')->first();

        if (! $profile) {
            $this->createDefaultProfile($appId, 'admob');
            $profile = DB::table('ad_profiles')->where('app_id', $appId)->latest('id')->first();
        }

        $meta = json_decode((string) ($profile->meta_json ?? '{}'), true);
        $meta = is_array($meta) ? $meta : [];
        $meta['ad_formats'] = [
            'banner' => $request->boolean('allow_banner'),
            'native' => $request->boolean('allow_native'),
            'interstitial' => $request->boolean('allow_interstitial'),
            'rewarded' => (bool) data_get($meta, 'ad_formats.rewarded', false),
        ];
        $meta['native_in_list'] = [
            'enabled' => $request->boolean('native_list_enabled'),
            'start_after' => (int) $data['native_start_after'],
            'every' => (int) $data['native_every'],
            'max_per_list' => (int) $data['native_max_per_list'],
        ];
        $meta['interstitial'] = [
            'cooldown_seconds' => (int) $data['interstitial_cooldown_seconds'],
            'every_n_safe_actions' => (int) $data['interstitial_every_n_safe_actions'],
            'minimum_launch_delay_seconds' => (int) $data['interstitial_min_launch_delay_seconds'],
            'maximum_per_session' => (int) $data['interstitial_max_per_session'],
            'minimum_page_dwell_seconds' => (int) $data['interstitial_min_page_dwell_seconds'],
        ];

        DB::table('ad_profiles')->where('id', $profile->id)->where('app_id', $appId)->update([
            'banner_unit_id' => trim((string) ($data['banner_unit_id'] ?? '')) ?: null,
            'native_unit_id' => trim((string) ($data['native_unit_id'] ?? '')) ?: null,
            'interstitial_unit_id' => trim((string) ($data['interstitial_unit_id'] ?? '')) ?: null,
            'meta_json' => json_encode($meta, JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);

        return redirect('/admin/ads-monetization?tab=setup#global-controls')
            ->with('status', 'Global ad IDs, native frequency, and interstitial timing saved.');
    }

    public function toggleRule(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'scope_type' => ['required', 'string', 'in:tab,route,bucket_list'],
            'scope_key' => ['required', 'string', 'max:160'],
            'field' => ['nullable', 'string', 'in:is_enabled,banner_enabled,native_enabled,interstitial_enabled'],
            'tab' => ['nullable', 'string', 'max:40'],
            'group' => ['nullable', 'string', 'max:40'],
            'banner_placement' => ['nullable', 'string', 'in:shell_bottom,page_bottom,page_top,disabled'],
            'native_start_after' => ['nullable', 'integer', 'min:0', 'max:100'],
            'native_every' => ['nullable', 'integer', 'min:1', 'max:100'],
            'native_max_per_list' => ['nullable', 'integer', 'min:0', 'max:100'],
            'interstitial_every_n_safe_actions' => ['nullable', 'integer', 'min:1', 'max:100'],
            'interstitial_cooldown_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'interstitial_min_launch_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'interstitial_max_per_session' => ['nullable', 'integer', 'min:0', 'max:100'],
            'interstitial_min_page_dwell_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ]);

        $appId = $this->activeAppId();
        $rule = $this->ensureRule(
            $appId,
            $data['scope_type'],
            $data['scope_key'],
            $this->safeDefaultsFor($data['scope_type'], $data['scope_key'])
        );

        $update = ['updated_at' => now()];

        if (! empty($data['field'])) {
            $field = $data['field'];
            $update[$field] = ! (bool) ($rule->{$field} ?? false);
        } else {
            $settings = $this->decodeSettings($rule->settings_json ?? null);
            $settings['banner'] = array_merge(
                ['placement' => 'page_bottom', 'hide_on_failure' => true, 'reserve_space_before_load' => false],
                is_array($settings['banner'] ?? null) ? $settings['banner'] : [],
                ['placement' => (string) ($data['banner_placement'] ?? 'page_bottom')]
            );
            $settings['native'] = array_merge(
                ['start_after' => 4, 'every' => 4, 'max_per_list' => 0],
                is_array($settings['native'] ?? null) ? $settings['native'] : [],
                [
                    'start_after' => (int) ($data['native_start_after'] ?? 4),
                    'every' => (int) ($data['native_every'] ?? 4),
                    'max_per_list' => (int) ($data['native_max_per_list'] ?? 0),
                ]
            );
            $settings['interstitial'] = array_merge(
                [
                    'every_n_safe_actions' => 4,
                    'cooldown_seconds' => 120,
                    'minimum_launch_delay_seconds' => 20,
                    'maximum_per_session' => 4,
                    'minimum_page_dwell_seconds' => 0,
                ],
                is_array($settings['interstitial'] ?? null) ? $settings['interstitial'] : [],
                [
                    'every_n_safe_actions' => (int) ($data['interstitial_every_n_safe_actions'] ?? 4),
                    'cooldown_seconds' => (int) ($data['interstitial_cooldown_seconds'] ?? 120),
                    'minimum_launch_delay_seconds' => (int) ($data['interstitial_min_launch_delay_seconds'] ?? 20),
                    'maximum_per_session' => (int) ($data['interstitial_max_per_session'] ?? 4),
                    'minimum_page_dwell_seconds' => (int) ($data['interstitial_min_page_dwell_seconds'] ?? 0),
                ]
            );

            $update['interstitial_cooldown_seconds'] = $settings['interstitial']['cooldown_seconds'];
            $update['settings_json'] = json_encode($settings, JSON_UNESCAPED_SLASHES);
        }

        DB::table('ad_rules')
            ->where('id', $rule->id)
            ->where('app_id', $appId)
            ->update($update);

        $tab = $data['tab'] ?: 'placements';
        $group = $data['group'] ? '&group=' . urlencode($data['group']) : '';

        return redirect('/admin/ads-monetization?tab=' . urlencode($tab) . $group)
            ->with('status', empty($data['field']) ? 'Ad placement behavior saved.' : 'Ad placement updated.');
    }

    private function activeAppId(): int
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        if ($appId <= 0) {
            abort(422, 'No active app selected.');
        }
        return $appId;
    }

    private function createDefaultProfile(int $appId, string $provider): void
    {
        DB::table('ad_profiles')->insert([
            'app_id' => $appId,
            'ads_enabled' => true,
            'banner_unit_id' => null,
            'native_unit_id' => null,
            'interstitial_unit_id' => null,
            'meta_json' => json_encode([
                'provider' => $provider,
                'ad_formats' => ['banner' => true, 'native' => true, 'interstitial' => false, 'rewarded' => false],
                'native_in_list' => ['enabled' => true, 'start_after' => 4, 'every' => 4, 'max_per_list' => 0],
                'interstitial' => ['cooldown_seconds' => 120, 'every_n_safe_actions' => 4, 'minimum_launch_delay_seconds' => 20, 'maximum_per_session' => 4, 'minimum_page_dwell_seconds' => 0],
            ], JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function ensureRule(int $appId, string $scopeType, string $scopeKey, array $defaults): object
    {
        $existing = DB::table('ad_rules')->where('app_id', $appId)->where('scope_type', $scopeType)->where('scope_key', $scopeKey)->first();
        if ($existing) {
            return $existing;
        }

        DB::table('ad_rules')->insert(array_merge([
            'app_id' => $appId,
            'scope_type' => $scopeType,
            'scope_key' => $scopeKey,
            'is_enabled' => true,
            'banner_enabled' => true,
            'native_enabled' => true,
            'interstitial_enabled' => false,
            'interstitial_cooldown_seconds' => 120,
            'settings_json' => json_encode($this->defaultSettingsFor($scopeType, $scopeKey), JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
            'updated_at' => now(),
        ], $defaults));

        return DB::table('ad_rules')->where('app_id', $appId)->where('scope_type', $scopeType)->where('scope_key', $scopeKey)->first();
    }

    private function decodeSettings(mixed $value): array
    {
        if (is_array($value)) return $value;
        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function defaultSettingsFor(string $scopeType, string $scopeKey): array
    {
        $key = strtolower(trim($scopeKey));
        $isRootTab = $scopeType === 'tab';
        $protected = str_contains($key, '.player')
            || str_contains($key, '.reader')
            || str_contains($key, '.active')
            || str_contains($key, 'login')
            || str_contains($key, 'account');

        return [
            'banner' => [
                'placement' => $protected ? 'disabled' : ($isRootTab ? 'shell_bottom' : 'page_bottom'),
                'hide_on_failure' => true,
                'reserve_space_before_load' => false,
            ],
            'native' => [
                'start_after' => 4,
                'every' => 4,
                'max_per_list' => 0,
            ],
            'interstitial' => [
                'every_n_safe_actions' => 4,
                'cooldown_seconds' => 120,
                'minimum_launch_delay_seconds' => 20,
                'maximum_per_session' => 4,
                'minimum_page_dwell_seconds' => 0,
            ],
        ];
    }

    private function safeDefaultsFor(string $scopeType, string $scopeKey): array
    {
        $text = strtolower($scopeType . ':' . $scopeKey);
        $isGameOrQuiz = str_contains($text, 'game')
            || str_contains($text, 'race_of_faith')
            || str_contains($text, 'dominion')
            || str_contains($text, 'quiz');

        $isWatchNative = str_starts_with($scopeKey, 'watch.native.');
        $isWatchAction = str_starts_with($scopeKey, 'watch.action.');
        $isWatchPlayer = str_starts_with($scopeKey, 'watch.player.');
        $isWatchBrowse = in_array($scopeKey, [
            'watch.channels',
            'watch.video_list',
            'watch.playlist_list',
        ], true);
        $watchInterstitialOn = in_array($scopeKey, [
            'watch.action.open_channels',
            'watch.action.open_video_collection',
            'watch.action.open_playlist',
        ], true);

        if ($scopeType === 'tab' && $scopeKey === 'watch') {
            return [
                'is_enabled' => true,
                'banner_enabled' => true,
                'native_enabled' => true,
                'interstitial_enabled' => false,
                'interstitial_cooldown_seconds' => 120,
            ];
        }

        if ($isWatchNative || $isWatchAction || $isWatchPlayer || $isWatchBrowse) {
            return [
                'is_enabled' => true,
                'banner_enabled' => $isWatchBrowse,
                'native_enabled' => $isWatchNative || $isWatchBrowse,
                'interstitial_enabled' => $isWatchAction && $watchInterstitialOn,
                'interstitial_cooldown_seconds' => 120,
            ];
        }

        $hardOff = str_contains($text, 'live')
            || str_contains($text, 'stream')
            || str_contains($text, 'player')
            || str_contains($text, 'youtube')
            || str_contains($text, 'read')
            || str_contains($text, 'reader');

        $bibleReadingOnly = str_contains($text, 'bible') && ! str_contains($text, 'quiz') && ! $isGameOrQuiz;
        $off = $hardOff || $bibleReadingOnly;
        $isShorts = str_contains($text, 'short_videos') || str_contains($text, 'shorts');

        if ($isGameOrQuiz && ! $hardOff) {
            $off = false;
        }

        $isHomeNativePlacement = str_starts_with($scopeKey, 'home.native.');
        $isHomeAction = str_starts_with($scopeKey, 'home.action.');
        $homeInterstitialOn = in_array($scopeKey, [
            'home.action.articles',
            'home.action.message_highlights',
            'home.action.short_videos',
            'home.action.quick_tools.books',
        ], true);

        if ($isHomeNativePlacement || $isHomeAction) {
            return [
                'is_enabled' => true,
                'banner_enabled' => false,
                'native_enabled' => $isHomeNativePlacement,
                'interstitial_enabled' => $isHomeAction && $homeInterstitialOn,
                'interstitial_cooldown_seconds' => 120,
            ];
        }

        return [
            'is_enabled' => ! $off,
            'banner_enabled' => ! $off,
            'native_enabled' => ! $off,
            'interstitial_enabled' => false,
            'interstitial_cooldown_seconds' => 120,
        ];
    }

    private function defaultRules(): array
    {
        $keys = [
            'shorts','shorts.general','shorts.player','shorts.action.open',
            'home:daily_scripture','home:daily_quote','home:featured_banners','home:short_videos','home:quick_tools',
            'home.native.after_quick_access','home.native.quick_tools',
            'home.action.watch','home.action.articles','home.action.message_highlights','home.action.short_videos',
            'home.action.daily_scripture.quote_creator','home.action.daily_scripture.bible',
            'home.action.daily_quote.quote_creator','home.action.daily_quote.notes',
            'home.action.quick_tools.quote_creator','home.action.quick_tools.notes','home.action.quick_tools.bible','home.action.quick_tools.books','home.action.other',
            'watch.native.channel_list','watch.channels','watch.video_list','watch.playlist_list','watch.player.hls','watch.player.youtube','watch.player.web_embed','watch.player.live','watch.action.open_channels','watch.action.open_video_collection','watch.action.open_playlist','watch.action.open_live_hls','watch.action.open_youtube','watch.action.open_web_embed','watch.action.other',
            'inspire','inspire.sod','inspire.sod.read','inspire.sod.watch','inspire.sod.quotes','inspire.sod.quiz',
            'inspire.message_highlights','inspire.message_highlights.detail','inspire.inside_dunamis','inspire.inside_dunamis.detail',
            'inspire.wordification','inspire.wordification.detail','inspire.motivation','inspire.motivation.detail','inspire.short_videos',
            'inspire.action.sod_quotes.share','inspire.action.article.open','inspire.action.article.share',
            'inspire.action.highlight.open','inspire.action.highlight.share','inspire.action.motivation.open','inspire.action.motivation.share','inspire.action.wordification.open','inspire.action.wordification.share',
            'explore','explore.quick_tools','explore.quote_creator','explore.quote_library','explore.notes','explore.bible',
            'explore.books','explore.books.library','explore.books.reader','explore.games','explore.games.bible_quiz',
            'explore.games.dominion_match','explore.games.race_of_faith','explore.games.kingdom_builder',
            'explore.shorts','explore.shorts.player','explore.action.tool.open','explore.action.book.open','explore.action.game.open','explore.action.short.open',
            'game','game.race_of_faith','game.dominion_match','game.kingdom_builder','game.future_games',
            'game.race_of_faith.active','game.dominion_match.active','game.kingdom_builder.active','game.action.open','game.action.complete',
            'quiz','quiz.bible','quiz.bible_quiz','quiz.sod','quiz.sod_quiz','quiz.article','quiz.general','quiz.active','quiz.action.open','quiz.action.complete',
            'more','more.account','more.saved','more.downloads','more.notifications','more.notifications.message','more.settings','more.support','more.about','more.contact','more.privacy','more.terms','more.rate','more.share',
            'more.item.open','notification.open','notification.action.open','saved.item.open','saved.item.share','download.item.open','support.open',
        ];

        $rules = [];
        foreach (['home', 'watch', 'inspire', 'explore', 'more'] as $tab) {
            $rules[] = ['scope_type' => 'tab', 'scope_key' => $tab, 'defaults' => $this->safeDefaultsFor('tab', $tab)];
        }
        foreach ($keys as $key) {
            $rules[] = ['scope_type' => 'route', 'scope_key' => $key, 'defaults' => $this->safeDefaultsFor('route', $key)];
        }
        return $rules;
    }
}

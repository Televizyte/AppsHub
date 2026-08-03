<x-filament::page>
    @php
        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);
        $activeApp = $activeAppId > 0 ? \App\Models\App::query()->find($activeAppId) : null;
        $profile = $activeAppId > 0 ? \App\Models\AdProfile::query()->where('app_id', $activeAppId)->latest('id')->first() : null;
        $rules = $activeAppId > 0 ? \App\Models\AdRule::query()->where('app_id', $activeAppId)->orderBy('scope_type')->orderBy('scope_key')->get() : collect();
        $rulesByKey = $rules->keyBy(fn ($rule) => $rule->scope_type . ':' . $rule->scope_key);

        $tabs = ['overview' => 'Overview', 'setup' => 'Providers & IDs', 'placements' => 'Main Tabs', 'inner' => 'Inner Pages', 'safety' => 'Safety'];
        $activeTab = strtolower((string) request('tab', 'placements'));
        if (! array_key_exists($activeTab, $tabs)) $activeTab = 'placements';

        $innerGroups = ['all' => 'All', 'home' => 'Home', 'watch' => 'Watch', 'inspire' => 'Inspire', 'explore' => 'Explore', 'shorts' => 'Short Videos', 'games' => 'Games / Quizzes', 'more' => 'More'];
        $activeGroup = strtolower((string) request('group', 'all'));
        if (! array_key_exists($activeGroup, $innerGroups)) $activeGroup = 'all';

        $provider = data_get($profile?->meta_json ?? [], 'provider', 'admob');
        $profileMeta = is_array($profile?->meta_json) ? $profile->meta_json : [];
        $formatSettings = array_merge(['banner' => true, 'native' => true, 'interstitial' => true], data_get($profileMeta, 'ad_formats', []));
        $nativeSettings = array_merge(['enabled' => true, 'start_after' => 4, 'every' => 4, 'max_per_list' => 0], data_get($profileMeta, 'native_in_list', []));
        $interstitialSettings = array_merge(['cooldown_seconds' => 120, 'every_n_safe_actions' => 4, 'minimum_launch_delay_seconds' => 20, 'maximum_per_session' => 4, 'minimum_page_dwell_seconds' => 0], data_get($profileMeta, 'interstitial', []));
        $unitStatus = [
            'banner' => filled($profile?->banner_unit_id),
            'native' => filled($profile?->native_unit_id),
            'interstitial' => filled($profile?->interstitial_unit_id),
        ];

        $providerCards = [
            ['key' => 'admob', 'title' => 'Google AdMob', 'note' => 'Recommended for Play Store apps and current Flutter wiring.', 'status' => 'Ready'],
            ['key' => 'applovin', 'title' => 'AppLovin MAX', 'note' => 'Future mediation option for multiple ad networks.', 'status' => 'Future'],
            ['key' => 'unity', 'title' => 'Unity Ads', 'note' => 'Good for games and rewarded ads when games expand.', 'status' => 'Future'],
            ['key' => 'iron_source', 'title' => 'ironSource / LevelPlay', 'note' => 'Future mediation option for larger monetization setup.', 'status' => 'Future'],
            ['key' => 'custom', 'title' => 'Custom / Direct Sponsor', 'note' => 'For manual sponsor banners or direct ad deals later.', 'status' => 'Future'],
        ];

        $offDefault = function (string $scopeType, string $scopeKey) {
            $text = strtolower($scopeType . ':' . $scopeKey);
            return str_contains($text, 'watch') || str_contains($text, 'live') || str_contains($text, 'stream') || str_contains($text, 'player') || str_contains($text, 'youtube') || str_contains($text, 'bible') || str_contains($text, 'read') || str_contains($text, 'reader');
        };

        $ruleFor = function (string $scopeType, string $scopeKey) use ($rulesByKey, $offDefault) {
            if (isset($rulesByKey[$scopeType . ':' . $scopeKey])) return $rulesByKey[$scopeType . ':' . $scopeKey];
            $off = $offDefault($scopeType, $scopeKey);
            return (object) ['scope_type' => $scopeType, 'scope_key' => $scopeKey, 'is_enabled' => ! $off, 'banner_enabled' => ! $off, 'native_enabled' => ! $off, 'interstitial_enabled' => false, 'interstitial_cooldown_seconds' => 120, 'virtual' => true];
        };

        $settingsFor = function ($rule, string $scopeType, string $scopeKey) {
            $settings = is_array($rule->settings_json ?? null) ? $rule->settings_json : [];
            $protected = str_contains(strtolower($scopeKey), '.player')
                || str_contains(strtolower($scopeKey), '.reader')
                || str_contains(strtolower($scopeKey), '.active')
                || str_contains(strtolower($scopeKey), 'account');
            return [
                'banner' => array_merge([
                    'placement' => $protected ? 'disabled' : ($scopeType === 'tab' ? 'shell_bottom' : 'page_bottom'),
                ], is_array($settings['banner'] ?? null) ? $settings['banner'] : []),
                'native' => array_merge(['start_after' => 4, 'every' => 4, 'max_per_list' => 0], is_array($settings['native'] ?? null) ? $settings['native'] : []),
                'interstitial' => array_merge(['every_n_safe_actions' => 4, 'cooldown_seconds' => 120, 'minimum_launch_delay_seconds' => 20, 'maximum_per_session' => 4, 'minimum_page_dwell_seconds' => 0], is_array($settings['interstitial'] ?? null) ? $settings['interstitial'] : []),
            ];
        };

        $tabPlacements = [
            ['key' => 'home', 'title' => 'Home Tab', 'note' => 'Best place for banner and native ads. Keep it clean and not too frequent.', 'safe' => 'Recommended'],
            ['key' => 'watch', 'title' => 'Watch Tab', 'note' => 'Browsing root: one shell banner and controlled native list ads. Active players remain protected.', 'safe' => 'Medium'],
            ['key' => 'inspire', 'title' => 'Inspire Tab', 'note' => 'Good for native/banner ads between articles and teaching content.', 'safe' => 'Recommended'],
            ['key' => 'explore', 'title' => 'Explore Tab', 'note' => 'Tools area. Use banners/native carefully, avoid disturbing tools.', 'safe' => 'Medium'],
            ['key' => 'more', 'title' => 'More Tab', 'note' => 'Settings/account area. Keep ads minimal.', 'safe' => 'Low'],
        ];

        $innerPlacements = [
            ['key'=>'home:daily_scripture','title'=>'Daily Scripture','group'=>'home','note'=>'Home daily scripture card. Ads should not cover scripture.','safe'=>'Medium'],
            ['key'=>'home:daily_quote','title'=>'Daily Quote','group'=>'home','note'=>'Home daily quote card. Banner below page is safer.','safe'=>'Medium'],
            ['key'=>'home:featured_banners','title'=>'Home Banners','group'=>'home','note'=>'Home banner carousel/list placement.','safe'=>'Recommended'],
            ['key'=>'home:short_videos','title'=>'Home Short Video Carousel','group'=>'shorts','note'=>'Short video block shown from Home.','safe'=>'Medium'],
            ['key'=>'home:quick_tools','title'=>'Home Quick Tools','group'=>'home','note'=>'Shortcut area to Bible, Notes, Quote Creator and tools.','safe'=>'Medium'],
            ['key'=>'home.native.after_quick_access','title'=>'Native After Quick Access','group'=>'home','note'=>'Dedicated native slot after the main Home shortcut cards.','safe'=>'Recommended'],
            ['key'=>'home.native.quick_tools','title'=>'Quick Tools Native Injection','group'=>'home','note'=>'Automatic native insertion in Quick Tools using the global backend list frequency.','safe'=>'Recommended'],
            ['key'=>'home.action.watch','title'=>'Open Watch From Home','group'=>'home','note'=>'Navigation action to Watch. Interstitial remains OFF by default for live/video caution.','safe'=>'High caution'],
            ['key'=>'home.action.articles','title'=>'Open Articles From Home','group'=>'home','note'=>'Safe Home navigation action eligible for centrally timed interstitials.','safe'=>'Recommended'],
            ['key'=>'home.action.message_highlights','title'=>'Open Message Highlights','group'=>'home','note'=>'Safe Home navigation action eligible for centrally timed interstitials.','safe'=>'Recommended'],
            ['key'=>'home.action.short_videos','title'=>'Open Shorts From Home','group'=>'shorts','note'=>'Natural transition into Shorts; controlled by global click count and cooldown.','safe'=>'Medium'],
            ['key'=>'home.action.daily_scripture.quote_creator','title'=>'Scripture to Quote Creator','group'=>'home','note'=>'Editor transition. Interstitial OFF by default but remotely controllable.','safe'=>'Medium'],
            ['key'=>'home.action.daily_scripture.bible','title'=>'Scripture to Bible','group'=>'home','note'=>'Bible reading transition. Interstitial OFF by default.','safe'=>'High caution'],
            ['key'=>'home.action.daily_quote.quote_creator','title'=>'Daily Quote to Quote Creator','group'=>'home','note'=>'Editor transition. Interstitial OFF by default.','safe'=>'Medium'],
            ['key'=>'home.action.daily_quote.notes','title'=>'Daily Quote to Notes','group'=>'home','note'=>'Writing transition. Interstitial OFF by default.','safe'=>'Medium'],
            ['key'=>'home.action.quick_tools.quote_creator','title'=>'Quick Tool: Quote Creator','group'=>'home','note'=>'Editor transition controlled independently.','safe'=>'Medium'],
            ['key'=>'home.action.quick_tools.notes','title'=>'Quick Tool: Notes','group'=>'home','note'=>'Writing transition controlled independently.','safe'=>'Medium'],
            ['key'=>'home.action.quick_tools.bible','title'=>'Quick Tool: Bible','group'=>'home','note'=>'Reading transition. Interstitial OFF by default.','safe'=>'High caution'],
            ['key'=>'home.action.quick_tools.books','title'=>'Quick Tool: Books','group'=>'home','note'=>'Library transition eligible for centrally timed interstitials.','safe'=>'Medium'],
            ['key'=>'home.action.other','title'=>'Other Home Navigation','group'=>'home','note'=>'Fallback for future Home destinations until an exact AppsHub rule is added.','safe'=>'Medium'],
            ['key'=>'watch.native.channel_list','title'=>'Watch Channel List Native Injection','group'=>'watch','note'=>'Automatic native insertion in the Watch browsing card list using backend frequency.','safe'=>'Recommended'],
            ['key'=>'watch.channels','title'=>'Watch Channels Browser','group'=>'watch','note'=>'Channel browsing/list page. Banner and native ads may be used without covering playback controls.','safe'=>'Recommended'],
            ['key'=>'watch.video_list','title'=>'Watch Video List','group'=>'watch','note'=>'Video browsing list. Native insertion is allowed; active playback is controlled separately.','safe'=>'Medium'],
            ['key'=>'watch.playlist_list','title'=>'Watch Playlist List','group'=>'watch','note'=>'Playlist browsing list. Keep ads between cards, not inside playback.','safe'=>'Medium'],
            ['key'=>'watch.player.hls','title'=>'HLS Player','group'=>'watch','note'=>'Active HLS playback surface. Keep banner, native and interstitial OFF.','safe'=>'High caution'],
            ['key'=>'watch.player.youtube','title'=>'YouTube Player','group'=>'watch','note'=>'Active YouTube playback surface. Keep banner, native and interstitial OFF.','safe'=>'High caution'],
            ['key'=>'watch.player.web_embed','title'=>'Web / Embed Player','group'=>'watch','note'=>'Embedded playback surface. Keep ads OFF to avoid obstruction and accidental clicks.','safe'=>'High caution'],
            ['key'=>'watch.player.live','title'=>'Live Player','group'=>'watch','note'=>'Live viewing surface. Keep all intrusive formats OFF.','safe'=>'High caution'],
            ['key'=>'watch.action.open_channels','title'=>'Open Watch Channels','group'=>'watch','note'=>'Natural transition into a channel browser; eligible for centrally timed interstitials.','safe'=>'Recommended'],
            ['key'=>'watch.action.open_video_collection','title'=>'Open Video Collection','group'=>'watch','note'=>'Transition into a browsable video collection; eligible for centrally timed interstitials.','safe'=>'Medium'],
            ['key'=>'watch.action.open_playlist','title'=>'Open Playlist','group'=>'watch','note'=>'Playlist transition; controlled by global cooldown and action count.','safe'=>'Medium'],
            ['key'=>'watch.action.open_live_hls','title'=>'Open Live HLS','group'=>'watch','note'=>'Direct live playback transition. Interstitial OFF by default.','safe'=>'High caution'],
            ['key'=>'watch.action.open_youtube','title'=>'Open YouTube Video','group'=>'watch','note'=>'Direct playback transition. Interstitial OFF by default.','safe'=>'High caution'],
            ['key'=>'watch.action.open_web_embed','title'=>'Open Web / Embed Player','group'=>'watch','note'=>'Direct embedded playback transition. Interstitial OFF by default.','safe'=>'High caution'],
            ['key'=>'watch.action.other','title'=>'Other Watch Navigation','group'=>'watch','note'=>'Fallback for future Watch destinations until an exact rule is added.','safe'=>'Medium'],
            ['key'=>'inspire:sod_external_read','title'=>'Read SOD External Link','group'=>'inspire','note'=>'External website link. Keep app ads OFF before/inside external content.','safe'=>'High caution'],
            ['key'=>'inspire:sod_watch_playlist','title'=>'Watch SOD Playlist','group'=>'inspire','note'=>'External YouTube playlist. Keep interstitial OFF.','safe'=>'High caution'],
            ['key'=>'inspire:sod_quotes','title'=>'SOD Quotes','group'=>'inspire','note'=>'Quote list can support banner/native ads.','safe'=>'Recommended'],
            ['key'=>'inspire:sod_quiz','title'=>'SOD Quiz','group'=>'inspire','note'=>'Queued quiz page. Banner/native allowed carefully.','safe'=>'Medium'],
            ['key'=>'inspire:sod_list','title'=>'SOD List','group'=>'inspire','note'=>'Good for banner/native between lists.','safe'=>'Recommended'],
            ['key'=>'inspire:sod_read','title'=>'SOD Reading','group'=>'inspire','note'=>'Keep ads low because users are reading devotionals.','safe'=>'High caution'],
            ['key'=>'inspire:short_videos','title'=>'Inspire Short Video Carousel','group'=>'shorts','note'=>'Reels/shorts-style content under Inspire.','safe'=>'Medium'],
            ['key'=>'inspire:motivation_list','title'=>'Motivation List','group'=>'inspire','note'=>'Good for banner/native between cards.','safe'=>'Recommended'],
            ['key'=>'inspire:motivation_read','title'=>'Motivation Reading','group'=>'inspire','note'=>'Keep interstitial OFF.','safe'=>'High caution'],
            ['key'=>'inspire:wordification_list','title'=>'Wordification List','group'=>'inspire','note'=>'Good for banner/native between cards.','safe'=>'Recommended'],
            ['key'=>'inspire:wordification_read','title'=>'Wordification Reading','group'=>'inspire','note'=>'Keep interstitial OFF.','safe'=>'High caution'],
            ['key'=>'inspire:highlights_list','title'=>'Highlights List','group'=>'inspire','note'=>'Good for banner/native between cards.','safe'=>'Recommended'],
            ['key'=>'inspire:highlights_read','title'=>'Highlights Reading','group'=>'inspire','note'=>'Keep interstitial OFF.','safe'=>'High caution'],
            ['key'=>'inspire:articles_list','title'=>'Articles List','group'=>'inspire','note'=>'Good for native/banner ads.','safe'=>'Recommended'],
            ['key'=>'inspire:articles_read','title'=>'Article Reading','group'=>'inspire','note'=>'Keep heavy ads OFF on reading pages.','safe'=>'High caution'],
            ['key'=>'inspire:article_quiz','title'=>'Article Quiz','group'=>'inspire','note'=>'Future quiz for articles. Banner/native allowed carefully.','safe'=>'Medium'],
            ['key'=>'explore:quote_creator','title'=>'Quote Creator','group'=>'explore','note'=>'Ad can show after export/share actions, but should not disturb design work.','safe'=>'Medium'],
            ['key'=>'explore:notes','title'=>'Notes','group'=>'explore','note'=>'Keep ads light because users are writing.','safe'=>'Medium'],
            ['key'=>'explore:bible','title'=>'Bible','group'=>'explore','note'=>'Recommended OFF or very limited for reading focus.','safe'=>'High caution'],
            ['key'=>'explore:books','title'=>'Book Library','group'=>'explore','note'=>'Library list can use banner/native.','safe'=>'Medium'],
            ['key'=>'explore:book_reader','title'=>'Book Reader','group'=>'explore','note'=>'Reading page should have low/no ads.','safe'=>'High caution'],
            ['key'=>'explore:games','title'=>'Games Hub','group'=>'explore','note'=>'Can support banner/native. Rewarded later.','safe'=>'Medium'],
            ['key'=>'explore:dominion_match','title'=>'Dominion Match Game','group'=>'explore','note'=>'Candy-crush style game. Rewarded ads can be added later.','safe'=>'Medium'],
            ['key'=>'explore:future_games','title'=>'Future Games','group'=>'explore','note'=>'Reserved for future Christian games.','safe'=>'Medium'],
            ['key'=>'explore:quiz','title'=>'Quiz Tools','group'=>'explore','note'=>'Bible/SOD/article quizzes can support careful ads.','safe'=>'Medium'],
            ['key'=>'explore:short_videos','title'=>'Explore Short Video Carousel','group'=>'shorts','note'=>'Short video page if exposed under Explore.','safe'=>'Medium'],
            ['key'=>'game','title'=>'All Games Policy','group'=>'games','note'=>'Parent policy inherited by standalone game screens when a specific game rule is not saved.','safe'=>'Medium'],
            ['key'=>'game.race_of_faith','title'=>'Race of Faith','group'=>'games','note'=>'Runner game. Top gameplay banner, native on home/level/result, interstitial only after level complete or game over.','safe'=>'Medium'],
            ['key'=>'game.dominion_match','title'=>'Dominion Match','group'=>'games','note'=>'Match/candy-crush style game. Banner/native allowed carefully; interstitial only after level/result.','safe'=>'Medium'],
            ['key'=>'game.dominion_builder','title'=>'Dominion Builder','group'=>'games','note'=>'Future builder game policy. Banner/native allowed carefully after menus/results.','safe'=>'Medium'],
            ['key'=>'game.future_games','title'=>'Future Games','group'=>'games','note'=>'Default policy for future games until a specific game policy is created.','safe'=>'Medium'],
            ['key'=>'quiz','title'=>'All Quiz Policy','group'=>'games','note'=>'Parent policy inherited by Bible, SOD, article, and general quiz screens.','safe'=>'Medium'],
            ['key'=>'quiz.bible','title'=>'Bible Quiz Engine','group'=>'games','note'=>'Bible quiz gameplay/list/result ads. Reading Bible itself remains under Bible/Reader caution controls.','safe'=>'Medium'],
            ['key'=>'quiz.bible_quiz','title'=>'Bible Quiz Route','group'=>'games','note'=>'Legacy/specific Bible quiz route policy for frontend compatibility.','safe'=>'Medium'],
            ['key'=>'quiz.sod','title'=>'SOD Quiz Engine','group'=>'games','note'=>'Seed of Destiny quiz gameplay/list/result ads.','safe'=>'Medium'],
            ['key'=>'quiz.sod_quiz','title'=>'SOD Quiz Route','group'=>'games','note'=>'Legacy/specific SOD quiz route policy for frontend compatibility.','safe'=>'Medium'],
            ['key'=>'quiz.article','title'=>'Article Quiz','group'=>'games','note'=>'Future article quiz gameplay/result policy.','safe'=>'Medium'],
            ['key'=>'quiz.general','title'=>'General Quiz','group'=>'games','note'=>'General knowledge quiz policy for future quiz packs.','safe'=>'Medium'],
            ['key'=>'more:account','title'=>'Account','group'=>'more','note'=>'Keep ads OFF around account settings.','safe'=>'High caution'],
            ['key'=>'more:settings','title'=>'Settings','group'=>'more','note'=>'Keep ads minimal.','safe'=>'Low'],
            ['key'=>'more:about','title'=>'About','group'=>'more','note'=>'Can allow banner only if needed.','safe'=>'Low'],
            ['key'=>'more:contact','title'=>'Contact / Support','group'=>'more','note'=>'Legacy compatibility key.','safe'=>'Low'],

            ['key'=>'inspire.sod','title'=>'SOD Hub','group'=>'inspire','note'=>'Canonical SOD hub policy.','safe'=>'Medium'],
            ['key'=>'inspire.sod.read','title'=>'SOD Reader','group'=>'inspire','note'=>'Devotional reading page. Keep interruptions low.','safe'=>'High caution'],
            ['key'=>'inspire.sod.watch','title'=>'SOD Video','group'=>'inspire','note'=>'Protected playback page.','safe'=>'High caution'],
            ['key'=>'inspire.sod.quotes','title'=>'SOD Quotes','group'=>'inspire','note'=>'Quote list can use backend-controlled native and banner ads.','safe'=>'Recommended'],
            ['key'=>'inspire.sod.quiz','title'=>'SOD Quiz','group'=>'inspire','note'=>'Quiz menu/result policy; active questions use quiz.active.','safe'=>'Medium'],
            ['key'=>'inspire.message_highlights','title'=>'Message Highlights','group'=>'inspire','note'=>'Highlights list policy.','safe'=>'Recommended'],
            ['key'=>'inspire.message_highlights.detail','title'=>'Message Highlight Detail','group'=>'inspire','note'=>'Detail reading page.','safe'=>'Medium'],
            ['key'=>'inspire.inside_dunamis','title'=>'Inside Dunamis','group'=>'inspire','note'=>'Inside Dunamis list policy.','safe'=>'Recommended'],
            ['key'=>'inspire.inside_dunamis.detail','title'=>'Inside Dunamis Detail','group'=>'inspire','note'=>'Detail reading page.','safe'=>'Medium'],
            ['key'=>'inspire.wordification','title'=>'Wordification','group'=>'inspire','note'=>'Wordification list policy.','safe'=>'Recommended'],
            ['key'=>'inspire.wordification.detail','title'=>'Wordification Detail','group'=>'inspire','note'=>'Detail reading page.','safe'=>'Medium'],
            ['key'=>'inspire.motivation','title'=>'Motivation','group'=>'inspire','note'=>'Motivation list policy.','safe'=>'Recommended'],
            ['key'=>'inspire.motivation.detail','title'=>'Motivation Detail','group'=>'inspire','note'=>'Detail reading page.','safe'=>'Medium'],

            ['key'=>'explore.quick_tools','title'=>'Quick Tools','group'=>'explore','note'=>'Canonical Quick Tools parent.','safe'=>'Medium'],
            ['key'=>'explore.quote_creator','title'=>'Quote Creator','group'=>'explore','note'=>'Editor page; avoid intrusive interstitials while editing.','safe'=>'Medium'],
            ['key'=>'explore.quote_library','title'=>'Quote Library','group'=>'explore','note'=>'List page suitable for native insertion.','safe'=>'Recommended'],
            ['key'=>'explore.notes','title'=>'Notes','group'=>'explore','note'=>'List page; protect active typing/editing.','safe'=>'Medium'],
            ['key'=>'explore.bible','title'=>'Bible','group'=>'explore','note'=>'Reading page. Keep ads conservative.','safe'=>'High caution'],
            ['key'=>'explore.books','title'=>'Books','group'=>'explore','note'=>'Books parent policy.','safe'=>'Medium'],
            ['key'=>'explore.books.library','title'=>'Book Library','group'=>'explore','note'=>'Native ads may be inserted between books.','safe'=>'Recommended'],
            ['key'=>'explore.books.reader','title'=>'Book Reader','group'=>'explore','note'=>'Protected reading page; placeholders must collapse.','safe'=>'High caution'],
            ['key'=>'explore.games','title'=>'Games Hub','group'=>'explore','note'=>'Games listing and preview policy.','safe'=>'Medium'],
            ['key'=>'explore.games.bible_quiz','title'=>'Bible Quiz Entry','group'=>'games','note'=>'Entry/menu policy before active quiz.','safe'=>'Medium'],
            ['key'=>'explore.games.dominion_match','title'=>'Dominion Match Entry','group'=>'games','note'=>'Entry/menu policy before active gameplay.','safe'=>'Medium'],
            ['key'=>'explore.games.race_of_faith','title'=>'Race of Faith Entry','group'=>'games','note'=>'Entry/menu policy before active gameplay.','safe'=>'Medium'],
            ['key'=>'explore.games.kingdom_builder','title'=>'Kingdom Builder Entry','group'=>'games','note'=>'Entry/menu policy before active gameplay.','safe'=>'Medium'],
            ['key'=>'explore.shorts','title'=>'Short Video Reel','group'=>'shorts','note'=>'Short-video feed policy.','safe'=>'Medium'],
            ['key'=>'explore.shorts.player','title'=>'Short Video Reel Player','group'=>'shorts','note'=>'Native appears as a dedicated feed item, never as overlay.','safe'=>'High caution'],
            ['key'=>'shorts.library','title'=>'Short Video Library','group'=>'shorts','note'=>'Two-column library grid. Native ads span full width after the backend-configured number of items.','safe'=>'Medium'],
            ['key'=>'game.race_of_faith.active','title'=>'Race of Faith Active Gameplay','group'=>'games','note'=>'Protected active gameplay. Keep all ads OFF.','safe'=>'High caution'],
            ['key'=>'game.dominion_match.active','title'=>'Dominion Match Active Gameplay','group'=>'games','note'=>'Protected active gameplay. Keep all ads OFF.','safe'=>'High caution'],
            ['key'=>'game.kingdom_builder.active','title'=>'Kingdom Builder Active Gameplay','group'=>'games','note'=>'Protected active gameplay. Keep all ads OFF.','safe'=>'High caution'],
            ['key'=>'quiz.active','title'=>'Active Quiz Question','group'=>'games','note'=>'Protected question screen. Keep intrusive ads OFF.','safe'=>'High caution'],

            ['key'=>'more.account','title'=>'Account','group'=>'more','note'=>'Protected account/login flow.','safe'=>'High caution'],
            ['key'=>'more.saved','title'=>'Saved','group'=>'more','note'=>'Saved-content list with backend-controlled native and banner ads.','safe'=>'Medium'],
            ['key'=>'more.downloads','title'=>'Downloads','group'=>'more','note'=>'Downloads list with backend-controlled native and banner ads.','safe'=>'Medium'],
            ['key'=>'more.notifications','title'=>'Notifications','group'=>'more','note'=>'Notification list with backend-controlled native and banner ads.','safe'=>'Medium'],
            ['key'=>'more.notifications.message','title'=>'Notification Message','group'=>'more','note'=>'Detail message; native only after the message boundary.','safe'=>'Medium'],
            ['key'=>'more.settings','title'=>'Settings','group'=>'more','note'=>'Keep ads minimal.','safe'=>'Low'],
            ['key'=>'more.support','title'=>'Support','group'=>'more','note'=>'Support page policy.','safe'=>'Low'],
            ['key'=>'more.about','title'=>'About','group'=>'more','note'=>'About page policy.','safe'=>'Low'],
            ['key'=>'more.privacy','title'=>'Privacy Policy','group'=>'more','note'=>'Legal reading page.','safe'=>'High caution'],
            ['key'=>'more.terms','title'=>'Terms and Conditions','group'=>'more','note'=>'Legal reading page.','safe'=>'High caution'],
        ];

        $visibleInnerPlacements = collect($innerPlacements)->filter(fn ($item) => $activeGroup === 'all' || $item['group'] === $activeGroup)->values();
        $boolLabel = fn ($state) => $state ? 'ON' : 'OFF';
        $toggleForm = function ($rule, string $field, string $tab, string $label, string $group = '') {
            $active = (bool) ($rule->{$field} ?? false);
            return '<form method="POST" action="' . e(route('admin.beginner.ads.toggle-rule')) . '" class="dxm-toggle-form">' . csrf_field() . method_field('PATCH') . '<input type="hidden" name="scope_type" value="' . e($rule->scope_type) . '"><input type="hidden" name="scope_key" value="' . e($rule->scope_key) . '"><input type="hidden" name="field" value="' . e($field) . '"><input type="hidden" name="tab" value="' . e($tab) . '"><input type="hidden" name="group" value="' . e($group) . '"><button type="submit" class="dxm-switch ' . ($active ? 'on' : 'off') . '"><span>' . e($label) . '</span><strong>' . ($active ? 'ON' : 'OFF') . '</strong></button></form>';
        };
        $summaryStats = ['master' => $profile?->ads_enabled ? 'ON' : 'OFF', 'rules' => $rules->count(), 'active' => $rules->where('is_enabled', true)->count(), 'units' => collect($unitStatus)->filter()->count() . '/3'];
    @endphp

    <style>
        .dxm-ads-shell{display:grid;gap:16px;padding-bottom:28px}.dxm-ads-hero{border:1px solid rgba(34,211,238,.18);border-radius:26px;padding:20px;background:radial-gradient(circle at top left,rgba(34,211,238,.18),transparent 34%),radial-gradient(circle at top right,rgba(236,72,153,.15),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(15,23,42,.88))}.dxm-ads-hero-inner{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.dxm-kicker{display:inline-flex;align-items:center;min-height:28px;padding:6px 11px;border-radius:999px;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:rgba(207,250,254,.96);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-title{margin:11px 0 0;color:#fff;font-size:clamp(28px,4vw,42px);line-height:1.03;font-weight:950;letter-spacing:-.055em}.dxm-sub{margin-top:9px;max-width:900px;color:rgba(255,255,255,.66);font-size:13px;line-height:1.6}.dxm-stat-grid{display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:10px;min-width:280px}.dxm-stat{padding:14px;border:1px solid rgba(255,255,255,.10);border-radius:18px;background:rgba(2,6,23,.48)}.dxm-stat strong{display:block;color:#fff;font-size:22px;line-height:1}.dxm-stat span{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11px;font-weight:850}.dxm-tabs,.dxm-filter-tabs{display:flex;gap:8px;flex-wrap:wrap;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:9px;background:rgba(3,7,18,.88);backdrop-filter:blur(14px)}.dxm-tabs{position:sticky;top:72px;z-index:20}.dxm-tab{display:inline-flex;gap:7px;align-items:center;min-height:36px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;text-decoration:none}.dxm-tab:hover,.dxm-tab.active{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.12);color:#fff}.dxm-panel{border:1px solid rgba(255,255,255,.09);border-radius:22px;padding:15px;background:rgba(255,255,255,.035)}.dxm-section-title{margin:0;color:#fff;font-size:20px;font-weight:950;letter-spacing:-.035em}.dxm-grid,.dxm-placement-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:12px}.dxm-card,.dxm-placement-card{border:1px solid rgba(255,255,255,.09);border-radius:20px;background:rgba(2,6,23,.42);padding:15px;display:grid;gap:11px;min-height:140px}.dxm-card.good,.dxm-placement-card.safe-good{border-color:rgba(34,197,94,.22);background:linear-gradient(135deg,rgba(34,197,94,.11),rgba(2,6,23,.42))}.dxm-card.warn,.dxm-placement-card.safe-medium{border-color:rgba(245,158,11,.23);background:linear-gradient(135deg,rgba(245,158,11,.11),rgba(2,6,23,.42))}.dxm-card.danger,.dxm-placement-card.safe-danger{border-color:rgba(239,68,68,.25);background:linear-gradient(135deg,rgba(239,68,68,.10),rgba(2,6,23,.42))}.dxm-card strong,.dxm-placement-title{color:#fff;font-size:15px;font-weight:950}.dxm-card small,.dxm-placement-note{color:rgba(255,255,255,.62);font-size:12px;line-height:1.5}.dxm-btn-row{display:flex;gap:8px;flex-wrap:wrap}.dxm-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.11);background:rgba(255,255,255,.055);color:rgba(255,255,255,.84);font-size:12px;font-weight:900;text-decoration:none;cursor:pointer}.dxm-btn.primary{border-color:rgba(34,211,238,.30);background:rgba(34,211,238,.10);color:#e0faff}.dxm-pill-row{display:flex;gap:7px;flex-wrap:wrap}.dxm-pill{display:inline-flex;align-items:center;min-height:24px;padding:5px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.055);color:rgba(255,255,255,.70);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}.dxm-pill.on{border-color:rgba(34,197,94,.28);background:rgba(34,197,94,.12);color:#bbf7d0}.dxm-pill.off{border-color:rgba(239,68,68,.25);background:rgba(239,68,68,.10);color:#fecaca}.dxm-switch-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.dxm-toggle-form{margin:0}.dxm-switch{width:100%;border:1px solid rgba(255,255,255,.11);border-radius:14px;padding:9px 10px;display:flex;align-items:center;justify-content:space-between;gap:8px;background:rgba(255,255,255,.055);color:rgba(255,255,255,.72);cursor:pointer}.dxm-switch span{font-size:11px;font-weight:900}.dxm-switch strong{font-size:11px;font-weight:950}.dxm-switch.on{border-color:rgba(34,197,94,.28);background:rgba(34,197,94,.12);color:#bbf7d0}.dxm-switch.off{border-color:rgba(239,68,68,.22);background:rgba(239,68,68,.08);color:#fecaca}.dxm-form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.dxm-field{display:grid;gap:7px}.dxm-field label{color:#fff;font-size:12px;font-weight:900}.dxm-field input,.dxm-field select{width:100%;border:1px solid rgba(255,255,255,.12);border-radius:12px;background:rgba(2,6,23,.68);color:#fff;padding:10px 12px}.dxm-behavior-form{display:grid;gap:10px;padding-top:10px;border-top:1px solid rgba(255,255,255,.08)}.dxm-form-grid.compact{grid-template-columns:repeat(3,minmax(0,1fr))}.dxm-check{display:flex;align-items:center;gap:8px;color:rgba(255,255,255,.82);font-size:12px;font-weight:850}.dxm-alert{border:1px dashed rgba(34,211,238,.24);border-radius:18px;background:rgba(34,211,238,.07);padding:13px;color:rgba(224,250,255,.85);font-size:12px;line-height:1.55}@media(max-width:980px){.dxm-ads-hero-inner{grid-template-columns:1fr}.dxm-stat-grid{min-width:0}}@media(max-width:980px){.dxm-form-grid{grid-template-columns:1fr 1fr}}@media(max-width:760px){.dxm-form-grid{grid-template-columns:1fr}.dxm-tabs{top:56px;overflow-x:auto;flex-wrap:nowrap}.dxm-tab{flex:0 0 auto}.dxm-stat-grid,.dxm-switch-row{grid-template-columns:1fr}.dxm-grid,.dxm-placement-grid{grid-template-columns:1fr}}
    </style>

    <div class="dxm-ads-shell">
        @if (session('status'))<div class="dxm-alert">{{ session('status') }}</div>@endif

        <section class="dxm-ads-hero"><div class="dxm-ads-hero-inner"><div><div class="dxm-kicker">Simple Ad Control Board</div><h1 class="dxm-title">{{ $activeApp?->name ?? 'Selected App' }} Ads</h1><div class="dxm-sub">The frontend already contains ad zones. This page decides where ads are allowed, which provider is active, and which format can show there.</div><div class="dxm-btn-row" style="margin-top:14px;"><form method="POST" action="{{ route('admin.beginner.ads.toggle-master') }}">@csrf @method('PATCH')<button class="dxm-btn primary" type="submit">{{ $profile?->ads_enabled ? 'Turn Master Ads OFF' : 'Turn Master Ads ON' }}</button></form><form method="POST" action="{{ route('admin.beginner.ads.sync-defaults') }}">@csrf <button class="dxm-btn" type="submit">Create / Refresh Default Rules</button></form><a href="{{ url('/admin/ads-monetization?tab=setup#global-controls') }}" class="dxm-btn">Ad IDs & Global Frequency</a></div></div><div class="dxm-stat-grid"><div class="dxm-stat"><strong>{{ $summaryStats['master'] }}</strong><span>Master Ads</span></div><div class="dxm-stat"><strong>{{ strtoupper(str_replace('_', ' ', $provider)) }}</strong><span>Provider</span></div><div class="dxm-stat"><strong>{{ $summaryStats['units'] }}</strong><span>Unit IDs Set</span></div><div class="dxm-stat"><strong>{{ $summaryStats['active'] }}/{{ $summaryStats['rules'] }}</strong><span>Active Rules</span></div></div></div></section>

        <nav class="dxm-tabs" aria-label="Ads sections">@foreach ($tabs as $key => $label)<a class="dxm-tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ url('/admin/ads-monetization') }}?tab={{ $key }}">{{ $label }}</a>@endforeach</nav>

        @if ($activeTab === 'overview')
            <section class="dxm-panel"><h2 class="dxm-section-title">Beginner Overview</h2><div class="dxm-sub">Use Main Tabs and Inner Pages to control ads visually. Use Providers & IDs only when changing the ad provider or unit IDs.</div></section>
            <section class="dxm-grid"><div class="dxm-card {{ $profile?->ads_enabled ? 'good' : 'warn' }}"><strong>1. Master Ads</strong><small>Turns all app ads on or off from the backend.</small><span class="dxm-pill {{ $profile?->ads_enabled ? 'on' : 'off' }}">{{ $profile?->ads_enabled ? 'ON' : 'OFF' }}</span></div><div class="dxm-card"><strong>2. Provider</strong><small>Active provider: {{ strtoupper(str_replace('_', ' ', $provider)) }}. Other providers are prepared for future expansion.</small><a href="{{ url('/admin/ads-monetization?tab=setup') }}" class="dxm-btn primary">Open Providers</a></div><div class="dxm-card good"><strong>3. Main Tab Ads</strong><small>Home, Watch, Inspire, Explore, and More each have their own ad switches.</small><a href="{{ url('/admin/ads-monetization?tab=placements') }}" class="dxm-btn primary">Open Main Tabs</a></div><div class="dxm-card warn"><strong>4. Inner Pages</strong><small>Inner pages are grouped under Home, Watch, Inspire, Explore, and More.</small><a href="{{ url('/admin/ads-monetization?tab=inner&group=all') }}" class="dxm-btn primary">Open Inner Pages</a></div></section>
        @elseif ($activeTab === 'setup')
            <section class="dxm-panel"><h2 class="dxm-section-title">Ad Providers & Unit IDs</h2><div class="dxm-sub">AdMob remains recommended for the current Flutter app. Other providers are prepared here so the backend can scale later.</div></section>
            <section class="dxm-grid">@foreach ($providerCards as $card)<div class="dxm-card {{ $provider === $card['key'] ? 'good' : '' }}"><strong>{{ $card['title'] }}</strong><small>{{ $card['note'] }}</small><div class="dxm-pill-row"><span class="dxm-pill {{ $provider === $card['key'] ? 'on' : '' }}">{{ $provider === $card['key'] ? 'Active' : $card['status'] }}</span></div><form method="POST" action="{{ route('admin.beginner.ads.update-provider') }}">@csrf @method('PATCH')<input type="hidden" name="provider" value="{{ $card['key'] }}"><button class="dxm-btn {{ $provider === $card['key'] ? '' : 'primary' }}" type="submit">{{ $provider === $card['key'] ? 'Currently Active' : 'Set Active Provider' }}</button></form></div>@endforeach<div class="dxm-card"><strong>Banner Unit</strong><small>{{ $profile?->banner_unit_id ?: 'Not set yet.' }}</small><span class="dxm-pill {{ $unitStatus['banner'] ? 'on' : 'off' }}">{{ $unitStatus['banner'] ? 'Set' : 'Missing' }}</span></div><div class="dxm-card"><strong>Native Unit</strong><small>{{ $profile?->native_unit_id ?: 'Not set yet.' }}</small><span class="dxm-pill {{ $unitStatus['native'] ? 'on' : 'off' }}">{{ $unitStatus['native'] ? 'Set' : 'Missing' }}</span></div><div class="dxm-card"><strong>Interstitial Unit</strong><small>{{ $profile?->interstitial_unit_id ?: 'Not set yet.' }}</small><span class="dxm-pill {{ $unitStatus['interstitial'] ? 'on' : 'off' }}">{{ $unitStatus['interstitial'] ? 'Set' : 'Missing' }}</span></div></section>
            <section id="global-controls" class="dxm-panel">
                <h2 class="dxm-section-title">Global Ad IDs, Frequency & Timing</h2>
                <div class="dxm-sub">These values are delivered through the bootstrap API. Flutter must follow them without a new app build.</div>
                <form method="POST" action="{{ route('admin.beginner.ads.update-global-settings') }}" style="margin-top:16px;display:grid;gap:16px;">
                    @csrf @method('PATCH')
                    <div class="dxm-form-grid">
                        <div class="dxm-field"><label>AdMob Banner Unit ID</label><input name="banner_unit_id" value="{{ old('banner_unit_id', $profile?->banner_unit_id) }}"></div>
                        <div class="dxm-field"><label>AdMob Native Unit ID</label><input name="native_unit_id" value="{{ old('native_unit_id', $profile?->native_unit_id) }}"></div>
                        <div class="dxm-field"><label>AdMob Interstitial Unit ID</label><input name="interstitial_unit_id" value="{{ old('interstitial_unit_id', $profile?->interstitial_unit_id) }}"></div>
                    </div>
                    <div class="dxm-form-grid">
                        <label class="dxm-check"><input type="hidden" name="allow_banner" value="0"><input type="checkbox" name="allow_banner" value="1" @checked($formatSettings['banner'])> Allow Banner Ads</label>
                        <label class="dxm-check"><input type="hidden" name="allow_native" value="0"><input type="checkbox" name="allow_native" value="1" @checked($formatSettings['native'])> Allow Native Ads</label>
                        <label class="dxm-check"><input type="hidden" name="allow_interstitial" value="0"><input type="checkbox" name="allow_interstitial" value="1" @checked($formatSettings['interstitial'])> Allow Interstitial Ads</label>
                    </div>
                    <div class="dxm-form-grid">
                        <label class="dxm-check"><input type="hidden" name="native_list_enabled" value="0"><input type="checkbox" name="native_list_enabled" value="1" @checked($nativeSettings['enabled'])> Enable Native Ads in Lists</label>
                        <div class="dxm-field"><label>First native after item</label><input type="number" min="1" max="100" name="native_start_after" value="{{ $nativeSettings['start_after'] }}"></div>
                        <div class="dxm-field"><label>Repeat native every N items</label><input type="number" min="1" max="100" name="native_every" value="{{ $nativeSettings['every'] }}"></div>
                        <div class="dxm-field"><label>Maximum native ads per list (0 = unlimited)</label><input type="number" min="0" max="100" name="native_max_per_list" value="{{ $nativeSettings['max_per_list'] }}"></div>
                        <div class="dxm-field"><label>Interstitial cooldown (seconds)</label><input type="number" min="1" max="86400" name="interstitial_cooldown_seconds" value="{{ $interstitialSettings['cooldown_seconds'] }}"></div>
                        <div class="dxm-field"><label>Show after every N safe actions</label><input type="number" min="1" max="100" name="interstitial_every_n_safe_actions" value="{{ $interstitialSettings['every_n_safe_actions'] }}"></div>
                        <div class="dxm-field"><label>Minimum launch delay (seconds)</label><input type="number" min="0" max="86400" name="interstitial_min_launch_delay_seconds" value="{{ $interstitialSettings['minimum_launch_delay_seconds'] }}"></div>
                        <div class="dxm-field"><label>Maximum interstitials per session (0 = unlimited)</label><input type="number" min="0" max="100" name="interstitial_max_per_session" value="{{ $interstitialSettings['maximum_per_session'] }}"></div>
                        <div class="dxm-field"><label>Minimum page dwell before eligible (seconds)</label><input type="number" min="0" max="3600" name="interstitial_min_page_dwell_seconds" value="{{ $interstitialSettings['minimum_page_dwell_seconds'] }}"></div>
                    </div>
                    <div class="dxm-alert">Testing: use 10–15 seconds and 1 safe action. Production: return to approximately 90–120 seconds and 4 safe actions.</div>
                    <div><button class="dxm-btn primary" type="submit">Save Global Ad Settings</button></div>
                </form>
            </section>
        @elseif ($activeTab === 'placements')
            <section class="dxm-panel"><h2 class="dxm-section-title">Main Tab Placements</h2><div class="dxm-sub">Control ads per major app tab. Inner pages are arranged separately under the Inner Pages tab.</div></section>
            <section class="dxm-placement-grid">@foreach ($tabPlacements as $placement)@php $rule = $ruleFor('tab', $placement['key']); $safeClass = $placement['safe'] === 'Recommended' ? 'safe-good' : ($placement['safe'] === 'High caution' ? 'safe-danger' : 'safe-medium'); @endphp<article id="ad-rule-tab-{{ $placement['key'] }}" class="dxm-placement-card {{ $safeClass }}"><div><h3 class="dxm-placement-title">{{ $placement['title'] }}</h3><div class="dxm-placement-note">{{ $placement['note'] }}</div></div><div class="dxm-pill-row"><span class="dxm-pill {{ $rule->is_enabled ? 'on' : 'off' }}">Placement {{ $boolLabel($rule->is_enabled) }}</span><span class="dxm-pill">{{ $placement['safe'] }}</span></div><div class="dxm-switch-row">{!! $toggleForm($rule, 'is_enabled', 'placements', 'Placement') !!}{!! $toggleForm($rule, 'banner_enabled', 'placements', 'Banner') !!}{!! $toggleForm($rule, 'native_enabled', 'placements', 'Native') !!}{!! $toggleForm($rule, 'interstitial_enabled', 'placements', 'Interstitial') !!}</div><a href="{{ url('/admin/ads-monetization?tab=inner&group=' . $placement['key']) }}" class="dxm-btn primary">View {{ $placement['title'] }} Inner Pages</a></article>@endforeach</section>
        @elseif ($activeTab === 'inner')
            <section class="dxm-panel"><h2 class="dxm-section-title">Inner Page Placements</h2><div class="dxm-sub">Grouped by Home, Watch, Inspire, Explore, Short Videos, Games / Quizzes, and More so game engines can be controlled separately without mixing them into generic Explore rules.</div></section>
            <nav class="dxm-filter-tabs">@foreach ($innerGroups as $key => $label)<a class="dxm-tab {{ $activeGroup === $key ? 'active' : '' }}" href="{{ url('/admin/ads-monetization?tab=inner&group=' . $key) }}">{{ $label }}</a>@endforeach</nav>
            <section class="dxm-placement-grid">
                @foreach ($visibleInnerPlacements as $placement)
                    @php
                        $rule = $ruleFor('route', $placement['key']);
                        $behavior = $settingsFor($rule, 'route', $placement['key']);
                        $safeClass = $placement['safe'] === 'Recommended' ? 'safe-good' : ($placement['safe'] === 'High caution' ? 'safe-danger' : 'safe-medium');
                    @endphp
                    <article id="ad-rule-route-{{ str_replace([':', '.'], '-', $placement['key']) }}" class="dxm-placement-card {{ $safeClass }}">
                        <div><h3 class="dxm-placement-title">{{ $placement['title'] }}</h3><div class="dxm-placement-note">{{ ucfirst($placement['group']) }} inner page • {{ $placement['note'] }}</div></div>
                        <div class="dxm-pill-row"><span class="dxm-pill {{ $rule->is_enabled ? 'on' : 'off' }}">Placement {{ $boolLabel($rule->is_enabled) }}</span><span class="dxm-pill">{{ $placement['safe'] }}</span>@if (($rule->virtual ?? false) === true)<span class="dxm-pill">Not saved yet</span>@endif</div>
                        <div class="dxm-switch-row">{!! $toggleForm($rule, 'is_enabled', 'inner', 'Placement', $activeGroup) !!}{!! $toggleForm($rule, 'banner_enabled', 'inner', 'Banner', $activeGroup) !!}{!! $toggleForm($rule, 'native_enabled', 'inner', 'Native', $activeGroup) !!}{!! $toggleForm($rule, 'interstitial_enabled', 'inner', 'Interstitial', $activeGroup) !!}</div>
                        <form method="POST" action="{{ route('admin.beginner.ads.toggle-rule') }}" class="dxm-behavior-form">
                            @csrf @method('PATCH')
                            <input type="hidden" name="scope_type" value="route"><input type="hidden" name="scope_key" value="{{ $placement['key'] }}"><input type="hidden" name="tab" value="inner"><input type="hidden" name="group" value="{{ $activeGroup }}">
                            <div class="dxm-form-grid compact">
                                <div class="dxm-field"><label>Banner position</label><select name="banner_placement"><option value="page_bottom" @selected($behavior['banner']['placement'] === 'page_bottom')>Page bottom</option><option value="page_top" @selected($behavior['banner']['placement'] === 'page_top')>Page top</option><option value="shell_bottom" @selected($behavior['banner']['placement'] === 'shell_bottom')>Shell bottom</option><option value="disabled" @selected($behavior['banner']['placement'] === 'disabled')>Disabled</option></select></div>
                                <div class="dxm-field"><label>Native starts after</label><input type="number" min="0" max="100" name="native_start_after" value="{{ $behavior['native']['start_after'] }}"></div>
                                <div class="dxm-field"><label>Native every N</label><input type="number" min="1" max="100" name="native_every" value="{{ $behavior['native']['every'] }}"></div>
                                <div class="dxm-field"><label>Native maximum</label><input type="number" min="0" max="100" name="native_max_per_list" value="{{ $behavior['native']['max_per_list'] }}"></div>
                                <div class="dxm-field"><label>Interstitial every N actions</label><input type="number" min="1" max="100" name="interstitial_every_n_safe_actions" value="{{ $behavior['interstitial']['every_n_safe_actions'] }}"></div>
                                <div class="dxm-field"><label>Cooldown seconds</label><input type="number" min="1" max="86400" name="interstitial_cooldown_seconds" value="{{ $behavior['interstitial']['cooldown_seconds'] }}"></div>
                                <div class="dxm-field"><label>Launch delay</label><input type="number" min="0" max="86400" name="interstitial_min_launch_delay_seconds" value="{{ $behavior['interstitial']['minimum_launch_delay_seconds'] }}"></div>
                                <div class="dxm-field"><label>Max per session</label><input type="number" min="0" max="100" name="interstitial_max_per_session" value="{{ $behavior['interstitial']['maximum_per_session'] }}"></div>
                                <div class="dxm-field"><label>Page dwell seconds</label><input type="number" min="0" max="3600" name="interstitial_min_page_dwell_seconds" value="{{ $behavior['interstitial']['minimum_page_dwell_seconds'] }}"></div>
                            </div>
                            <button class="dxm-btn primary" type="submit">Save Behaviour</button>
                        </form>
                    </article>
                @endforeach
            </section>
        @else
            <section class="dxm-panel"><h2 class="dxm-section-title">Safety Guide</h2><div class="dxm-sub">Simple guide for non-technical editors.</div></section><section class="dxm-grid"><div class="dxm-card good"><strong>Good places for ads</strong><small>Home, Inspire lists, article lists, quote lists, and content feeds.</small><span class="dxm-pill on">Recommended</span></div><div class="dxm-card warn"><strong>Use carefully</strong><small>Explore tools, Notes, Quote Creator, Book Library, quizzes, and games.</small><span class="dxm-pill">Medium</span></div><div class="dxm-card danger"><strong>Keep ads low or OFF</strong><small>Watch/live streaming, Bible reading, article reading, devotional reading, video player, login/signup, and external links.</small><span class="dxm-pill off">High caution</span></div><div class="dxm-card"><strong>Dynamic future rule</strong><small>When new frontend routes/tabs are added, create a backend ad rule for that route key. Future drop can auto-discover them from destination builder route metadata.</small><span class="dxm-pill">Scalable</span></div></section>
        @endif
    </div>
</x-filament::page>

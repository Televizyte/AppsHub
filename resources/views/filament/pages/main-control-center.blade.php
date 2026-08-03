<x-filament::page>
    @php
        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);
        $activeApp = $activeAppId > 0 ? \App\Models\App::query()->find($activeAppId) : null;

        $apps = \App\Models\App::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(24)
            ->get();

        $stats = [
            'apps' => \App\Models\App::query()->count(),
            'active_apps' => \App\Models\App::query()->where('is_active', true)->count(),
            'sections' => \Illuminate\Support\Facades\Schema::hasTable('app_sections') ? \App\Models\AppSection::query()->count() : 0,
            'posts' => \Illuminate\Support\Facades\Schema::hasTable('content_posts') ? \App\Models\ContentPost::query()->count() : 0,
            'media' => \Illuminate\Support\Facades\Schema::hasTable('media_assets') ? \App\Models\MediaAsset::query()->count() : 0,
            'watch' => \Illuminate\Support\Facades\Schema::hasTable('watch_links') ? \App\Models\WatchLink::query()->count() : 0,
        ];

        $tabs = [
            'overview' => 'Overview',
            'apps' => 'App Manager',
            'engines' => 'Engines',
            'libraries' => 'Libraries',
            'admin' => 'Admin',
        ];

        $launchCards = [
            [
                'tab' => 'overview',
                'title' => 'Open App Workspace',
                'subtitle' => 'Continue managing the selected app without mixing global engine controls.',
                'icon' => '⌂',
                'url' => url('/admin/beginner-dashboard'),
                'badge' => $activeApp?->name ?? 'Selected App',
                'tone' => 'live',
            ],
            [
                'tab' => 'apps',
                'title' => 'Beginner App Manager',
                'subtitle' => 'Create, clone, switch, open, and configure apps from a clean beginner workspace.',
                'icon' => '▦',
                'url' => url('/admin/app-manager?workspace=list'),
                'badge' => $stats['apps'] . ' apps',
                'tone' => 'live',
            ],
            [
                'tab' => 'apps',
                'title' => 'Create New App',
                'subtitle' => 'Start a new app with identity, branding, theme, store/build, contact, and engine selection.',
                'icon' => '+',
                'url' => url('/admin/app-manager?workspace=create&builder_tab=identity'),
                'badge' => 'Beginner Builder',
                'tone' => 'accent',
            ],
            [
                'tab' => 'apps',
                'title' => 'Clone Existing App',
                'subtitle' => 'Create or update a target app by copying structure and optional capabilities from a source app.',
                'icon' => '⧉',
                'url' => url('/admin/app-manager?workspace=clone'),
                'badge' => 'Safe Clone',
                'tone' => 'accent',
            ],
            [
                'tab' => 'engines',
                'title' => 'Engine Room',
                'subtitle' => 'Global reusable engines: content, watch, short video, books, ads, quiz, notifications, media, API/AI usage.',
                'icon' => '⚙',
                'url' => url('/admin/engine-room'),
                'badge' => 'Global',
                'tone' => 'engine',
            ],
            [
                'tab' => 'libraries',
                'title' => 'Template Library',
                'subtitle' => 'Visual app, page, section, card, form, widget, icon, image, and layout templates.',
                'icon' => '▣',
                'url' => url('/admin/template-library'),
                'badge' => 'Visual library',
                'tone' => 'engine',
            ],
            [
                'tab' => 'libraries',
                'title' => 'Media Library',
                'subtitle' => 'Manage shared and app-scoped images, logos, banners, icons, thumbnails, audio, video, and files.',
                'icon' => '◩',
                'url' => url('/admin/media-library'),
                'badge' => $stats['media'] . ' assets',
                'tone' => 'live',
            ],
            [
                'tab' => 'admin',
                'title' => 'Users & Roles',
                'subtitle' => 'Create staff users, assign apps, assign roles, and preview permissions.',
                'icon' => '👥',
                'url' => url('/admin/roles-permissions?tab=admin_users'),
                'badge' => 'Live',
                'tone' => 'live',
            ],
            [
                'tab' => 'admin',
                'title' => 'Usage Monitor',
                'subtitle' => 'Future dashboard for API, AI, storage, media, push, and app engine usage.',
                'icon' => '▥',
                'url' => null,
                'badge' => 'Queued',
                'tone' => 'queued',
            ],
        ];

        $engineCards = [
            ['Content Engine', 'Articles, SOD, motivation, wordification, highlights, and reading content.', 'Active'],
            ['Watch / Video Engine', 'Live streams, YouTube, HLS, HTML embeds, video library, and playlists.', 'Active'],
            ['Short Video Engine', 'Short clips, Short Channels, categories, feed rules, and home/inspire output blocks.', 'Active'],
            ['Book Engine', 'Books, chapters, covers, reader payloads, and library publishing.', 'Active'],
            ['Ads Engine', 'Per-app ad units, placement rules, native/banner/interstitial settings.', 'Active'],
            ['Quiz Engine', 'Bible quiz, SOD quiz, article quiz, generated quiz, and result tracking.', 'Active'],
            ['Notification Engine', 'Instant, scheduled, repeating, image, and deep-link notifications.', 'Active'],
            ['API / AI Usage Monitor', 'Track service usage, cost, limits, keys, and platform consumption.', 'Queued'],
        ];
    @endphp

    <style>
        .dxm-control-shell{display:grid;gap:18px}.dxm-control-hero{border:1px solid rgba(34,211,238,.18);border-radius:28px;padding:22px;background:radial-gradient(circle at top left,rgba(34,211,238,.18),transparent 36%),radial-gradient(circle at top right,rgba(168,85,247,.14),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.95),rgba(15,23,42,.88));overflow:hidden}.dxm-control-hero-inner{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.dxm-kicker{display:inline-flex;padding:7px 12px;border-radius:999px;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:rgba(207,250,254,.95);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-title{margin:12px 0 0;color:#fff;font-size:clamp(28px,4vw,44px);line-height:1.04;font-weight:950;letter-spacing:-.055em}.dxm-sub{margin:10px 0 0;max-width:920px;color:rgba(255,255,255,.68);font-size:14px;line-height:1.6}.dxm-stat-grid{display:grid;grid-template-columns:repeat(2,minmax(130px,1fr));gap:10px;min-width:300px}.dxm-stat{padding:14px;border:1px solid rgba(255,255,255,.10);border-radius:20px;background:rgba(2,6,23,.48)}.dxm-stat strong{display:block;color:#fff;font-size:23px;line-height:1}.dxm-stat span{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11px;font-weight:850}.dxm-control-tabs{display:flex;gap:8px;flex-wrap:wrap}.dxm-control-tab{border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:9px 13px;font-size:12px;font-weight:900;cursor:pointer}.dxm-control-tab.active,.dxm-control-tab:hover{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.12);color:#fff}.dxm-panel{display:none}.dxm-panel.active{display:block}.dxm-card-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:12px}.dxm-launch-card{min-height:145px;display:grid;grid-template-columns:50px minmax(0,1fr);gap:13px;border:1px solid rgba(255,255,255,.09);border-radius:22px;background:rgba(2,6,23,.36);padding:15px;color:#fff;text-decoration:none;transition:.18s ease}.dxm-launch-card:hover{transform:translateY(-1px);border-color:rgba(34,211,238,.36);background:rgba(8,47,73,.20)}.dxm-launch-card.disabled{opacity:.7;cursor:not-allowed}.dxm-launch-card.engine{border-color:rgba(168,85,247,.26);background:linear-gradient(135deg,rgba(168,85,247,.12),rgba(34,211,238,.06))}.dxm-launch-card.accent{border-color:rgba(34,211,238,.26);background:linear-gradient(135deg,rgba(34,211,238,.12),rgba(2,6,23,.34))}.dxm-launch-card.queued{border-style:dashed}.dxm-card-icon{width:50px;height:50px;display:grid;place-items:center;border-radius:18px;background:rgba(255,255,255,.07);font-size:21px;font-weight:950}.dxm-launch-card strong{display:block;color:#fff;font-size:14px;font-weight:950;line-height:1.25}.dxm-launch-card small{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11.5px;line-height:1.45}.dxm-launch-card em{display:inline-flex;margin-top:11px;min-height:25px;align-items:center;padding:5px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.055);color:rgba(255,255,255,.72);font-style:normal;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.05em}.dxm-section-box{border:1px solid rgba(255,255,255,.09);border-radius:24px;background:rgba(255,255,255,.035);padding:16px;margin-bottom:14px}.dxm-section-box h2{margin:0;color:#fff;font-size:20px;font-weight:950;letter-spacing:-.03em}.dxm-section-box p{margin:7px 0 0;color:rgba(255,255,255,.62);font-size:13px;line-height:1.55}.dxm-app-row{display:grid;grid-template-columns:52px minmax(0,1fr) auto auto auto;gap:10px;align-items:center;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:12px;background:rgba(2,6,23,.32);margin-bottom:10px}.dxm-app-logo{width:52px;height:52px;border-radius:16px;background:rgba(255,255,255,.07);display:grid;place-items:center;overflow:hidden;color:#fff;font-weight:950}.dxm-app-logo img{width:100%;height:100%;object-fit:cover}.dxm-app-row strong{display:block;color:#fff}.dxm-app-row small{display:block;color:rgba(255,255,255,.55);margin-top:3px}.dxm-mini-btn{display:inline-flex;align-items:center;justify-content:center;min-height:33px;padding:7px 11px;border-radius:11px;border:1px solid rgba(34,211,238,.28);background:rgba(34,211,238,.08);color:#e0faff;font-size:11px;font-weight:900;text-decoration:none}.dxm-mini-btn.muted{border-color:rgba(148,163,184,.18);background:rgba(15,23,42,.55);color:#cbd5e1}.dxm-mini-btn.active{border-color:rgba(16,185,129,.35);background:rgba(16,185,129,.12);color:#bbf7d0}.dxm-engine-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px}.dxm-engine-card{border:1px solid rgba(255,255,255,.09);border-radius:20px;background:rgba(2,6,23,.34);padding:14px}.dxm-engine-card strong{display:block;color:#fff;font-size:14px}.dxm-engine-card small{display:block;color:rgba(255,255,255,.58);line-height:1.45;margin-top:6px}.dxm-engine-card span{display:inline-flex;margin-top:10px;border-radius:999px;padding:5px 8px;background:rgba(255,255,255,.06);color:rgba(255,255,255,.7);font-size:10px;font-weight:950;text-transform:uppercase}@media(max-width:1100px){.dxm-app-row{grid-template-columns:52px minmax(0,1fr) auto}}@media(max-width:980px){.dxm-control-hero-inner{grid-template-columns:1fr}.dxm-stat-grid{min-width:0}}@media(max-width:640px){.dxm-stat-grid{grid-template-columns:1fr}.dxm-app-row{grid-template-columns:44px minmax(0,1fr)}.dxm-app-row .dxm-mini-btn{grid-column:1/-1}.dxm-app-logo{width:44px;height:44px}}
    </style>

    <div
        class="dxm-control-shell"
        x-data="{ tab: new URLSearchParams(window.location.search).get('tab') || localStorage.getItem('appshub_control_center_tab') || 'overview' }"
        x-init="$watch('tab', value => { localStorage.setItem('appshub_control_center_tab', value); const url = new URL(window.location.href); url.searchParams.set('tab', value); history.replaceState({}, '', url); })"
    >
        <section class="dxm-control-hero">
            <div class="dxm-control-hero-inner">
                <div>
                    <div class="dxm-kicker">AppsHub Main Control Center</div>
                    <h1 class="dxm-title">Global launchpad for apps, engines, libraries, and admin tools.</h1>
                    <div class="dxm-sub">Create apps, clone structures, switch workspaces, manage reusable engines, and keep global controls separate from app-specific editing.</div>
                </div>
                <div class="dxm-stat-grid">
                    <div class="dxm-stat"><strong>{{ $stats['active_apps'] }}/{{ $stats['apps'] }}</strong><span>Active Apps</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['sections'] }}</strong><span>App Sections</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['posts'] }}</strong><span>Content Posts</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['media'] }}</strong><span>Media Assets</span></div>
                </div>
            </div>
        </section>

        <div class="dxm-control-tabs">
            @foreach ($tabs as $key => $label)
                <button type="button" class="dxm-control-tab" :class="tab === '{{ $key }}' ? 'active' : ''" x-on:click="tab = '{{ $key }}'">{{ $label }}</button>
            @endforeach
        </div>

        @foreach ($tabs as $key => $label)
            <section class="dxm-panel" :class="tab === '{{ $key }}' ? 'active' : ''">
                <div class="dxm-section-box">
                    <h2>{{ $label }}</h2>
                    <p>
                        @if ($key === 'overview')
                            The most important shortcuts for moving between global control and the selected app workspace.
                        @elseif ($key === 'apps')
                            Beginner App Manager: create, clone, open, and manage apps with clean workflow screens.
                        @elseif ($key === 'engines')
                            Shared reusable services that apps can enable without rebuilding the backend each time.
                        @elseif ($key === 'libraries')
                            Visual template, media, icon, form, widget, and layout libraries for faster app building.
                        @else
                            Owner-level tools for roles, reports, settings, and future usage monitoring.
                        @endif
                    </p>
                </div>

                @if ($key === 'engines')
                    <div class="dxm-engine-grid">
                        @foreach ($engineCards as $engine)
                            <div class="dxm-engine-card">
                                <strong>{{ $engine[0] }}</strong>
                                <small>{{ $engine[1] }}</small>
                                <span>{{ $engine[2] }}</span>
                            </div>
                        @endforeach
                    </div>
                @elseif ($key === 'apps')
                    <div class="dxm-card-grid" style="margin-bottom:14px;">
                        @foreach (collect($launchCards)->where('tab', 'apps') as $card)
                            @if ($card['url'])
                                <a href="{{ $card['url'] }}" class="dxm-launch-card {{ $card['tone'] }}">
                                    <div class="dxm-card-icon">{{ $card['icon'] }}</div><div><strong>{{ $card['title'] }}</strong><small>{{ $card['subtitle'] }}</small><em>{{ $card['badge'] }}</em></div>
                                </a>
                            @endif
                        @endforeach
                    </div>
                    <div>
                        @foreach ($apps as $app)
                            <div class="dxm-app-row">
                                <div class="dxm-app-logo">
                                    @if ($app->logo_url)
                                        <img src="{{ $app->logo_url }}" alt="{{ $app->name }}">
                                    @else
                                        {{ strtoupper(substr($app->name, 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <strong>{{ $app->name }}</strong>
                                    <small>{{ $app->slug }} · App ID {{ $app->id }} · {{ $app->is_active ? 'Active' : 'Inactive' }}</small>
                                </div>
                                @if ((int) $activeAppId === (int) $app->id)
                                    <span class="dxm-mini-btn active">Current</span>
                                @else
                                    <a class="dxm-mini-btn muted" href="{{ route('admin.switch-active-app', ['id' => $app->id, 'return' => '/admin/control-center?tab=apps']) }}">Make Active</a>
                                @endif
                                <a class="dxm-mini-btn" href="{{ route('admin.switch-active-app', ['id' => $app->id, 'return' => '/admin/beginner-dashboard']) }}">Workspace</a>
                                <a class="dxm-mini-btn" href="{{ route('admin.switch-active-app', ['id' => $app->id, 'return' => '/admin/app-settings']) }}">Settings</a>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="dxm-card-grid">
                        @foreach (collect($launchCards)->where('tab', $key) as $card)
                            @if ($card['url'])
                                <a href="{{ $card['url'] }}" class="dxm-launch-card {{ $card['tone'] }}">
                                    <div class="dxm-card-icon">{{ $card['icon'] }}</div><div><strong>{{ $card['title'] }}</strong><small>{{ $card['subtitle'] }}</small><em>{{ $card['badge'] }}</em></div>
                                </a>
                            @else
                                <div class="dxm-launch-card {{ $card['tone'] }} disabled">
                                    <div class="dxm-card-icon">{{ $card['icon'] }}</div><div><strong>{{ $card['title'] }}</strong><small>{{ $card['subtitle'] }}</small><em>{{ $card['badge'] }}</em></div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach
    </div>

    <script>
        /* AppsHub global scroll memory for Control Center tabbed workspace. */
        (function(){
            const key = 'appshub_control_center_scroll';
            window.addEventListener('beforeunload', function(){ localStorage.setItem(key, String(window.scrollY || 0)); });
            window.addEventListener('load', function(){
                const y = parseInt(localStorage.getItem(key) || '0', 10);
                if (y > 0) setTimeout(function(){ window.scrollTo({ top:y, behavior:'instant' }); }, 80);
            });
        })();
    </script>
</x-filament::page>

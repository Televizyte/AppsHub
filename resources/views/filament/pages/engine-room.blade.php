<x-filament::page>
    @php
        $groups = \App\Support\EngineRoomCatalog::groups();
        $stats = \App\Support\EngineRoomCatalog::stats();
        $engines = collect(\App\Support\EngineRoomCatalog::capabilityUsage());
        $activeTab = strtolower((string) request('tab', 'overview'));

        if (! array_key_exists($activeTab, $groups)) {
            $activeTab = 'overview';
        }

        $overviewCards = [
            [
                'title' => 'Open Selected App Workspace',
                'description' => 'Go back to the active app workspace. Editors should work there, not inside global engine settings.',
                'url' => url('/admin/beginner-dashboard'),
                'badge' => 'App Workspace',
                'icon' => '⌂',
                'tone' => 'cyan',
            ],
            [
                'title' => 'Manage App Capabilities',
                'description' => 'Choose which tools and engines belong to the selected app.',
                'url' => url('/admin/app-capabilities'),
                'badge' => 'Per App',
                'icon' => '☷',
                'tone' => 'blue',
            ],
            [
                'title' => 'Open Template Library',
                'description' => 'Create, save, preview, and apply reusable app/page/section templates.',
                'url' => url('/admin/template-library'),
                'badge' => $stats['templates'] . ' Templates',
                'icon' => '▣',
                'tone' => 'purple',
            ],
            [
                'title' => 'Open Media Library',
                'description' => 'Manage reusable images, logos, banners, thumbnails, audio, video, and files.',
                'url' => url('/admin/media-assets'),
                'badge' => $stats['media'] . ' Assets',
                'icon' => '◩',
                'tone' => 'cyan',
            ],
        ];
    @endphp

    <style>
        .dxm-engine-shell{display:grid;gap:16px;padding-bottom:28px}.dxm-engine-hero{border:1px solid rgba(168,85,247,.22);border-radius:28px;padding:22px;background:radial-gradient(circle at top left,rgba(168,85,247,.18),transparent 35%),radial-gradient(circle at top right,rgba(34,211,238,.13),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(15,23,42,.88));overflow:hidden}.dxm-engine-hero-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.dxm-kicker{display:inline-flex;padding:7px 12px;border-radius:999px;border:1px solid rgba(168,85,247,.34);background:rgba(168,85,247,.10);color:rgba(243,232,255,.96);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-title{margin:12px 0 0;color:#fff;font-size:clamp(28px,4vw,44px);line-height:1.04;font-weight:950;letter-spacing:-.055em}.dxm-sub{margin:10px 0 0;max-width:920px;color:rgba(255,255,255,.68);font-size:14px;line-height:1.6}.dxm-engine-stats{display:grid;grid-template-columns:repeat(2,minmax(125px,1fr));gap:10px;min-width:300px}.dxm-engine-stat{padding:14px;border:1px solid rgba(255,255,255,.10);border-radius:20px;background:rgba(2,6,23,.48)}.dxm-engine-stat strong{display:block;color:#fff;font-size:23px;line-height:1}.dxm-engine-stat span{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11px;font-weight:850}.dxm-tabs{position:sticky;top:72px;z-index:20;display:flex;gap:8px;flex-wrap:wrap;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:9px;background:rgba(3,7,18,.88);backdrop-filter:blur(14px)}.dxm-tab{display:inline-flex;gap:7px;align-items:center;min-height:36px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;text-decoration:none}.dxm-tab.active,.dxm-tab:hover{border-color:rgba(168,85,247,.45);background:rgba(168,85,247,.12);color:#fff}.dxm-note,.dxm-section-head{border:1px solid rgba(255,255,255,.09);border-radius:22px;background:rgba(255,255,255,.035);padding:15px;color:rgba(255,255,255,.66);font-size:13px;line-height:1.55}.dxm-section-head strong{display:block;color:#fff;font-size:18px;line-height:1.2;margin-bottom:4px}.dxm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(255px,1fr));gap:12px}.dxm-engine-card{min-height:210px;display:grid;grid-template-columns:54px minmax(0,1fr);gap:13px;border:1px solid rgba(255,255,255,.09);border-radius:22px;background:rgba(2,6,23,.36);padding:15px;color:#fff;text-decoration:none;transition:.18s ease}.dxm-engine-card:hover{transform:translateY(-1px);border-color:rgba(34,211,238,.34);background:rgba(8,47,73,.18)}.dxm-engine-card[data-tone="purple"]{background:linear-gradient(135deg,rgba(168,85,247,.11),rgba(2,6,23,.36))}.dxm-engine-card[data-tone="blue"]{background:linear-gradient(135deg,rgba(59,130,246,.11),rgba(2,6,23,.36))}.dxm-icon{width:54px;height:54px;display:grid;place-items:center;border-radius:18px;background:rgba(255,255,255,.07);font-size:20px;font-weight:950}.dxm-engine-card strong{display:block;color:#fff;font-size:15px;font-weight:950;line-height:1.25}.dxm-engine-card small{display:block;color:rgba(255,255,255,.60);line-height:1.5;margin-top:7px;font-size:11.7px}.dxm-meta{display:flex;gap:7px;flex-wrap:wrap;margin-top:11px}.dxm-pill{display:inline-flex;align-items:center;min-height:24px;padding:5px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.055);color:rgba(255,255,255,.68);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}.dxm-pill.ready{border-color:rgba(34,197,94,.22);background:rgba(34,197,94,.08);color:#bbf7d0}.dxm-pill.queued{border-color:rgba(251,191,36,.22);background:rgba(251,191,36,.08);color:#fde68a}.dxm-apps{margin-top:11px;padding:10px;border:1px solid rgba(255,255,255,.07);border-radius:14px;background:rgba(255,255,255,.035)}.dxm-apps b{display:block;color:rgba(255,255,255,.82);font-size:11px;margin-bottom:7px}.dxm-apps span{display:inline-flex;margin:0 5px 5px 0;padding:4px 7px;border-radius:999px;background:rgba(255,255,255,.06);color:rgba(255,255,255,.65);font-size:10px;font-weight:800}.dxm-btn{display:inline-flex;margin-top:11px;align-items:center;justify-content:center;min-height:33px;padding:7px 10px;border-radius:11px;border:1px solid rgba(34,211,238,.28);background:rgba(34,211,238,.08);color:#e0faff;font-size:11px;font-weight:900}.dxm-btn.disabled{border-color:rgba(255,255,255,.09);background:rgba(255,255,255,.04);color:rgba(255,255,255,.45)}@media(max-width:980px){.dxm-engine-hero-grid{grid-template-columns:1fr}.dxm-engine-stats{min-width:0}}@media(max-width:700px){.dxm-tabs{top:56px;overflow-x:auto;flex-wrap:nowrap}.dxm-tab{flex:0 0 auto}.dxm-engine-stats{grid-template-columns:1fr}.dxm-engine-card{grid-template-columns:44px minmax(0,1fr)}.dxm-icon{width:44px;height:44px}}
    </style>

    <div class="dxm-engine-shell">
        <section class="dxm-engine-hero">
            <div class="dxm-engine-hero-grid">
                <div>
                    <div class="dxm-kicker">Global Engine Room</div>
                    <h1 class="dxm-title">Reusable services for every app.</h1>
                    <div class="dxm-sub">
                        This is the owner-level control area for shared engines. Apps should enable engines instead of rebuilding functionality.
                        App editors stay inside the selected app workspace, while global services and usage ownership stay here.
                    </div>
                </div>

                <div class="dxm-engine-stats">
                    <div class="dxm-engine-stat"><strong>{{ $stats['active_engines'] }}/{{ $stats['engines'] }}</strong><span>Active Engines</span></div>
                    <div class="dxm-engine-stat"><strong>{{ $stats['active_apps'] }}/{{ $stats['apps'] }}</strong><span>Active Apps</span></div>
                    <div class="dxm-engine-stat"><strong>{{ $stats['templates'] }}</strong><span>Templates</span></div>
                    <div class="dxm-engine-stat"><strong>{{ $stats['media'] }}</strong><span>Media Assets</span></div>
                </div>
            </div>
        </section>

        <nav class="dxm-tabs" aria-label="Engine room tabs">
            @foreach ($groups as $key => $group)
                <a href="{{ url('/admin/engine-room') }}?tab={{ $key }}" class="dxm-tab {{ $activeTab === $key ? 'active' : '' }}">{{ $group['label'] }}</a>
            @endforeach
        </nav>

        <section class="dxm-section-head">
            <strong>{{ $groups[$activeTab]['label'] }}</strong>
            {{ $groups[$activeTab]['description'] }}
        </section>

        @if ($activeTab === 'overview')
            <div class="dxm-note">
                Engine Room is global. It should show reusable engines, which apps use them, and where to manage each engine.
                Per-app enable/disable remains in App Capabilities.
            </div>

            <div class="dxm-grid">
                @foreach ($overviewCards as $card)
                    <a href="{{ $card['url'] }}" class="dxm-engine-card" data-tone="{{ $card['tone'] }}">
                        <div class="dxm-icon">{{ $card['icon'] }}</div>
                        <div>
                            <strong>{{ $card['title'] }}</strong>
                            <small>{{ $card['description'] }}</small>
                            <div class="dxm-meta"><span class="dxm-pill ready">{{ $card['badge'] }}</span></div>
                            <span class="dxm-btn">Open</span>
                        </div>
                    </a>
                @endforeach
            </div>
        @else
            <div class="dxm-grid">
                @foreach ($engines->where('group', $activeTab)->values() as $engine)
                    @php
                        $statusClass = strtolower((string) $engine['status']) === 'active' ? 'ready' : 'queued';
                    @endphp

                    <article class="dxm-engine-card" data-tone="{{ $engine['tone'] }}">
                        <div class="dxm-icon">{{ $engine['icon'] }}</div>
                        <div>
                            <strong>{{ $engine['name'] }}</strong>
                            <small>{{ $engine['description'] }}</small>

                            <div class="dxm-meta">
                                <span class="dxm-pill {{ $statusClass }}">{{ $engine['status'] }}</span>
                                <span class="dxm-pill">Key: {{ $engine['key'] }}</span>
                                <span class="dxm-pill">{{ $engine['apps_count'] }} app(s)</span>
                            </div>

                            <div class="dxm-apps">
                                <b>Apps using this engine</b>
                                @forelse (($engine['apps'] ?? []) as $app)
                                    <span>{{ $app['name'] }}</span>
                                @empty
                                    <span>No app enabled yet</span>
                                @endforelse
                            </div>

                            @if (! empty($engine['route']))
                                <a href="{{ url($engine['route']) }}" class="dxm-btn">{{ $engine['manage_label'] }}</a>
                            @else
                                <span class="dxm-btn disabled">{{ $engine['manage_label'] }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-filament::page>

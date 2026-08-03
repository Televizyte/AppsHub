<x-filament::page>
    @php
        $groups = \App\Models\IconPreset::query()
            ->where('is_active', true)
            ->select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group')
            ->map(fn ($group) => trim((string) $group) !== '' ? trim((string) $group) : 'General')
            ->unique()
            ->values()
            ->all();

        if (empty($groups)) {
            $groups = ['General'];
        }

        $tabs = ['all' => 'All Icons'];
        foreach ($groups as $group) {
            $key = \Illuminate\Support\Str::slug($group, '_') ?: 'general';
            $tabs[$key] = $group;
        }

        $activeTab = strtolower((string) request('tab', 'all'));
        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = 'all';
        }

        $search = trim((string) request('q', ''));

        $query = \App\Models\IconPreset::query()
            ->orderBy('group')
            ->orderBy('sort_order')
            ->orderBy('label');

        if ($activeTab !== 'all') {
            $selectedGroup = $tabs[$activeTab] ?? null;
            if ($selectedGroup) {
                $query->where(function ($inner) use ($selectedGroup) {
                    if ($selectedGroup === 'General') {
                        $inner->whereNull('group')->orWhere('group', '')->orWhere('group', 'General');
                    } else {
                        $inner->where('group', $selectedGroup);
                    }
                });
            }
        }

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('label', 'like', '%' . $search . '%')
                    ->orWhere('key', 'like', '%' . $search . '%')
                    ->orWhere('group', 'like', '%' . $search . '%')
                    ->orWhere('svg', 'like', '%' . $search . '%');
            });
        }

        $icons = $query->paginate(60)->withQueryString();

        $countFor = function (string $tabKey) use ($tabs) {
            $query = \App\Models\IconPreset::query();

            if ($tabKey !== 'all') {
                $selectedGroup = $tabs[$tabKey] ?? null;
                if ($selectedGroup) {
                    $query->where(function ($inner) use ($selectedGroup) {
                        if ($selectedGroup === 'General') {
                            $inner->whereNull('group')->orWhere('group', '')->orWhere('group', 'General');
                        } else {
                            $inner->where('group', $selectedGroup);
                        }
                    });
                }
            }

            return (int) $query->count();
        };

        $activeCount = (int) \App\Models\IconPreset::query()->where('is_active', true)->count();
        $totalCount = (int) \App\Models\IconPreset::query()->count();
        $groupCount = count($groups);
    @endphp

    <style>
        .dxm-icon-shell{display:grid;gap:16px;padding-bottom:28px}.dxm-icon-hero{border:1px solid rgba(168,85,247,.20);border-radius:26px;padding:20px;background:radial-gradient(circle at top left,rgba(168,85,247,.18),transparent 34%),radial-gradient(circle at top right,rgba(34,211,238,.14),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(15,23,42,.88))}.dxm-icon-hero-inner{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.dxm-kicker{display:inline-flex;align-items:center;min-height:28px;padding:6px 11px;border-radius:999px;border:1px solid rgba(168,85,247,.34);background:rgba(168,85,247,.10);color:rgba(243,232,255,.96);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-title{margin:11px 0 0;color:#fff;font-size:clamp(28px,4vw,42px);line-height:1.03;font-weight:950;letter-spacing:-.055em}.dxm-sub{margin-top:9px;max-width:900px;color:rgba(255,255,255,.66);font-size:13px;line-height:1.6}.dxm-stat-grid{display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:10px;min-width:280px}.dxm-stat{padding:14px;border:1px solid rgba(255,255,255,.10);border-radius:18px;background:rgba(2,6,23,.48)}.dxm-stat strong{display:block;color:#fff;font-size:22px;line-height:1}.dxm-stat span{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11px;font-weight:850}.dxm-tabs{position:sticky;top:72px;z-index:20;display:flex;gap:8px;flex-wrap:wrap;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:9px;background:rgba(3,7,18,.88);backdrop-filter:blur(14px)}.dxm-tab{display:inline-flex;gap:7px;align-items:center;min-height:36px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;text-decoration:none}.dxm-tab:hover,.dxm-tab.active{border-color:rgba(168,85,247,.45);background:rgba(168,85,247,.12);color:#fff}.dxm-tab span{display:inline-grid;place-items:center;min-width:21px;height:21px;border-radius:999px;background:rgba(255,255,255,.08);font-size:10px;color:rgba(255,255,255,.72)}.dxm-panel{border:1px solid rgba(255,255,255,.09);border-radius:22px;padding:15px;background:rgba(255,255,255,.035)}.dxm-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between}.dxm-search{min-width:min(420px,100%);flex:1}.dxm-search input{width:100%;min-height:42px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(2,6,23,.45);color:#fff;padding:10px 13px}.dxm-actions{display:flex;gap:8px;flex-wrap:wrap}.dxm-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.11);background:rgba(255,255,255,.055);color:rgba(255,255,255,.84);font-size:12px;font-weight:900;text-decoration:none;cursor:pointer}.dxm-btn.primary{border-color:rgba(168,85,247,.32);background:rgba(168,85,247,.12);color:#f3e8ff}.dxm-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px}.dxm-card{border:1px solid rgba(255,255,255,.09);border-radius:20px;background:rgba(2,6,23,.42);overflow:hidden;min-height:245px;display:grid;grid-template-rows:130px auto;transition:.16s ease}.dxm-card:hover{transform:translateY(-1px);border-color:rgba(168,85,247,.36)}.dxm-preview{position:relative;background:radial-gradient(circle at top,rgba(168,85,247,.20),transparent 38%),linear-gradient(135deg,rgba(15,23,42,.9),rgba(30,41,59,.72));display:grid;place-items:center;overflow:hidden}.dxm-preview-icon{width:78px;height:78px;border-radius:26px;background:rgba(255,255,255,.09);display:grid;place-items:center;color:#fff}.dxm-preview-icon svg{width:42px!important;height:42px!important;max-width:42px!important;max-height:42px!important;color:#fff;fill:currentColor;stroke:currentColor}.dxm-preview-icon svg *{max-width:42px!important;max-height:42px!important}.dxm-status{position:absolute;left:10px;top:10px;display:inline-flex;min-height:25px;align-items:center;border-radius:999px;padding:5px 8px;background:rgba(0,0,0,.45);backdrop-filter:blur(8px);color:#fff;font-size:10px;font-weight:950;text-transform:uppercase}.dxm-body{padding:13px;display:grid;gap:10px}.dxm-title-sm{color:#fff;font-size:14px;font-weight:950;line-height:1.25;word-break:break-word}.dxm-key{font-size:10.5px;color:rgba(255,255,255,.48);line-height:1.35;word-break:break-all}.dxm-meta{display:flex;gap:6px;flex-wrap:wrap}.dxm-pill{display:inline-flex;align-items:center;min-height:23px;padding:5px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.055);color:rgba(255,255,255,.68);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}.dxm-card-actions{display:flex;gap:7px;flex-wrap:wrap}.dxm-empty{border:1px dashed rgba(255,255,255,.18);border-radius:20px;padding:22px;color:rgba(255,255,255,.65);background:rgba(2,6,23,.25);text-align:center;line-height:1.6}.dxm-modal-backdrop{position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.72);backdrop-filter:blur(10px);display:grid;place-items:center;padding:22px}.dxm-modal-card{width:min(760px,96vw);max-height:88vh;border:1px solid rgba(168,85,247,.34);border-radius:24px;background:linear-gradient(135deg,rgba(2,6,23,.98),rgba(15,23,42,.96));box-shadow:0 24px 90px rgba(0,0,0,.55);overflow:hidden;display:grid;grid-template-rows:auto minmax(0,1fr) auto}.dxm-modal-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:15px 17px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-modal-head strong{color:#fff;font-size:15px;font-weight:950}.dxm-modal-close{width:40px;height:40px;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);color:#fff;font-size:22px;line-height:1;cursor:pointer}.dxm-modal-body{min-height:300px;display:grid;place-items:center;background:radial-gradient(circle at center,rgba(168,85,247,.18),transparent 44%),rgba(0,0,0,.20);overflow:auto}.dxm-modal-icon{width:min(300px,58vw);height:min(300px,58vw);border-radius:42px;background:rgba(255,255,255,.08);display:grid;place-items:center;color:#fff}.dxm-modal-icon svg{width:150px!important;height:150px!important;max-width:150px!important;max-height:150px!important;color:#fff;fill:currentColor;stroke:currentColor}.dxm-modal-key{padding:11px 17px;color:rgba(255,255,255,.62);font-size:12px;word-break:break-all;border-top:1px solid rgba(255,255,255,.08)}.dxm-modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:13px 17px;border-top:1px solid rgba(255,255,255,.08)}.dxm-toast{position:fixed;right:18px;bottom:18px;z-index:9999;border:1px solid rgba(168,85,247,.30);border-radius:14px;background:rgba(3,7,18,.92);color:#f3e8ff;padding:10px 12px;font-size:12px;font-weight:900;box-shadow:0 18px 60px rgba(0,0,0,.35)}.dxm-pagination{margin-top:14px}@media(max-width:980px){.dxm-icon-hero-inner{grid-template-columns:1fr}.dxm-stat-grid{min-width:0}}@media(max-width:760px){.dxm-tabs{top:56px;overflow-x:auto;flex-wrap:nowrap}.dxm-tab{flex:0 0 auto}.dxm-stat-grid{grid-template-columns:1fr}.dxm-grid{grid-template-columns:repeat(auto-fill,minmax(150px,1fr))}.dxm-card{grid-template-rows:110px auto}}
    </style>

    <div class="dxm-icon-shell" x-data="{ copied: false, iconOpen: false, iconSvg: null, iconTitle: null, iconKey: null, copy(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 1700); }, openIconPreview(svg, title, key) { this.iconSvg = svg; this.iconTitle = title; this.iconKey = key; this.iconOpen = true; document.body.style.overflow = 'hidden'; }, closeIconPreview() { this.iconOpen = false; this.iconSvg = null; this.iconTitle = null; this.iconKey = null; document.body.style.overflow = ''; } }" x-on:keydown.escape.window="closeIconPreview()">
        <section class="dxm-icon-hero">
            <div class="dxm-icon-hero-inner">
                <div>
                    <div class="dxm-kicker">Beginner Icon Library</div>
                    <h1 class="dxm-title">Reusable visual icons for app tools and templates.</h1>
                    <div class="dxm-sub">
                        Browse, copy, and manage icon presets visually. Use these keys in cards, tools, templates, and app builder blocks.
                    </div>
                </div>
                <div class="dxm-stat-grid">
                    <div class="dxm-stat"><strong>{{ $activeCount }}/{{ $totalCount }}</strong><span>Active Icons</span></div>
                    <div class="dxm-stat"><strong>{{ $groupCount }}</strong><span>Icon Groups</span></div>
                </div>
            </div>
        </section>

        <nav class="dxm-tabs" aria-label="Icon library groups">
            @foreach ($tabs as $key => $label)
                <a class="dxm-tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ url('/admin/icon-library') }}?tab={{ $key }}">{{ $label }} <span>{{ $countFor($key) }}</span></a>
            @endforeach
        </nav>

        <section class="dxm-panel">
            <div class="dxm-toolbar">
                <div>
                    <div class="dxm-kicker">{{ $tabs[$activeTab] ?? 'Icons' }}</div>
                    <div class="dxm-sub">Click Copy Key to use an icon inside cards, sections, or templates.</div>
                </div>

                <form class="dxm-search" method="GET" action="{{ url('/admin/icon-library') }}">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search icon label, key, group, or SVG...">
                </form>

                <div class="dxm-actions">
                    <a href="{{ url('/admin/icon-presets/create') }}" class="dxm-btn primary">+ New Icon</a>
                    <a href="{{ url('/admin/icon-presets') }}" class="dxm-btn">Advanced Table</a>
                </div>
            </div>
        </section>

        @if ($icons->count() > 0)
            <section class="dxm-grid">
                @foreach ($icons as $icon)
                    <article class="dxm-card">
                        <div class="dxm-preview">
                            <span class="dxm-status">{{ $icon->is_active ? 'Active' : 'Inactive' }}</span>
                            <button type="button" class="dxm-preview-icon" style="border:0;cursor:pointer;" x-on:click="openIconPreview(@js($icon->svg), @js($icon->label), @js($icon->key))">
                                {!! $icon->svg !!}
                            </button>
                        </div>

                        <div class="dxm-body">
                            <div>
                                <div class="dxm-title-sm">{{ $icon->label }}</div>
                                <div class="dxm-key">{{ $icon->key }}</div>
                            </div>

                            <div class="dxm-meta">
                                <span class="dxm-pill">{{ $icon->group ?: 'General' }}</span>
                                <span class="dxm-pill">Order {{ $icon->sort_order ?? 0 }}</span>
                            </div>

                            <div class="dxm-card-actions">
                                <button type="button" class="dxm-btn primary" x-on:click="copy(@js($icon->key))">Copy Key</button>
                                <button type="button" class="dxm-btn" x-on:click="openIconPreview(@js($icon->svg), @js($icon->label), @js($icon->key))">Preview</button>
                                <a href="{{ url('/admin/icon-presets/' . $icon->id . '/edit') }}" class="dxm-btn">Edit</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="dxm-pagination">{{ $icons->links() }}</div>
        @else
            <div class="dxm-empty">
                No icons found for this group yet.
                <br><br>
                <a href="{{ url('/admin/icon-presets/create') }}" class="dxm-btn primary">+ New Icon</a>
            </div>
        @endif

        <template x-if="iconOpen">
            <div class="dxm-modal-backdrop" x-on:click.self="closeIconPreview()" x-transition>
                <div class="dxm-modal-card">
                    <div class="dxm-modal-head">
                        <strong x-text="iconTitle || 'Icon Preview'"></strong>
                        <button type="button" class="dxm-modal-close" x-on:click="closeIconPreview()">×</button>
                    </div>

                    <div class="dxm-modal-body">
                        <div class="dxm-modal-icon" x-html="iconSvg"></div>
                    </div>

                    <div class="dxm-modal-key">
                        Key: <span x-text="iconKey"></span>
                    </div>

                    <div class="dxm-modal-foot">
                        <button type="button" class="dxm-btn" x-on:click="copy(iconKey)">Copy Key</button>
                        <button type="button" class="dxm-btn primary" x-on:click="closeIconPreview()">Close Preview</button>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="copied" x-transition class="dxm-toast">Icon key copied</div>
    </div>
</x-filament::page>

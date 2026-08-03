<x-filament::page>
    @php
        $validTabs = ['apps', 'tabs', 'pages', 'sections', 'items', 'cards', 'widgets', 'forms', 'media', 'icons'];
        $activeTab = strtolower((string) request('tab', 'apps'));

        if (! in_array($activeTab, $validTabs, true)) {
            $activeTab = 'apps';
        }

        $tabs = [
            'apps' => ['label' => 'App Templates', 'note' => 'Starter app structures that can later be cloned or applied safely.'],
            'tabs' => ['label' => 'Tab Templates', 'note' => 'Complete tab structures such as Inspire, Explore, Watch, or More with ready sections and insert blocks.'],
            'pages' => ['label' => 'Page Templates', 'note' => 'Ready page arrangements for Home, Watch, Inspire, Explore, More, and future pages.'],
            'sections' => ['label' => 'Section Templates', 'note' => 'Reusable blocks such as banners, grids, video strips, daily cards, books, and tools.'],
            'items' => ['label' => 'Item Templates', 'note' => 'Single reusable items/cards such as icon cards, image cards, CTA cards, book cards, and tool cards.'],
            'cards' => ['label' => 'Card Templates', 'note' => 'Visual card designs for videos, books, tools, articles, banners, and menu items.'],
            'forms' => ['label' => 'Form Templates', 'note' => 'Reusable form layouts for contact, prayer requests, feedback, registrations, and more.'],
            'widgets' => ['label' => 'Widgets', 'note' => 'Small reusable app widgets like daily scripture, quote card, counters, and quick launchers.'],
            'media' => ['label' => 'Media Blocks', 'note' => 'Image, banner, thumbnail, gallery, and media-driven block starter templates.'],
            'icons' => ['label' => 'Icons', 'note' => 'Visual icon presets and icon groups. Real icon preset records appear here when available.'],
        ];

        $templateRecords = collect();
        $databaseReady = false;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('builder_templates')) {
                $databaseReady = true;
                $templateRecords = \App\Models\BuilderTemplate::query()
                    ->where('is_active', true)
                    ->orderBy('category')
                    ->orderBy('sort_order')
                    ->orderBy('title')
                    ->get();
            }
        } catch (\Throwable $e) {
            $databaseReady = false;
            $templateRecords = collect();
        }

        $fallbackTemplates = collect([
            ['tab' => 'apps', 'title' => 'Church TV App', 'description' => 'Home, Watch, Inspire, Explore, More with live/video/content tools.', 'badge' => 'App Template', 'glyph' => 'TV', 'status' => 'Planning', 'tone' => 'cyan', 'can_apply' => false, 'payload' => [], 'preview' => []],
            ['tab' => 'apps', 'title' => 'General TV App', 'description' => 'Watch-first media app for channels, playlists, and video libraries.', 'badge' => 'App Template', 'glyph' => '▶', 'status' => 'Planning', 'tone' => 'purple', 'can_apply' => false, 'payload' => [], 'preview' => []],
            ['tab' => 'apps', 'title' => 'Ministry Content App', 'description' => 'Articles, devotionals, books, quotes, notifications, and media library.', 'badge' => 'App Template', 'glyph' => '✦', 'status' => 'Planning', 'tone' => 'blue', 'can_apply' => false, 'payload' => [], 'preview' => []],
            ['tab' => 'tabs', 'title' => 'Inspire Tab Starter', 'description' => 'Featured articles, quotes, shorts, and insert blocks for an Inspire-style tab.', 'badge' => 'Tab Template', 'glyph' => '✦', 'status' => 'Seeder Ready', 'tone' => 'purple', 'can_apply' => false, 'payload' => [], 'preview' => []],
            ['tab' => 'items', 'title' => 'Icon Card Item', 'description' => 'Single icon-based item for tools, actions, and quick shortcuts.', 'badge' => 'Item Template', 'glyph' => '▣', 'status' => 'Seeder Ready', 'tone' => 'cyan', 'can_apply' => false, 'payload' => [], 'preview' => []],
            ['tab' => 'media', 'title' => 'Image Banner Block', 'description' => 'Media-friendly starter for banners, thumbnails, and visual announcements.', 'badge' => 'Media Template', 'glyph' => '▭', 'status' => 'Seeder Ready', 'tone' => 'blue', 'can_apply' => false, 'payload' => [], 'preview' => []],
        ]);

        $templates = $databaseReady
            ? $templateRecords->map(function ($template) {
                $payload = is_array($template->payload_json) ? $template->payload_json : [];
                $preview = is_array($template->preview_json) ? $template->preview_json : [];

                return [
                    'id' => $template->id,
                    'tab' => $template->category,
                    'title' => $template->title,
                    'description' => $template->description ?: $template->subtitle,
                    'subtitle' => $template->subtitle,
                    'badge' => $template->badge ?: 'Template',
                    'glyph' => $template->glyph ?: '▣',
                    'status' => $template->status ?: 'Ready',
                    'tone' => $template->tone ?: 'cyan',
                    'apply_mode' => $template->apply_mode ?: 'append',
                    'can_apply' => in_array($template->category, ['tabs', 'pages', 'sections', 'items', 'cards', 'forms', 'widgets', 'media'], true),
                    'payload' => $payload,
                    'preview' => $preview,
                    'section_count' => is_array($payload['sections'] ?? null) ? count($payload['sections']) : 0,
                    'item_count' => collect($payload['sections'] ?? [])->sum(fn ($section) => is_array($section['items'] ?? null) ? count($section['items']) : 0),
                ];
            })->values()
            : $fallbackTemplates;

        $iconTemplates = [];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('icon_presets')) {
                $rows = \Illuminate\Support\Facades\DB::table('icon_presets')->limit(60)->get();
                foreach ($rows as $row) {
                    $asArray = (array) $row;
                    $title = $asArray['label'] ?? $asArray['name'] ?? $asArray['title'] ?? $asArray['key'] ?? ('Icon Preset #' . ($asArray['id'] ?? ''));
                    $subtitle = $asArray['description'] ?? $asArray['slug'] ?? $asArray['key'] ?? 'Saved icon preset';
                    $iconTemplates[] = [
                        'id' => null,
                        'tab' => 'icons',
                        'title' => (string) $title,
                        'description' => (string) $subtitle,
                        'subtitle' => '',
                        'badge' => 'Icon Preset',
                        'glyph' => '▣',
                        'status' => 'Saved',
                        'tone' => 'cyan',
                        'can_apply' => false,
                        'payload' => [],
                        'preview' => [],
                        'section_count' => 0,
                        'item_count' => 0,
                    ];
                }
            }
        } catch (\Throwable $e) {
            $iconTemplates = [];
        }

        if (count($iconTemplates) === 0) {
            $iconTemplates = [
                ['id' => null, 'tab' => 'icons', 'title' => 'Christian Tools Icon Set', 'description' => 'Bible, Notes, Quote Creator, Books, Quiz, Watch, and More icons.', 'subtitle' => '', 'badge' => 'Icon Set', 'glyph' => '▣', 'status' => 'Starter', 'tone' => 'cyan', 'can_apply' => false, 'payload' => [], 'preview' => [], 'section_count' => 0, 'item_count' => 0],
                ['id' => null, 'tab' => 'icons', 'title' => 'Media App Icons', 'description' => 'Watch, livestream, playlist, shorts, podcast, and video categories.', 'subtitle' => '', 'badge' => 'Icon Set', 'glyph' => '▶', 'status' => 'Starter', 'tone' => 'purple', 'can_apply' => false, 'payload' => [], 'preview' => [], 'section_count' => 0, 'item_count' => 0],
            ];
        }

        $templates = collect($templates)->merge($iconTemplates)->values();

        $counts = [];
        foreach (array_keys($tabs) as $key) {
            $counts[$key] = $templates->where('tab', $key)->count();
        }

        $activeTabData = $tabs[$activeTab] ?? $tabs['apps'];
        $panelTemplates = $templates->where('tab', $activeTab)->values();

        $targetTabs = [
            'home' => 'Home',
            'watch' => 'Watch',
            'inspire' => 'Inspire',
            'explore' => 'Explore',
            'more' => 'More',
        ];

        $recommendedTabFor = function (array $template) {
            $payload = $template['payload'] ?? [];
            $targetTab = $payload['target_tab'] ?? null;

            if (is_string($targetTab) && in_array($targetTab, ['home', 'watch', 'inspire', 'explore', 'more'], true)) {
                return $targetTab;
            }

            return match ($template['tab'] ?? '') {
                'tabs' => 'inspire',
                'pages' => 'home',
                'sections' => 'home',
                'items' => 'explore',
                'cards' => 'home',
                'forms' => 'more',
                'widgets' => 'home',
                'media' => 'home',
                default => 'home',
            };
        };
    @endphp

    <style>
        .dxm-template-shell{display:grid;gap:16px;padding-bottom:26px}.dxm-template-hero{border:1px solid rgba(34,211,238,.18);border-radius:26px;padding:20px;background:radial-gradient(circle at top left,rgba(34,211,238,.18),transparent 34%),radial-gradient(circle at top right,rgba(168,85,247,.16),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(15,23,42,.88))}.dxm-template-kicker{display:inline-flex;align-items:center;min-height:28px;padding:6px 11px;border-radius:999px;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:rgba(207,250,254,.96);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-template-title{margin:11px 0 0;color:#fff;font-size:clamp(28px,4vw,42px);line-height:1.03;font-weight:950;letter-spacing:-.055em}.dxm-template-sub{margin-top:9px;max-width:900px;color:rgba(255,255,255,.66);font-size:13px;line-height:1.6}.dxm-template-tabs{position:sticky;top:72px;z-index:20;display:flex;gap:8px;flex-wrap:wrap;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:9px;background:rgba(3,7,18,.88);backdrop-filter:blur(14px)}.dxm-template-tab{display:inline-flex;gap:7px;align-items:center;min-height:36px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;text-decoration:none}.dxm-template-tab:hover,.dxm-template-tab.active{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.12);color:#fff}.dxm-template-tab span{display:inline-grid;place-items:center;min-width:21px;height:21px;border-radius:999px;background:rgba(255,255,255,.08);font-size:10px;color:rgba(255,255,255,.72)}.dxm-template-note,.dxm-template-panel-head{border:1px solid rgba(255,255,255,.09);border-radius:22px;padding:15px;background:rgba(255,255,255,.035);color:rgba(255,255,255,.66);font-size:13px;line-height:1.55}.dxm-template-panel-head strong{display:block;color:#fff;font-size:18px;line-height:1.2;margin-bottom:4px}.dxm-template-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(255px,1fr));gap:12px}.dxm-template-card{min-height:292px;display:grid;grid-template-rows:118px auto;border:1px solid rgba(255,255,255,.09);border-radius:22px;overflow:hidden;background:rgba(2,6,23,.38);color:#fff}.dxm-template-preview{position:relative;display:grid;place-items:center;overflow:hidden;background:radial-gradient(circle at 20% 20%,rgba(34,211,238,.24),transparent 30%),linear-gradient(135deg,rgba(14,165,233,.18),rgba(168,85,247,.22));border-bottom:1px solid rgba(255,255,255,.08)}.dxm-template-card[data-tone="purple"] .dxm-template-preview{background:radial-gradient(circle at 20% 20%,rgba(168,85,247,.30),transparent 30%),linear-gradient(135deg,rgba(88,28,135,.32),rgba(34,211,238,.12))}.dxm-template-card[data-tone="blue"] .dxm-template-preview{background:radial-gradient(circle at 20% 20%,rgba(59,130,246,.26),transparent 30%),linear-gradient(135deg,rgba(30,64,175,.26),rgba(2,6,23,.22))}.dxm-template-glyph{width:72px;height:72px;display:grid;place-items:center;border-radius:24px;background:rgba(255,255,255,.11);color:rgba(255,255,255,.95);font-size:30px;font-weight:950}.dxm-template-body{padding:14px}.dxm-template-body strong{display:block;color:#fff;font-size:15px;line-height:1.25;font-weight:950}.dxm-template-body small{display:block;margin-top:7px;color:rgba(255,255,255,.60);line-height:1.5;font-size:12px}.dxm-template-meta{display:flex;flex-wrap:wrap;gap:7px;margin-top:12px}.dxm-template-pill{display:inline-flex;align-items:center;min-height:24px;padding:5px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.055);color:rgba(255,255,255,.68);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}.dxm-template-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:13px}.dxm-template-btn,.dxm-template-select{display:inline-flex;align-items:center;justify-content:center;min-height:33px;padding:7px 10px;border-radius:11px;border:1px solid rgba(255,255,255,.11);background:rgba(255,255,255,.055);color:rgba(255,255,255,.82);font-size:11px;font-weight:900}.dxm-template-select{min-width:110px}.dxm-template-btn.primary{border-color:rgba(34,211,238,.30);background:rgba(34,211,238,.10);color:#e0faff;cursor:pointer}.dxm-template-btn.disabled{opacity:.55;cursor:not-allowed}.dxm-template-safe{border-color:rgba(34,197,94,.24);background:rgba(34,197,94,.08);color:#bbf7d0}.dxm-template-modal-backdrop{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.72);display:grid;place-items:center;padding:22px}.dxm-template-modal{width:min(760px,100%);max-height:86vh;overflow:auto;border:1px solid rgba(34,211,238,.22);border-radius:24px;background:#070b18;color:#fff;box-shadow:0 25px 80px rgba(0,0,0,.55)}.dxm-template-modal-head{padding:18px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;gap:14px;align-items:center;justify-content:space-between}.dxm-template-modal-title{font-size:20px;font-weight:950;line-height:1.2}.dxm-template-modal-close{width:38px;height:38px;border-radius:999px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.06);color:#fff;font-weight:950}.dxm-template-modal-body{padding:18px;display:grid;gap:13px}.dxm-template-preview-strip{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px}.dxm-template-mini-card{min-height:90px;border:1px solid rgba(255,255,255,.09);border-radius:16px;background:linear-gradient(135deg,rgba(34,211,238,.10),rgba(168,85,247,.12));padding:12px}.dxm-template-mini-card strong{display:block;font-size:13px}.dxm-template-mini-card span{display:block;margin-top:6px;font-size:11px;color:rgba(255,255,255,.58)}.dxm-template-json{white-space:pre-wrap;overflow:auto;max-height:240px;padding:12px;border-radius:16px;background:rgba(255,255,255,.045);color:rgba(255,255,255,.72);font-size:11px;line-height:1.55}@media(max-width:800px){.dxm-template-tabs{top:56px;overflow-x:auto;flex-wrap:nowrap}.dxm-template-tab{flex:0 0 auto}}
    </style>

    <div
        class="dxm-template-shell"
        data-dxm-flow="template-library"
        x-data="{ open: false, selected: null }"
    >
        <section class="dxm-template-hero">
            <div class="dxm-template-kicker">Visual Template Library</div>
            <h1 class="dxm-template-title">Reusable app builder blocks.</h1>
            <div class="dxm-template-sub">
                Select app templates, tab structures, page layouts, section blocks, item cards, media blocks, widgets, icons, and forms visually.
                This version keeps a safe preview-first workflow. Apply is append-only and never wipes existing sections/items.
            </div>
        </section>

        <nav class="dxm-template-tabs" aria-label="Template library tabs">
            @foreach ($tabs as $key => $data)
                <a href="{{ url('/admin/template-library') }}?tab={{ $key }}" class="dxm-template-tab {{ $activeTab === $key ? 'active' : '' }}">{{ $data['label'] }} <span>{{ $counts[$key] ?? 0 }}</span></a>
            @endforeach
        </nav>

        <div class="dxm-template-note">
            @if ($databaseReady)
                Template database is ready. Apply remains safe append-only: it creates new sections/items inside the selected app and does not wipe existing builder data. Use templates for full tabs, pages, sections, insert blocks, media blocks, and single item/card starters.
            @else
                Template database is not ready yet. Run the migration and seeder, then refresh this page.
            @endif
        </div>

        <section class="dxm-template-panel-head"><strong>{{ $activeTabData['label'] }}</strong>{{ $activeTabData['note'] }}</section>

        <section class="dxm-template-grid">
            @forelse ($panelTemplates as $template)
                @php
                    $previewPayload = [
                        'title' => $template['title'] ?? 'Template',
                        'description' => $template['description'] ?? '',
                        'badge' => $template['badge'] ?? 'Template',
                        'status' => $template['status'] ?? 'Ready',
                        'glyph' => $template['glyph'] ?? '▣',
                        'section_count' => $template['section_count'] ?? 0,
                        'item_count' => $template['item_count'] ?? 0,
                        'preview' => $template['preview'] ?? [],
                        'payload' => $template['payload'] ?? [],
                    ];
                @endphp

                <article class="dxm-template-card" data-tone="{{ $template['tone'] ?? 'cyan' }}">
                    <div class="dxm-template-preview"><div class="dxm-template-glyph">{{ $template['glyph'] }}</div></div>
                    <div class="dxm-template-body">
                        <strong>{{ $template['title'] }}</strong>
                        <small>{{ $template['description'] }}</small>
                        <div class="dxm-template-meta">
                            <span class="dxm-template-pill">{{ $template['badge'] }}</span>
                            <span class="dxm-template-pill {{ ($template['can_apply'] ?? false) ? 'dxm-template-safe' : '' }}">{{ $template['status'] }}</span>
                            @if (($template['can_apply'] ?? false) && $databaseReady)
                                <span class="dxm-template-pill">Append Only</span>
                            @endif
                            @if (($template['section_count'] ?? 0) > 0)
                                <span class="dxm-template-pill">{{ $template['section_count'] }} section(s)</span>
                            @endif
                            @if (($template['item_count'] ?? 0) > 0)
                                <span class="dxm-template-pill">{{ $template['item_count'] }} item(s)</span>
                            @endif
                        </div>
                        <div class="dxm-template-actions">
                            <button
                                type="button"
                                class="dxm-template-btn primary"
                                x-on:click='selected = @json($previewPayload); open = true'
                            >Preview</button>

                            @if (($template['can_apply'] ?? false) && $databaseReady && ! empty($template['id']))
                                <form method="POST" action="{{ route('admin.template-library.apply', $template['id']) }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;" onsubmit="return confirm('Apply this template to the selected app? This will append new sections/items and will not wipe existing content.');">
                                    @csrf
                                    <select name="target_tab" class="dxm-template-select">
                                        @foreach ($targetTabs as $tabKey => $tabLabel)
                                            <option value="{{ $tabKey }}" {{ $recommendedTabFor($template) === $tabKey ? 'selected' : '' }}>{{ $tabLabel }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="dxm-template-btn primary">Apply</button>
                                </form>
                            @else
                                <button type="button" class="dxm-template-btn disabled" disabled>{{ $databaseReady ? 'Visual Only' : 'Run Migration' }}</button>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="dxm-template-note">No template records found for this category yet.</div>
            @endforelse
        </section>

        <template x-if="open && selected">
            <div class="dxm-template-modal-backdrop" x-on:click.self="open = false">
                <section class="dxm-template-modal">
                    <div class="dxm-template-modal-head">
                        <div>
                            <div class="dxm-template-kicker" x-text="selected.badge"></div>
                            <div class="dxm-template-modal-title" x-text="selected.title"></div>
                        </div>
                        <button type="button" class="dxm-template-modal-close" x-on:click="open = false">×</button>
                    </div>
                    <div class="dxm-template-modal-body">
                        <div class="dxm-template-note" x-text="selected.description"></div>

                        <div class="dxm-template-preview-strip">
                            <div class="dxm-template-mini-card"><strong>Sections</strong><span x-text="selected.section_count"></span></div>
                            <div class="dxm-template-mini-card"><strong>Items</strong><span x-text="selected.item_count"></span></div>
                            <div class="dxm-template-mini-card"><strong>Apply Mode</strong><span>Append only</span></div>
                        </div>

                        <template x-if="selected.preview && selected.preview.what_it_creates">
                            <div class="dxm-template-note">
                                <strong style="display:block;color:#fff;margin-bottom:8px;">What it creates</strong>
                                <template x-for="row in selected.preview.what_it_creates">
                                    <div>• <span x-text="row"></span></div>
                                </template>
                            </div>
                        </template>

                        <template x-if="selected.preview && selected.preview.best_for">
                            <div class="dxm-template-note">
                                <strong style="display:block;color:#fff;margin-bottom:8px;">Best for</strong>
                                <template x-for="row in selected.preview.best_for">
                                    <span class="dxm-template-pill" style="margin-right:6px;" x-text="row"></span>
                                </template>
                            </div>
                        </template>

                        <details>
                            <summary class="dxm-template-btn">View technical payload</summary>
                            <pre class="dxm-template-json" x-text="JSON.stringify(selected.payload, null, 2)"></pre>
                        </details>
                    </div>
                </section>
            </div>
        </template>
    </div>
</x-filament::page>

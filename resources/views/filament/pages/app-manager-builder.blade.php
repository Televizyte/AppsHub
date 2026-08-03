<x-filament::page>
    @php
        $apps = $this->apps();
        $sourceApps = $this->sourceApps();
        $targetApps = $this->targetApps();
        $capabilityGroups = $this->capabilityGroups();
        $activeId = (int) \App\Support\ActiveApp::ensureId();
        $builderTabs = [
            'identity' => 'Identity',
            'branding' => 'Branding',
            'theme' => 'Theme',
            'store' => 'Store / Build',
            'contact' => 'Contact / Social',
            'engines' => 'Engines',
            'review' => 'Review',
        ];
        $previewPrimary = $createForm['primary_color'] ?? '#0f172a';
        $previewAccent = $createForm['accent_color'] ?? '#06b6d4';
        $previewBackground = $createForm['background_color'] ?? '#020617';
        $previewText = $createForm['text_color'] ?? '#ffffff';
        $enabledCapabilities = collect($createForm['capabilities'] ?? [])->filter()->keys()->values()->all();
    @endphp

    <style>
        /* AppsHub App Manager UI polish: visual color picker, one-arrow selects, stronger preview, compact builder. */
        .am-shell{display:grid;gap:16px;max-width:1180px;margin:0 auto}.am-hero{border:1px solid rgba(34,211,238,.18);border-radius:28px;padding:20px;background:radial-gradient(circle at top left,rgba(34,211,238,.16),transparent 36%),radial-gradient(circle at top right,rgba(168,85,247,.14),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(15,23,42,.9))}.am-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}.am-kicker{display:inline-flex;padding:7px 12px;border-radius:999px;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:rgba(207,250,254,.95);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.am-title{margin:10px 0 0;color:#fff;font-size:clamp(28px,4vw,42px);line-height:1.02;font-weight:950;letter-spacing:-.055em}.am-sub{margin-top:8px;color:rgba(255,255,255,.65);font-size:14px;line-height:1.55;max-width:850px}.am-actions{display:flex;gap:8px;flex-wrap:wrap}.am-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:38px;padding:9px 13px;border:1px solid rgba(148,163,184,.16);border-radius:13px;background:rgba(15,23,42,.58);color:#fff;text-decoration:none;font-size:12px;font-weight:900;cursor:pointer}.am-btn.primary{border-color:rgba(34,211,238,.38);background:linear-gradient(135deg,#6d5dfc,#06b6d4)}.am-btn.danger{border-color:rgba(248,113,113,.28);background:rgba(127,29,29,.18);color:#fecaca}.am-btn:hover{filter:brightness(1.08)}.am-tabs{display:flex;gap:8px;flex-wrap:wrap;border:1px solid rgba(148,163,184,.10);border-radius:22px;background:rgba(15,23,42,.58);padding:8px}.am-tab{border:0;border-radius:15px;padding:10px 13px;background:transparent;color:#93c5fd;font-size:12px;font-weight:950;cursor:pointer}.am-tab.active{background:linear-gradient(135deg,#6d5dfc,#06b6d4);color:#fff}.am-panel{border:1px solid rgba(148,163,184,.12);border-radius:24px;background:rgba(15,23,42,.58);padding:16px}.am-panel-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-bottom:14px}.am-panel-title{color:#fff;font-size:20px;font-weight:950}.am-note{color:#93c5fd;font-size:12px;line-height:1.5}.am-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}.am-app-card{border:1px solid rgba(148,163,184,.12);border-radius:22px;padding:14px;background:linear-gradient(145deg,rgba(15,23,42,.72),rgba(2,6,23,.48));display:grid;gap:12px;min-height:180px}.am-app-card.current{border-color:rgba(34,211,238,.42);box-shadow:0 0 0 1px rgba(34,211,238,.12),0 18px 48px rgba(8,145,178,.10)}.am-app-visual{height:70px;border-radius:18px;border:1px solid rgba(255,255,255,.07);background:radial-gradient(circle at 20% 10%,rgba(34,211,238,.22),transparent 30%),radial-gradient(circle at 80% 0%,rgba(168,85,247,.26),transparent 35%),linear-gradient(135deg,rgba(8,47,73,.78),rgba(30,41,59,.64));display:flex;align-items:center;padding:10px;gap:12px}.am-app-head{display:grid;grid-template-columns:54px minmax(0,1fr);gap:12px;align-items:center}.am-logo{width:54px;height:54px;border-radius:16px;background:rgba(255,255,255,.07);display:grid;place-items:center;overflow:hidden;color:#fff;font-weight:950}.am-logo img{width:100%;height:100%;object-fit:cover}.am-app-card strong{display:block;color:#fff;font-size:15px;font-weight:950}.am-app-card small{display:block;color:rgba(255,255,255,.58);margin-top:4px}.am-badges{display:flex;gap:6px;flex-wrap:wrap}.am-badge{display:inline-flex;align-items:center;border:1px solid rgba(148,163,184,.14);border-radius:999px;padding:5px 8px;background:rgba(15,23,42,.72);color:#bfdbfe;font-size:10px;font-weight:950}.am-badge.good{border-color:rgba(16,185,129,.24);background:rgba(16,185,129,.12);color:#bbf7d0}.am-card-actions{display:flex;gap:7px;flex-wrap:wrap}.am-form-grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,390px);gap:14px;align-items:start}.am-field-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.am-field{display:grid;gap:6px}.am-field.full{grid-column:1/-1}.am-label{color:#93c5fd;font-size:11px;font-weight:950}.am-input,.am-select,.am-textarea{width:100%;border:1px solid rgba(148,163,184,.16);border-radius:13px;background-color:rgba(2,6,23,.62);color:#fff;min-height:43px;padding:10px 12px;font-size:13px}.am-select{appearance:none;-webkit-appearance:none;-moz-appearance:none;background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 20 20' fill='none'%3E%3Cpath d='M6 8l4 4 4-4' stroke='%237dd3fc' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 13px center;padding-right:42px}.am-select::-ms-expand{display:none}.am-textarea{min-height:90px}.am-check{display:flex;gap:9px;align-items:center;color:#e2e8f0;font-size:12px;font-weight:850}.am-preview{position:sticky;top:78px;border:1px solid rgba(34,211,238,.16);border-radius:24px;background:linear-gradient(145deg,rgba(2,6,23,.94),rgba(15,23,42,.84));padding:14px;display:grid;gap:12px}.am-preview-card{border:1px solid rgba(148,163,184,.12);border-radius:18px;padding:13px;background:rgba(15,23,42,.7)}.am-preview-card h3{margin:0;color:#fff;font-size:18px;font-weight:950}.am-preview-card p{margin:8px 0 0;color:rgba(255,255,255,.62);font-size:12px;line-height:1.45}.am-builder-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}.am-builder-tab{border:1px solid rgba(148,163,184,.12);border-radius:999px;padding:8px 11px;background:rgba(15,23,42,.58);color:#bae6fd;font-size:11px;font-weight:950;cursor:pointer}.am-builder-tab.active{border-color:rgba(34,211,238,.38);background:rgba(8,145,178,.20);color:#fff}.am-savebar{display:flex;justify-content:flex-end;gap:9px;margin-top:14px;padding:10px;border-radius:18px;background:rgba(2,6,23,.45)}.am-engine-group{border:1px solid rgba(148,163,184,.12);border-radius:20px;background:rgba(2,6,23,.34);padding:14px}.am-engine-group h4{margin:0;color:#fff;font-weight:950}.am-engine-group p{margin:6px 0 12px;color:rgba(255,255,255,.58);font-size:12px;line-height:1.45}.am-engine-list{display:grid;gap:8px}.am-engine-item{display:grid;grid-template-columns:18px minmax(0,1fr);gap:8px;align-items:start;border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:9px;background:rgba(15,23,42,.52)}.am-engine-item span{color:#fff;font-size:12px;font-weight:900}.am-engine-item small{display:block;color:rgba(255,255,255,.55);font-size:11px;line-height:1.35;margin-top:2px}.am-color-field{display:grid;grid-template-columns:48px minmax(0,1fr);gap:8px;align-items:center}.am-color-swatch{width:48px;height:43px;border:1px solid rgba(148,163,184,.18);border-radius:13px;background:rgba(15,23,42,.72);padding:4px}.am-color-swatch input{width:100%;height:100%;border:0;padding:0;background:transparent;cursor:pointer}.am-color-chip{height:34px;border-radius:12px;border:1px solid rgba(255,255,255,.12)}.am-phone{border:1px solid rgba(34,211,238,.22);border-radius:30px;background:#020617;padding:12px;box-shadow:0 22px 70px rgba(0,0,0,.32)}.am-phone-screen{overflow:hidden;border-radius:24px;border:1px solid rgba(255,255,255,.10);background:var(--am-bg,#020617);color:var(--am-text,#fff);min-height:390px}.am-phone-hero{height:128px;background:linear-gradient(135deg,var(--am-primary,#0f172a),var(--am-accent,#06b6d4));display:flex;align-items:flex-end;padding:14px}.am-phone-logo{width:58px;height:58px;border-radius:18px;background:rgba(255,255,255,.18);display:grid;place-items:center;font-weight:950;color:#fff;border:1px solid rgba(255,255,255,.22)}.am-phone-body{padding:16px}.am-phone-title{font-size:22px;line-height:1.05;font-weight:950;letter-spacing:-.04em}.am-phone-sub{margin-top:8px;font-size:12px;line-height:1.5;opacity:.72}.am-phone-actions{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:15px}.am-phone-action{height:42px;border-radius:14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.10)}.am-phone-action.primary{background:var(--am-accent,#06b6d4)}.am-phone-badges{display:flex;flex-wrap:wrap;gap:6px;margin-top:14px}.am-phone-badges span{font-size:10px;font-weight:900;border-radius:999px;padding:5px 8px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.10)}.am-review-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.am-review-item{border:1px solid rgba(148,163,184,.12);border-radius:16px;padding:12px;background:rgba(2,6,23,.38)}.am-review-item span{display:block;color:#93c5fd;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.06em}.am-review-item strong{display:block;color:#fff;font-size:13px;margin-top:5px}.am-empty{border:1px dashed rgba(148,163,184,.18);border-radius:18px;padding:16px;color:#94a3b8;background:rgba(2,6,23,.25)}@media(max-width:1100px){.am-form-grid{grid-template-columns:1fr}.am-preview{position:relative;top:auto}.am-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.am-field-grid,.am-grid,.am-review-grid{grid-template-columns:1fr}.am-title{font-size:30px}.am-panel{padding:12px}.am-phone-screen{min-height:320px}.am-savebar{justify-content:stretch}.am-savebar .am-btn{flex:1}.am-actions .am-btn{flex:1}}
    </style>

    <div class="am-shell">
        <section class="am-hero">
            <div class="am-top">
                <div>
                    <div class="am-kicker">Beginner App Manager</div>
                    <h1 class="am-title">Create, clone, and prepare AppsHub apps visually.</h1>
                    <div class="am-sub">Use this beginner workspace for app identity, branding direction, theme colors, store details, and engine selection. Detailed uploads and deeper settings still continue inside App Settings and App Capabilities after creation.</div>
                </div>
                <div class="am-actions">
                    <a class="am-btn" href="{{ url('/admin/control-center?tab=apps') }}">← Control Center</a>
                    <button type="button" class="am-btn primary" wire:click="setWorkspace('create')">+ Create App</button>
                    <button type="button" class="am-btn" wire:click="setWorkspace('clone')">Clone App</button>
                </div>
            </div>
        </section>

        <div class="am-tabs">
            <button type="button" class="am-tab {{ $workspace === 'list' ? 'active' : '' }}" wire:click="setWorkspace('list')">App Manager</button>
            <button type="button" class="am-tab {{ $workspace === 'create' ? 'active' : '' }}" wire:click="setWorkspace('create')">Create New App</button>
            <button type="button" class="am-tab {{ $workspace === 'clone' ? 'active' : '' }}" wire:click="setWorkspace('clone')">Clone Existing App</button>
        </div>

        @if ($workspace === 'list')
            <section class="am-panel">
                <div class="am-panel-head">
                    <div>
                        <div class="am-panel-title">App Manager</div>
                        <div class="am-note">Open workspaces, switch active app, create new apps, or prepare clone work safely.</div>
                    </div>
                    <div class="am-actions">
                        <button type="button" class="am-btn primary" wire:click="setWorkspace('create')">+ Create New App</button>
                        <button type="button" class="am-btn" wire:click="setWorkspace('clone')">Clone Existing App</button>
                    </div>
                </div>

                <div class="am-grid">
                    @forelse ($apps as $app)
                        <article class="am-app-card {{ $app['is_current'] ? 'current' : '' }}">
                            <div class="am-app-visual">
                                <div class="am-logo">
                                    @if (! empty($app['logo_url']))
                                        <img src="{{ $app['logo_url'] }}" alt="{{ $app['name'] }}">
                                    @else
                                        {{ strtoupper(substr($app['name'], 0, 1)) }}
                                    @endif
                                </div>
                                <div>
                                    <strong>{{ $app['name'] }}</strong>
                                    <small>{{ $app['slug'] }} · App ID {{ $app['id'] }}</small>
                                </div>
                            </div>
                            <div class="am-badges">
                                <span class="am-badge {{ $app['is_active'] ? 'good' : '' }}">{{ $app['is_active'] ? 'Active' : 'Inactive' }}</span>
                                @if ($app['is_current'])<span class="am-badge good">Current Workspace</span>@endif
                                <span class="am-badge">{{ $app['counts']['sections'] ?? 0 }} sections</span>
                                <span class="am-badge">{{ $app['counts']['items'] ?? 0 }} items</span>
                            </div>
                            <div class="am-card-actions">
                                @if (! $app['is_current'])
                                    <button type="button" class="am-btn" wire:click="makeActive({{ (int) $app['id'] }})">Make Active</button>
                                @endif
                                <a class="am-btn" href="{{ route('admin.switch-active-app', ['id' => $app['id'], 'return' => '/admin/beginner-dashboard']) }}">Workspace</a>
                                <a class="am-btn" href="{{ route('admin.switch-active-app', ['id' => $app['id'], 'return' => '/admin/app-settings']) }}">Settings</a>
                            </div>
                        </article>
                    @empty
                        <div class="am-empty">No apps found yet.</div>
                    @endforelse
                </div>
            </section>
        @elseif ($workspace === 'create')
            <section class="am-panel">
                <div class="am-panel-head">
                    <div>
                        <div class="am-panel-title">Create New App</div>
                        <div class="am-note">Tabs are persistent through query string. Color fields now support picker + hex input.</div>
                    </div>
                    <button type="button" class="am-btn" wire:click="setWorkspace('list')">Back to App Manager</button>
                </div>

                <div class="am-builder-tabs">
                    @foreach ($builderTabs as $key => $label)
                        <button type="button" class="am-builder-tab {{ $builder_tab === $key ? 'active' : '' }}" wire:click="setBuilderTab('{{ $key }}')">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="am-form-grid">
                    <div>
                        @if ($builder_tab === 'identity')
                            <div class="am-field-grid">
                                <label class="am-field"><span class="am-label">App Name</span><input class="am-input" wire:model.live="createForm.name" placeholder="E.g. Dunamis TV"></label>
                                <label class="am-field"><span class="am-label">App Slug</span><input class="am-input" wire:model.defer="createForm.slug" placeholder="dunamis-tv"></label>
                                <label class="am-field"><span class="am-label">Display Name</span><input class="am-input" wire:model.defer="createForm.display_name" placeholder="Defaults to app name"></label>
                                <label class="am-field"><span class="am-label">App Type</span><select class="am-select" wire:model.defer="createForm.app_type"><option value="church_tv">Church TV</option><option value="media_tv">Media TV</option><option value="business">Business</option><option value="education">Education</option><option value="logistics_sourcing">Logistics / Sourcing</option><option value="custom">Custom</option></select></label>
                                <label class="am-field full"><span class="am-label">Tagline</span><input class="am-input" wire:model.defer="createForm.tagline" placeholder="E.g. Inspire. Watch. Grow."></label>
                                <label class="am-check"><input type="checkbox" wire:model.defer="createForm.is_active"> <span>App active after creation</span></label>
                            </div>
                        @elseif ($builder_tab === 'branding')
                            <div class="am-field-grid">
                                <label class="am-field full"><span class="am-label">Branding Assets</span><div class="am-note">Logo, banner, splash, and icon uploads continue in App Settings after creation. This step keeps creation light and safe.</div></label>
                                <label class="am-check"><input type="checkbox" wire:model.defer="createForm.clone_structure"> <span>Create from existing app structure</span></label>
                                <label class="am-field"><span class="am-label">Clone From App</span><select class="am-select" wire:model.defer="createForm.clone_from_app_id"><option value="">No source app</option>@foreach($sourceApps as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                                <label class="am-check"><input type="checkbox" wire:model.defer="createForm.copy_capabilities"> <span>Copy capabilities from source</span></label>
                            </div>
                        @elseif ($builder_tab === 'theme')
                            <div class="am-field-grid">
                                <label class="am-field"><span class="am-label">Primary Color</span><div class="am-color-field"><span class="am-color-swatch"><input type="color" wire:model.live="createForm.primary_color"></span><input class="am-input" wire:model.live="createForm.primary_color" placeholder="#0f172a"></div></label>
                                <label class="am-field"><span class="am-label">Accent Color</span><div class="am-color-field"><span class="am-color-swatch"><input type="color" wire:model.live="createForm.accent_color"></span><input class="am-input" wire:model.live="createForm.accent_color" placeholder="#06b6d4"></div></label>
                                <label class="am-field"><span class="am-label">Background Color</span><div class="am-color-field"><span class="am-color-swatch"><input type="color" wire:model.live="createForm.background_color"></span><input class="am-input" wire:model.live="createForm.background_color" placeholder="#020617"></div></label>
                                <label class="am-field"><span class="am-label">Text Color</span><div class="am-color-field"><span class="am-color-swatch"><input type="color" wire:model.live="createForm.text_color"></span><input class="am-input" wire:model.live="createForm.text_color" placeholder="#ffffff"></div></label>
                                <label class="am-field"><span class="am-label">Theme Mode</span><select class="am-select" wire:model.defer="createForm.theme_mode"><option value="dark">Dark</option><option value="light">Light</option><option value="system">System</option></select></label>
                                <div class="am-field"><span class="am-label">Color Preview</span><div class="am-color-chip" style="background:linear-gradient(135deg,{{ $previewPrimary }},{{ $previewAccent }});"></div></div>
                            </div>
                        @elseif ($builder_tab === 'store')
                            <div class="am-field-grid">
                                <label class="am-field"><span class="am-label">Package Name</span><input class="am-input" wire:model.defer="createForm.store_package" placeholder="com.company.app"></label>
                                <label class="am-field"><span class="am-label">Play Store URL</span><input class="am-input" wire:model.defer="createForm.play_store_url" placeholder="https://play.google.com/..."></label>
                            </div>
                        @elseif ($builder_tab === 'contact')
                            <div class="am-field-grid">
                                <label class="am-field"><span class="am-label">Support Email</span><input class="am-input" wire:model.defer="createForm.support_email" placeholder="support@example.com"></label>
                                <label class="am-field"><span class="am-label">Website URL</span><input class="am-input" wire:model.defer="createForm.website_url" placeholder="https://example.com"></label>
                            </div>
                        @elseif ($builder_tab === 'engines')
                            <div class="am-grid">
                                @foreach ($capabilityGroups as $groupKey => $group)
                                    <div class="am-engine-group">
                                        <h4>{{ $group['label'] ?? $groupKey }}</h4>
                                        <p>{{ $group['description'] ?? '' }}</p>
                                        <div class="am-engine-list">
                                            @foreach (($group['items'] ?? []) as $capKey => $item)
                                                <label class="am-engine-item">
                                                    <input type="checkbox" wire:model.defer="createForm.capabilities.{{ $capKey }}">
                                                    <span>{{ $item['label'] ?? $capKey }}<small>{{ $item['description'] ?? '' }}</small></span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="am-review-grid">
                                <div class="am-review-item"><span>Name</span><strong>{{ $createForm['name'] ?: 'New App' }}</strong></div>
                                <div class="am-review-item"><span>Slug</span><strong>{{ $createForm['slug'] ?: 'not-set' }}</strong></div>
                                <div class="am-review-item"><span>Type</span><strong>{{ $createForm['app_type'] ?? 'church_tv' }}</strong></div>
                                <div class="am-review-item"><span>Theme</span><strong>{{ $previewPrimary }} / {{ $previewAccent }}</strong></div>
                                <div class="am-review-item"><span>Package</span><strong>{{ $createForm['store_package'] ?: 'not set' }}</strong></div>
                                <div class="am-review-item"><span>Enabled Engines</span><strong>{{ count($enabledCapabilities) }}</strong></div>
                            </div>
                        @endif

                        <div class="am-savebar">
                            <button type="button" class="am-btn" wire:click="setWorkspace('list')">Cancel</button>
                            <button type="button" class="am-btn primary" wire:click="createApp" wire:loading.attr="disabled">Create App</button>
                        </div>
                    </div>

                    <aside class="am-preview">
                        <div class="am-phone" style="--am-primary: {{ $previewPrimary }}; --am-accent: {{ $previewAccent }}; --am-bg: {{ $previewBackground }}; --am-text: {{ $previewText }};">
                            <div class="am-phone-screen">
                                <div class="am-phone-hero"><div class="am-phone-logo">{{ strtoupper(substr($createForm['display_name'] ?: ($createForm['name'] ?: 'A'), 0, 1)) }}</div></div>
                                <div class="am-phone-body">
                                    <div class="am-phone-title">{{ $createForm['display_name'] ?: ($createForm['name'] ?: 'New App') }}</div>
                                    <div class="am-phone-sub">{{ $createForm['tagline'] ?: 'Your app tagline appears here.' }}</div>
                                    <div class="am-phone-actions"><div class="am-phone-action primary"></div><div class="am-phone-action"></div></div>
                                    <div class="am-phone-badges">
                                        <span>{{ $createForm['is_active'] ? 'Active' : 'Inactive' }}</span>
                                        <span>{{ $createForm['app_type'] ?? 'church_tv' }}</span>
                                        <span>{{ count($enabledCapabilities) }} engines</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="am-preview-card">
                            <h3>After creation</h3>
                            <p>The new app becomes the active workspace. Use App Settings for graphics/uploads and App Capabilities for advanced toggles.</p>
                        </div>
                    </aside>
                </div>
            </section>
        @else
            <section class="am-panel">
                <div class="am-panel-head">
                    <div>
                        <div class="am-panel-title">Clone Existing App</div>
                        <div class="am-note">Safely copy Tabs, Routes, Sections, Items, and optional capabilities from one app to another.</div>
                    </div>
                    <button type="button" class="am-btn" wire:click="setWorkspace('list')">Back to App Manager</button>
                </div>

                <div class="am-form-grid">
                    <div class="am-field-grid">
                        <label class="am-field"><span class="am-label">Source App</span><select class="am-select" wire:model.live="cloneForm.source_app_id"><option value="">Select source app</option>@foreach($sourceApps as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                        <label class="am-field"><span class="am-label">Target Mode</span><select class="am-select" wire:model.live="cloneForm.target_mode"><option value="new">Create New App</option><option value="existing">Clone Into Existing App</option></select></label>

                        @if (($cloneForm['target_mode'] ?? 'new') === 'new')
                            <label class="am-field"><span class="am-label">New App Name</span><input class="am-input" wire:model.live="cloneForm.new_name" placeholder="New app name"></label>
                            <label class="am-field"><span class="am-label">New App Slug</span><input class="am-input" wire:model.defer="cloneForm.new_slug" placeholder="new-app-slug"></label>
                        @else
                            <label class="am-field full"><span class="am-label">Target Existing App</span><select class="am-select" wire:model.defer="cloneForm.target_app_id"><option value="">Select target app</option>@foreach($targetApps as $id => $name)<option value="{{ $id }}">{{ $name }}</option>@endforeach</select></label>
                        @endif

                        <label class="am-check"><input type="checkbox" wire:model.defer="cloneForm.copy_capabilities"> <span>Copy source capabilities</span></label>
                        <label class="am-check"><input type="checkbox" wire:model.defer="cloneForm.wipe_target_first"> <span>Wipe target structure first</span></label>
                    </div>

                    <aside class="am-preview">
                        <div class="am-preview-card">
                            <h3>Clone safety</h3>
                            <p>Use structure-only clone when preparing new apps from a proven template. Wipe target only when intentionally replacing the target structure.</p>
                        </div>
                        <button type="button" class="am-btn primary" wire:click="cloneApp" wire:loading.attr="disabled">Run Clone</button>
                    </aside>
                </div>
            </section>
        @endif
    </div>

    <script>
        /* AppsHub App Manager scroll persistence: keeps position after refresh/navigation. */
        (function(){
            const scrollKey = 'appshub_app_manager_scroll';
            window.addEventListener('beforeunload', function(){ localStorage.setItem(scrollKey, String(window.scrollY || 0)); });
            window.addEventListener('load', function(){
                const y = parseInt(localStorage.getItem(scrollKey) || '0', 10);
                if (y > 0) setTimeout(function(){ window.scrollTo({ top:y, behavior:'instant' }); }, 80);
            });
        })();
    </script>
</x-filament::page>

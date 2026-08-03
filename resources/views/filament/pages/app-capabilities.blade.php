<x-filament::page>
    <style>
        .dxm-cap-shell{display:grid;gap:18px}.dxm-cap-hero{border:1px solid rgba(34,211,238,.18);border-radius:28px;padding:22px;background:radial-gradient(circle at top left,rgba(34,211,238,.18),transparent 36%),radial-gradient(circle at top right,rgba(168,85,247,.14),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.95),rgba(15,23,42,.88));overflow:hidden}.dxm-cap-hero-grid{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.dxm-cap-kicker{display:inline-flex;padding:7px 12px;border-radius:999px;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:rgba(207,250,254,.95);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-cap-title{margin:12px 0 0;color:#fff;font-size:clamp(28px,4vw,42px);line-height:1.04;font-weight:950;letter-spacing:-.055em}.dxm-cap-sub{margin:10px 0 0;max-width:900px;color:rgba(255,255,255,.68);font-size:14px;line-height:1.6}.dxm-cap-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.dxm-cap-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:9px 13px;border-radius:13px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.055);color:rgba(255,255,255,.82);font-size:12px;font-weight:900;transition:.16s ease}.dxm-cap-btn:hover{border-color:rgba(34,211,238,.42);background:rgba(34,211,238,.10);color:#fff}.dxm-cap-btn.primary{border-color:rgba(34,211,238,.38);background:rgba(34,211,238,.14);color:#e0fbff}.dxm-cap-stats{display:grid;grid-template-columns:repeat(2,minmax(125px,1fr));gap:10px;min-width:280px}.dxm-cap-stat{padding:15px;border:1px solid rgba(255,255,255,.10);border-radius:20px;background:rgba(2,6,23,.48)}.dxm-cap-stat strong{display:block;color:#fff;font-size:23px;line-height:1}.dxm-cap-stat span{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11px;font-weight:850}.dxm-cap-tabs{display:flex;gap:8px;flex-wrap:wrap}.dxm-cap-tab{border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:9px 13px;font-size:12px;font-weight:900;cursor:pointer}.dxm-cap-tab.active,.dxm-cap-tab:hover{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.12);color:#fff}.dxm-cap-panel{display:none}.dxm-cap-panel.active{display:block}.dxm-cap-box{border:1px solid rgba(255,255,255,.09);border-radius:24px;background:rgba(255,255,255,.035);padding:16px;margin-bottom:14px}.dxm-cap-box h2{margin:0;color:#fff;font-size:20px;font-weight:950;letter-spacing:-.03em}.dxm-cap-box p{margin:7px 0 0;color:rgba(255,255,255,.62);font-size:13px;line-height:1.55}.dxm-cap-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:12px}.dxm-cap-card{position:relative;min-height:150px;border:1px solid rgba(255,255,255,.09);border-radius:22px;background:rgba(2,6,23,.36);padding:15px;color:#fff;transition:.18s ease}.dxm-cap-card.enabled{border-color:rgba(34,211,238,.28);background:linear-gradient(135deg,rgba(34,211,238,.10),rgba(2,6,23,.36))}.dxm-cap-card.disabled{opacity:.78;border-style:dashed}.dxm-cap-card strong{display:block;color:#fff;font-size:14px;font-weight:950;line-height:1.25;padding-right:82px}.dxm-cap-card small{display:block;margin-top:7px;color:rgba(255,255,255,.58);font-size:11.7px;line-height:1.5}.dxm-cap-switch{position:absolute;top:14px;right:14px;display:inline-flex;align-items:center;gap:6px;cursor:pointer}.dxm-cap-switch input{width:18px;height:18px;accent-color:#22d3ee}.dxm-cap-status{display:inline-flex;margin-top:12px;min-height:25px;align-items:center;padding:5px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.055);color:rgba(255,255,255,.72);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.05em}.dxm-cap-hint{border:1px dashed rgba(255,255,255,.15);border-radius:20px;padding:15px;color:rgba(255,255,255,.64);background:rgba(2,6,23,.24);line-height:1.6}.dxm-cap-payload{border:1px solid rgba(34,211,238,.16);border-radius:22px;background:rgba(2,6,23,.42);padding:14px;overflow:hidden}.dxm-cap-payload-head{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:10px}.dxm-cap-payload-title{color:#fff;font-size:14px;font-weight:950}.dxm-cap-payload-note{color:rgba(255,255,255,.58);font-size:12px;line-height:1.45}.dxm-cap-code{max-height:280px;overflow:auto;border:1px solid rgba(255,255,255,.08);border-radius:16px;background:rgba(0,0,0,.22);padding:12px;color:#bfdbfe;font-size:11px;line-height:1.55;white-space:pre-wrap}.dxm-cap-key{display:inline-flex;margin-top:10px;padding:5px 8px;border-radius:999px;background:rgba(59,130,246,.11);border:1px solid rgba(59,130,246,.22);color:#bfdbfe;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.05em}@media(max-width:980px){.dxm-cap-hero-grid{grid-template-columns:1fr}.dxm-cap-stats{min-width:0}}@media(max-width:640px){.dxm-cap-stats{grid-template-columns:1fr}}
    </style>

    @php
        $enabledCount = collect($capabilities ?? [])->filter(fn ($value) => (bool) $value)->count();
        $totalCount = collect($capabilities ?? [])->count();
        $firstTab = array_key_first($groups ?? []) ?: 'workspace';
        $payloadPreview = $this->frontendPayload();
    @endphp

    <div class="dxm-cap-shell" x-data="{ tab: localStorage.getItem('appshub_app_capabilities_tab') || '{{ $firstTab }}' }" x-init="$watch('tab', value => localStorage.setItem('appshub_app_capabilities_tab', value))">
        <section class="dxm-cap-hero">
            <div class="dxm-cap-hero-grid">
                <div>
                    <div class="dxm-cap-kicker">Selected App Capability System</div>
                    <h1 class="dxm-cap-title">{{ $activeApp?->name ?? 'Selected App' }} Capabilities</h1>
                    <div class="dxm-cap-sub">
                        Choose which tools, engines, and app-builder modules belong to this selected app.
                        These settings are stored in <strong>branding_json.capabilities</strong> and will help the workspace/sidebar show only relevant features.
                    </div>
                    <div class="dxm-cap-actions">
                        <button type="button" class="dxm-cap-btn primary" wire:click="save">Save Capabilities</button>
                        <button type="button" class="dxm-cap-btn" wire:click="resetToDefaults">Restore Defaults</button>
                        <a class="dxm-cap-btn" href="{{ url('/admin/beginner-dashboard') }}">Open App Workspace</a>
                        <a class="dxm-cap-btn" href="{{ url('/admin/control-center') }}">Main Control Center</a>
                    </div>
                </div>

                <div class="dxm-cap-stats">
                    <div class="dxm-cap-stat">
                        <strong>{{ $enabledCount }}/{{ $totalCount }}</strong>
                        <span>Enabled Capabilities</span>
                    </div>
                    <div class="dxm-cap-stat">
                        <strong>{{ $activeApp?->id ?? '—' }}</strong>
                        <span>Active App ID</span>
                    </div>
                    <div class="dxm-cap-stat">
                        <strong>{{ count($groups ?? []) }}</strong>
                        <span>Capability Groups</span>
                    </div>
                    <div class="dxm-cap-stat">
                        <strong>JSON</strong>
                        <span>Stored in Branding</span>
                    </div>
                </div>
            </div>
        </section>

        <div class="dxm-cap-hint">
            This is the safe first foundation. It does not create new tables and does not break APIs.
            Later, the app workspace cards and sidebar will read these capabilities to hide features that an app does not use.
        </div>

        <div class="dxm-cap-tabs">
            @foreach ($groups as $groupKey => $group)
                <button
                    type="button"
                    class="dxm-cap-tab"
                    :class="tab === '{{ $groupKey }}' ? 'active' : ''"
                    x-on:click="tab = '{{ $groupKey }}'"
                >
                    {{ $group['label'] ?? ucwords(str_replace('_', ' ', $groupKey)) }}
                </button>
            @endforeach
        </div>

        @foreach ($groups as $groupKey => $group)
            <section class="dxm-cap-panel" :class="tab === '{{ $groupKey }}' ? 'active' : ''">
                <div class="dxm-cap-box">
                    <h2>{{ $group['label'] ?? ucwords(str_replace('_', ' ', $groupKey)) }}</h2>
                    <p>{{ $group['description'] ?? 'Select the capabilities that should be enabled for this app.' }}</p>

                    <div class="dxm-cap-actions">
                        <button type="button" class="dxm-cap-btn" wire:click="enableGroup('{{ $groupKey }}')">Enable Group</button>
                        <button type="button" class="dxm-cap-btn" wire:click="disableGroup('{{ $groupKey }}')">Disable Group</button>
                    </div>
                </div>

                <div class="dxm-cap-grid">
                    @foreach (($group['items'] ?? []) as $key => $item)
                        @php
                            $isEnabled = (bool) ($capabilities[$key] ?? false);
                        @endphp

                        <div class="dxm-cap-card {{ $isEnabled ? 'enabled' : 'disabled' }}">
                            <label class="dxm-cap-switch">
                                <input type="checkbox" wire:model.live="capabilities.{{ $key }}">
                            </label>

                            <strong>{{ $item['label'] ?? ucwords(str_replace('_', ' ', $key)) }}</strong>
                            <small>{{ $item['description'] ?? '' }}</small>
                            <span class="dxm-cap-status">{{ $isEnabled ? 'Enabled' : 'Disabled' }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach

        <section class="dxm-cap-payload">
            <div class="dxm-cap-payload-head">
                <div>
                    <div class="dxm-cap-payload-title">Frontend Payload Preview</div>
                    <div class="dxm-cap-payload-note">This is the safe API alignment view. The Flutter bootstrap can now read capabilities directly while old feature flags remain compatible.</div>
                </div>
                <span class="dxm-cap-key">Bootstrap Ready</span>
            </div>
            <pre class="dxm-cap-code">{{ json_encode($payloadPreview, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
        </section>
    </div>
</x-filament::page>

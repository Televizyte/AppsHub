<div class="dxm-preview-rail">
    <button type="button" class="dxm-preview-handle" data-dxm-toggle-preview>Preview</button>
</div>

<div class="dxm-preview-backdrop" data-dxm-preview-backdrop></div>

<aside class="dxm-preview-drawer" data-dxm-preview-drawer aria-hidden="true">
    <div class="dxm-preview-drawer-head">
        <div>
            <div class="dxm-preview-drawer-title"><span data-dxm-preview-title>{{ $defaultTabs[$activeTab] }}</span> Mobile Preview</div>
            <div class="dxm-preview-drawer-sub">Modular Flutter-style backend preview.</div>
        </div>

        <div class="dxm-preview-drawer-actions">
            <button type="button" class="dxm-preview-icon-btn" title="Refresh preview" onclick="window.location.reload()">↻</button>
            <button type="button" class="dxm-preview-icon-btn" title="Close preview" data-dxm-close-preview>×</button>
        </div>
    </div>

    <div class="dxm-preview-drawer-body">
        <div class="dxm-phone-shell">
            <div class="dxm-phone-screen">
                <div class="dxm-phone-topbar">
                    <div class="dxm-phone-topbar-title" data-dxm-phone-title>{{ $defaultTabs[$activeTab] }}</div>
                    <div class="dxm-phone-topbar-icons">
                        <span>☀</span>
                        <span>⋮</span>
                    </div>
                </div>

                <div class="dxm-phone-content">
                    <div class="dxm-preview-main" data-dxm-preview-main>
                        @include('filament.pages.beginner-dashboard.partials.preview-home')
                        @include('filament.pages.beginner-dashboard.partials.preview-watch')
                        @include('filament.pages.beginner-dashboard.partials.preview-inspire')
                        @include('filament.pages.beginner-dashboard.partials.preview-explore')
                        @include('filament.pages.beginner-dashboard.partials.preview-more')
                    </div>

                    <div class="dxm-preview-detail" data-dxm-preview-detail>
                        <div class="dxm-preview-back-row">
                            <button type="button" class="dxm-preview-back-btn" data-dxm-preview-back>← Back</button>
                        </div>

                        <div class="dxm-preview-detail-card">
                            <div class="dxm-preview-detail-image">
                                <span data-dxm-detail-image-empty>Preview</span>
                                <img src="" alt="" data-dxm-detail-image style="display:none;">
                            </div>

                            <div class="dxm-preview-detail-body">
                                <div class="dxm-preview-detail-title" data-dxm-detail-title></div>
                                <div class="dxm-preview-detail-sub" data-dxm-detail-subtitle></div>
                                <div class="dxm-preview-detail-sub" data-dxm-detail-description></div>
                                <span class="dxm-preview-action-pill" data-dxm-detail-action></span>
                            </div>
                        </div>
                    </div>

                    <div class="dxm-preview-detail" data-dxm-preview-player>
                        <div class="dxm-preview-back-row">
                            <button type="button" class="dxm-preview-back-btn" data-dxm-preview-back>← Back</button>
                        </div>

                        <div class="dxm-preview-detail-card">
                            <div class="dxm-preview-player">
                                <div class="dxm-preview-play">▶</div>
                                <div class="dxm-preview-player-label" data-dxm-player-url></div>
                            </div>

                            <div class="dxm-preview-detail-body">
                                <div class="dxm-preview-detail-title" data-dxm-player-title></div>
                                <div class="dxm-preview-detail-sub" data-dxm-player-subtitle></div>
                                <span class="dxm-preview-action-pill" data-dxm-player-engine></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dxm-phone-bottom">
                    @foreach ($defaultTabs as $key => $label)
                        <button type="button" class="dxm-phone-nav-item {{ $activeTab === $key ? 'is-active' : '' }}" data-dxm-preview-tab="{{ $key }}" data-dxm-preview-label="{{ $label }}">
                            <span class="dxm-phone-nav-dot">▦</span>
                            <span>{{ $label }}</span>
                        </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</aside>

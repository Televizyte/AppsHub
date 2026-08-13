<x-filament-panels::page>
    <style>
        [x-cloak] { display: none !important; }
        .ve-shell { display: flex; flex-direction: column; gap: 12px; }
        .ve-hero { position: relative; overflow: hidden; padding: 16px 18px; border: 1px solid rgba(148,163,184,.18); border-radius: 22px; background: linear-gradient(135deg,rgba(88,28,135,.34),rgba(15,23,42,.96) 48%,rgba(14,165,233,.13)); box-shadow: 0 18px 50px rgba(2,6,23,.22); color: #e5e7eb; }
        .ve-hero::after { content: ''; position: absolute; width: 210px; height: 210px; right: -70px; top: -70px; border-radius: 999px; background: radial-gradient(circle,rgba(168,85,247,.32),transparent 68%); pointer-events: none; }
        .ve-hero-top { position: relative; z-index: 1; display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; }
        .ve-kicker { display: inline-flex; padding: 6px 10px; border: 1px solid rgba(196,181,253,.20); border-radius: 999px; background: rgba(139,92,246,.14); color: #c4b5fd; font-size: 11px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .ve-title { margin-top: 8px; color: #fff; font-size: 24px; line-height: 1.08; font-weight: 950; letter-spacing: -.03em; }
        .ve-desc { margin-top: 7px; color: #cbd5e1; font-size: 12.5px; }
        .ve-app-pill { display: flex; flex-direction: column; min-width: 180px; padding: 10px 12px; border: 1px solid rgba(148,163,184,.18); border-radius: 18px; background: rgba(15,23,42,.74); text-align: right; }
        .ve-app-pill span { color: #94a3b8; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .ve-app-pill strong { color: #fff; font-size: 13px; }
        .ve-actions { position: relative; z-index: 1; display: flex; flex-wrap: wrap; gap: 8px; margin-top: 13px; }
        .ve-btn,.ve-mini-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid rgba(148,163,184,.22); border-radius: 12px; background: rgba(15,23,42,.72); color: #e2e8f0; font-size: 12px; font-weight: 900; text-decoration: none; cursor: pointer; }
        .ve-btn { min-height: 34px; padding: 8px 12px; }
        .ve-mini-btn { min-height: 30px; padding: 6px 9px; font-size: 11px; }
        .ve-btn.primary,.ve-mini-btn.primary { border-color: transparent; background: linear-gradient(135deg,#7c3aed,#06b6d4); color: #fff; }
        .ve-tabs,.ve-editor-tabs { display: flex; gap: 6px; overflow-x: auto; padding: 6px; border: 1px solid rgba(148,163,184,.16); border-radius: 16px; background: rgba(15,23,42,.72); }
        .ve-tab { flex: 0 0 auto; padding: 8px 11px; border: 0; border-radius: 13px; background: transparent; color: #94a3b8; font-size: 12px; font-weight: 900; cursor: pointer; }
        .ve-tab.active { background: linear-gradient(135deg,rgba(124,58,237,.95),rgba(14,165,233,.72)); color: #fff; box-shadow: 0 10px 24px rgba(14,165,233,.16); }
        .ve-card { overflow: hidden; border: 1px solid rgba(148,163,184,.16); border-radius: 20px; background: rgba(15,23,42,.78); color: #e5e7eb; box-shadow: 0 14px 40px rgba(2,6,23,.15); }
        .ve-card-pad { padding: 13px; }
        .ve-card-head,.ve-toolbar { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .ve-card-head { margin-bottom: 12px; }
        .ve-card-title { color: #fff; font-size: 15px; font-weight: 950; }
        .ve-note { margin-top: 3px; color: #94a3b8; font-size: 11.5px; line-height: 1.45; }
        .ve-stats { display: grid; grid-template-columns: repeat(6,minmax(0,1fr)); gap: 9px; }
        .ve-stat { min-height: 78px; padding: 11px; border: 1px solid rgba(148,163,184,.14); border-radius: 16px; background: linear-gradient(180deg,rgba(30,41,59,.72),rgba(15,23,42,.88)); }
        .ve-stat span { color: #94a3b8; font-size: 10px; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
        .ve-stat strong { display: block; margin-top: 6px; color: #fff; font-size: 22px; font-weight: 950; }
        .ve-input,.ve-select,.ve-textarea { width: 100%; min-height: 38px; padding: 8px 10px; border: 1px solid rgba(148,163,184,.22); border-radius: 12px; background-color: rgba(2,6,23,.44) !important; color: #e5e7eb; font-size: 11.5px; outline: none; }
        .ve-select { appearance: none !important; -webkit-appearance: none !important; background-image: none !important; padding-right: 28px !important; }
        .ve-select-wrap { position: relative; display: block; }
        .ve-select-wrap::after { content: '⌄'; position: absolute; right: 10px; top: 50%; transform: translateY(-55%); color: #94a3b8; font-size: 12px; pointer-events: none; }
        .ve-label { display: block; margin-bottom: 5px; color: #94a3b8; font-size: 11px; font-weight: 900; }
        .ve-filterbar { display: grid; grid-template-columns: minmax(190px,1fr) 150px 150px auto; gap: 8px; align-items: end; margin-bottom: 10px; }
        .ve-view-toggle { display: inline-flex; gap: 6px; padding: 4px; border: 1px solid rgba(148,163,184,.16); border-radius: 14px; background: rgba(2,6,23,.42); }
        .ve-view-toggle button { padding: 6px 10px; border: 0; border-radius: 10px; background: transparent; color: #94a3b8; font-size: 11px; font-weight: 900; cursor: pointer; }
        .ve-view-toggle button.active { background: linear-gradient(135deg,rgba(124,58,237,.9),rgba(14,165,233,.65)); color: #fff; }
        .ve-library { display: grid; gap: 12px; }
        .ve-library.is-grid { grid-template-columns: repeat(auto-fill,minmax(210px,1fr)); }
        .ve-library.is-list { grid-template-columns: 1fr; }
        .ve-video-card { min-width: 0; padding: 10px; border: 1px solid rgba(148,163,184,.14); border-radius: 18px; background: rgba(2,6,23,.33); }
        .ve-video-card.is-grid { display: flex; flex-direction: column; gap: 8px; }
        .ve-video-card.is-list { display: grid; grid-template-columns: 160px minmax(0,1fr); gap: 11px; }
        .ve-thumb { overflow: hidden; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(148,163,184,.14); border-radius: 15px; background: linear-gradient(135deg,rgba(124,58,237,.25),rgba(14,165,233,.16)); color: #c4b5fd; font-size: 30px; font-weight: 950; }
        .ve-video-card.is-grid .ve-thumb { width: 100%; aspect-ratio: 16/9; }
        .ve-video-card.is-list .ve-thumb { width: 160px; height: 90px; }
        .ve-thumb img { width: 100%; height: 100%; object-fit: cover; }
        .ve-video-title { color: #fff; font-size: 13px; font-weight: 950; line-height: 1.25; }
        .ve-meta { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 7px; }
        .ve-badge { display: inline-flex; align-items: center; gap: 4px; padding: 4px 7px; border: 1px solid rgba(148,163,184,.16); border-radius: 999px; background: rgba(15,23,42,.75); color: #cbd5e1; font-size: 10px; font-weight: 900; }
        .ve-badge.published { border-color: rgba(34,197,94,.22); background: rgba(22,163,74,.1); color: #86efac; }
        .ve-badge.draft { border-color: rgba(245,158,11,.25); background: rgba(245,158,11,.1); color: #fde68a; }
        .ve-badge.featured { border-color: rgba(217,70,239,.24); background: rgba(168,85,247,.12); color: #f0abfc; }
        .ve-empty { padding: 22px; border: 1px dashed rgba(148,163,184,.24); border-radius: 20px; background: rgba(2,6,23,.24); color: #94a3b8; text-align: center; }
        .ve-focus { display: grid; grid-template-columns: minmax(0,1.35fr) minmax(300px,.65fr); gap: 14px; align-items: start; }
        .ve-focus-main,.ve-focus-side { border: 1px solid rgba(148,163,184,.16); border-radius: 22px; background: rgba(15,23,42,.78); color: #e5e7eb; }
        .ve-focus-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 15px; border-bottom: 1px solid rgba(148,163,184,.12); }
        .ve-focus-title { margin-top: 7px; color: #fff; font-size: 20px; font-weight: 950; }
        .ve-focus-body { padding: 14px; }
        .ve-form-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 10px; margin-top: 12px; }
        .ve-form-span { grid-column: 1/-1; }
        .ve-check { display: flex; align-items: center; gap: 8px; color: #cbd5e1; font-size: 12px; font-weight: 800; }
        .ve-preview { position: sticky; top: 76px; padding: 13px; }
        .ve-preview-art { overflow: hidden; display: grid; place-items: center; width: 100%; aspect-ratio: 16/9; border: 1px solid rgba(148,163,184,.14); border-radius: 18px; background: linear-gradient(135deg,rgba(124,58,237,.34),rgba(14,165,233,.18)); color: #ddd6fe; font-size: 42px; }
        .ve-preview-art img { width: 100%; height: 100%; object-fit: cover; }
        .ve-preview-title { margin-top: 12px; color: #fff; font-size: 18px; font-weight: 950; }
        .ve-preview-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; border-bottom: 1px solid rgba(148,163,184,.1); font-size: 11px; }
        .ve-preview-row span { color: #94a3b8; }
        .ve-preview-row strong { color: #e2e8f0; text-align: right; }
        .ve-media-selected { display: grid; grid-template-columns: 112px minmax(0,1fr); gap: 11px; padding: 10px; border: 1px solid rgba(148,163,184,.14); border-radius: 16px; background: rgba(2,6,23,.28); }
        .ve-media-selected .ve-thumb { width: 112px; height: 70px; }
        .ve-video-pick-list { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 8px; max-height: 400px; overflow: auto; padding: 8px; border: 1px solid rgba(148,163,184,.14); border-radius: 16px; background: rgba(2,6,23,.24); }
        .ve-video-pick { display: grid; grid-template-columns: 72px minmax(0,1fr) auto; gap: 8px; align-items: center; padding: 8px; border: 1px solid rgba(148,163,184,.12); border-radius: 14px; background: rgba(15,23,42,.55); }
        .ve-video-pick .ve-thumb { width: 72px; height: 42px; font-size: 14px; }
        .ve-video-pick strong { display: block; overflow: hidden; color: #fff; font-size: 11px; text-overflow: ellipsis; white-space: nowrap; }
        .ve-video-pick small { color: #94a3b8; font-size: 9px; }
        .ve-order-list { display: grid; gap: 7px; margin-top: 10px; }
        .ve-order-item { display: grid; grid-template-columns: 28px minmax(0,1fr) auto; gap: 8px; align-items: center; padding: 8px; border: 1px solid rgba(148,163,184,.12); border-radius: 13px; background: rgba(2,6,23,.3); }
        .ve-order-item b { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 9px; background: rgba(124,58,237,.18); color: #ddd6fe; font-size: 10px; }
        .ve-modal { position: fixed; inset: 0; z-index: 9999; display: grid; place-items: center; padding: 12px; }
        .ve-modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,.75); backdrop-filter: blur(9px); }
        .ve-modal-dialog { position: relative; display: flex; flex-direction: column; width: min(1120px,100%); height: min(760px,calc(100vh - 24px)); overflow: hidden; border: 1px solid rgba(148,163,184,.2); border-radius: 24px; background: linear-gradient(180deg,#0f172a,#020617); box-shadow: 0 30px 100px rgba(0,0,0,.55); }
        .ve-modal-head,.ve-modal-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 13px 15px; border-bottom: 1px solid rgba(148,163,184,.14); }
        .ve-modal-foot { border-top: 1px solid rgba(148,163,184,.14); border-bottom: 0; }
        .ve-modal-body { display: grid; grid-template-columns: 270px minmax(0,1fr); flex: 1; min-height: 0; }
        .ve-upload-card { padding: 14px; border-right: 1px solid rgba(148,163,184,.14); background: rgba(2,6,23,.36); }
        .ve-media-library { display: flex; flex-direction: column; min-height: 0; padding: 14px; }
        .ve-media-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(150px,1fr)); gap: 10px; overflow: auto; margin-top: 10px; }
        .ve-media-tile { overflow: hidden; padding: 7px; border: 1px solid rgba(148,163,184,.14); border-radius: 15px; background: rgba(30,41,59,.45); color: #fff; text-align: left; cursor: pointer; }
        .ve-media-tile img { width: 100%; height: 105px; object-fit: contain; border-radius: 11px; background: #020617; }
        .ve-media-tile strong,.ve-media-tile small { display: block; overflow: hidden; margin-top: 6px; text-overflow: ellipsis; white-space: nowrap; }
        .ve-media-tile strong { font-size: 11px; }.ve-media-tile small { color: #94a3b8; font-size: 9px; }
        @media(max-width:1180px) { .ve-stats { grid-template-columns: repeat(3,minmax(0,1fr)); }.ve-focus { grid-template-columns: 1fr; }.ve-preview { position: static; }.ve-filterbar { grid-template-columns: 1fr 1fr; } }
        @media(max-width:760px) { .ve-hero-top { flex-direction: column; }.ve-app-pill { width: 100%; text-align: left; }.ve-stats,.ve-form-grid,.ve-filterbar { grid-template-columns: 1fr; }.ve-video-card.is-list { grid-template-columns: 100px minmax(0,1fr); }.ve-video-card.is-list .ve-thumb { width: 100px; height: 64px; }.ve-video-pick-list { grid-template-columns: 1fr; }.ve-modal-body { grid-template-columns: 1fr; }.ve-upload-card { border-right: 0; border-bottom: 1px solid rgba(148,163,184,.14); }.ve-media-grid { grid-template-columns: repeat(2,minmax(0,1fr)); } }
    </style>

    <div class="ve-shell"
         x-data="videoEngineWorkspace()"
         x-init="restoreScroll()"
         @beforeunload.window="rememberScroll()"
         @video-engine-keep-position.window="rememberScroll(); $nextTick(() => restoreScroll())">
        <section class="ve-hero">
            <div class="ve-hero-top">
                <div>
                    <div class="ve-kicker">Video Engine</div>
                    <div class="ve-title">Long-form video workspace</div>
                    <div class="ve-desc">Manage videos, channels, playlists and delivery readiness.</div>
                </div>
                <div class="ve-app-pill">
                    <span>Active app</span>
                    <strong>{{ $activeAppId ? ($activeAppName ?: 'App #' . $activeAppId) : 'Not selected' }}</strong>
                </div>
            </div>
            @if($activeAppId)
                <div class="ve-actions">
                    <button type="button" class="ve-btn primary" wire:click="createVideo">+ Add Video</button>
                    <button type="button" class="ve-btn" wire:click="createChannel">+ Add Channel</button>
                    <button type="button" class="ve-btn" wire:click="createPlaylist">+ Add Playlist</button>
                </div>
            @endif
        </section>

        <nav class="ve-tabs" aria-label="Video Engine workspace">
            @foreach(['overview' => 'Overview', 'videos' => 'Videos', 'channels' => 'Channels', 'playlists' => 'Playlists', 'preview' => 'Preview'] as $tabKey => $tabLabel)
                <button type="button" class="ve-tab {{ $activeTab === $tabKey ? 'active' : '' }}" wire:click="selectTab('{{ $tabKey }}')">
                    {{ $tabLabel }}
                </button>
            @endforeach
        </nav>

        @if(!$activeAppId)
            <section class="ve-card">
                <div class="ve-card-pad">
                    <div class="ve-empty">Select an active app. Video Engine never falls back to another app.</div>
                </div>
            </section>
        @elseif($workspaceMode !== 'index')
            @php
                $editorTabs = match($workspaceMode) {
                    'video_editor' => ['content' => 'Content', 'source' => 'Source', 'thumbnail' => 'Thumbnail', 'publishing' => 'Publishing', 'preview' => 'Preview'],
                    'channel_editor' => ['details' => 'Details', 'thumbnail' => 'Thumbnail', 'publishing' => 'Publishing', 'preview' => 'Preview'],
                    default => ['details' => 'Details', 'videos' => 'Videos', 'thumbnail' => 'Thumbnail', 'publishing' => 'Publishing', 'preview' => 'Preview'],
                };
                $preview = $this->preview;
                $mediaTarget = $workspaceMode === 'video_editor' ? 'video' : ($workspaceMode === 'channel_editor' ? 'channel' : 'playlist');
            @endphp
            <section class="ve-focus">
                <div class="ve-focus-main">
                    <div class="ve-focus-head">
                        <div>
                            <div class="ve-kicker">{{ ucfirst($mediaTarget) }} workspace</div>
                            <div class="ve-focus-title">{{ str_starts_with($workspaceMode, 'video') ? ($editingVideoId ? 'Edit Video' : 'Create Video') : (str_starts_with($workspaceMode, 'channel') ? ($editingChannelId ? 'Edit Channel' : 'Create Channel') : ($editingPlaylistId ? 'Edit Playlist' : 'Create Playlist')) }}</div>
                        </div>
                        <button type="button" class="ve-btn" wire:click="closeEditor">← Back</button>
                    </div>
                    <div class="ve-focus-body">
                        <div class="ve-editor-tabs">
                            @foreach($editorTabs as $tabKey => $tabLabel)
                                <button type="button" class="ve-tab {{ $editorTab === $tabKey ? 'active' : '' }}" wire:click="selectEditorTab('{{ $tabKey }}')">
                                    {{ $tabLabel }}
                                </button>
                            @endforeach
                        </div>

                        @if($workspaceMode === 'video_editor')
                            @if($editorTab === 'content')
                                <div class="ve-form-grid">
                                    <div>
                                        <label class="ve-label">Title</label>
                                        <input class="ve-input" wire:model.live.debounce.350ms="videoForm.title">
                                    </div>
                                    <div>
                                        <label class="ve-label">Channel</label>
                                        <span class="ve-select-wrap">
                                            <select class="ve-select" wire:model.live="videoForm.video_channel_id">
                                                <option value="">Select channel</option>
                                                @foreach($this->channelOptions as $id => $label)
                                                    <option value="{{ $id }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </div>
                                    <div class="ve-form-span">
                                        <label class="ve-label">Slug</label>
                                        <input class="ve-input" wire:model.defer="videoForm.slug" placeholder="Generated from title when blank">
                                    </div>
                                    <div class="ve-form-span">
                                        <label class="ve-label">Description / Summary</label>
                                        <textarea class="ve-textarea" rows="5" wire:model.live.debounce.500ms="videoForm.description"></textarea>
                                    </div>
                                </div>
                            @elseif($editorTab === 'source')
                                <div class="ve-form-grid">
                                    <div>
                                        <label class="ve-label">Source type</label>
                                        <span class="ve-select-wrap">
                                            <select class="ve-select" wire:model.live="videoForm.source_type">
                                                @foreach(['uploaded_video' => 'Uploaded video', 'external_video' => 'External video', 'hls' => 'HLS stream', 'youtube_video' => 'YouTube video', 'web_embed' => 'Web embed'] as $sourceKey => $sourceLabel)
                                                    <option value="{{ $sourceKey }}">{{ $sourceLabel }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </div>
                                    <div>
                                        <label class="ve-label">Provider</label>
                                        <input class="ve-input" wire:model.live.debounce.350ms="videoForm.provider" placeholder="Optional provider label">
                                    </div>
                                    @if($videoForm['source_type'] === 'uploaded_video')
                                        <div class="ve-form-span">
                                            <label class="ve-label">Video from Media Library</label>
                                            <div class="ve-video-pick-list">
                                                @forelse($this->videoAssets as $asset)
                                                    <button type="button" class="ve-video-pick" wire:click="selectVideoAsset({{ $asset->id }})">
                                                        <span class="ve-thumb">Play</span>
                                                        <span>
                                                            <strong>{{ $asset->label ?: 'Video #' . $asset->id }}</strong>
                                                            <small>{{ $asset->mime ?: 'Uploaded video' }}</small>
                                                        </span>
                                                        <span class="ve-badge {{ (int) ($videoForm['media_asset_id'] ?? 0) === (int) $asset->id ? 'published' : '' }}">
                                                            {{ (int) ($videoForm['media_asset_id'] ?? 0) === (int) $asset->id ? 'Selected' : 'Use' }}
                                                        </span>
                                                    </button>
                                                @empty
                                                    <div class="ve-empty">No active-app video assets are available.</div>
                                                @endforelse
                                            </div>
                                            <div class="ve-note">
                                                <a href="{{ url('/admin/media-library') }}" target="_blank" rel="noopener" class="ve-mini-btn">Open Media Library</a>
                                            </div>
                                        </div>
                                    @elseif($videoForm['source_type'] === 'youtube_video')
                                        <div>
                                            <label class="ve-label">YouTube video URL</label>
                                            <input class="ve-input" wire:model.live.debounce.500ms="videoForm.external_url" placeholder="https://www.youtube.com/watch?v=...">
                                        </div>
                                        <div>
                                            <label class="ve-label">YouTube video ID</label>
                                            <input class="ve-input" wire:model.live.debounce.500ms="videoForm.provider_video_id" placeholder="Optional canonical video ID">
                                        </div>
                                    @else
                                        <div class="ve-form-span">
                                            <label class="ve-label">{{ $videoForm['source_type'] === 'hls' ? 'HLS URL' : ($videoForm['source_type'] === 'web_embed' ? 'Embed URL' : 'Video URL') }}</label>
                                            <input class="ve-input" wire:model.live.debounce.500ms="videoForm.external_url" placeholder="https://">
                                        </div>
                                    @endif
                                </div>
                            @elseif($editorTab === 'thumbnail')
                                @include('filament.pages.partials.video-engine-thumbnail-picker', ['target' => 'video', 'selectedUrl' => $preview['thumbnail_url']])
                            @elseif($editorTab === 'publishing')
                                @include('filament.pages.partials.video-engine-publishing', ['formKey' => 'videoForm', 'showLive' => true])
                            @else
                                @include('filament.pages.partials.video-engine-preview', ['preview' => $preview])
                            @endif
                        @elseif($workspaceMode === 'channel_editor')
                            @if($editorTab === 'details')
                                <div class="ve-form-grid">
                                    <div><label class="ve-label">Channel Name</label><input class="ve-input" wire:model.live.debounce.350ms="channelForm.title"></div>
                                    <div><label class="ve-label">Slug / Key</label><input class="ve-input" wire:model.defer="channelForm.slug" placeholder="Generated from name"></div>
                                    <div class="ve-form-span"><label class="ve-label">Description</label><textarea class="ve-textarea" rows="4" wire:model.live.debounce.500ms="channelForm.description"></textarea></div>
                                </div>
                            @elseif($editorTab === 'thumbnail')
                                @include('filament.pages.partials.video-engine-thumbnail-picker', ['target' => 'channel', 'selectedUrl' => $preview['thumbnail_url']])
                            @elseif($editorTab === 'publishing')
                                @include('filament.pages.partials.video-engine-publishing', ['formKey' => 'channelForm', 'showLive' => false])
                            @else
                                @include('filament.pages.partials.video-engine-preview', ['preview' => $preview])
                            @endif
                        @else
                            @if($editorTab === 'details')
                                <div class="ve-form-grid">
                                    <div><label class="ve-label">Playlist Name</label><input class="ve-input" wire:model.live.debounce.350ms="playlistForm.title"></div>
                                    <div>
                                        <label class="ve-label">Channel</label>
                                        <span class="ve-select-wrap">
                                            <select class="ve-select" wire:model.live="playlistForm.video_channel_id">
                                                <option value="">Select channel</option>
                                                @foreach($this->channelOptions as $id => $label)
                                                    <option value="{{ $id }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </span>
                                    </div>
                                    <div><label class="ve-label">Slug</label><input class="ve-input" wire:model.defer="playlistForm.slug" placeholder="Generated from name"></div>
                                    <div><label class="ve-label">Provider</label><input class="ve-input" wire:model.live.debounce.350ms="playlistForm.provider"></div>
                                    <div><label class="ve-label">Provider playlist ID</label><input class="ve-input" wire:model.defer="playlistForm.provider_playlist_id"></div>
                                    <div><label class="ve-label">External URL</label><input class="ve-input" wire:model.defer="playlistForm.external_url"></div>
                                    <div class="ve-form-span"><label class="ve-label">Description</label><textarea class="ve-textarea" rows="4" wire:model.live.debounce.500ms="playlistForm.description"></textarea></div>
                                </div>
                            @elseif($editorTab === 'videos')
                                <div class="ve-form-grid">
                                    <div class="ve-form-span"><label class="ve-label">Search available videos</label><input class="ve-input" wire:model.live.debounce.350ms="playlistVideoSearch" placeholder="Search title"></div>
                                    <div class="ve-form-span ve-video-pick-list">
                                        @forelse($this->availableVideos as $candidate)
                                            <label class="ve-video-pick">
                                                <span class="ve-thumb">
                                                    @if($this->publicThumbnailUrl($candidate->thumbnailMediaAsset))
                                                        <img src="{{ $this->publicThumbnailUrl($candidate->thumbnailMediaAsset) }}" alt="">
                                                    @else
                                                        Play
                                                    @endif
                                                </span>
                                                <span><strong>{{ $candidate->title }}</strong><small>{{ $candidate->channel?->title ?: 'No channel' }} / {{ $candidate->status ?: 'draft' }}</small></span>
                                                <input type="checkbox" @checked(in_array((int) $candidate->id, array_map('intval', $playlistForm['video_ids'] ?? []), true)) wire:click="togglePlaylistVideo({{ $candidate->id }})">
                                            </label>
                                        @empty
                                            <div class="ve-empty">No matching videos.</div>
                                        @endforelse
                                    </div>
                                    <div class="ve-form-span">
                                        <div class="ve-card-title">Selected order</div>
                                        <div class="ve-order-list">
                                            @forelse($this->selectedPlaylistVideos as $video)
                                                <div class="ve-order-item">
                                                    <b>{{ $loop->iteration }}</b>
                                                    <span class="ve-video-title">{{ $video->title }}</span>
                                                    <span>
                                                        <button type="button" class="ve-mini-btn" wire:click="movePlaylistVideo({{ $video->id }}, 'up')">↑</button>
                                                        <button type="button" class="ve-mini-btn" wire:click="movePlaylistVideo({{ $video->id }}, 'down')">↓</button>
                                                        <button type="button" class="ve-mini-btn" wire:click="togglePlaylistVideo({{ $video->id }})">Remove</button>
                                                    </span>
                                                </div>
                                            @empty
                                                <div class="ve-empty">No videos selected.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>
                            @elseif($editorTab === 'thumbnail')
                                @include('filament.pages.partials.video-engine-thumbnail-picker', ['target' => 'playlist', 'selectedUrl' => $preview['thumbnail_url']])
                            @elseif($editorTab === 'publishing')
                                @include('filament.pages.partials.video-engine-publishing', ['formKey' => 'playlistForm', 'showLive' => false])
                            @else
                                @include('filament.pages.partials.video-engine-preview', ['preview' => $preview])
                            @endif
                        @endif

                        <div class="ve-actions">
                            <button type="button" class="ve-btn primary" x-on:click="rememberScroll()" wire:click="{{ $workspaceMode === 'video_editor' ? 'saveVideo' : ($workspaceMode === 'channel_editor' ? 'saveChannel' : 'savePlaylist') }}">Save</button>
                            <button type="button" class="ve-btn" wire:click="closeEditor">Cancel</button>
                        </div>
                    </div>
                </div>
                <aside class="ve-focus-side ve-preview">
                    @include('filament.pages.partials.video-engine-preview', ['preview' => $preview, 'compact' => true])
                </aside>
            </section>
        @elseif($activeTab === 'overview')
            <section class="ve-card"><div class="ve-card-pad">
                <div class="ve-card-head"><div><div class="ve-card-title">Overview</div><div class="ve-note">Current long-form catalog.</div></div></div>
                <div class="ve-stats">
                    @foreach(['videos' => 'Videos', 'published' => 'Published', 'channels' => 'Channels', 'playlists' => 'Playlists', 'live' => 'Live', 'drafts' => 'Drafts'] as $metric => $label)
                        <div class="ve-stat"><span>{{ $label }}</span><strong>{{ $this->overview[$metric] }}</strong></div>
                    @endforeach
                </div>
                <div class="ve-card-title" style="margin-top:16px">Recent videos</div>
                <div class="ve-library is-grid" style="margin-top:10px">
                    @forelse($this->recentVideos as $video)
                        @include('filament.pages.partials.video-engine-card', ['record' => $video, 'kind' => 'video', 'viewMode' => 'grid'])
                    @empty
                        <div class="ve-empty">No videos yet.</div>
                    @endforelse
                </div>
            </div></section>
        @elseif($activeTab === 'preview')
            <section class="ve-card"><div class="ve-card-pad">
                <div class="ve-card-head"><div><div class="ve-card-title">Delivery Preview</div><div class="ve-note">Visual catalog readiness for the active app.</div></div></div>
                <div class="ve-stats">
                    @foreach(['videos' => 'Videos', 'published' => 'Published', 'channels' => 'Channels', 'playlists' => 'Playlists', 'live' => 'Live', 'drafts' => 'Drafts'] as $metric => $label)
                        <div class="ve-stat"><span>{{ $label }}</span><strong>{{ $this->overview[$metric] }}</strong></div>
                    @endforeach
                </div>
                <div class="ve-library is-grid" style="margin-top:14px">
                    @forelse($this->recentVideos as $video)
                        @include('filament.pages.partials.video-engine-card', ['record' => $video, 'kind' => 'video', 'viewMode' => 'grid', 'previewOnly' => true])
                    @empty
                        <div class="ve-empty">Add a video to preview delivery readiness.</div>
                    @endforelse
                </div>
            </div></section>
        @else
            @php
                $kind = $activeTab === 'videos' ? 'video' : ($activeTab === 'channels' ? 'channel' : 'playlist');
                $records = $activeTab === 'videos' ? $this->videos : ($activeTab === 'channels' ? $this->channels : $this->playlists);
            @endphp
            <section class="ve-card"><div class="ve-card-pad">
                <div class="ve-card-head">
                    <div><div class="ve-card-title">{{ ucfirst($activeTab) }}</div><div class="ve-note">{{ $activeTab === 'videos' ? 'Long-form video library.' : ($activeTab === 'channels' ? 'Organize delivery channels.' : 'Curate ordered playlists.') }}</div></div>
                    <button type="button" class="ve-btn primary" wire:click="{{ $activeTab === 'videos' ? 'createVideo' : ($activeTab === 'channels' ? 'createChannel' : 'createPlaylist') }}">+ Add {{ ucfirst($kind) }}</button>
                </div>
                <div class="ve-filterbar">
                    <div><label class="ve-label">Search</label><input class="ve-input" wire:model.live.debounce.400ms="search" placeholder="Search title"></div>
                    <div><label class="ve-label">Status</label><span class="ve-select-wrap"><select class="ve-select" wire:model.live="statusFilter"><option value="all">All status</option><option value="published">Published</option><option value="draft">Draft</option><option value="archived">Archived</option></select></span></div>
                    @if($activeTab === 'videos')
                        <div>
                            <label class="ve-label">Source</label>
                            <span class="ve-select-wrap">
                                <select class="ve-select" wire:model.live="sourceFilter">
                                    <option value="all">All sources</option>
                                    @foreach(\App\Support\Video\VideoSourceContract::sourceTypes() as $source)
                                        <option value="{{ $source }}">{{ str_replace('_', ' ', ucfirst($source)) }}</option>
                                    @endforeach
                                </select>
                            </span>
                        </div>
                    @else
                        <div></div>
                    @endif
                    <button type="button" class="ve-btn" wire:click="$set('search', ''); $set('statusFilter', 'all'); $set('sourceFilter', 'all')">Reset</button>
                </div>
                <div class="ve-toolbar" style="margin-bottom:10px">
                    <div class="ve-note">Showing {{ count($records) }} item(s)</div>
                    <div class="ve-view-toggle"><button type="button" class="{{ $libraryView === 'grid' ? 'active' : '' }}" wire:click="setLibraryView('grid')">Grid</button><button type="button" class="{{ $libraryView === 'list' ? 'active' : '' }}" wire:click="setLibraryView('list')">List</button></div>
                </div>
                @if(count($records))
                    <div class="ve-library is-{{ $libraryView }}">
                        @foreach($records as $record)
                            @include('filament.pages.partials.video-engine-card', ['record' => $record, 'kind' => $kind, 'viewMode' => $libraryView])
                        @endforeach
                    </div>
                @else
                    <div class="ve-empty">No {{ $activeTab }} match these filters.</div>
                @endif
            </div></section>
        @endif

        @if($showMediaPicker)
            <div class="ve-modal" x-cloak>
                <button type="button" class="ve-modal-backdrop" wire:click="closeMediaPicker" aria-label="Close thumbnail library"></button>
                <section class="ve-modal-dialog" role="dialog" aria-modal="true" aria-label="Thumbnail Library">
                    <header class="ve-modal-head"><div><div class="ve-card-title">Thumbnail Library</div><div class="ve-note">Active-app images only.</div></div><button type="button" class="ve-btn" wire:click="closeMediaPicker">Close</button></header>
                    <div class="ve-modal-body">
                        <aside class="ve-upload-card">
                            <div class="ve-card-title">Upload Image</div>
                            <div class="ve-note">Uses the existing Beginner Media Center.</div>
                            <input class="ve-input" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" x-ref="mediaUpload" style="margin-top:12px">
                            <button type="button" class="ve-btn primary" style="margin-top:8px" x-bind:disabled="uploading" x-on:click="uploadThumbnail('{{ $mediaPickerTarget }}')"><span x-text="uploading ? 'Uploading…' : 'Upload & Use'"></span></button>
                            <div class="ve-note" x-text="uploadMessage"></div>
                            <a href="{{ url('/admin/media-library') }}" target="_blank" rel="noopener" class="ve-btn" style="margin-top:12px">Open Full Media Library</a>
                        </aside>
                        <div class="ve-media-library">
                            <input class="ve-input" wire:model.live.debounce.350ms="mediaSearch" placeholder="Search images or buckets">
                            <div class="ve-media-grid">
                                @forelse($this->imageAssets as $asset)
                                    <button type="button" class="ve-media-tile" wire:click="selectMediaAsset({{ $asset['id'] }})">
                                        <img src="{{ $asset['url'] }}" alt="">
                                        <strong>{{ $asset['label'] }}</strong>
                                        <small>{{ $asset['bucket'] }}{{ $asset['dimensions'] ? ' · ' . $asset['dimensions'] : '' }}</small>
                                    </button>
                                @empty
                                    <div class="ve-empty">No matching images. Upload the first one.</div>
                                @endforelse
                            </div>
                        </div>
                    </div>
                    <footer class="ve-modal-foot"><button type="button" class="ve-btn" wire:click="closeMediaPicker">Cancel</button></footer>
                </section>
            </div>
        @endif
    </div>

    @script
    <script>
        Alpine.data('videoEngineWorkspace', () => ({
            uploading: false,
            uploadMessage: '',
            rememberScroll() { sessionStorage.setItem('video_engine_scroll_y', String(window.scrollY || 0)); },
            restoreScroll() { const y = Number(sessionStorage.getItem('video_engine_scroll_y') || 0); if (y > 0) setTimeout(() => window.scrollTo({ top: y, behavior: 'instant' }), 70); },
            async uploadThumbnail(target) {
                const file = this.$refs.mediaUpload?.files?.[0];
                if (!file) { this.uploadMessage = 'Choose an image first.'; return; }
                this.uploading = true;
                this.uploadMessage = 'Uploading…';
                const body = new FormData();
                body.append('image_file', file);
                body.append('bucket', 'video_engine_thumbnails');
                body.append('label', 'Video Engine Thumbnail');
                try {
                    const response = await fetch(@js(route('admin.beginner.media-center.upload')), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '', 'Accept': 'application/json' },
                        body,
                    });
                    const data = await response.json();
                    if (!response.ok || !data.ok || !data.asset?.id) throw new Error(data.message || 'Upload failed.');
                    await this.$wire.selectMediaAsset(Number(data.asset.id), target);
                    this.uploadMessage = 'Thumbnail selected.';
                } catch (error) {
                    this.uploadMessage = error.message || 'Upload failed.';
                } finally {
                    this.uploading = false;
                }
            },
        }));
    </script>
    @endscript
</x-filament-panels::page>

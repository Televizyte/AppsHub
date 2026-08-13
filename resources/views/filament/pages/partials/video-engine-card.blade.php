@php
    $mode = in_array($viewMode ?? 'grid', ['grid', 'list'], true) ? $viewMode : 'grid';
    $thumbnail = $this->publicThumbnailUrl($record->thumbnailMediaAsset ?? null);
@endphp
<article class="ve-video-card is-{{ $mode }}">
    <div class="ve-thumb">
        @if($thumbnail)
            <img src="{{ $thumbnail }}" alt="">
        @else
            ▶
        @endif
    </div>
    <div style="min-width:0">
        <div class="ve-video-title">{{ $record->title }}</div>
        <div class="ve-meta">
            <span class="ve-badge {{ $record->status === 'published' ? 'published' : 'draft' }}">{{ ucfirst($record->status) }}</span>
            @if($kind === 'video')
                <span class="ve-badge">{{ $record->channel?->title ?: 'No channel' }}</span>
                <span class="ve-badge">{{ str_replace('_', ' ', $record->source_type) }}</span>
                @if($record->duration_seconds)
                    <span class="ve-badge">{{ gmdate($record->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $record->duration_seconds) }}</span>
                @endif
                @if($record->is_live)
                    <span class="ve-badge featured">Live</span>
                @endif
            @elseif($kind === 'channel')
                <span class="ve-badge">{{ $record->videos_count }} videos</span>
                <span class="ve-badge">{{ $record->playlists_count }} playlists</span>
            @else
                <span class="ve-badge">{{ $record->channel?->title ?: 'No channel' }}</span>
                <span class="ve-badge">{{ $record->items_count }} videos</span>
                @if($record->provider)
                    <span class="ve-badge">{{ $record->provider }}</span>
                @endif
            @endif
            @if($record->is_featured)
                <span class="ve-badge featured">Featured</span>
            @endif
        </div>
        @if(empty($previewOnly))
            <div class="ve-actions" style="margin-top:9px">
                <button type="button" class="ve-mini-btn primary" wire:click="{{ $kind === 'video' ? 'editVideo' : ($kind === 'channel' ? 'editChannel' : 'editPlaylist') }}({{ $record->id }})">{{ $kind === 'playlist' ? 'Manage' : 'Edit' }}</button>
                @if($kind === 'video')
                    <button type="button" class="ve-mini-btn" wire:click="previewVideo({{ $record->id }})">Preview</button>
                @endif
            </div>
        @endif
    </div>
</article>

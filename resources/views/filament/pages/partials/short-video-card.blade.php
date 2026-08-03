@php
    $mode = in_array($viewMode ?? 'grid', ['grid', 'list'], true) ? ($viewMode ?? 'grid') : 'grid';
@endphp

<div class="sv-video-card is-{{ $mode }}">
    <div class="sv-thumb">
        @if(!empty($short['thumbnail_url']))
            <img src="{{ $short['thumbnail_url'] }}" alt="">
        @else
            ▶
        @endif
    </div>
    <div style="min-width:0;">
        <div class="sv-video-title">{{ $short['title'] ?? 'Untitled Short' }}</div>
        @if(!empty($short['subtitle']))
            <div class="sv-card-note" style="margin-top:4px;">{{ $short['subtitle'] }}</div>
        @endif
        <div class="sv-meta">
            <span class="sv-badge {{ ($short['status'] ?? '') === 'published' ? 'published' : 'draft' }}">{{ ucfirst($short['status'] ?? 'draft') }}</span>
            <span class="sv-badge">{{ $short['category_label'] ?? 'General' }}</span>
            @if(!empty($short['is_featured']))<span class="sv-badge featured">Featured</span>@endif
            @if(!empty($short['video_duration']))<span class="sv-badge">{{ $short['video_duration'] }}</span>@endif
        </div>
        @if(!empty($short['short_channels']))
            <div class="sv-meta">
                @foreach(array_slice($short['short_channels'], 0, 3) as $channelLabel)
                    <span class="sv-badge featured">{{ $channelLabel }}</span>
                @endforeach
            </div>
        @endif

        @if(!empty($short['tags']))
            <div class="sv-meta">
                @foreach(array_slice($short['tags'], 0, 3) as $tag)
                    <span class="sv-badge">#{{ $tag }}</span>
                @endforeach
            </div>
        @endif

        @if(!empty($categoryOptions ?? []))
            <div style="margin-top:9px; display:grid; gap:5px;">
                <label class="sv-label" style="margin:0;">Quick Category</label>
                <span class="sv-select-wrap">
                    <select class="sv-select" wire:change="assignShortCategory({{ (int) ($short['id'] ?? 0) }}, $event.target.value)">
                        @foreach($categoryOptions as $key => $label)
                            <option value="{{ $key }}" @selected(($short['category_key'] ?? 'general') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </span>
            </div>
        @endif

        <div class="sv-video-actions">
            <a class="sv-mini-link" href="{{ $short['edit_url'] ?? '#' }}">Edit</a>
            @if(!empty($short['video_url']))
                <a class="sv-mini-link" target="_blank" rel="noopener" href="{{ $short['video_url'] }}">Open Video</a>
            @endif
        </div>
    </div>
</div>

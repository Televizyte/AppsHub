<div class="ve-preview {{ ! empty($compact) ? 'is-compact' : '' }}">
    <div class="ve-preview-art">
        @if($preview['thumbnail_url'])
            <img src="{{ $preview['thumbnail_url'] }}" alt="">
        @else
            <span>Preview</span>
        @endif
    </div>

    <div class="ve-preview-copy">
        <span class="ve-badge">{{ ucfirst(str_replace('_', ' ', $preview['type'])) }}</span>
        <h3 class="ve-preview-title">{{ $preview['title'] ?: 'Untitled item' }}</h3>
        @foreach($preview['meta'] as $label => $value)
            <div class="ve-preview-row">
                <span>{{ is_string($label) ? $label : ucfirst((string) $label) }}</span>
                <strong>{{ is_bool($value) ? ($value ? 'Yes' : 'No') : $value }}</strong>
            </div>
        @endforeach
        <div class="ve-meta">
            <span class="ve-badge {{ $preview['ready'] ? 'published' : 'draft' }}">
                {{ $preview['ready'] ? 'Ready' : 'Not ready' }}
            </span>
        </div>
    </div>
</div>

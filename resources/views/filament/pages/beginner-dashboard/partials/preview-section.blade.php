@php
    $items = $section->items->where('is_enabled', true)->values();
    $layout = $layoutFor($section);
    $previewClass = $previewClassFor($section);
@endphp

<div class="dxm-mobile-section">
    @if ($layout === 'ad_block')
        <div class="dxm-ad-card">Dynamic Ad Block Placeholder</div>
    @else
        @if (trim((string) $section->title) !== '' || trim((string) $section->subtitle) !== '')
            <div class="dxm-mobile-section-head">
                @if (trim((string) $section->title) !== '')
                    <div class="dxm-mobile-section-title">{{ $section->title }}</div>
                @endif

                @if (trim((string) $section->subtitle) !== '')
                    <div class="dxm-mobile-section-sub">{{ $section->subtitle }}</div>
                @endif
            </div>
        @endif

        <div class="{{ $previewClass }}">
            @forelse ($items as $item)
                @include('filament.pages.beginner-dashboard.partials.preview-card', ['section' => $section, 'item' => $item])
            @empty
                <div class="dxm-empty">No cards yet</div>
            @endforelse
        </div>
    @endif
</div>

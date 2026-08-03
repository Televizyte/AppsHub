@php
    $img = $imageFor($item);
    $display = $displayTextFor($section, $item);
    $action = $actionFor($item);
    $isDaily = $isDailyCard($section, $item);
    $layout = $layoutFor($section);
    $height = $cardHeightFor($section, 170);
    $width = $cardWidthFor($section, 310);
@endphp

@if ($isDaily)
    <div
        class="dxm-mobile-daily-frame"
        data-dxm-preview-card
        data-title="{{ e($display['main']) }}"
        data-subtitle="{{ e($display['sub']) }}"
        data-description="{{ e($display['note']) }}"
        data-image="{{ e($img ?? '') }}"
        data-action-type="{{ e($action['type']) }}"
        data-engine="{{ e($action['engine'] ?? '') }}"
        data-url="{{ e($action['url'] ?? '') }}"
        data-route="{{ e($action['route'] ?? '') }}"
        data-route-key="{{ e($action['route_key'] ?? '') }}"
    >
        <button type="button" class="dxm-mobile-daily-card" style="{{ $dailyStyleFor($item) }}">
            <div class="dxm-mobile-daily-inner">
                {!! nl2br(e($display['main'])) !!}
                @if ($display['sub'])
                    <div class="dxm-mobile-daily-ref">{!! nl2br(e($display['sub'])) !!}</div>
                @endif
            </div>
        </button>

        <div class="dxm-mobile-daily-actions">
            @if ($display['kind'] === 'daily_scripture')
                <button type="button" class="dxm-mobile-daily-action" data-dxm-route-jump data-target-tab="explore" data-target-route-key="quote_creator">✦ Quote Creator</button>
                <button type="button" class="dxm-mobile-daily-action" data-dxm-route-jump data-target-tab="explore" data-target-route-key="bible">▤ Open Bible</button>
                <button type="button" class="dxm-mobile-daily-action dxm-mobile-daily-action-light">↗ Share</button>
            @else
                <button type="button" class="dxm-mobile-daily-action" data-dxm-route-jump data-target-tab="explore" data-target-route-key="quote_creator">✦ Quote Creator</button>
                <button type="button" class="dxm-mobile-daily-action" data-dxm-route-jump data-target-tab="explore" data-target-route-key="notes">▣ Add to Notes</button>
                <button type="button" class="dxm-mobile-daily-action dxm-mobile-daily-action-light">↗ Share</button>
            @endif
        </div>
    </div>
@else
    <button
        type="button"
        class="dxm-mobile-card"
        style="height:{{ $height }}px;{{ in_array($layout, ['horizontal_scroll', 'carousel'], true) ? 'min-width:' . $width . 'px;max-width:' . $width . 'px;' : '' }}"
        data-dxm-preview-card
        data-title="{{ e($display['main']) }}"
        data-subtitle="{{ e($display['sub']) }}"
        data-description="{{ e($display['note']) }}"
        data-image="{{ e($img ?? '') }}"
        data-action-type="{{ e($action['type']) }}"
        data-engine="{{ e($action['engine'] ?? '') }}"
        data-url="{{ e($action['url'] ?? '') }}"
        data-route="{{ e($action['route'] ?? '') }}"
        data-route-key="{{ e($action['route_key'] ?? '') }}"
    >
        <div class="dxm-mobile-card-img">
            @if ($img)
                <img src="{{ $img }}" alt="{{ $item->title }}">
            @endif
        </div>
        <div class="dxm-mobile-card-overlay"></div>
        <div class="dxm-mobile-card-watermark">{{ $iconFor($item) }}</div>
        <div class="dxm-mobile-card-body">
            <div class="dxm-mobile-card-top">
                <span class="dxm-mobile-badge">{{ $badgeFor($item) }}</span>
                <span class="dxm-mobile-card-icon">{{ $iconFor($item) }}</span>
            </div>
            <div class="dxm-mobile-card-spacer"></div>
            <div class="dxm-mobile-card-title">{!! nl2br(e($display['main'])) !!}</div>
            @if ($display['sub'])
                <div class="dxm-mobile-card-sub">{!! nl2br(e($display['sub'])) !!}</div>
            @endif
        </div>
    </button>
@endif

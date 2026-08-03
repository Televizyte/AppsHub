@php
    $image = (string) ($item->image_url ?? '');
    $deepLink = (string) ($item->deep_link_url ?? '');
@endphp
<div class="dxm-inapp-card">
    <div class="dxm-inapp-image">
        @if ($image)
            <img src="{{ $image }}" alt="Notification image">
        @else
            <div class="dxm-preview-label">No image attached</div>
        @endif
    </div>
    <div class="dxm-inapp-body">
        <div class="dxm-logo-line">
            <div class="dxm-logo">@if($appLogo)<img src="{{ $appLogo }}" alt="{{ $appName }} logo">@else{{ $appInitial }}@endif</div>
            <div><strong>{{ $appName }}</strong><small style="display:block;color:rgba(255,255,255,.6);">In-app message preview</small></div>
        </div>
        <strong style="font-size:18px;color:#fff;">{{ $item->title }}</strong>
        <small style="color:rgba(255,255,255,.72);line-height:1.5;">{{ $item->body }}</small>
        <div class="dxm-pill-row">
            @if($deepLink)<span class="dxm-pill info">{{ $deepLink }}</span>@endif
            <span class="dxm-pill">{{ $item->target_type ?: 'topic' }}</span>
        </div>
        <div class="dxm-btn-row"><span class="dxm-btn primary">{{ $deepLink ? 'Open Deep Link' : 'View Message' }}</span><span class="dxm-btn">Close</span></div>
    </div>
</div>

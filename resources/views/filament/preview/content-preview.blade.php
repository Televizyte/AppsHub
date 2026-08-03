<div style="
    background: rgba(255,255,255,0.03);
    border:1px solid rgba(255,255,255,0.08);
    border-radius:14px;
    padding:16px;
">

    @php
        $title = $get('title');
        $subtitle = $get('subtitle');
        $image = $get('cover_image_url');
    @endphp

    @if($image)
        <div style="margin-bottom:12px;">
            <img src="{{ $image }}"
                 style="width:100%; border-radius:10px; max-height:220px; object-fit:cover;">
        </div>
    @endif

    <div style="color:#fff; font-weight:800; font-size:18px;">
        {{ $title ?: 'Content Title Preview' }}
    </div>

    @if($subtitle)
        <div style="color:rgba(255,255,255,0.7); margin-top:6px;">
            {{ $subtitle }}
        </div>
    @endif

    <div style="
        margin-top:12px;
        font-size:12px;
        color:rgba(255,255,255,0.5);
    ">
        Live preview updates as you type.
    </div>

</div>

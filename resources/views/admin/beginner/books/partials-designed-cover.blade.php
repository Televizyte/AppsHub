@php
    $bookForCover = $bookForCover ?? $book ?? null;
    $coverSize = $coverSize ?? 'normal';
    $coverClass = trim('dxm-designed-book-cover ' . ($coverClass ?? '') . ' is-' . $coverSize);

    $meta = $bookForCover && is_array($bookForCover->meta_json ?? null) ? $bookForCover->meta_json : [];
    $designer = is_array($meta['cover_designer'] ?? null) ? $meta['cover_designer'] : [];
    $enabled = (bool) ($designer['enabled'] ?? false);

    $rawCover = $bookForCover ? ($bookForCover->cover_image_src ?: ($bookForCover->cover_image_url ?? '')) : '';
    $finalCover = $bookForCover ? ($bookForCover->final_cover_image_src ?? '') : '';
    $cover = $finalCover ?: $rawCover;

    $ratio = $meta['cover_ratio'] ?? 'portrait_3_4';
    $ratioCss = match ($ratio) {
        'portrait_4_5' => '4 / 5',
        'portrait_2_3' => '2 / 3',
        'square_1_1' => '1 / 1',
        'landscape_16_9' => '16 / 9',
        default => '3 / 4',
    };

    $bgImage = trim((string) ($designer['bg_image_url'] ?? '')) !== '' ? $designer['bg_image_url'] : $rawCover;
    $bgColor = $designer['bg_color'] ?? '#0B1F4D';
    $gradient = $designer['gradient_color'] ?? '#E2388A';
    $overlay = max(0, min(90, (int) ($designer['overlay'] ?? 42))) / 100;
    $title = $designer['title'] ?? ($bookForCover->title ?? 'Book');
    $subtitle = $designer['subtitle'] ?? ($bookForCover->subtitle ?? '');
    $author = $designer['author'] ?? ($bookForCover->author_name ?? '');
    $badge = $designer['badge'] ?? '';
    $textColor = $designer['text_color'] ?? '#FFFFFF';
    $textPosition = $designer['text_position'] ?? 'bottom';
    $titleSize = max(12, (int) ($designer['title_size'] ?? 34));
    $subtitleSize = max(8, (int) ($designer['subtitle_size'] ?? 15));
    $authorSize = max(8, (int) ($designer['author_size'] ?? 13));
    $badgeSize = max(6, (int) ($designer['badge_size'] ?? 10));
    $fontFamily = $designer['font_family'] ?? 'display';
    $textAlign = $designer['text_align'] ?? 'left';
    $textShadow = $designer['text_shadow'] ?? 'soft';
    $bgFit = $designer['bg_fit'] ?? 'cover';
    $bgPosition = $designer['bg_position'] ?? 'center';
    $layout = $designer['layout_style'] ?? 'bold';
    $textWidth = max(35, min(100, (int) ($designer['text_width'] ?? 88)));
    $panelOpacity = max(0, min(80, (int) ($designer['panel_opacity'] ?? 0))) / 100;

    $titleCqw = round(($titleSize / 300) * 100, 4);
    $subtitleCqw = round(($subtitleSize / 300) * 100, 4);
    $authorCqw = round(($authorSize / 300) * 100, 4);
    $badgeCqw = round(($badgeSize / 300) * 100, 4);
    $paddingCqw = round((14 / 300) * 100, 4);
    $radiusCqw = round((14 / 300) * 100, 4);
    $bottomCqw = round((16 / 300) * 100, 4);
    $sideCqw = round((16 / 300) * 100, 4);
    $badgePadYCqw = round((5 / 300) * 100, 4);
    $badgePadXCqw = round((8 / 300) * 100, 4);
@endphp

<div
    class="{{ $coverClass }} {{ $finalCover ? 'is-final-image' : ($enabled ? 'is-designed' : 'is-uploaded') }}"
    style="aspect-ratio:{{ $ratioCss }};--cover-bg:{{ $bgColor }};--cover-gradient:{{ $gradient }};--cover-overlay:{{ $overlay }};--cover-text:{{ $textColor }};--cover-title-cqw:{{ $titleCqw }}cqw;--cover-subtitle-cqw:{{ $subtitleCqw }}cqw;--cover-author-cqw:{{ $authorCqw }}cqw;--cover-badge-cqw:{{ $badgeCqw }}cqw;--cover-text-width:{{ $textWidth }}%;--cover-panel-opacity:{{ $panelOpacity }};--cover-pad-cqw:{{ $paddingCqw }}cqw;--cover-radius-cqw:{{ $radiusCqw }}cqw;--cover-bottom-cqw:{{ $bottomCqw }}cqw;--cover-side-cqw:{{ $sideCqw }}cqw;--cover-badge-pad-y:{{ $badgePadYCqw }}cqw;--cover-badge-pad-x:{{ $badgePadXCqw }}cqw;"
    data-position="{{ $textPosition }}"
    data-font="{{ $fontFamily }}"
    data-align="{{ $textAlign }}"
    data-shadow="{{ $textShadow }}"
    data-bg-fit="{{ $bgFit }}"
    data-bg-position="{{ $bgPosition }}"
    data-layout="{{ $layout }}"
>
    @if ($finalCover)
        <img src="{{ $finalCover }}" alt="{{ $bookForCover->title ?? 'Final book cover' }}">
    @elseif ($enabled)
        <div class="dxm-designed-cover-bg" @if($bgImage) style="background-image:url('{{ $bgImage }}')" @endif></div>
        <div class="dxm-designed-cover-overlay"></div>
        <div class="dxm-designed-cover-spine"></div>
        <div class="dxm-designed-cover-text">
            @if (trim((string) $badge) !== '')
                <span>{{ $badge }}</span>
            @endif
            <strong>{{ $title }}</strong>
            @if (trim((string) $subtitle) !== '')
                <em>{{ $subtitle }}</em>
            @endif
            @if (trim((string) $author) !== '')
                <small>{{ $author }}</small>
            @endif
        </div>
    @elseif ($cover)
        <img src="{{ $cover }}" alt="{{ $bookForCover->title ?? 'Book cover' }}">
        <div class="dxm-designed-cover-spine"></div>
    @else
        <div class="dxm-designed-cover-empty">
            <span>{{ strtoupper(substr($bookForCover->title ?? 'B', 0, 1)) }}</span>
            <small>No Cover</small>
        </div>
        <div class="dxm-designed-cover-spine"></div>
    @endif
</div>

@once
@push('styles')
<style>
.dxm-designed-book-cover{position:relative;width:100%;container-type:inline-size;border-radius:14px;overflow:hidden;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);box-shadow:0 18px 34px rgba(0,0,0,.35);border:1px solid rgba(255,255,255,.12);isolation:isolate;box-sizing:border-box}.dxm-designed-book-cover.is-mini{width:92px;flex:0 0 92px;border-radius:14px}.dxm-designed-book-cover.is-small{width:116px;max-width:116px;border-radius:14px}.dxm-designed-book-cover.is-card{width:100%}.dxm-designed-book-cover.book-editor-cover{width:96px!important;flex:0 0 96px!important;border-radius:16px!important}.dxm-designed-book-cover img{width:100%;height:100%;object-fit:cover;display:block}.dxm-designed-book-cover.is-designed{background:linear-gradient(135deg,var(--cover-bg),var(--cover-gradient))}.dxm-designed-book-cover.is-final-image{background:#111827}.dxm-designed-cover-bg{position:absolute;inset:0;background-color:var(--cover-bg);background-image:linear-gradient(135deg,var(--cover-bg),var(--cover-gradient));background-position:center;background-size:cover;background-repeat:no-repeat;z-index:0}.dxm-designed-book-cover[data-bg-fit="contain"] .dxm-designed-cover-bg{background-size:contain;background-color:var(--cover-bg)}.dxm-designed-book-cover[data-bg-fit="pattern"] .dxm-designed-cover-bg{background-size:40cqw;background-repeat:repeat}.dxm-designed-book-cover[data-bg-position="top"] .dxm-designed-cover-bg{background-position:top center}.dxm-designed-book-cover[data-bg-position="bottom"] .dxm-designed-cover-bg{background-position:bottom center}.dxm-designed-book-cover[data-bg-position="left"] .dxm-designed-cover-bg{background-position:center left}.dxm-designed-book-cover[data-bg-position="right"] .dxm-designed-cover-bg{background-position:center right}.dxm-designed-cover-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(0,0,0,calc(var(--cover-overlay) * .35)),rgba(0,0,0,var(--cover-overlay)));z-index:1}.dxm-designed-book-cover[data-layout="cinematic"] .dxm-designed-cover-overlay{background:linear-gradient(90deg,rgba(0,0,0,var(--cover-overlay)),rgba(0,0,0,calc(var(--cover-overlay) * .45)),rgba(0,0,0,.08))}.dxm-designed-cover-spine{position:absolute;inset:0 auto 0 0;width:14%;background:linear-gradient(90deg,rgba(0,0,0,.42),rgba(255,255,255,.04),transparent);mix-blend-mode:multiply;z-index:2}.dxm-designed-cover-text{position:absolute;left:var(--cover-side-cqw);right:auto;bottom:var(--cover-bottom-cqw);width:var(--cover-text-width);max-width:calc(100% - (var(--cover-side-cqw) * 2));color:var(--cover-text);z-index:3;text-align:left;text-shadow:0 .65cqw 6cqw rgba(0,0,0,.42);padding:calc(var(--cover-panel-opacity) * var(--cover-pad-cqw));border-radius:var(--cover-radius-cqw);background:rgba(0,0,0,var(--cover-panel-opacity));backdrop-filter:blur(calc(var(--cover-panel-opacity) * 3.4cqw));box-sizing:border-box;transform-origin:center}.dxm-designed-book-cover[data-align="center"] .dxm-designed-cover-text{text-align:center;left:50%;right:auto;transform:translateX(-50%)}.dxm-designed-book-cover[data-align="right"] .dxm-designed-cover-text{text-align:right;left:auto;right:var(--cover-side-cqw)}.dxm-designed-book-cover[data-position="top"] .dxm-designed-cover-text{top:var(--cover-bottom-cqw);bottom:auto}.dxm-designed-book-cover[data-position="center"] .dxm-designed-cover-text{top:50%;bottom:auto}.dxm-designed-book-cover[data-position="center"][data-align="center"] .dxm-designed-cover-text{transform:translate(-50%,-50%)}.dxm-designed-book-cover[data-position="center"]:not([data-align="center"]) .dxm-designed-cover-text{transform:translateY(-50%)}.dxm-designed-book-cover[data-position="split"] .dxm-designed-cover-text{top:var(--cover-bottom-cqw);bottom:var(--cover-bottom-cqw);display:flex;flex-direction:column;justify-content:space-between}.dxm-designed-book-cover[data-shadow="none"] .dxm-designed-cover-text{text-shadow:none}.dxm-designed-book-cover[data-shadow="strong"] .dxm-designed-cover-text{text-shadow:0 .8cqw 1.2cqw rgba(0,0,0,.72),0 4cqw 10cqw rgba(0,0,0,.72)}.dxm-designed-book-cover[data-shadow="glow"] .dxm-designed-cover-text{text-shadow:0 0 5cqw rgba(255,255,255,.42),0 1.4cqw 8cqw rgba(0,0,0,.72)}.dxm-designed-book-cover[data-font="serif"] .dxm-designed-cover-text{font-family:Georgia,'Times New Roman',serif}.dxm-designed-book-cover[data-font="sans"] .dxm-designed-cover-text{font-family:Inter,Arial,Helvetica,sans-serif}.dxm-designed-book-cover[data-font="condensed"] .dxm-designed-cover-text{font-family:'Arial Narrow','Roboto Condensed',Impact,sans-serif;letter-spacing:.01em}.dxm-designed-book-cover[data-font="elegant"] .dxm-designed-cover-text{font-family:Cambria,Georgia,serif}.dxm-designed-book-cover[data-font="display"] .dxm-designed-cover-text{font-family:Inter,Arial,Helvetica,sans-serif}.dxm-designed-book-cover[data-layout="minimal"] .dxm-designed-cover-text{background:transparent!important;backdrop-filter:none!important;padding:0!important}.dxm-designed-book-cover[data-layout="boxed"] .dxm-designed-cover-text{background:rgba(0,0,0,max(var(--cover-panel-opacity),.38));padding:var(--cover-pad-cqw)}.dxm-designed-cover-text strong{display:block;font-size:var(--cover-title-cqw);line-height:.98;font-weight:950;letter-spacing:-.045em;white-space:normal;word-break:normal;overflow-wrap:normal}.dxm-designed-cover-text em{display:block;font-style:normal;font-size:var(--cover-subtitle-cqw);line-height:1.25;margin-top:2.3cqw;opacity:.92;white-space:normal;word-break:normal;overflow-wrap:normal}.dxm-designed-cover-text small{display:block;font-size:var(--cover-author-cqw);font-weight:850;margin-top:4.3cqw;text-transform:uppercase;letter-spacing:.08em;opacity:.88;white-space:normal;word-break:normal;overflow-wrap:normal}.dxm-designed-cover-text span{display:inline-flex;border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.14);backdrop-filter:blur(10px);border-radius:999px;padding:var(--cover-badge-pad-y) var(--cover-badge-pad-x);font-size:var(--cover-badge-cqw);font-weight:950;text-transform:uppercase;letter-spacing:.08em;margin-bottom:3.3cqw;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.dxm-designed-cover-empty{height:100%;display:grid;place-items:center;text-align:center;color:#fff;padding:16px}.dxm-designed-cover-empty span{font-size:36px;font-weight:950}.dxm-designed-cover-empty small{display:block;color:rgba(255,255,255,.7);font-size:11px}@supports not (font-size:1cqw){.dxm-designed-cover-text strong{font-size:clamp(9px,14vw,44px)}.dxm-designed-cover-text em{font-size:clamp(7px,5vw,15px)}.dxm-designed-cover-text small{font-size:clamp(6px,4vw,13px)}.dxm-designed-cover-text span{font-size:clamp(5px,3vw,10px)}}@media(max-width:760px){.dxm-designed-book-cover.is-mini{width:82px;flex-basis:82px}.dxm-designed-book-cover.book-editor-cover{width:86px!important;flex-basis:86px!important}}
</style>
@endpush
@endonce

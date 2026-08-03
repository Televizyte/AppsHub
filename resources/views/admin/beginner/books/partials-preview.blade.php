@php
    $cover = old('cover_image_url', $book->cover_image_src ?: $book->cover_image_url);
    $finalCover = $book->final_cover_image_src ?? null;
    $meta = is_array($book->meta_json ?? null) ? $book->meta_json : [];
    $designer = is_array($meta['cover_designer'] ?? null) ? $meta['cover_designer'] : [];

    $ratio = old('cover_ratio', $meta['cover_ratio'] ?? 'portrait_3_4');
    $ratioCss = match ($ratio) {
        'portrait_4_5' => '4 / 5',
        'portrait_2_3' => '2 / 3',
        'square_1_1' => '1 / 1',
        'landscape_16_9' => '16 / 9',
        default => '3 / 4',
    };

    $chapterCount = method_exists($book, 'chapters') && $book->exists ? $book->chapters()->count() : 0;
    $designerEnabled = (bool) old('cover_designer_enabled', $designer['enabled'] ?? false);
    $designerBgImage = old('cover_designer_bg_image_url', $designer['bg_image_url'] ?? ($book->cover_image_src ?: $cover));
    $designerBgColor = old('cover_designer_bg_color', $designer['bg_color'] ?? '#0B1F4D');
    $designerGradient = old('cover_designer_gradient_color', $designer['gradient_color'] ?? '#E2388A');
    $designerOverlay = (int) old('cover_designer_overlay', $designer['overlay'] ?? 42);
    $designerTitle = old('cover_designer_title', $designer['title'] ?? ($book->title ?: 'Book title preview'));
    $designerSubtitle = old('cover_designer_subtitle', $designer['subtitle'] ?? ($book->subtitle ?: 'Subtitle preview'));
    $designerAuthor = old('cover_designer_author', $designer['author'] ?? ($book->author_name ?: 'Author name'));
    $designerBadge = old('cover_designer_badge', $designer['badge'] ?? 'New Book');
    $designerTextColor = old('cover_designer_text_color', $designer['text_color'] ?? '#FFFFFF');
    $designerTextPosition = old('cover_designer_text_position', $designer['text_position'] ?? 'bottom');
    $designerTitleSize = (int) old('cover_designer_title_size', $designer['title_size'] ?? 34);
    $designerSubtitleSize = (int) old('cover_designer_subtitle_size', $designer['subtitle_size'] ?? 15);
    $designerAuthorSize = (int) old('cover_designer_author_size', $designer['author_size'] ?? 13);
    $designerBadgeSize = (int) old('cover_designer_badge_size', $designer['badge_size'] ?? 10);
    $designerFontFamily = old('cover_designer_font_family', $designer['font_family'] ?? 'display');
    $designerTextAlign = old('cover_designer_text_align', $designer['text_align'] ?? 'left');
    $designerTextShadow = old('cover_designer_text_shadow', $designer['text_shadow'] ?? 'soft');
    $designerBgFit = old('cover_designer_bg_fit', $designer['bg_fit'] ?? 'cover');
    $designerBgPosition = old('cover_designer_bg_position', $designer['bg_position'] ?? 'center');
    $designerLayoutStyle = old('cover_designer_layout_style', $designer['layout_style'] ?? 'bold');
    $designerTextWidth = (int) old('cover_designer_text_width', $designer['text_width'] ?? 88);
    $designerPanelOpacity = (int) old('cover_designer_panel_opacity', $designer['panel_opacity'] ?? 0);
@endphp

<div class="book-live-preview" id="bookPreviewTabs">
    <div class="book-preview-tabs" role="tablist" aria-label="Book preview tabs">
        <button type="button" class="is-active" data-book-preview-tab="design">Live Design</button>
        <button type="button" data-book-preview-tab="frozen">Frozen/API Cover</button>
        <button type="button" data-book-preview-tab="card">Book Card</button>
    </div>

    <div class="book-preview-panel is-active" data-book-preview-panel="design">
        <div
            class="book-cover-preview {{ $designerEnabled ? 'is-designed' : '' }}"
            id="bookDesignedCoverPreview"
            style="aspect-ratio:{{ $ratioCss }}; --designer-bg: {{ $designerBgColor }}; --designer-gradient: {{ $designerGradient }}; --designer-overlay: {{ $designerOverlay / 100 }}; --designer-text: {{ $designerTextColor }}; --designer-title-size: {{ $designerTitleSize }}px; --designer-subtitle-size: {{ $designerSubtitleSize }}px; --designer-author-size: {{ $designerAuthorSize }}px; --designer-badge-size: {{ $designerBadgeSize }}px; --designer-text-width: {{ $designerTextWidth }}%; --designer-panel-opacity: {{ $designerPanelOpacity / 100 }};"
            data-position="{{ $designerTextPosition }}"
            data-font="{{ $designerFontFamily }}"
            data-align="{{ $designerTextAlign }}"
            data-shadow="{{ $designerTextShadow }}"
            data-bg-fit="{{ $designerBgFit }}"
            data-bg-position="{{ $designerBgPosition }}"
            data-layout="{{ $designerLayoutStyle }}"
        >
            <div class="designer-bg-image" data-live-cover-bg-image style="background-image:url('{{ $designerBgImage ?: $cover }}')"></div>
            @if ($cover)
                <img class="uploaded-cover-img" src="{{ $cover }}" alt="Book cover">
            @else
                <div class="book-cover-placeholder">No cover selected.</div>
            @endif
            <div class="book-cover-spine"></div>
            <div class="designer-cover-overlay"></div>
            <div class="designer-cover-text" data-live-cover-position>
                <span class="designer-cover-badge" data-live-cover-badge>{{ $designerBadge }}</span>
                <strong data-live-cover-title>{{ $designerTitle }}</strong>
                <em data-live-cover-subtitle>{{ $designerSubtitle }}</em>
                <small data-live-cover-author>{{ $designerAuthor }}</small>
            </div>
        </div>
        <p class="preview-note">This is the design you are editing. Save Book to refresh the frozen image.</p>
    </div>

    <div class="book-preview-panel" data-book-preview-panel="frozen">
        <div class="frozen-cover-card">
            <strong>Frozen Final Cover</strong>
            <span>This exact image is sent to Flutter first after saving.</span>
            @if ($finalCover)
                <img src="{{ $finalCover }}" alt="Final designed book cover">
                <code>{{ $finalCover }}</code>
            @else
                <div class="frozen-empty">No frozen cover yet. Enable cover designer and save the book.</div>
            @endif
        </div>
    </div>

    <div class="book-preview-panel" data-book-preview-panel="card">
        <div class="book-preview-card-row">
            <div class="book-preview-thumb">
                @if ($finalCover)
                    <img src="{{ $finalCover }}" alt="Final cover">
                @elseif ($cover)
                    <img src="{{ $cover }}" alt="Book cover">
                @else
                    <span>No cover</span>
                @endif
            </div>
            <div>
                <div class="dxm-preview__title" data-live="content-title">{{ old('title', $book->title ?: 'Book title preview') }}</div>
                <div class="dxm-preview__subtitle" data-live="content-subtitle">{{ old('subtitle', $book->subtitle ?: 'Subtitle preview') }}</div>
                <div class="dxm-preview__meta">
                    <span class="dxm-tag" data-live="content-status">{{ old('status', $book->status ?: 'draft') }}</span>
                    <span class="dxm-tag" data-live-book-type>{{ old('book_type', $book->book_type ?: 'manual') }}</span>
                    <span class="dxm-tag" data-live-access>{{ old('access_type', $book->access_type ?: 'free') }}</span>
                    <span class="dxm-tag">{{ $chapterCount }} Chapters</span>
                    @if ($finalCover)
                        <span class="dxm-tag">Final cover ready</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="dxm-preview__card">
            <div class="dxm-preview__title" style="font-size:16px;">Description Preview</div>
            <div class="dxm-preview__subtitle" data-live="content-body">{{ old('description', $book->description ?: 'No description yet.') }}</div>
        </div>
    </div>
</div>

@push('styles')
<style>
.book-live-preview{display:flex;flex-direction:column;gap:12px}.book-preview-tabs{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.28);border-radius:16px;padding:8px}.book-preview-tabs button{border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.04);color:rgba(255,255,255,.72);border-radius:12px;padding:10px 8px;font-weight:900;cursor:pointer}.book-preview-tabs button.is-active{border-color:rgba(34,211,238,.48);background:rgba(8,145,178,.22);color:#fff}.book-preview-panel{display:none}.book-preview-panel.is-active{display:block}.preview-note{margin:10px 0 0;color:rgba(255,255,255,.58);font-size:12px;text-align:center}.frozen-cover-card{border:1px solid rgba(34,211,238,.18);background:rgba(8,47,73,.18);border-radius:18px;padding:14px}.frozen-cover-card strong{display:block;color:#fff}.frozen-cover-card span{display:block;color:rgba(255,255,255,.62);font-size:12px;margin-top:3px}.frozen-cover-card img{display:block;width:min(252px,82%);aspect-ratio:3/4;object-fit:cover;border-radius:18px;margin:14px auto;border:1px solid rgba(255,255,255,.18)}.frozen-cover-card code{display:block;font-size:10px;color:rgba(255,255,255,.58);word-break:break-all}.frozen-empty{display:grid;place-items:center;min-height:180px;border:1px dashed rgba(255,255,255,.14);border-radius:16px;color:rgba(255,255,255,.62);text-align:center;padding:18px}.book-preview-card-row{display:grid;grid-template-columns:112px minmax(0,1fr);gap:14px;align-items:center;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.035);border-radius:18px;padding:14px;margin-bottom:12px}.book-preview-thumb{aspect-ratio:3/4;border-radius:14px;overflow:hidden;background:rgba(255,255,255,.06);display:grid;place-items:center;color:rgba(255,255,255,.55);font-size:12px}.book-preview-thumb img{width:100%;height:100%;object-fit:cover}.book-cover-preview{position:relative;width:min(252px,82%);margin:0 auto;border-radius:20px;overflow:hidden;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);box-shadow:0 24px 56px rgba(0,0,0,.46);border:1px solid rgba(255,255,255,.14);isolation:isolate}.book-cover-preview .uploaded-cover-img{width:100%;height:100%;object-fit:cover;display:block}.book-cover-preview .designer-bg-image{position:absolute;inset:0;background-position:center;background-size:cover;background-repeat:no-repeat;display:none;z-index:0}.book-cover-preview.is-designed{background:linear-gradient(135deg,var(--designer-bg),var(--designer-gradient))}.book-cover-preview.is-designed .uploaded-cover-img,.book-cover-preview.is-designed .book-cover-placeholder{display:none}.book-cover-preview.is-designed .designer-bg-image{display:block}.book-cover-preview[data-bg-fit="contain"] .designer-bg-image{background-size:contain;background-color:var(--designer-bg)}.book-cover-preview[data-bg-fit="pattern"] .designer-bg-image{background-size:120px;background-repeat:repeat}.book-cover-preview[data-bg-position="top"] .designer-bg-image{background-position:top center}.book-cover-preview[data-bg-position="bottom"] .designer-bg-image{background-position:bottom center}.book-cover-preview[data-bg-position="left"] .designer-bg-image{background-position:center left}.book-cover-preview[data-bg-position="right"] .designer-bg-image{background-position:center right}.designer-cover-overlay{position:absolute;inset:0;display:none;background:linear-gradient(180deg,rgba(0,0,0,calc(var(--designer-overlay) * .35)),rgba(0,0,0,var(--designer-overlay)));z-index:1}.book-cover-preview.is-designed .designer-cover-overlay{display:block}.book-cover-spine{position:absolute;inset:0 auto 0 0;width:14%;background:linear-gradient(90deg,rgba(0,0,0,.42),rgba(255,255,255,.04),transparent);mix-blend-mode:multiply;z-index:2}.book-cover-placeholder{height:100%;display:grid;place-items:center;text-align:center;color:rgba(255,255,255,.66);font-size:13px;font-weight:850;padding:20px;background:linear-gradient(135deg,rgba(34,211,238,.10),rgba(168,85,247,.12))}.designer-cover-text{position:absolute;inset:auto 18px 22px 22px;width:var(--designer-text-width);max-width:calc(100% - 34px);color:var(--designer-text);z-index:3;text-align:left;text-shadow:0 2px 18px rgba(0,0,0,.42);padding:calc(var(--designer-panel-opacity) * 18px);border-radius:18px;background:rgba(0,0,0,var(--designer-panel-opacity));backdrop-filter:blur(calc(var(--designer-panel-opacity) * 12px))}.book-cover-preview[data-align="center"] .designer-cover-text{text-align:center;left:50%;right:auto;transform:translateX(-50%)}.book-cover-preview[data-align="right"] .designer-cover-text{text-align:right;left:auto;right:22px}.book-cover-preview[data-position="top"] .designer-cover-text{top:22px;bottom:auto}.book-cover-preview[data-position="center"] .designer-cover-text{top:50%;bottom:auto}.book-cover-preview[data-position="center"][data-align="center"] .designer-cover-text{transform:translate(-50%,-50%)}.book-cover-preview[data-position="center"]:not([data-align="center"]) .designer-cover-text{transform:translateY(-50%)}.book-cover-preview[data-position="split"] .designer-cover-text{top:22px;bottom:22px;display:flex;flex-direction:column;justify-content:space-between}.book-cover-preview[data-shadow="none"] .designer-cover-text{text-shadow:none}.book-cover-preview[data-shadow="strong"] .designer-cover-text{text-shadow:0 3px 4px rgba(0,0,0,.72),0 12px 34px rgba(0,0,0,.72)}.book-cover-preview[data-shadow="glow"] .designer-cover-text{text-shadow:0 0 16px rgba(255,255,255,.42),0 4px 24px rgba(0,0,0,.72)}.book-cover-preview[data-font="serif"] .designer-cover-text{font-family:Georgia,'Times New Roman',serif}.book-cover-preview[data-font="sans"] .designer-cover-text{font-family:Inter,Arial,Helvetica,sans-serif}.book-cover-preview[data-font="condensed"] .designer-cover-text{font-family:'Arial Narrow','Roboto Condensed',Impact,sans-serif;letter-spacing:.01em}.book-cover-preview[data-font="elegant"] .designer-cover-text{font-family:Cambria,Georgia,serif}.book-cover-preview[data-font="display"] .designer-cover-text{font-family:Inter,Arial,Helvetica,sans-serif}.book-cover-preview[data-layout="minimal"] .designer-cover-text{background:transparent!important;backdrop-filter:none!important}.book-cover-preview[data-layout="boxed"] .designer-cover-text{background:rgba(0,0,0,max(var(--designer-panel-opacity),.38));padding:18px}.book-cover-preview[data-layout="cinematic"] .designer-cover-overlay{background:linear-gradient(90deg,rgba(0,0,0,var(--designer-overlay)),rgba(0,0,0,calc(var(--designer-overlay) * .45)),rgba(0,0,0,.08))}.designer-cover-text strong{display:block;font-size:var(--designer-title-size);line-height:.98;font-weight:950;letter-spacing:-.045em}.designer-cover-text em{display:block;font-style:normal;font-size:var(--designer-subtitle-size);line-height:1.25;margin-top:8px;opacity:.92}.designer-cover-text small{display:block;font-size:var(--designer-author-size);font-weight:850;margin-top:16px;text-transform:uppercase;letter-spacing:.08em;opacity:.88}.designer-cover-badge{display:inline-flex;border:1px solid rgba(255,255,255,.35);background:rgba(255,255,255,.14);backdrop-filter:blur(10px);border-radius:999px;padding:6px 9px;font-size:var(--designer-badge-size);font-weight:950;text-transform:uppercase;letter-spacing:.08em;margin-bottom:12px}.designer-cover-badge:empty{display:none}@media(max-width:760px){.book-cover-preview{width:min(230px,78%)}.book-preview-card-row{grid-template-columns:88px minmax(0,1fr)}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const root = document.getElementById('bookPreviewTabs');
    if (root) {
        root.querySelectorAll('[data-book-preview-tab]').forEach(function (button) {
            button.addEventListener('click', function () {
                const key = button.getAttribute('data-book-preview-tab');
                root.querySelectorAll('[data-book-preview-tab]').forEach(function (item) { item.classList.toggle('is-active', item === button); });
                root.querySelectorAll('[data-book-preview-panel]').forEach(function (panel) { panel.classList.toggle('is-active', panel.getAttribute('data-book-preview-panel') === key); });
            });
        });
    }

    const preview = document.getElementById('bookDesignedCoverPreview');
    if (!preview) return;

    const fields = {
        enabled: document.getElementById('cover_designer_enabled'),
        bgImage: document.getElementById('cover_designer_bg_image_url'),
        syncBg: document.getElementById('cover_designer_sync_bg'),
        bgColor: document.getElementById('cover_designer_bg_color'),
        gradient: document.getElementById('cover_designer_gradient_color'),
        overlay: document.getElementById('cover_designer_overlay'),
        title: document.getElementById('cover_designer_title'),
        subtitle: document.getElementById('cover_designer_subtitle'),
        author: document.getElementById('cover_designer_author'),
        badge: document.getElementById('cover_designer_badge'),
        textColor: document.getElementById('cover_designer_text_color'),
        position: document.getElementById('cover_designer_text_position'),
        titleSize: document.getElementById('cover_designer_title_size'),
        subtitleSize: document.getElementById('cover_designer_subtitle_size'),
        authorSize: document.getElementById('cover_designer_author_size'),
        badgeSize: document.getElementById('cover_designer_badge_size'),
        fontFamily: document.getElementById('cover_designer_font_family'),
        textAlign: document.getElementById('cover_designer_text_align'),
        textShadow: document.getElementById('cover_designer_text_shadow'),
        bgFit: document.getElementById('cover_designer_bg_fit'),
        bgPosition: document.getElementById('cover_designer_bg_position'),
        layoutStyle: document.getElementById('cover_designer_layout_style'),
        textWidth: document.getElementById('cover_designer_text_width'),
        panelOpacity: document.getElementById('cover_designer_panel_opacity'),
        bookTitle: document.getElementById('title'),
        bookSubtitle: document.getElementById('subtitle'),
        bookAuthor: document.getElementById('author_name'),
        coverUrl: document.getElementById('cover_image_url'),
        ratio: document.getElementById('cover_ratio')
    };

    function ratioToCss(value){
        if(value==='portrait_4_5')return'4 / 5';
        if(value==='portrait_2_3')return'2 / 3';
        if(value==='square_1_1')return'1 / 1';
        if(value==='landscape_16_9')return'16 / 9';
        return'3 / 4';
    }

    function syncDesignerBackgroundFromCover(force){
        if(!fields.bgImage||!fields.coverUrl)return;
        if(!force&&!fields.syncBg?.checked)return;
        const selectedCover=String(fields.coverUrl.value||'').trim();
        if(!selectedCover)return;
        if(fields.bgImage.value!==selectedCover)fields.bgImage.value=selectedCover;
    }

    function syncDesigner(){
        syncDesignerBackgroundFromCover(false);
        preview.classList.toggle('is-designed',!!fields.enabled?.checked);
        preview.style.setProperty('--designer-bg',fields.bgColor?.value||'#0B1F4D');
        preview.style.setProperty('--designer-gradient',fields.gradient?.value||'#E2388A');
        preview.style.setProperty('--designer-text',fields.textColor?.value||'#FFFFFF');
        preview.style.setProperty('--designer-overlay',String((parseInt(fields.overlay?.value||'42',10)||0)/100));
        preview.style.setProperty('--designer-title-size',(fields.titleSize?.value||34)+'px');
        preview.style.setProperty('--designer-subtitle-size',(fields.subtitleSize?.value||15)+'px');
        preview.style.setProperty('--designer-author-size',(fields.authorSize?.value||13)+'px');
        preview.style.setProperty('--designer-badge-size',(fields.badgeSize?.value||10)+'px');
        preview.style.setProperty('--designer-text-width',(fields.textWidth?.value||88)+'%');
        preview.style.setProperty('--designer-panel-opacity',String((parseInt(fields.panelOpacity?.value||'0',10)||0)/100));
        preview.style.aspectRatio=ratioToCss(fields.ratio?.value||'portrait_3_4');
        preview.setAttribute('data-position',fields.position?.value||'bottom');
        preview.setAttribute('data-font',fields.fontFamily?.value||'display');
        preview.setAttribute('data-align',fields.textAlign?.value||'left');
        preview.setAttribute('data-shadow',fields.textShadow?.value||'soft');
        preview.setAttribute('data-bg-fit',fields.bgFit?.value||'cover');
        preview.setAttribute('data-bg-position',fields.bgPosition?.value||'center');
        preview.setAttribute('data-layout',fields.layoutStyle?.value||'bold');
        const bg=fields.bgImage?.value||fields.coverUrl?.value||'';
        const bgTarget=preview.querySelector('[data-live-cover-bg-image]');
        if(bgTarget)bgTarget.style.backgroundImage=bg?`url('${bg}')`:'';
        const titleTarget=preview.querySelector('[data-live-cover-title]');
        const subtitleTarget=preview.querySelector('[data-live-cover-subtitle]');
        const authorTarget=preview.querySelector('[data-live-cover-author]');
        const badgeTarget=preview.querySelector('[data-live-cover-badge]');
        if(titleTarget)titleTarget.textContent=fields.title?.value||fields.bookTitle?.value||'Book title preview';
        if(subtitleTarget)subtitleTarget.textContent=fields.subtitle?.value||fields.bookSubtitle?.value||'';
        if(authorTarget)authorTarget.textContent=fields.author?.value||fields.bookAuthor?.value||'';
        if(badgeTarget)badgeTarget.textContent=fields.badge?.value||'';
        const overlayLabel=document.querySelector('[data-designer-overlay-value]');
        const widthLabel=document.querySelector('[data-designer-text-width-value]');
        const panelLabel=document.querySelector('[data-designer-panel-opacity-value]');
        if(overlayLabel)overlayLabel.textContent=fields.overlay?.value||'0';
        if(widthLabel)widthLabel.textContent=fields.textWidth?.value||'88';
        if(panelLabel)panelLabel.textContent=fields.panelOpacity?.value||'0';
    }

    Object.values(fields).forEach(function(field){
        if(!field)return;
        field.addEventListener('input',syncDesigner);
        field.addEventListener('change',syncDesigner);
    });

    const useCoverNow=document.getElementById('designerUseCoverNow');
    if(useCoverNow){
        useCoverNow.addEventListener('click',function(){
            syncDesignerBackgroundFromCover(true);
            syncDesigner();
        });
    }

    let lastCoverValue=fields.coverUrl?fields.coverUrl.value:'';
    window.setInterval(function(){
        if(!fields.coverUrl)return;
        if(fields.coverUrl.value!==lastCoverValue){
            lastCoverValue=fields.coverUrl.value;
            syncDesignerBackgroundFromCover(false);
            syncDesigner();
        }
    },450);

    syncDesigner();
});
</script>
@endpush

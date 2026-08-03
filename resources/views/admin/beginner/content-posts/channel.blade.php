@extends('layouts.beginner')

@section('title', $bucketLabel . ' Library')
@section('eyebrow', $isQuoteDesignerBucket ? 'Visual Content Library' : 'Content Channel Library')
@section('page_title', $bucketLabel)
@section('page_description', $item ? 'Manage content connected to the card: ' . $item->title : 'Manage this reusable content channel for the selected app.')
@section('back_url', $returnUrl)
@section('form_title', $bucketLabel . ' Manager')
@section('form_description', $isQuoteDesignerBucket ? 'Design, preview, duplicate, edit, or delete reusable quote cards.' : 'Preview media, edit content, create new entries, and safely remove wrong items.')
@section('preview_description', 'Channel preview and media inspection stay inside this screen.')

@section('form')
    @if (session('status'))
        <div class="dxm-alert"><strong>{{ session('status') }}</strong></div>
    @endif

    <div class="dxm-content-channel-shell" x-data="{
        previewOpen:false,
        preview:{type:'image',title:'',subtitle:'',src:''},
        openPreview(type,title,subtitle,src){this.preview={type:type||'image',title:title||'Preview',subtitle:subtitle||'',src:src||''};this.previewOpen=true},
        closePreview(){this.previewOpen=false}
    }" x-on:keydown.escape.window="closePreview()">
        <section class="dxm-channel-hero">
            <div class="dxm-channel-hero-main">
                <span>{{ $isQuoteDesignerBucket ? 'Quote Channel' : 'Content Channel' }}</span>
                <h2>{{ $bucketLabel }}</h2>
                <p>
                    @if ($item)
                        Connected to card: <strong>{{ $item->title }}</strong>
                    @elseif ($isQuoteDesignerBucket)
                        Only {{ $bucketLabel }} records are shown here. Other quote categories stay in their own managers.
                    @else
                        Reusable app-scoped posts for this channel.
                    @endif
                </p>
            </div>

            <div class="dxm-channel-hero-actions">
                <a href="{{ $returnUrl }}" class="dxm-btn">← Back</a>
                @unless($isQuoteDesignerBucket)
                    <a href="{{ route('admin.beginner.books.index') }}" class="dxm-btn">Books</a>
                @endunless
                <a href="{{ $createUrl }}" class="dxm-btn dxm-btn--primary">{{ $isQuoteDesignerBucket ? '+ Create Quote' : '+ Create Content' }}</a>
            </div>
        </section>

        @unless($isQuoteDesignerBucket)
            <section class="dxm-channel-tools">
                @if(!empty($isShortVideoBucket))
                    <a href="{{ url('/admin/short-video-engine?tab=library') }}" class="dxm-tool-card">
                        <span class="dxm-tool-icon">
                            <svg viewBox="0 0 24 24" fill="none"><rect x="4" y="3" width="16" height="18" rx="4" stroke="currentColor" stroke-width="1.8"/><path d="M10 9.5v5l4.5-2.5L10 9.5Z" fill="currentColor"/></svg>
                        </span>
                        <span><strong>Short Video Engine</strong><small>Return to the standalone shorts dashboard, categories, feed settings, and preview.</small></span>
                        <em>Open</em>
                    </a>
                @else
                    <a href="{{ route('admin.beginner.books.index') }}" class="dxm-tool-card">
                        <span class="dxm-tool-icon">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 6.4C10.5 4.9 8.1 4 5 4v14c3.1 0 5.5.9 7 2.4" stroke="currentColor" stroke-width="1.8"/><path d="M12 6.4C13.5 4.9 15.9 4 19 4v14c-3.1 0-5.5.9-7 2.4V6.4Z" stroke="currentColor" stroke-width="1.8"/></svg>
                        </span>
                        <span><strong>Books & Library Engine</strong><small>Open books, chapters, PDF books, and future compiled content.</small></span>
                        <em>Open</em>
                    </a>
                @endif
            </section>
        @endunless

        <section class="dxm-library-grid {{ $isQuoteDesignerBucket ? 'is-quotes' : '' }}">
            @if($isQuoteDesignerBucket && !empty($activeDailyCard))
                @php
                    $dailyPayload = is_array($activeDailyCard['payload'] ?? null) ? $activeDailyCard['payload'] : [];
                    $dailyText = (string) ($activeDailyCard['quote_text'] ?? '');
                    $dailySource = (string) ($activeDailyCard['quote_source'] ?? '');
                    $dailyMode = (string) ($dailyPayload['background_mode'] ?? 'gradient');
                    $dailyCover = (string) ($dailyPayload['image_url'] ?? $dailyPayload['background_image'] ?? '');
                    if ($dailyCover && !\Illuminate\Support\Str::startsWith($dailyCover, ['http://', 'https://', '/'])) {
                        $dailyCover = \Illuminate\Support\Facades\Storage::disk('public')->url($dailyCover);
                    }
                    $dailyBg = (string) ($dailyPayload['bg_color'] ?? $dailyPayload['background_color'] ?? '#160042');
                    $dailyBg2 = (string) ($dailyPayload['bg_color_2'] ?? $dailyPayload['gradient_end'] ?? '#e2388a');
                    $dailyBgStyle = $dailyMode === 'solid'
                        ? $dailyBg
                        : ($dailyMode === 'image' && $dailyCover
                            ? 'linear-gradient(135deg,rgba(2,6,23,.1),rgba(2,6,23,.4))'
                            : 'linear-gradient(135deg,' . $dailyBg . ',' . $dailyBg2 . ')');
                    $dailyTextColor = (string) ($dailyPayload['text_color'] ?? '#ffffff');
                    $dailyAccent = (string) ($dailyPayload['accent_color'] ?? $dailyPayload['highlight_color'] ?? '#38bdf8');
                    $dailySize = max(13, min(28, (int) ($dailyPayload['quote_size'] ?? $dailyPayload['title_size'] ?? 18)));
                    $dailyAlign = (string) ($dailyPayload['text_align'] ?? 'center');
                    $dailyOverlay = max(0, min(100, (int) ($dailyPayload['overlay_strength'] ?? 58))) / 100;
                @endphp

                <article class="dxm-library-card dxm-active-daily-card">
                    <a href="{{ $activeDailyCard['edit_url'] ?? '#' }}" class="dxm-library-cover" style="text-decoration:none">
                        <div class="dxm-quote-mini" style="aspect-ratio:{{ str_replace(' ', '', $dailyPayload['format_ratio'] ?? '4/5') }};background:{{ $dailyBgStyle }};color:{{ $dailyTextColor }};--quote-accent:{{ $dailyAccent }};--quote-size:{{ $dailySize }}px;--quote-align:{{ $dailyAlign }};--quote-overlay:{{ $dailyOverlay }};">
                            @if($dailyMode === 'image' && $dailyCover)
                                <img src="{{ $dailyCover }}" alt="">
                            @endif
                            <span>{{ \Illuminate\Support\Str::limit($dailyText, 90) }}</span>
                        </div>
                    </a>

                    <div class="dxm-library-body">
                        <strong>{{ $activeDailyCard['title'] ?? 'Active Daily Card' }}</strong>
                        <small>{{ $dailySource ?: 'No source yet' }}</small>
                        <div class="dxm-meta-row">
                            <span>Active Daily Card</span>
                            <span>Home</span>
                            <span>{{ strtoupper($bucket === 'daily_scriptures' ? 'Scripture' : 'Quote') }}</span>
                        </div>
                    </div>

                    <div class="dxm-card-actions">
                        <a href="{{ $activeDailyCard['edit_url'] ?? '#' }}">Edit Active Card</a>
                    </div>
                </article>
            @endif

            @forelse ($posts as $post)
                @php
                    $meta = is_array($post->meta_json) ? $post->meta_json : [];

                    $editUrl = $isQuoteDesignerBucket
                        ? route('admin.beginner.quote-designer.edit', [
                            'contentPost' => $post->id,
                            'bucket' => $bucket,
                            'item_id' => $itemId ?: null,
                            'return' => $returnTo ?? ($item ? 'item' : 'channels'),
                        ])
                        : route('admin.beginner.content-posts.edit', [
                            'contentPost' => $post->id,
                            'bucket' => $bucket,
                            'item_id' => $itemId ?: null,
                            'return' => $returnTo ?? ($item ? 'item' : 'channels'),
                        ]);

                    $deleteUrl = $isQuoteDesignerBucket
                        ? route('admin.beginner.quote-designer.update', ['contentPost' => $post->id])
                        : route('admin.beginner.content-posts.update', ['contentPost' => $post->id]);

                    $cover = $post->cover_image_url;
                    if ($cover && !\Illuminate\Support\Str::startsWith($cover, ['http://', 'https://', '/'])) {
                        $cover = \Illuminate\Support\Facades\Storage::disk('public')->url($cover);
                    }

                    $videoUrl = $meta['video_url'] ?? $meta['url'] ?? '';
                    $isVideo = in_array($post->bucket, ['short_videos', 'shorts', 'short', 'reels', 'reel'], true);
                    $previewType = $isVideo && $videoUrl ? 'video' : 'image';
                    $previewSrc = $previewType === 'video' ? $videoUrl : ($cover ?: '');
                    $quoteText = $meta['quote_text'] ?? strip_tags((string) $post->body_html);
                    $quoteSource = $meta['quote_source'] ?? $post->subtitle;
                    $formatRatio = str_replace(' ', '', $meta['format_ratio'] ?? '4/5');
                    $quoteMode = (string) ($meta['background_mode'] ?? 'gradient');
                    $quoteBg = (string) ($meta['bg_color'] ?? $meta['background_color'] ?? '#160042');
                    $quoteBg2 = (string) ($meta['bg_color_2'] ?? $meta['gradient_end'] ?? '#e2388a');
                    $quoteBgStyle = $quoteMode === 'solid'
                        ? $quoteBg
                        : ($quoteMode === 'image' && $cover
                            ? 'linear-gradient(135deg,rgba(2,6,23,.1),rgba(2,6,23,.4))'
                            : 'linear-gradient(135deg,' . $quoteBg . ',' . $quoteBg2 . ')');
                    $quoteTextColor = (string) ($meta['text_color'] ?? '#ffffff');
                    $quoteAccent = (string) ($meta['accent_color'] ?? $meta['highlight_color'] ?? '#38bdf8');
                    $quoteSize = max(13, min(28, (int) ($meta['quote_size'] ?? $meta['title_size'] ?? 18)));
                    $quoteAlign = (string) ($meta['text_align'] ?? 'center');
                    $quoteOverlay = max(0, min(100, (int) ($meta['overlay_strength'] ?? 58))) / 100;
                @endphp

                <article class="dxm-library-card">
                    <button type="button" class="dxm-library-cover" x-on:click="openPreview(@js($previewType), @js($post->title), @js($post->subtitle), @js($previewSrc))">
                        @if($isQuoteDesignerBucket)
                            <div class="dxm-quote-mini" style="aspect-ratio:{{ $formatRatio }};background:{{ $quoteBgStyle }};color:{{ $quoteTextColor }};--quote-accent:{{ $quoteAccent }};--quote-size:{{ $quoteSize }}px;--quote-align:{{ $quoteAlign }};--quote-overlay:{{ $quoteOverlay }};">
                                @if($cover && $quoteMode === 'image')
                                    <img src="{{ $cover }}" alt="">
                                @endif
                                <span>{{ \Illuminate\Support\Str::limit($quoteText ?: $post->title, 90) }}</span>
                            </div>
                        @elseif ($cover)
                            <img src="{{ $cover }}" alt="">
                        @else
                            <span class="dxm-placeholder-svg">
                                <svg viewBox="0 0 24 24" fill="none"><rect x="4" y="5" width="16" height="14" rx="3" stroke="currentColor" stroke-width="1.8"/><path d="m6 16 4-4 3 3 2-2 3 3" stroke="currentColor" stroke-width="1.8"/></svg>
                            </span>
                        @endif

                        @if($isVideo)
                            <em class="dxm-play-pill">▶</em>
                        @endif
                    </button>

                    <div class="dxm-library-body">
                        <strong>{{ $post->title }}</strong>
                        <small>{{ $isQuoteDesignerBucket ? ($quoteSource ?: 'No source yet') : ($post->subtitle ?: 'No subtitle yet') }}</small>
                        <div class="dxm-meta-row">
                            <span>{{ ucfirst($post->status) }}</span>
                            @if ($post->is_featured)<span>Featured</span>@endif
                            <span>Order: {{ $post->sort_order }}</span>
                            <span>{{ optional($post->published_at ?? $post->created_at)->format('M d, Y') }}</span>
                        </div>
                    </div>

                    <div class="dxm-card-actions">
                        @if($previewSrc)
                            <button type="button" x-on:click="openPreview(@js($previewType), @js($post->title), @js($post->subtitle), @js($previewSrc))">Preview</button>
                        @endif
                        <a href="{{ $editUrl }}">{{ $isQuoteDesignerBucket ? 'Design' : 'Edit' }}</a>
                        <form method="POST" action="{{ $deleteUrl }}" class="post-delete-form">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="{{ $isQuoteDesignerBucket ? '_designer_action' : '_beginner_action' }}" value="delete">
                            <input type="hidden" name="delete_confirmation" value="">
                            <input type="hidden" name="bucket" value="{{ $bucket }}">
                            <input type="hidden" name="item_id" value="{{ $itemId ?: '' }}">
                            <input type="hidden" name="return" value="{{ $returnTo ?? ($item ? 'item' : 'channels') }}">
                            <button type="button" class="dxm-delete-btn" data-delete-title="{{ $post->title }}">Delete</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="dxm-empty-state">
                    <strong>{{ $isQuoteDesignerBucket ? 'No quote designs yet' : 'No content yet' }}</strong>
                    <p>{{ $isQuoteDesignerBucket ? 'Create the first visual quote card for this app.' : 'Create the first ' . strtolower($bucketLabel) . ' post for this channel.' }}</p>
                    <a href="{{ $createUrl }}" class="dxm-btn dxm-btn--primary">{{ $isQuoteDesignerBucket ? 'Create First Quote' : 'Create First Content' }}</a>
                </div>
            @endforelse
        </section>

        <div class="pagination-wrap">{{ $posts->links() }}</div>

        <div class="dxm-preview-modal" x-show="previewOpen" x-cloak>
            <button type="button" class="dxm-preview-backdrop" x-on:click="closePreview()"></button>
            <div class="dxm-preview-box">
                <div class="dxm-preview-head"><div><strong x-text="preview.title"></strong><small x-text="preview.subtitle"></small></div><button type="button" x-on:click="closePreview()">×</button></div>
                <div class="dxm-preview-body">
                    <template x-if="preview.src && preview.type === 'video'"><video x-bind:src="preview.src" controls playsinline style="width:100%;max-height:70vh;border-radius:18px;background:#000;"></video></template>
                    <template x-if="preview.src && preview.type !== 'video'"><img x-bind:src="preview.src" alt="" style="max-width:100%;max-height:70vh;border-radius:18px;object-fit:contain;"></template>
                    <template x-if="!preview.src"><div class="dxm-preview-empty">No media preview available.</div></template>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('preview')
    <div class="preview-panel">
        <h3>{{ $bucketLabel }}</h3>
        <p>This manager is scoped to the active app and keeps content separated by reusable channel buckets. Use generic titles so the structure can be cloned into other apps.</p>
        @if ($item)
            <div class="linked-card"><span>Linked Card</span><strong>{{ $item->title }}</strong><small>{{ $item->subtitle }}</small></div>
        @endif
    </div>
@endsection

@push('styles')
<style>
[x-cloak]{display:none!important}.dxm-alert{border:1px solid rgba(34,197,94,.35);background:rgba(34,197,94,.12);color:#bbf7d0;padding:12px 14px;border-radius:14px;margin-bottom:14px;font-weight:750;font-size:13px}.dxm-content-channel-shell{display:grid;gap:14px}.dxm-channel-hero{display:flex;justify-content:space-between;gap:16px;align-items:center;border:1px solid rgba(34,211,238,.20);border-radius:24px;background:linear-gradient(135deg,rgba(8,47,73,.40),rgba(15,23,42,.66));padding:18px}.dxm-channel-hero-main span{display:inline-flex;color:#67e8f9;font-size:11px;font-weight:950;letter-spacing:.07em;text-transform:uppercase}.dxm-channel-hero h2{margin:5px 0 0;color:#fff;font-size:28px;font-weight:950;letter-spacing:-.04em}.dxm-channel-hero p{margin:7px 0 0;color:rgba(255,255,255,.64);line-height:1.45}.dxm-channel-hero-actions,.dxm-card-actions{display:flex;gap:9px;flex-wrap:wrap}.dxm-btn,.dxm-card-actions>a,.dxm-card-actions>button,.dxm-delete-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;border-radius:13px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.055);color:#fff;text-decoration:none;padding:9px 12px;font-size:12px;font-weight:950;cursor:pointer}.dxm-btn--primary{border-color:rgba(34,211,238,.45);background:linear-gradient(135deg,rgba(34,211,238,.30),rgba(59,130,246,.22))}.dxm-channel-tools{display:grid}.dxm-tool-card{display:grid;grid-template-columns:54px minmax(0,1fr) auto;align-items:center;gap:13px;border:1px solid rgba(34,211,238,.22);background:rgba(34,211,238,.07);border-radius:20px;padding:14px;color:#fff;text-decoration:none}.dxm-tool-icon{width:54px;height:54px;border-radius:18px;background:rgba(2,6,23,.44);display:grid;place-items:center;color:#e0faff}.dxm-tool-icon svg{width:27px;height:27px}.dxm-tool-card strong{display:block}.dxm-tool-card small{display:block;color:rgba(255,255,255,.62);margin-top:4px}.dxm-tool-card em{font-style:normal;color:#93c5fd;font-weight:950}.dxm-library-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:13px}.dxm-active-daily-card{border-color:rgba(34,211,238,.38)!important;box-shadow:0 0 0 1px rgba(34,211,238,.10) inset}.dxm-library-card{border:1px solid rgba(255,255,255,.08);border-radius:22px;background:rgba(2,6,23,.32);overflow:hidden;transition:.18s}.dxm-library-card:hover{transform:translateY(-1px);border-color:rgba(34,211,238,.32)}.dxm-library-cover{position:relative;width:100%;height:152px;border:0;padding:0;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);display:grid;place-items:center;cursor:pointer;overflow:hidden;color:#fff}.dxm-library-cover>img{width:100%;height:100%;object-fit:cover}.dxm-placeholder-svg svg{width:46px;height:46px}.dxm-quote-mini{position:relative;width:100%;height:100%;display:grid;place-items:end;overflow:hidden;color:#fff}.dxm-quote-mini img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.dxm-quote-mini::after{content:'';position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.08),rgba(0,0,0,var(--quote-overlay,.70)))}.dxm-quote-mini:before{content:'“';position:absolute;top:12px;left:15px;z-index:2;color:var(--quote-accent);font-size:38px;font-weight:950;line-height:.8}.dxm-quote-mini span{position:relative;z-index:2;padding:42px 16px 16px;font-size:var(--quote-size);font-weight:850;line-height:1.22;text-align:var(--quote-align);width:100%;white-space:pre-line}.dxm-play-pill{position:absolute;right:12px;bottom:12px;width:42px;height:42px;border-radius:999px;display:grid;place-items:center;background:rgba(0,0,0,.58);color:#fff;font-style:normal}.dxm-library-body{padding:13px}.dxm-library-body strong{display:block;color:#fff;font-size:15px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-library-body small{display:block;color:rgba(255,255,255,.58);font-size:12px;margin-top:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-meta-row{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.dxm-meta-row span{display:inline-flex;min-height:24px;align-items:center;border-radius:999px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.08);padding:4px 7px;color:rgba(255,255,255,.70);font-size:10.5px;font-weight:900;text-transform:uppercase}.dxm-card-actions{padding:0 13px 13px}.dxm-delete-btn{border-color:rgba(248,113,113,.45);background:rgba(127,29,29,.22);color:#fecaca}.dxm-empty-state{grid-column:1/-1;border:1px dashed rgba(255,255,255,.18);border-radius:22px;padding:26px;text-align:center;color:#fff;background:rgba(2,6,23,.24)}.dxm-empty-state p{color:rgba(255,255,255,.62)}.pagination-wrap{margin-top:4px}.preview-panel,.linked-card{border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.25);border-radius:18px;padding:16px;color:#fff}.preview-panel p{color:rgba(255,255,255,.65);line-height:1.55}.linked-card{margin-top:14px}.linked-card span{display:block;color:rgba(255,255,255,.52);font-size:11px;font-weight:900;text-transform:uppercase}.linked-card small{display:block;color:rgba(255,255,255,.60);margin-top:5px}.dxm-preview-modal{position:fixed;inset:0;z-index:9999;display:grid;place-items:center;padding:24px}.dxm-preview-backdrop{position:absolute;inset:0;border:0;background:rgba(0,0,0,.72);backdrop-filter:blur(8px)}.dxm-preview-box{position:relative;width:min(920px,96vw);border:1px solid rgba(34,211,238,.24);border-radius:26px;background:#070b18;box-shadow:0 30px 90px rgba(0,0,0,.55);overflow:hidden}.dxm-preview-head{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:15px 17px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-preview-head strong{display:block;color:#fff}.dxm-preview-head small{display:block;color:rgba(255,255,255,.58);font-size:12px;margin-top:3px}.dxm-preview-head button{width:38px;height:38px;border-radius:999px;border:1px solid rgba(255,255,255,.13);background:rgba(255,255,255,.08);color:#fff;font-size:24px;cursor:pointer}.dxm-preview-body{display:grid;place-items:center;min-height:260px;padding:18px}.dxm-preview-empty{color:rgba(255,255,255,.65);border:1px dashed rgba(255,255,255,.16);border-radius:18px;padding:24px;text-align:center}@media(max-width:760px){.dxm-channel-hero{display:block}.dxm-channel-hero-actions{margin-top:14px}.dxm-channel-hero-actions .dxm-btn{width:100%}.dxm-tool-card{grid-template-columns:46px minmax(0,1fr)}.dxm-tool-card>em{grid-column:1/-1}.dxm-library-grid{grid-template-columns:1fr}.dxm-card-actions>*{flex:1}.dxm-delete-btn{width:100%}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.dxm-delete-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            const title = button.getAttribute('data-delete-title') || 'this item';
            const form = button.closest('form');
            if (!form) return;
            if (!window.confirm('Delete "' + title + '"? This cannot be undone.')) return;
            const typed = window.prompt('Type DELETE to confirm.');
            if (typed !== 'DELETE') { alert('Delete cancelled. You must type DELETE exactly.'); return; }
            const input = form.querySelector('input[name="delete_confirmation"]');
            if (input) input.value = 'DELETE';
            form.submit();
        });
    });
});
</script>
@endpush

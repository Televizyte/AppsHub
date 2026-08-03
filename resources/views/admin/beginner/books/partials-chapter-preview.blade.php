@php
    $body = old('body_html', $chapter->body_html);
    $plainBody = trim(strip_tags((string) $body));
    $wordCount = $plainBody === '' ? 0 : str_word_count($plainBody);
    $estimatedPages = max(1, (int) ceil($wordCount / 420));
    $estimatedMinutes = max(1, (int) ceil($wordCount / 180));
@endphp

<div class="chapter-reader-preview">
    <div class="dxm-preview__card reader-hero-card">
        <div class="reader-label">Reader Preview</div>
        <div class="dxm-preview__title" data-live="content-title">{{ old('title', $chapter->title ?: 'Chapter title preview') }}</div>
        <div class="dxm-preview__subtitle" data-live="content-subtitle">{{ old('subtitle', $chapter->subtitle ?: 'Chapter subtitle preview') }}</div>
        <div class="dxm-preview__meta">
            <span class="dxm-tag" data-live="content-status">{{ old('status', $chapter->status ?: 'draft') }}</span>
            <span class="dxm-tag">Read Aloud Ready</span>
            <span class="dxm-tag">Chapter Mode</span>
        </div>
    </div>

    <div class="dxm-preview__card page-stats-card">
        <div class="page-stat"><strong data-preview-word-count>{{ $wordCount }}</strong><span>Words</span></div>
        <div class="page-stat"><strong data-preview-page-count>{{ $estimatedPages }}</strong><span>Est. Pages</span></div>
        <div class="page-stat"><strong data-preview-read-minutes>{{ $estimatedMinutes }}</strong><span>Min Read</span></div>
    </div>

    <div class="book-page-shell">
        <div class="book-page-topline">
            <span>{{ $book->title }}</span>
            <span>Page 1 / <b data-preview-page-count-inline>{{ $estimatedPages }}</b></span>
        </div>

        <div class="book-page-paper">
            <div class="book-page-title" data-live="content-title">{{ old('title', $chapter->title ?: 'Chapter title preview') }}</div>
            <div class="book-page-subtitle" data-live="content-subtitle">{{ old('subtitle', $chapter->subtitle ?: '') }}</div>

            <div class="reader-page-body dxm-preview-body" data-live="content-body">
                @if ($body)
                    {!! $body !!}
                @else
                    <div class="dxm-preview__empty">Start writing the chapter to preview the book page.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="dxm-preview__card">
        <div class="dxm-preview__title" style="font-size:16px;">Study Sections</div>
        <div class="study-preview-list">
            <p><strong>Summary:</strong> <span data-study-live="summary">{{ old('summary', $chapter->summary ?: 'No summary yet.') }}</span></p>
            <p><strong>Key Thought:</strong> <span data-study-live="key_thought">{{ old('key_thought', $chapter->key_thought ?: 'No key thought yet.') }}</span></p>
            <p><strong>Memory Verse:</strong> <span data-study-live="memory_verse">{{ old('memory_verse', $chapter->memory_verse ?: 'No memory verse yet.') }}</span></p>
        </div>
    </div>
</div>

@push('styles')
<style>
.reader-label{display:inline-flex;border:1px solid rgba(34,211,238,.32);background:rgba(34,211,238,.08);color:#cffafe;border-radius:999px;padding:6px 9px;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}.reader-hero-card{background:linear-gradient(135deg,rgba(34,211,238,.08),rgba(168,85,247,.08))}.page-stats-card{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}.page-stat{border:1px solid rgba(255,255,255,.08);border-radius:14px;background:rgba(255,255,255,.035);padding:10px;text-align:center}.page-stat strong{display:block;color:#fff;font-size:18px}.page-stat span{display:block;color:rgba(255,255,255,.58);font-size:10.5px;font-weight:900;text-transform:uppercase;margin-top:4px}.book-page-shell{border:1px solid rgba(255,255,255,.10);border-radius:20px;background:rgba(2,6,23,.34);padding:12px}.book-page-topline{display:flex;justify-content:space-between;gap:10px;color:rgba(255,255,255,.56);font-size:11px;font-weight:850;text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px}.book-page-paper{min-height:430px;border-radius:18px;background:linear-gradient(180deg,rgba(255,255,255,.92),rgba(246,241,231,.88));color:#111827;padding:28px 26px;box-shadow:0 18px 60px rgba(0,0,0,.24);font-family:Georgia,'Times New Roman',serif}.book-page-title{font-size:26px;line-height:1.2;color:#111827;font-weight:900;margin-bottom:8px}.book-page-subtitle{font-size:14px;color:#4b5563;margin-bottom:18px}.reader-page-body{font-size:15.5px;line-height:1.82;color:#1f2937}.reader-page-body h2{font-size:21px;color:#111827;margin:18px 0 9px}.reader-page-body h3{font-size:18px;color:#111827;margin:16px 0 8px}.reader-page-body p{margin:0 0 13px}.reader-page-body blockquote{border-left:4px solid #0ea5e9;background:rgba(14,165,233,.10);color:#1f2937;border-radius:12px;padding:12px 14px;margin:14px 0}.reader-page-body figure{margin:18px 0;padding:10px;border:1px solid rgba(17,24,39,.12);border-radius:16px;background:rgba(255,255,255,.66)}.reader-page-body figure img{width:100%;max-height:320px;object-fit:contain;border-radius:12px;background:#f3f4f6}.reader-page-body figcaption{margin-top:8px;text-align:center;color:#6b7280;font-size:12px;font-family:Arial,Helvetica,sans-serif}.reader-page-body .book-callout{border-left:4px solid #0ea5e9;background:rgba(14,165,233,.10);border-radius:14px;padding:13px 14px;margin:16px 0}.study-preview-list p{color:rgba(255,255,255,.70);line-height:1.55}.study-preview-list strong{color:#fff}@media(max-width:760px){.page-stats-card{grid-template-columns:1fr}.book-page-paper{padding:20px 16px}.book-page-title{font-size:22px}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const editor = document.querySelector('[data-dxm-rich-editor]');
    const wordTargets = document.querySelectorAll('[data-preview-word-count]');
    const pageTargets = document.querySelectorAll('[data-preview-page-count], [data-preview-page-count-inline]');
    const minuteTargets = document.querySelectorAll('[data-preview-read-minutes]');
    const fields = ['summary', 'key_thought', 'memory_verse'];

    function countWords(text) {
        text = String(text || '').replace(/\s+/g, ' ').trim();
        return text ? text.split(' ').filter(Boolean).length : 0;
    }

    function syncStats() {
        if (!editor) return;
        const words = countWords(editor.innerText || '');
        const pages = Math.max(1, Math.ceil(words / 420));
        const minutes = Math.max(1, Math.ceil(words / 180));
        wordTargets.forEach(function (target) { target.textContent = words; });
        pageTargets.forEach(function (target) { target.textContent = pages; });
        minuteTargets.forEach(function (target) { target.textContent = minutes; });
    }

    fields.forEach(function (name) {
        const field = document.getElementById(name);
        const target = document.querySelector('[data-study-live="' + name + '"]');
        if (!field || !target) return;
        const sync = function () { target.textContent = field.value.trim() || (name === 'memory_verse' ? 'No memory verse yet.' : name === 'key_thought' ? 'No key thought yet.' : 'No summary yet.'); };
        field.addEventListener('input', sync);
        sync();
    });

    if (editor) {
        editor.addEventListener('input', syncStats);
        editor.addEventListener('keyup', syncStats);
        editor.addEventListener('paste', function () { setTimeout(syncStats, 20); });
        setTimeout(syncStats, 100);
    }
});
</script>
@endpush

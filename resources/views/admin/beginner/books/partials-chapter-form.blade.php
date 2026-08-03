@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $action = $isEdit ? route('admin.beginner.books.chapters.update', $chapter) : route('admin.beginner.books.chapters.store', $book);
    $allChapters = $chapters ?? collect();
    $assetsForInsert = $mediaAssets ?? collect();
    $bookMeta = is_array($book->meta_json ?? null) ? $book->meta_json : [];
    $pageSize = $bookMeta['book_page_size'] ?? 'standard_6x9';
@endphp

@if ($errors->any())
    <div class="dxm-alert dxm-alert--danger">
        <strong>Please fix these issues:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if (session('status'))
    <div class="dxm-alert"><strong>{{ session('status') }}</strong></div>
@endif

<form method="POST" action="{{ $action }}" id="chapterForm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="dxm-tab-panel is-active" data-dxm-tab-panel="content">
        <div class="book-editor-header-card">
            <div class="book-editor-cover">
                @if ($book->cover_image_src)
                    <img src="{{ $book->cover_image_src }}" alt="{{ $book->title }}">
                @else
                    <span>No Cover</span>
                @endif
            </div>

            <div class="book-editor-head-copy">
                <span>Current Book</span>
                <strong>{{ $book->title }}</strong>
                <small>{{ $book->subtitle ?: $book->author_name ?: 'No subtitle yet' }}</small>

                <div class="book-editor-head-tags">
                    <b>{{ $allChapters->count() }} chapter(s)</b>
                    <b>{{ ucfirst($book->book_type ?? 'manual') }}</b>
                    <b>{{ ucfirst($book->status ?? 'draft') }}</b>
                    <b>{{ str_replace('_', ' ', $pageSize) }}</b>
                </div>
            </div>
        </div>

        <div class="dxm-section-card compact-book-identity">
            <h3>Chapter Setup</h3>
            <p>Set the chapter identity here. The main book writing space stays clean below.</p>

            <div class="dxm-grid">
                <div class="dxm-col-6">
                    <label for="title">Chapter Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $chapter->title) }}" required>
                </div>

                <div class="dxm-col-6">
                    <label for="subtitle">Chapter Subtitle</label>
                    <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $chapter->subtitle) }}">
                </div>
            </div>
        </div>

        <div class="dxm-section-card book-writing-studio">
            <div class="book-studio-title-row">
                <div>
                    <h3>Book Writing Studio</h3>
                    <p>Use the compact scrollable rails, then write inside the full-width page editor. The reader preview updates on the right.</p>
                </div>

                <button type="button" class="dxm-btn book-focus-button" data-book-focus title="Focus writing mode">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 9V5h4M15 5h4v4M19 15v4h-4M9 19H5v-4"/><path d="M9 5 5 9M15 5l4 4M19 15l-4 4M5 15l4 4"/></svg>
                    <span>Focus Writer</span>
                </button>
            </div>

            <div class="book-rail-wrap">
                <div class="book-rail-head">
                    <strong>Writing Helpers</strong>
                    <span>Swipe or scroll horizontally</span>
                </div>

                <div class="book-tool-rail book-scroll-rail" aria-label="Writing helpers">
                    <button type="button" data-book-insert="intro" title="Insert Introduction">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M4 12h10M4 19h16"/></svg>
                        <span>Intro</span>
                    </button>
                    <button type="button" data-book-insert="scripture" title="Insert Scripture Block">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 4h10a4 4 0 0 1 4 4v12H8a3 3 0 0 1-3-3V4Z"/><path d="M9 8h6M9 12h6M9 16h4"/></svg>
                        <span>Scripture</span>
                    </button>
                    <button type="button" data-book-insert="teaching" title="Insert Teaching Block">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4 3 9l9 5 9-5-9-5Z"/><path d="M7 12v5c3 2 7 2 10 0v-5"/></svg>
                        <span>Teaching</span>
                    </button>
                    <button type="button" data-book-insert="key_thought" title="Insert Key Thought">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18h6M10 22h4"/><path d="M8 14a6 6 0 1 1 8 0c-.8.7-1 1.4-1 2H9c0-.6-.2-1.3-1-2Z"/></svg>
                        <span>Thought</span>
                    </button>
                    <button type="button" data-book-insert="reflection" title="Insert Reflection Questions">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 18h.01"/><path d="M9.5 9a2.5 2.5 0 1 1 4.3 1.7c-.9.8-1.8 1.3-1.8 2.8"/><path d="M4 4h16v16H4z"/></svg>
                        <span>Reflect</span>
                    </button>
                    <button type="button" data-book-insert="prayer" title="Insert Prayer Point">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3v18M6 9h12"/><path d="M7 21h10"/></svg>
                        <span>Prayer</span>
                    </button>
                    <button type="button" data-book-insert="action" title="Insert Action Step">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4 4L19 6"/><path d="M4 20h16"/></svg>
                        <span>Action</span>
                    </button>
                    <button type="button" data-book-insert="summary" title="Insert Summary">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10M4 20h8"/></svg>
                        <span>Summary</span>
                    </button>
                    <button type="button" data-book-insert="divider" title="Insert Divider">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16"/></svg>
                        <span>Divider</span>
                    </button>
                </div>
            </div>

            <div class="book-rail-wrap">
                <div class="book-rail-head">
                    <strong>Text Tools</strong>
                    <span>SVG icons with tooltips</span>
                </div>

                <div class="book-format-rail book-scroll-rail" aria-label="Text formatting tools">
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="formatBlock" data-dxm-value="P" title="Paragraph" aria-label="Paragraph">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 5h14M5 12h10M5 19h8"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="formatBlock" data-dxm-value="H2" title="Heading 2" aria-label="Heading 2">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5v14M14 5v14M4 12h10"/><path d="M17 11a2 2 0 1 1 4 0c0 2-4 3-4 5h4"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="formatBlock" data-dxm-value="H3" title="Heading 3" aria-label="Heading 3">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5v14M14 5v14M4 12h10"/><path d="M17 9h4l-3 3a2 2 0 1 1-1 4"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="bold" title="Bold" aria-label="Bold">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4h6a4 4 0 0 1 0 8H7z"/><path d="M7 12h7a4 4 0 0 1 0 8H7z"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="italic" title="Italic" aria-label="Italic">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 4h8M6 20h8M14 4l-4 16"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="underline" title="Underline" aria-label="Underline">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v7a5 5 0 0 0 10 0V4"/><path d="M5 21h14"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="insertUnorderedList" title="Bullet List" aria-label="Bullet List">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 6h11M9 12h11M9 18h11"/><path d="M4 6h.01M4 12h.01M4 18h.01"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="insertOrderedList" title="Numbered List" aria-label="Numbered List">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6h10M10 12h10M10 18h10"/><path d="M4 6h2M4 11h2v2H4M4 17h2v2H4"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="formatBlock" data-dxm-value="BLOCKQUOTE" title="Quote" aria-label="Quote">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 11H5a4 4 0 0 1 4-4v3a5 5 0 0 1-5 5"/><path d="M19 11h-3a4 4 0 0 1 4-4v3a5 5 0 0 1-5 5"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="createLink" title="Insert Link" aria-label="Insert Link">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7 0l2-2a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-2 2a5 5 0 0 0 7 7l1-1"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-book-insert="divider" title="Divider" aria-label="Divider">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12h16"/><path d="M12 6v12" opacity=".4"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="undo" title="Undo" aria-label="Undo">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 7 4 12l5 5"/><path d="M4 12h11a5 5 0 0 1 0 10"/></svg>
                    </button>
                    <button type="button" class="dxm-tool book-icon-tool" data-dxm-command="redo" title="Redo" aria-label="Redo">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 7 5 5-5 5"/><path d="M20 12H9a5 5 0 0 0 0 10"/></svg>
                    </button>
                </div>
            </div>

            <div class="book-media-rail">
                <div class="book-media-copy">
                    <strong>Insert Image / Graphic</strong>
                    <span>Scroll horizontally through media tools. Select from app media, paste a URL, upload, or drag an image from your device.</span>
                </div>

                <div class="book-media-actions book-scroll-rail">
                    <select id="bookInsertImageAsset" title="Choose image from media library">
                        <option value="">Choose from media library...</option>
                        @foreach ($assetsForInsert as $asset)
                            @php
                                $assetUrl = $asset->url;
                                if (!$assetUrl && $asset->path) {
                                    try {
                                        $assetUrl = \Illuminate\Support\Facades\Storage::disk($asset->disk ?: 'public')->url($asset->path);
                                    } catch (\Throwable $e) {
                                        $assetUrl = '';
                                    }
                                }
                            @endphp
                            @if ($assetUrl)
                                <option value="{{ $assetUrl }}" data-label="{{ $asset->label ?: 'Book image' }}">
                                    {{ $asset->label ?: 'Asset #' . $asset->id }}
                                </option>
                            @endif
                        @endforeach
                    </select>

                    <input id="bookInsertImageUrl" type="text" placeholder="Paste image URL..." title="Paste image URL">
                    <input id="bookInsertImageCaption" type="text" placeholder="Caption optional" title="Optional image caption">
                    <button type="button" class="dxm-btn dxm-btn--primary book-media-insert-btn" id="insertBookImageButton" title="Insert selected image into page">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4z"/><path d="m4 16 5-5 4 4 2-2 5 5"/><path d="M15 9h.01"/></svg>
                        <span>Insert</span>
                    </button>
                </div>

                <div class="book-upload-row" data-book-drop-zone>
                    <input type="hidden" id="bookMediaCsrf" value="{{ csrf_token() }}">
                    <input type="hidden" id="bookMediaUploadUrl" value="{{ route('admin.beginner.media-center.upload') }}">
                    <input type="file" id="bookInlineImageUpload" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <span id="bookUploadStatus">Optional: choose or drag an image here, then it uploads and inserts into the page.</span>
                </div>

                <div class="book-thumb-strip book-scroll-rail" aria-label="Recent media thumbnails">
                    @foreach ($assetsForInsert->take(24) as $asset)
                        @php
                            $assetUrl = $asset->url;
                            if (!$assetUrl && $asset->path) {
                                try {
                                    $assetUrl = \Illuminate\Support\Facades\Storage::disk($asset->disk ?: 'public')->url($asset->path);
                                } catch (\Throwable $e) {
                                    $assetUrl = '';
                                }
                            }
                        @endphp
                        @if ($assetUrl)
                            <button type="button" data-book-image-thumb data-url="{{ $assetUrl }}" data-label="{{ $asset->label ?: 'Book image' }}" style="background-image:url('{{ $assetUrl }}')" title="{{ $asset->label ?: 'Book image' }}"></button>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="book-editor-shell">
                <div class="dxm-editor-wrap book-rich-editor-wrap">
                    <div class="dxm-editor-top book-editor-mini-head">
                        <div class="dxm-editor-title">
                            <strong>Chapter Page Editor</strong>
                            <span>Main writing area. Helpers, tools, and images stay above so this space remains wide.</span>
                        </div>
                    </div>

                    <div class="dxm-editor-surface book-editor-surface" contenteditable="true" data-dxm-rich-editor data-placeholder="Start writing this chapter like a real book page...">{!! old('body_html', $chapter->body_html) !!}</div>
                    <textarea id="body_html" name="body_html" class="dxm-editor-hidden">{{ old('body_html', $chapter->body_html) }}</textarea>
                </div>
            </div>

            <div class="book-bottom-panels">
                <div class="book-editor-stats">
                    <span><b data-book-word-count>0</b> words</span>
                    <span><b data-book-page-count>1</b> estimated page(s)</span>
                    <span><b data-book-read-minutes>1</b> min read</span>
                    <span>Read Aloud Ready</span>
                </div>

                <div class="book-toc-horizontal">
                    <strong>Table of Contents</strong>
                    <div class="book-scroll-rail">
                        @forelse ($allChapters as $tocChapter)
                            <a href="{{ route('admin.beginner.books.chapters.edit', $tocChapter) }}" class="{{ (int) $tocChapter->id === (int) ($chapter->id ?? 0) ? 'is-current' : '' }}">
                                <b>{{ $loop->iteration }}</b>
                                <span>{{ $tocChapter->title }}</span>
                            </a>
                        @empty
                            <em>No chapters yet. This will become Chapter 1.</em>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dxm-tab-panel" data-dxm-tab-panel="study">
        <div class="dxm-section-card">
            <h3>Study Guide Sections</h3>
            <p>Optional sections that make Christian books, devotionals, and manuals more engaging.</p>

            <div class="dxm-grid">
                <div class="dxm-col-12">
                    <label for="summary">Chapter Summary</label>
                    <textarea id="summary" name="summary" rows="4">{{ old('summary', $chapter->summary) }}</textarea>
                </div>

                <div class="dxm-col-12">
                    <label for="key_thought">Key Thought</label>
                    <textarea id="key_thought" name="key_thought" rows="3">{{ old('key_thought', $chapter->key_thought) }}</textarea>
                </div>

                <div class="dxm-col-12">
                    <label for="memory_verse">Memory Verse</label>
                    <input id="memory_verse" name="memory_verse" type="text" value="{{ old('memory_verse', $chapter->memory_verse) }}" placeholder="Example: Proverbs 18:21">
                </div>

                <div class="dxm-col-12">
                    <label for="reflection_questions">Reflection Questions</label>
                    <textarea id="reflection_questions" name="reflection_questions" rows="4">{{ old('reflection_questions', $chapter->reflection_questions) }}</textarea>
                </div>

                <div class="dxm-col-12">
                    <label for="prayer_points">Prayer Points</label>
                    <textarea id="prayer_points" name="prayer_points" rows="4">{{ old('prayer_points', $chapter->prayer_points) }}</textarea>
                </div>

                <div class="dxm-col-12">
                    <label for="action_steps">Action Steps</label>
                    <textarea id="action_steps" name="action_steps" rows="4">{{ old('action_steps', $chapter->action_steps) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="dxm-tab-panel" data-dxm-tab-panel="publishing">
        <div class="dxm-section-card">
            <h3>Publishing</h3>
            <p>Control whether this chapter appears in the frontend table of contents.</p>

            <div class="dxm-grid">
                <div class="dxm-col-6">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $chapter->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dxm-col-6">
                    <label for="sort_order">Chapter Order</label>
                    <input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $chapter->sort_order) }}">
                </div>
            </div>
        </div>
    </div>

    <div class="dxm-submit">
        <a href="{{ route('admin.beginner.books.chapters.index', $book) }}" class="dxm-btn">Cancel</a>
        <button type="submit" class="dxm-btn dxm-btn--primary">{{ $isEdit ? 'Save Chapter' : 'Create Chapter' }}</button>
    </div>
</form>

@push('styles')
<style>
.book-editor-header-card{display:flex;gap:13px;align-items:center;border:1px solid rgba(255,255,255,.10);background:linear-gradient(135deg,rgba(15,23,42,.88),rgba(30,41,59,.72));border-radius:20px;padding:12px;margin-bottom:12px}.book-editor-cover{width:72px;aspect-ratio:3/4;border-radius:12px;background:#020617;overflow:hidden;display:grid;place-items:center;color:rgba(255,255,255,.48);font-size:10px;font-weight:900;flex:0 0 auto}.book-editor-cover img{width:100%;height:100%;object-fit:cover}.book-editor-head-copy{min-width:0}.book-editor-head-copy span{display:block;color:#67e8f9;font-size:10px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.book-editor-head-copy strong{display:block;color:#fff;font-size:18px;margin-top:2px}.book-editor-head-copy small{display:block;color:rgba(255,255,255,.68);font-size:12px;margin-top:3px}.book-editor-head-tags{display:flex;gap:7px;flex-wrap:wrap;margin-top:8px}.book-editor-head-tags b,.book-editor-stats span{font-size:10px;font-weight:950;text-transform:uppercase;border:1px solid rgba(34,211,238,.28);background:rgba(34,211,238,.08);color:#cffafe;border-radius:999px;padding:6px 8px}.compact-book-identity{margin-bottom:12px}.book-writing-studio{overflow:hidden}.book-studio-title-row{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:12px}.book-studio-title-row h3{margin:0}.book-studio-title-row p{margin:5px 0 0}.book-focus-button{display:inline-flex!important;align-items:center;gap:8px}.book-focus-button svg,.book-media-insert-btn svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.book-rail-wrap{border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.18);border-radius:18px;padding:10px;margin-bottom:10px}.book-rail-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:8px}.book-rail-head strong{font-size:12px;color:#fff}.book-rail-head span{font-size:10.5px;color:rgba(255,255,255,.48);font-weight:850;text-transform:uppercase;letter-spacing:.05em}.book-scroll-rail{overflow-x:auto;overflow-y:hidden;scrollbar-width:thin;scrollbar-color:rgba(34,211,238,.55) rgba(255,255,255,.05);overscroll-behavior-x:contain;-webkit-overflow-scrolling:touch}.book-scroll-rail::-webkit-scrollbar{height:7px}.book-scroll-rail::-webkit-scrollbar-track{background:rgba(255,255,255,.05);border-radius:999px}.book-scroll-rail::-webkit-scrollbar-thumb{background:linear-gradient(90deg,rgba(34,211,238,.72),rgba(168,85,247,.60));border-radius:999px}.book-tool-rail,.book-format-rail{display:flex;gap:8px;min-width:0;padding:2px 0 8px}.book-tool-rail button,.book-format-rail button{white-space:nowrap;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.055);color:#fff;border-radius:12px;min-height:38px;font-size:11px;font-weight:950;cursor:pointer;flex:0 0 auto}.book-tool-rail button{display:inline-flex;align-items:center;gap:7px;padding:8px 11px}.book-tool-rail button svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;color:#a5f3fc}.book-format-rail button.book-icon-tool{width:40px;min-width:40px;height:38px;padding:0;display:grid;place-items:center}.book-format-rail button.book-icon-tool svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;color:#e0f2fe}.book-tool-rail button:hover,.book-format-rail button:hover{border-color:rgba(34,211,238,.55);background:rgba(34,211,238,.12);transform:translateY(-1px)}.book-media-rail{border:1px solid rgba(34,211,238,.18);background:linear-gradient(135deg,rgba(8,47,73,.24),rgba(2,6,23,.28));border-radius:18px;padding:12px;margin-bottom:12px}.book-media-copy{margin-bottom:10px}.book-media-copy strong{display:block;color:#fff;font-size:13px}.book-media-copy span{display:block;color:rgba(255,255,255,.58);font-size:11.5px;line-height:1.45;margin-top:3px}.book-media-actions{display:flex!important;gap:8px;align-items:center;padding-bottom:8px}.book-media-actions select,.book-media-actions input{min-height:40px;min-width:190px;flex:0 0 190px}.book-media-actions .book-media-insert-btn{flex:0 0 auto;display:inline-flex!important;align-items:center;gap:7px;min-height:40px}.book-upload-row{display:grid;grid-template-columns:minmax(180px,.8fr) minmax(0,1fr);gap:8px;align-items:center;margin-top:10px;border:1px dashed rgba(34,211,238,.24);border-radius:14px;padding:10px;background:rgba(2,6,23,.28)}.book-upload-row.is-dragging{border-color:rgba(34,211,238,.75);background:rgba(34,211,238,.12)}.book-upload-row span{font-size:11.5px;color:rgba(255,255,255,.60);line-height:1.4}.book-thumb-strip{display:flex;gap:8px;margin-top:10px;padding-bottom:8px}.book-thumb-strip button{width:62px;min-width:62px;height:48px;border:1px solid rgba(255,255,255,.10);border-radius:12px;background-color:#020617;background-position:center;background-size:cover;background-repeat:no-repeat;cursor:pointer}.book-thumb-strip button:hover{border-color:rgba(34,211,238,.65);transform:translateY(-1px)}.book-editor-shell{display:block}.book-rich-editor-wrap{min-height:420px}.book-editor-mini-head{border-bottom:1px solid rgba(255,255,255,.08)}.book-editor-surface{min-height:340px!important;font-size:16px;line-height:1.85;padding:22px!important;background:rgba(2,6,23,.42)!important}.book-editor-surface h2{font-size:25px;line-height:1.25;margin:24px 0 12px}.book-editor-surface h3{font-size:20px;line-height:1.3;margin:20px 0 10px}.book-editor-surface figure{margin:18px 0;padding:10px;border:1px solid rgba(255,255,255,.10);border-radius:16px;background:rgba(255,255,255,.04)}.book-editor-surface figure img{max-width:100%;border-radius:12px;display:block;margin:0 auto}.book-editor-surface figcaption{margin-top:8px;color:rgba(255,255,255,.62);font-size:12px;text-align:center}.book-editor-surface .book-callout{border-left:4px solid #22d3ee;background:rgba(34,211,238,.08);padding:12px 14px;border-radius:14px;margin:16px 0}.book-editor-surface .book-divider{border:0;border-top:1px solid rgba(255,255,255,.18);margin:22px 0}.book-bottom-panels{display:grid;grid-template-columns:minmax(0,.75fr) minmax(0,1fr);gap:10px;margin-top:12px}.book-editor-stats{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.book-editor-stats span b{color:#fff}.book-toc-horizontal{border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.22);border-radius:16px;padding:10px;min-width:0}.book-toc-horizontal>strong{display:block;color:#fff;font-size:12px;margin-bottom:8px}.book-toc-horizontal>div{display:flex;gap:8px}.book-toc-horizontal a{display:inline-flex;gap:7px;align-items:center;color:#fff;text-decoration:none;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.05);border-radius:999px;padding:7px 10px;font-size:11px;font-weight:900;white-space:nowrap;flex:0 0 auto}.book-toc-horizontal a.is-current{border-color:rgba(34,211,238,.62);background:rgba(34,211,238,.16)}.book-toc-horizontal a b{display:grid;place-items:center;width:20px;height:20px;border-radius:999px;background:rgba(34,211,238,.18);color:#cffafe}.book-toc-horizontal em{color:rgba(255,255,255,.52);font-size:12px}.book-focus-mode .dxm-shell-sidebar,.book-focus-mode .dxm-topbar,.book-focus-mode .dxm-dock-tabs,.book-focus-mode .dxm-studio-preview{display:none!important}.book-focus-mode .dxm-studio-grid{grid-template-columns:1fr!important}.book-focus-mode .book-editor-surface{min-height:70vh!important}.book-focus-mode .dxm-studio-form{max-width:1100px;margin:0 auto}@media(max-width:980px){.book-upload-row{grid-template-columns:1fr}.book-bottom-panels{grid-template-columns:1fr}.book-studio-title-row{display:block}.book-studio-title-row .dxm-btn{margin-top:10px;width:100%}.book-editor-surface{min-height:300px!important}.book-media-actions select,.book-media-actions input{min-width:170px;flex-basis:170px}}@media(max-width:640px){.book-editor-header-card{align-items:flex-start}.book-editor-cover{width:58px}.book-editor-head-copy strong{font-size:16px}.book-rail-head{display:block}.book-rail-head span{display:block;margin-top:3px}.book-tool-rail button span{display:none}.book-format-rail button.book-icon-tool{width:38px;min-width:38px}.book-media-actions select,.book-media-actions input{min-width:160px;flex-basis:160px}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('chapterForm');
    const editor = document.querySelector('[data-dxm-rich-editor]');
    const hidden = document.getElementById('body_html');
    const title = document.getElementById('title');
    const subtitle = document.getElementById('subtitle');
    const status = document.getElementById('status');
    const summary = document.getElementById('summary');
    const keyThought = document.getElementById('key_thought');
    const memoryVerse = document.getElementById('memory_verse');
    const imageSelect = document.getElementById('bookInsertImageAsset');
    const imageUrl = document.getElementById('bookInsertImageUrl');
    const imageCaption = document.getElementById('bookInsertImageCaption');
    const insertImageButton = document.getElementById('insertBookImageButton');
    const uploadInput = document.getElementById('bookInlineImageUpload');
    const uploadStatus = document.getElementById('bookUploadStatus');
    const dropZone = document.querySelector('[data-book-drop-zone]');
    const csrf = document.getElementById('bookMediaCsrf')?.value || '';
    const uploadUrl = document.getElementById('bookMediaUploadUrl')?.value || '';

    if (!editor || !hidden) return;

    function safeText(value) {
        return String(value || '').replace(/[&<>"]/g, function (match) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[match];
        });
    }

    function placeCaret() {
        editor.focus();
        const selection = window.getSelection();
        if (!selection || selection.rangeCount) return;
        const range = document.createRange();
        range.selectNodeContents(editor);
        range.collapse(false);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function insertHtml(html) {
        placeCaret();
        document.execCommand('insertHTML', false, html);
        syncAll();
    }

    function textStats() {
        const text = editor.innerText || '';
        const words = (text.trim().match(/\S+/g) || []).length;
        const pages = Math.max(1, Math.ceil(words / 420));
        const minutes = Math.max(1, Math.ceil(words / 180));
        return {words, pages, minutes};
    }

    function syncAll() {
        hidden.value = editor.innerHTML;
        const stats = textStats();
        document.querySelectorAll('[data-live="content-title"]').forEach(el => el.textContent = title && title.value ? title.value : 'Chapter title preview');
        document.querySelectorAll('[data-live="content-subtitle"]').forEach(el => el.textContent = subtitle && subtitle.value ? subtitle.value : '');
        document.querySelectorAll('[data-live="content-status"]').forEach(el => el.textContent = status && status.value ? status.value : 'draft');
        document.querySelectorAll('[data-live="content-body"]').forEach(el => {
            el.innerHTML = editor.innerHTML.trim() || '<div class="dxm-preview__empty">Start writing the chapter to preview the reading page.</div>';
        });
        document.querySelectorAll('[data-book-word-count]').forEach(el => el.textContent = stats.words);
        document.querySelectorAll('[data-book-page-count]').forEach(el => el.textContent = stats.pages);
        document.querySelectorAll('[data-book-read-minutes]').forEach(el => el.textContent = stats.minutes);
        document.querySelectorAll('[data-study-live="summary"]').forEach(el => el.textContent = summary && summary.value ? summary.value : 'No summary yet.');
        document.querySelectorAll('[data-study-live="key_thought"]').forEach(el => el.textContent = keyThought && keyThought.value ? keyThought.value : 'No key thought yet.');
        document.querySelectorAll('[data-study-live="memory_verse"]').forEach(el => el.textContent = memoryVerse && memoryVerse.value ? memoryVerse.value : 'No memory verse yet.');
    }

    document.querySelectorAll('[data-dxm-command]').forEach(function (button) {
        button.addEventListener('click', function () {
            const command = button.getAttribute('data-dxm-command');
            const value = button.getAttribute('data-dxm-value') || null;
            if (command === 'createLink') {
                const url = window.prompt('Paste link URL');
                if (url) document.execCommand(command, false, url);
            } else {
                document.execCommand(command, false, value);
            }
            syncAll();
        });
    });

    const helperBlocks = {
        intro: '<h2>Introduction</h2><p>Start this chapter by introducing the main thought clearly.</p>',
        scripture: '<div class="book-callout"><strong>Key Scripture:</strong><p>Write the scripture reference and passage here.</p></div>',
        teaching: '<h3>Main Teaching</h3><p>Explain the lesson, principle, or message here.</p>',
        key_thought: '<div class="book-callout"><strong>Key Thought:</strong><p>Write the central lesson here.</p></div>',
        reflection: '<h3>Reflection Questions</h3><ul><li>What did you learn from this chapter?</li><li>How will you apply it?</li></ul>',
        prayer: '<h3>Prayer Point</h3><p>Lord, help me to apply this truth in my daily life.</p>',
        action: '<h3>Action Step</h3><p>Write one practical step the reader should take.</p>',
        summary: '<h3>Chapter Summary</h3><p>Summarize the most important ideas from this chapter.</p>',
        divider: '<hr class="book-divider">'
    };

    document.querySelectorAll('[data-book-insert]').forEach(function (button) {
        button.addEventListener('click', function () {
            const key = button.getAttribute('data-book-insert');
            insertHtml(helperBlocks[key] || '');
        });
    });

    function insertImage(url, caption) {
        if (!url) return;
        const html = '<figure><img src="' + safeText(url) + '" alt="Book image">' + (caption ? '<figcaption>' + safeText(caption) + '</figcaption>' : '') + '</figure>';
        insertHtml(html);
    }

    if (insertImageButton) {
        insertImageButton.addEventListener('click', function () {
            const selected = imageSelect && imageSelect.value ? imageSelect.value : '';
            const pasted = imageUrl && imageUrl.value ? imageUrl.value.trim() : '';
            const caption = imageCaption && imageCaption.value ? imageCaption.value.trim() : '';
            insertImage(pasted || selected, caption);
        });
    }

    document.querySelectorAll('[data-book-image-thumb]').forEach(function (button) {
        button.addEventListener('click', function () {
            insertImage(button.getAttribute('data-url'), button.getAttribute('data-label') || '');
        });
    });

    function uploadFile(file) {
        if (!file || !uploadUrl) return;
        const formData = new FormData();
        formData.append('image_file', file);
        formData.append('bucket', 'book_chapter_graphics');
        formData.append('label', title && title.value ? title.value + ' chapter image' : 'Book chapter image');
        if (uploadStatus) uploadStatus.textContent = 'Uploading image...';
        fetch(uploadUrl, {method:'POST', headers:{'X-CSRF-TOKEN':csrf, 'Accept':'application/json'}, body:formData})
            .then(response => response.json().then(data => ({response, data})))
            .then(({response, data}) => {
                if (!response.ok || !data.ok || !data.asset || !data.asset.url) throw new Error(data.message || 'Upload failed.');
                if (uploadStatus) uploadStatus.textContent = 'Uploaded and inserted.';
                insertImage(data.asset.url, data.asset.label || '');
                if (uploadInput) uploadInput.value = '';
            })
            .catch(error => {
                if (uploadStatus) uploadStatus.textContent = error.message || 'Upload failed.';
            });
    }

    if (uploadInput) uploadInput.addEventListener('change', function () { uploadFile(uploadInput.files && uploadInput.files[0]); });
    if (dropZone) {
        ['dragenter','dragover'].forEach(eventName => dropZone.addEventListener(eventName, function (event) { event.preventDefault(); dropZone.classList.add('is-dragging'); }));
        ['dragleave','drop'].forEach(eventName => dropZone.addEventListener(eventName, function (event) { event.preventDefault(); dropZone.classList.remove('is-dragging'); }));
        dropZone.addEventListener('drop', function (event) { uploadFile(event.dataTransfer && event.dataTransfer.files ? event.dataTransfer.files[0] : null); });
    }

    document.querySelectorAll('[data-book-focus]').forEach(function(button){
        button.addEventListener('click', function(){ document.body.classList.toggle('book-focus-mode'); });
    });

    [editor, title, subtitle, status, summary, keyThought, memoryVerse].forEach(function (field) {
        if (!field) return;
        field.addEventListener('input', syncAll);
        field.addEventListener('change', syncAll);
    });

    if (form) form.addEventListener('submit', syncAll);
    syncAll();
});
</script>
@endpush

@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $action = $isEdit ? route('admin.beginner.books.update', $book) : route('admin.beginner.books.store');

    $coverValue = old('cover_image_url', $book->cover_image_url);
    $meta = is_array($book->meta_json ?? null) ? $book->meta_json : [];
    $designer = is_array($meta['cover_designer'] ?? null) ? $meta['cover_designer'] : [];

    $currentCoverRatio = old('cover_ratio', $meta['cover_ratio'] ?? 'portrait_3_4');
    $currentDisplayStyle = old('display_style', $meta['display_style'] ?? 'book_cover');
    $currentPageSize = old('book_page_size', $meta['book_page_size'] ?? 'standard_6x9');
    $currentReaderFont = old('reader_font', $meta['reader_font'] ?? 'serif');
    $currentPageTheme = old('page_theme', $meta['page_theme'] ?? 'classic');
    $currentTargetPages = old('target_pages', $meta['target_pages'] ?? '');

    $coverDesignerEnabled = (bool) old('cover_designer_enabled', $designer['enabled'] ?? false);
    $designerBgImage = old('cover_designer_bg_image_url', $designer['bg_image_url'] ?? ($book->cover_image_src ?: $coverValue));
    $designerBgColor = old('cover_designer_bg_color', $designer['bg_color'] ?? '#0B1F4D');
    $designerGradient = old('cover_designer_gradient_color', $designer['gradient_color'] ?? '#E2388A');
    $designerOverlay = old('cover_designer_overlay', $designer['overlay'] ?? 42);
    $designerTitle = old('cover_designer_title', $designer['title'] ?? $book->title);
    $designerSubtitle = old('cover_designer_subtitle', $designer['subtitle'] ?? $book->subtitle);
    $designerAuthor = old('cover_designer_author', $designer['author'] ?? $book->author_name);
    $designerBadge = old('cover_designer_badge', $designer['badge'] ?? 'New Book');
    $designerTextColor = old('cover_designer_text_color', $designer['text_color'] ?? '#FFFFFF');
    $designerTextPosition = old('cover_designer_text_position', $designer['text_position'] ?? 'bottom');
    $designerTitleSize = old('cover_designer_title_size', $designer['title_size'] ?? 34);
    $designerSubtitleSize = old('cover_designer_subtitle_size', $designer['subtitle_size'] ?? 15);
    $designerAuthorSize = old('cover_designer_author_size', $designer['author_size'] ?? 13);
    $designerBadgeSize = old('cover_designer_badge_size', $designer['badge_size'] ?? 10);
    $designerFontFamily = old('cover_designer_font_family', $designer['font_family'] ?? 'display');
    $designerTextAlign = old('cover_designer_text_align', $designer['text_align'] ?? 'left');
    $designerTextShadow = old('cover_designer_text_shadow', $designer['text_shadow'] ?? 'soft');
    $designerBgFit = old('cover_designer_bg_fit', $designer['bg_fit'] ?? 'cover');
    $designerBgPosition = old('cover_designer_bg_position', $designer['bg_position'] ?? 'center');
    $designerLayoutStyle = old('cover_designer_layout_style', $designer['layout_style'] ?? 'bold');
    $designerTextWidth = old('cover_designer_text_width', $designer['text_width'] ?? 88);
    $designerPanelOpacity = old('cover_designer_panel_opacity', $designer['panel_opacity'] ?? 0);
    $designerSyncBg = (bool) old('cover_designer_sync_bg', $designer['sync_bg'] ?? true);

    $chapters = $isEdit ? $book->chapters()->orderBy('sort_order')->orderBy('id')->get() : collect();
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

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" id="bookForm">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="dxm-tab-panel is-active" data-dxm-tab-panel="details">
        <div class="book-inner-tabs" data-book-inner-root="details">
            <button type="button" class="is-active" data-book-inner-tab="identity">Identity</button>
            <button type="button" data-book-inner-tab="toc">TOC Snapshot</button>
        </div>

        <div class="book-inner-panel is-active" data-book-inner-panel="identity">
            <div class="dxm-section-card">
                <h3>Book Identity</h3>
                <p>Set the book title, author, category, and description.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-6">
                        <label for="title">Book Title</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $book->title) }}" required>
                    </div>

                    <div class="dxm-col-6">
                        <label for="subtitle">Subtitle</label>
                        <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $book->subtitle) }}">
                    </div>

                    <div class="dxm-col-6">
                        <label for="author_name">Author Name</label>
                        <input id="author_name" name="author_name" type="text" value="{{ old('author_name', $book->author_name) }}">
                    </div>

                    <div class="dxm-col-6">
                        <label for="book_category_id">Category</label>
                        <select id="book_category_id" name="book_category_id">
                            <option value="">No category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((string) old('book_category_id', $book->book_category_id) === (string) $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dxm-col-12">
                        <label for="new_category_name">Create New Category</label>
                        <input id="new_category_name" name="new_category_name" type="text" value="{{ old('new_category_name') }}" placeholder="Example: Prayer Guides">
                        <small>Optional. If filled, this creates and assigns a new category.</small>
                    </div>

                    <div class="dxm-col-12">
                        <label for="description">Book Description / Back Cover Text</label>
                        <textarea id="description" name="description" rows="6">{{ old('description', $book->description) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="book-inner-panel" data-book-inner-panel="toc">
            @if ($isEdit)
                <div class="dxm-section-card">
                    <h3>Book Studio Snapshot</h3>
                    <p>Confirm the book structure before Flutter reader testing.</p>

                    <div class="book-studio-stats">
                        <div><strong>{{ $chapters->count() }}</strong><span>Total Chapters</span></div>
                        <div><strong>{{ $chapters->where('status', 'published')->count() }}</strong><span>Published</span></div>
                        <div><strong>{{ $chapters->where('status', 'draft')->count() }}</strong><span>Draft</span></div>
                        <div><strong>{{ max(1, (int) ceil(strlen(strip_tags($chapters->pluck('body_html')->implode(' '))) / 1800)) }}</strong><span>Estimated Pages</span></div>
                    </div>

                    <div class="toc-mini">
                        <strong>Table of Contents Preview</strong>
                        @forelse ($chapters as $chapter)
                            <a href="{{ route('admin.beginner.books.chapters.edit', $chapter) }}">
                                <span>{{ $loop->iteration }}.</span>
                                <em>{{ $chapter->title }}</em>
                                <small>{{ ucfirst($chapter->status) }}</small>
                            </a>
                        @empty
                            <p>No chapters yet. Add chapters to build the table of contents.</p>
                        @endforelse
                    </div>

                    <div class="book-studio-actions">
                        <a href="{{ route('admin.beginner.books.chapters.create', $book) }}" class="dxm-btn dxm-btn--primary">+ Add Chapter</a>
                        <a href="{{ route('admin.beginner.books.chapters.index', $book) }}" class="dxm-btn">Open Chapter Builder</a>
                    </div>
                </div>
            @else
                <div class="dxm-section-card">
                    <h3>TOC will appear after saving</h3>
                    <p>Create the book first, then add chapters from the Chapter Builder.</p>
                </div>
            @endif
        </div>
    </div>

    <div class="dxm-tab-panel" data-dxm-tab-panel="media">
        <div class="book-inner-tabs" data-book-inner-root="media">
            <button type="button" class="is-active" data-book-inner-tab="source">Cover Source</button>
            <button type="button" data-book-inner-tab="designer">Cover Designer</button>
            <button type="button" data-book-inner-tab="text">Text Styling</button>
            <button type="button" data-book-inner-tab="format">Book Format</button>
            <button type="button" data-book-inner-tab="files">Files / Links</button>
        </div>

        <div class="book-inner-panel is-active" data-book-inner-panel="source">
            <div class="dxm-section-card">
                <h3>Cover Source</h3>
                <p>Select the base cover image. The designer can use this as its background.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-6">
                        <label for="book_type">Book Type</label>
                        <select id="book_type" name="book_type">
                            @foreach ($bookTypeOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('book_type', $book->book_type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_ratio">Cover Format</label>
                        <select id="cover_ratio" name="cover_ratio">
                            @foreach ($coverRatioOptions as $value => $label)
                                <option value="{{ $value }}" @selected($currentCoverRatio === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="display_style">Display Style</label>
                        <select id="display_style" name="display_style">
                            <option value="book_cover" @selected($currentDisplayStyle === 'book_cover')>Book Cover / Library Shelf</option>
                            <option value="document_card" @selected($currentDisplayStyle === 'document_card')>Document Card</option>
                            <option value="magazine" @selected($currentDisplayStyle === 'magazine')>Magazine Style</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_image_file">Upload Finished Cover Image</label>
                        <input id="cover_image_file" name="cover_image_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </div>

                    <div class="dxm-col-12">
                        <label for="cover_image_url">Finished Cover Image URL</label>
                        <input id="cover_image_url" name="cover_image_url" type="text" value="{{ $coverValue }}" placeholder="Paste URL or select from media library">
                    </div>

                    <div class="dxm-col-12">
                        @include('admin.beginner.partials.media-picker', [
                            'pickerId' => 'bookCoverMediaCenter',
                            'inputId' => 'cover_image_url',
                            'selectName' => 'media_asset_id',
                            'assets' => $mediaAssets,
                            'title' => 'Book Cover Library',
                            'subtitle' => 'Browse, upload, preview, and select a portrait-style cover image for this book.',
                            'buttonLabel' => 'Open Cover Library',
                            'uploadBucket' => 'book_covers',
                            'uploadLabel' => old('title', $book->title ?: 'Book Cover'),
                            'emptyText' => 'No images found yet. Upload one from the modal.'
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="book-inner-panel" data-book-inner-panel="designer">
            <div class="dxm-section-card">
                <div class="book-cover-designer-head">
                    <div>
                        <h3>Book Cover Designer</h3>
                        <p>Create a cover from background image/color, title, subtitle, author, and badge.</p>
                    </div>
                    <label class="dxm-checkbox designer-toggle">
                        <input type="checkbox" id="cover_designer_enabled" name="cover_designer_enabled" value="1" @checked($coverDesignerEnabled)>
                        <span>Use designed cover</span>
                    </label>
                </div>

                <div class="dxm-grid">
                    <div class="dxm-col-12">
                        <div class="designer-bg-sync-card">
                            <div>
                                <label for="cover_designer_bg_image_url">Designer Background Image URL</label>
                                <input id="cover_designer_bg_image_url" name="cover_designer_bg_image_url" type="text" value="{{ $designerBgImage }}" placeholder="Paste image URL or select from cover library">
                                <small>When sync is enabled, selecting a cover from the library updates this designer background.</small>
                            </div>
                            <label class="dxm-checkbox designer-sync-toggle">
                                <input type="checkbox" id="cover_designer_sync_bg" name="cover_designer_sync_bg" value="1" @checked($designerSyncBg)>
                                <span>Use selected cover as designer background</span>
                            </label>
                            <button type="button" class="dxm-btn" id="designerUseCoverNow">Use Current Cover Now</button>
                        </div>
                    </div>

                    <div class="dxm-col-3">
                        <label for="cover_designer_bg_color">Start Color</label>
                        <input id="cover_designer_bg_color" name="cover_designer_bg_color" type="color" value="{{ $designerBgColor }}">
                    </div>

                    <div class="dxm-col-3">
                        <label for="cover_designer_gradient_color">Accent Color</label>
                        <input id="cover_designer_gradient_color" name="cover_designer_gradient_color" type="color" value="{{ $designerGradient }}">
                    </div>

                    <div class="dxm-col-3">
                        <label for="cover_designer_text_color">Text Color</label>
                        <input id="cover_designer_text_color" name="cover_designer_text_color" type="color" value="{{ $designerTextColor }}">
                    </div>

                    <div class="dxm-col-3">
                        <label for="cover_designer_overlay">Overlay</label>
                        <input id="cover_designer_overlay" name="cover_designer_overlay" type="range" min="0" max="90" value="{{ $designerOverlay }}">
                        <small><span data-designer-overlay-value>{{ $designerOverlay }}</span>% darkness</small>
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_title">Cover Title</label>
                        <input id="cover_designer_title" name="cover_designer_title" type="text" value="{{ $designerTitle }}" placeholder="Use book title if empty">
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_subtitle">Cover Subtitle</label>
                        <input id="cover_designer_subtitle" name="cover_designer_subtitle" type="text" value="{{ $designerSubtitle }}" placeholder="Use subtitle if empty">
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_author">Cover Author</label>
                        <input id="cover_designer_author" name="cover_designer_author" type="text" value="{{ $designerAuthor }}" placeholder="Use author if empty">
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_badge">Badge / Label</label>
                        <input id="cover_designer_badge" name="cover_designer_badge" type="text" value="{{ $designerBadge }}" placeholder="Optional: New Book, Study Guide, Prayer Manual">
                    </div>
                </div>
            </div>
        </div>

        <div class="book-inner-panel" data-book-inner-panel="text">
            <div class="dxm-section-card">
                <h3>Text Styling</h3>
                <p>Fine-tune the designer text without crowding the cover source settings.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-3">
                        <label for="cover_designer_title_size">Title Size</label>
                        <input id="cover_designer_title_size" name="cover_designer_title_size" type="number" min="16" max="72" value="{{ $designerTitleSize }}">
                    </div>

                    <div class="dxm-col-3">
                        <label for="cover_designer_subtitle_size">Subtitle Size</label>
                        <input id="cover_designer_subtitle_size" name="cover_designer_subtitle_size" type="number" min="10" max="36" value="{{ $designerSubtitleSize }}">
                    </div>

                    <div class="dxm-col-3">
                        <label for="cover_designer_author_size">Author Size</label>
                        <input id="cover_designer_author_size" name="cover_designer_author_size" type="number" min="10" max="32" value="{{ $designerAuthorSize }}">
                    </div>

                    <div class="dxm-col-3">
                        <label for="cover_designer_badge_size">Badge Size</label>
                        <input id="cover_designer_badge_size" name="cover_designer_badge_size" type="number" min="8" max="24" value="{{ $designerBadgeSize }}">
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_font_family">Cover Font</label>
                        <select id="cover_designer_font_family" name="cover_designer_font_family">
                            <option value="display" @selected($designerFontFamily === 'display')>Display / Bold Cover</option>
                            <option value="serif" @selected($designerFontFamily === 'serif')>Classic Serif</option>
                            <option value="sans" @selected($designerFontFamily === 'sans')>Clean Sans</option>
                            <option value="condensed" @selected($designerFontFamily === 'condensed')>Condensed Poster</option>
                            <option value="elegant" @selected($designerFontFamily === 'elegant')>Elegant Book</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_layout_style">Cover Layout Style</label>
                        <select id="cover_designer_layout_style" name="cover_designer_layout_style">
                            <option value="bold" @selected($designerLayoutStyle === 'bold')>Bold Publisher Cover</option>
                            <option value="minimal" @selected($designerLayoutStyle === 'minimal')>Minimal Clean Cover</option>
                            <option value="editorial" @selected($designerLayoutStyle === 'editorial')>Editorial / Magazine</option>
                            <option value="boxed" @selected($designerLayoutStyle === 'boxed')>Boxed Text Panel</option>
                            <option value="cinematic" @selected($designerLayoutStyle === 'cinematic')>Cinematic Overlay</option>
                        </select>
                    </div>

                    <div class="dxm-col-4">
                        <label for="cover_designer_text_position">Text Position</label>
                        <select id="cover_designer_text_position" name="cover_designer_text_position">
                            <option value="top" @selected($designerTextPosition === 'top')>Top</option>
                            <option value="center" @selected($designerTextPosition === 'center')>Center</option>
                            <option value="bottom" @selected($designerTextPosition === 'bottom')>Bottom</option>
                            <option value="split" @selected($designerTextPosition === 'split')>Split Top/Bottom</option>
                        </select>
                    </div>

                    <div class="dxm-col-4">
                        <label for="cover_designer_text_align">Text Alignment</label>
                        <select id="cover_designer_text_align" name="cover_designer_text_align">
                            <option value="left" @selected($designerTextAlign === 'left')>Left</option>
                            <option value="center" @selected($designerTextAlign === 'center')>Center</option>
                            <option value="right" @selected($designerTextAlign === 'right')>Right</option>
                        </select>
                    </div>

                    <div class="dxm-col-4">
                        <label for="cover_designer_text_shadow">Text Shadow</label>
                        <select id="cover_designer_text_shadow" name="cover_designer_text_shadow">
                            <option value="none" @selected($designerTextShadow === 'none')>None</option>
                            <option value="soft" @selected($designerTextShadow === 'soft')>Soft</option>
                            <option value="strong" @selected($designerTextShadow === 'strong')>Strong</option>
                            <option value="glow" @selected($designerTextShadow === 'glow')>Glow</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_text_width">Text Area Width</label>
                        <input id="cover_designer_text_width" name="cover_designer_text_width" type="range" min="35" max="100" value="{{ $designerTextWidth }}">
                        <small><span data-designer-text-width-value>{{ $designerTextWidth }}</span>% cover width</small>
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_panel_opacity">Text Panel Background</label>
                        <input id="cover_designer_panel_opacity" name="cover_designer_panel_opacity" type="range" min="0" max="80" value="{{ $designerPanelOpacity }}">
                        <small><span data-designer-panel-opacity-value>{{ $designerPanelOpacity }}</span>% dark panel</small>
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_bg_fit">Background Fit</label>
                        <select id="cover_designer_bg_fit" name="cover_designer_bg_fit">
                            <option value="cover" @selected($designerBgFit === 'cover')>Cover / Fill</option>
                            <option value="contain" @selected($designerBgFit === 'contain')>Contain / Full Image</option>
                            <option value="pattern" @selected($designerBgFit === 'pattern')>Pattern Repeat</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="cover_designer_bg_position">Background Position</label>
                        <select id="cover_designer_bg_position" name="cover_designer_bg_position">
                            <option value="center" @selected($designerBgPosition === 'center')>Center</option>
                            <option value="top" @selected($designerBgPosition === 'top')>Top</option>
                            <option value="bottom" @selected($designerBgPosition === 'bottom')>Bottom</option>
                            <option value="left" @selected($designerBgPosition === 'left')>Left</option>
                            <option value="right" @selected($designerBgPosition === 'right')>Right</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="book-inner-panel" data-book-inner-panel="format">
            <div class="dxm-section-card">
                <h3>Book Format</h3>
                <p>These settings guide the Flutter reader page style.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-6">
                        <label for="book_page_size">Book / Page Size</label>
                        <select id="book_page_size" name="book_page_size">
                            <option value="standard_6x9" @selected($currentPageSize === 'standard_6x9')>Standard Book 6×9</option>
                            <option value="paperback_5x8" @selected($currentPageSize === 'paperback_5x8')>Paperback 5×8</option>
                            <option value="a5" @selected($currentPageSize === 'a5')>A5 Study Book</option>
                            <option value="magazine" @selected($currentPageSize === 'magazine')>Magazine / Manual</option>
                            <option value="mobile_reader" @selected($currentPageSize === 'mobile_reader')>Mobile Reader Optimized</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="target_pages">Target Page Count</label>
                        <input id="target_pages" name="target_pages" type="number" min="0" value="{{ $currentTargetPages }}" placeholder="Optional, e.g. 50">
                    </div>

                    <div class="dxm-col-6">
                        <label for="reader_font">Reader Font Style</label>
                        <select id="reader_font" name="reader_font">
                            <option value="serif" @selected($currentReaderFont === 'serif')>Serif / Printed Book Feel</option>
                            <option value="sans" @selected($currentReaderFont === 'sans')>Sans / Modern Reader</option>
                            <option value="large_reading" @selected($currentReaderFont === 'large_reading')>Large Reading</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="page_theme">Reader Page Theme</label>
                        <select id="page_theme" name="page_theme">
                            <option value="classic" @selected($currentPageTheme === 'classic')>Classic Paper</option>
                            <option value="soft" @selected($currentPageTheme === 'soft')>Soft Cream</option>
                            <option value="dark" @selected($currentPageTheme === 'dark')>Dark Reader</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="book-inner-panel" data-book-inner-panel="files">
            <div class="dxm-section-card">
                <h3>Files / Links</h3>
                <p>Attach PDF files or external book links when this is not a manual chapter book.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-12">
                        <label for="book_file">Upload PDF Book</label>
                        <input id="book_file" name="book_file" type="file" accept="application/pdf,.pdf">
                        <small>Only required for PDF books. Current file: {{ $book->file_src ?: 'None' }}</small>
                    </div>

                    <div class="dxm-col-12">
                        <label for="file_url">PDF / File URL</label>
                        <input id="file_url" name="file_url" type="text" value="{{ old('file_url', $book->file_url) }}" placeholder="Optional external PDF URL">
                    </div>

                    <div class="dxm-col-12">
                        <label for="external_url">External Book URL</label>
                        <input id="external_url" name="external_url" type="text" value="{{ old('external_url', $book->external_url) }}" placeholder="Only for external link books">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="dxm-tab-panel" data-dxm-tab-panel="publishing">
        <div class="dxm-section-card">
            <h3>Access & Publishing</h3>
            <p>Prepare this book for free, login-required, premium, or token-based reading.</p>

            <div class="dxm-grid">
                <div class="dxm-col-4">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('status', $book->status) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dxm-col-4">
                    <label for="access_type">Access Type</label>
                    <select id="access_type" name="access_type">
                        @foreach ($accessOptions as $value => $label)
                            <option value="{{ $value }}" @selected(old('access_type', $book->access_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="dxm-col-4">
                    <label for="sort_order">Display Order</label>
                    <input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $book->sort_order) }}">
                </div>

                <div class="dxm-col-6">
                    <label class="dxm-checkbox"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $book->is_featured))><span>Mark as featured</span></label>
                </div>

                <div class="dxm-col-6">
                    <label class="dxm-checkbox"><input type="checkbox" name="is_downloadable" value="1" @checked(old('is_downloadable', $book->is_downloadable))><span>Allow download later</span></label>
                </div>
            </div>
        </div>

        @if ($isEdit)
            <div class="dxm-section-card" style="border-color:rgba(248,113,113,.34);background:rgba(127,29,29,.12);">
                <h3>Danger Zone</h3>
                <p>Delete this book and its chapters. Use only if it was created by mistake.</p>
                <input type="hidden" name="_beginner_action" id="beginnerAction" value="">
                <input type="hidden" name="delete_confirmation" id="deleteConfirmation" value="">
                <button type="button" class="dxm-btn" id="deleteBookButton" style="border-color:rgba(248,113,113,.55);background:rgba(127,29,29,.35);">Delete This Book</button>
            </div>
        @endif
    </div>

    <div class="dxm-submit">
        <a href="{{ route('admin.beginner.books.index') }}" class="dxm-btn">Cancel</a>
        <button type="submit" class="dxm-btn dxm-btn--primary">{{ $isEdit ? 'Save Book' : 'Create Book' }}</button>
    </div>
</form>

@if ($isEdit)
<script>
document.addEventListener('DOMContentLoaded', function () {
    const button = document.getElementById('deleteBookButton');
    const form = document.getElementById('bookForm');
    const action = document.getElementById('beginnerAction');
    const confirmInput = document.getElementById('deleteConfirmation');

    if (!button || !form || !action || !confirmInput) return;

    button.addEventListener('click', function () {
        if (!window.confirm('Delete this book and all chapters?')) return;
        const typed = window.prompt('Type DELETE to confirm.');
        if (typed !== 'DELETE') return;
        action.value = 'delete';
        confirmInput.value = 'DELETE';
        form.submit();
    });
});
</script>
@endif

@push('styles')
<style>
.book-inner-tabs{display:flex;gap:8px;overflow-x:auto;padding:8px;border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.28);border-radius:16px;margin-bottom:12px;scrollbar-color:#22d3ee rgba(255,255,255,.08)}.book-inner-tabs button{flex:0 0 auto;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.72);border-radius:12px;padding:10px 14px;font-weight:900;cursor:pointer}.book-inner-tabs button.is-active{border-color:rgba(34,211,238,.54);background:rgba(8,145,178,.25);color:#fff}.book-inner-panel{display:none}.book-inner-panel.is-active{display:block}.book-studio-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:14px}.book-studio-stats div{border:1px solid rgba(255,255,255,.08);border-radius:16px;background:rgba(255,255,255,.04);padding:12px}.book-studio-stats strong{display:block;color:#fff;font-size:22px;line-height:1}.book-studio-stats span{display:block;color:rgba(255,255,255,.58);font-size:11px;margin-top:6px;font-weight:800}.toc-mini{border:1px solid rgba(255,255,255,.08);border-radius:16px;background:rgba(2,6,23,.34);padding:12px;margin-bottom:12px}.toc-mini>strong{display:block;color:#fff;margin-bottom:10px}.toc-mini a{display:grid;grid-template-columns:28px minmax(0,1fr) auto;gap:8px;align-items:center;border-top:1px solid rgba(255,255,255,.06);padding:9px 0;color:#fff}.toc-mini a:first-of-type{border-top:0}.toc-mini em{font-style:normal;font-weight:850;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.toc-mini small{margin:0;color:rgba(255,255,255,.55)}.toc-mini p{margin:0;color:rgba(255,255,255,.62)}.book-studio-actions{display:flex;gap:10px;flex-wrap:wrap}.book-cover-designer-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.book-cover-designer-head h3{margin:0;color:#fff}.book-cover-designer-head p{margin:4px 0 0;color:rgba(255,255,255,.62);font-size:12px;line-height:1.45}.designer-toggle{white-space:nowrap}.designer-bg-sync-card{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:10px;align-items:end;border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.24);border-radius:16px;padding:12px}.designer-sync-toggle{align-self:center;max-width:240px}.designer-bg-sync-card .dxm-btn{grid-column:2;white-space:nowrap}input[type=color]{min-height:42px;padding:5px}input[type=range]{padding:0;accent-color:#22d3ee}small{color:rgba(255,255,255,.62)}@media(max-width:760px){.book-studio-stats{grid-template-columns:repeat(2,1fr)}.toc-mini a{grid-template-columns:24px minmax(0,1fr)}.book-cover-designer-head{display:block}.designer-toggle{margin-top:10px}.designer-bg-sync-card{grid-template-columns:1fr}.designer-bg-sync-card .dxm-btn{grid-column:auto}.designer-sync-toggle{max-width:none}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.book-inner-tabs').forEach(function (tabs) {
        const scope = tabs.getAttribute('data-book-inner-root') || '';
        tabs.querySelectorAll('[data-book-inner-tab]').forEach(function (button) {
            button.addEventListener('click', function () {
                const key = button.getAttribute('data-book-inner-tab');
                tabs.querySelectorAll('[data-book-inner-tab]').forEach(function (item) {
                    item.classList.toggle('is-active', item === button);
                });

                let cursor = tabs.nextElementSibling;
                while (cursor) {
                    if (cursor.classList && cursor.classList.contains('book-inner-tabs')) break;
                    if (cursor.classList && cursor.classList.contains('book-inner-panel')) {
                        cursor.classList.toggle('is-active', cursor.getAttribute('data-book-inner-panel') === key);
                    }
                    cursor = cursor.nextElementSibling;
                }
            });
        });
    });
});
</script>
@endpush

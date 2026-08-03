@extends('layouts.beginner')

@section('title', 'Create Item')
@section('eyebrow', 'Beginner Builder')
@section('page_title', 'Create Item')
@section('page_description', 'Create any reusable frontend item: quote card, short video entry, article card, book card, quiz item, watch card, button, banner, or custom link.')
@section('back_url', $returnUrl)
@section('form_title', 'Unified Item Builder')
@section('form_description', 'Use the tabs below to build the item cleanly without a long mixed form.')
@section('preview_description', 'Live preview summarizes item type, source, action, layout, animation, and media.')

@php
    $iconSvgMap = [];
    foreach ($icons as $icon) {
        $iconSvgMap[$icon->key] = $icon->svg ?? '';
    }

    $itemKinds = [
        'card' => 'General Card',
        'banner' => 'Image Banner',
        'button' => 'Button / Quick Link',
        'text' => 'Text / Notice',
        'quote' => 'Quote Card',
        'short_video' => 'Short Video Item',
        'article' => 'Article / Content Item',
        'book' => 'Book / Library Item',
        'quiz' => 'Quiz Item',
        'watch' => 'Watch / Live Stream Item',
        'external' => 'External Link Item',
        'ad_slot' => 'Ad Slot Placeholder',
        'custom' => 'Custom Item',
    ];

    $sourceTypes = [
        'manual' => 'Manual item values',
        'content_channel' => 'Content channel / articles',
        'quote_channel' => 'Quote channel',
        'short_video_channel' => 'Short video channel',
        'book_channel' => 'Books / Library',
        'quiz_channel' => 'Quiz engine',
        'watch_builder' => 'Watch Builder link',
        'external_link' => 'External link',
        'ad_slot' => 'Ad slot',
        'mixed' => 'Mixed content reference',
    ];

    $layoutTemplates = [
        'card' => 'Standard Card',
        'mini_card' => 'Mini Card',
        'hero_card' => 'Hero Card',
        'banner' => 'Banner',
        'quote_card' => 'Quote Design Card',
        'short_video_tile' => 'Short Video Tile',
        'reel_entry' => 'Reel Entry',
        'book_card' => 'Book Card',
        'quiz_card' => 'Quiz Card',
        'button_pill' => 'Button Pill',
        'text_chip' => 'Text Chip',
        'ad_placeholder' => 'Ad Placeholder',
    ];

    $cardStyles = [
        'auto' => 'Auto / Section Default',
        'gradient' => 'Gradient Card',
        'image_overlay' => 'Image Overlay',
        'clean_white' => 'Clean White Card',
        'dark_premium' => 'Dark Premium Card',
        'quote_poster' => 'Quote Poster',
        'short_thumbnail' => 'Short Thumbnail',
        'book_cover' => 'Book Cover',
        'outline' => 'Outline Card',
    ];

    $sizePresets = [
        'auto' => 'Auto',
        'wide_16_9' => 'Wide 16:9',
        'poster_4_5' => 'Poster 4:5',
        'square_1_1' => 'Square 1:1',
        'story_9_16' => 'Story/Reel 9:16',
        'compact' => 'Compact',
        'full_width' => 'Full Width',
    ];

    $animations = [
        'none' => 'No animation',
        'fade' => 'Fade in',
        'slide_up' => 'Slide up',
        'slide_left' => 'Slide left',
        'scale' => 'Soft scale',
        'pulse' => 'Pulse emphasis',
    ];
@endphp

@section('form')
    @if (session('status'))
        <div class="dxm-alert">{{ session('status') }}</div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.beginner.items.store') }}" id="itemCreateForm">
        @csrf

        <input type="hidden" name="return" value="{{ $returnTo }}">
        <input type="hidden" name="current_tab" value="{{ $currentTab ?? 'home' }}">

        <div class="builder-tabs" role="tablist" aria-label="Item builder tabs">
            <button type="button" class="builder-tab is-active" data-tab="basic">Basic</button>
            <button type="button" class="builder-tab" data-tab="source">Source</button>
            <button type="button" class="builder-tab" data-tab="media">Media</button>
            <button type="button" class="builder-tab" data-tab="action">Action</button>
            <button type="button" class="builder-tab" data-tab="display">Display</button>
            <button type="button" class="builder-tab" data-tab="motion">Motion</button>
            <button type="button" class="builder-tab" data-tab="advanced">Advanced</button>
        </div>

        <div class="builder-panel is-active" data-panel="basic">
            <div class="unified-form-grid">
                <div class="field">
                    <label for="section_id">Place Inside Section</label>
                    <select id="section_id" name="section_id">
                        @foreach ($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $prefillSectionId) === (string) $section->id)>
                                [{{ ucfirst($section->tab_key) }}] {{ $section->title }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="item_kind">Item Type</label>
                    <select id="item_kind" name="item_kind">
                        @foreach ($itemKinds as $value => $label)
                            <option value="{{ $value }}" @selected(old('item_kind', 'card') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small>Choose what this item represents. Frontend can later use this as the renderer hint.</small>
                </div>

                <div class="field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Item title preview">
                </div>

                <div class="field">
                    <label for="subtitle">Subtitle</label>
                    <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle') }}" placeholder="Optional supporting text">
                </div>

                <div class="field">
                    <label for="icon">Icon</label>
                    <select id="icon" name="icon">
                        <option value="">No icon</option>
                        @foreach ($icons as $icon)
                            <option value="{{ $icon->key }}" @selected(old('icon') === $icon->key)>{{ $icon->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="badge_text">Badge / Label</label>
                    <input id="badge_text" name="badge_text" type="text" value="{{ old('badge_text') }}" placeholder="Example: New, Featured, Live">
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="source">
            <div class="unified-form-grid">
                <div class="field">
                    <label for="source_type">Content Source</label>
                    <select id="source_type" name="source_type">
                        @foreach ($sourceTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('source_type', 'manual') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="source_bucket">Bucket / Group / Collection</label>
                    <input id="source_bucket" name="source_bucket" type="text" value="{{ old('source_bucket') }}" placeholder="Example: quote_dr_eneches_quotes, highlights, books">
                </div>

                <div class="field">
                    <label for="source_channel">Channel / Short Channel</label>
                    <input id="source_channel" name="source_channel" type="text" value="{{ old('source_channel') }}" placeholder="Example: inspire-shorts, teachings, weird-facts">
                </div>

                <div class="field">
                    <label for="content_id">Article / Content ID</label>
                    <input id="content_id" name="content_id" type="text" value="{{ old('content_id') }}" placeholder="Optional exact content ID">
                </div>

                <div class="field">
                    <label for="quote_id">Quote ID</label>
                    <input id="quote_id" name="quote_id" type="text" value="{{ old('quote_id') }}" placeholder="Optional exact quote ID">
                </div>

                <div class="field">
                    <label for="short_video_id">Short Video ID</label>
                    <input id="short_video_id" name="short_video_id" type="text" value="{{ old('short_video_id') }}" placeholder="Optional exact short video ID">
                </div>

                <div class="field">
                    <label for="book_id">Book ID</label>
                    <input id="book_id" name="book_id" type="text" value="{{ old('book_id') }}" placeholder="Optional exact book ID">
                </div>

                <div class="field">
                    <label for="quiz_set_id">Quiz Set / Level ID</label>
                    <input id="quiz_set_id" name="quiz_set_id" type="text" value="{{ old('quiz_set_id') }}" placeholder="Optional exact quiz set ID">
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="media">
            <div class="panel full">
                <h3>Image / Design / Thumbnail</h3>
                <div class="panel-grid">
                    <div class="field">
                        <label for="image_file">Quick Upload</label>
                        <input id="image_file" name="image_file" type="file" accept="image/*">
                        <small>Use this for item cards, quote designs, thumbnails, banners, or book covers.</small>
                    </div>

                    <div class="field">
                        <label for="image_url">Image URL / Path</label>
                        <input id="image_url" name="image_url" type="text" value="{{ old('image_url') }}" placeholder="Paste URL or select from Media Library">
                        <small>This field updates automatically when you select media.</small>
                    </div>

                    <div class="field full">
                        @include('admin.beginner.partials.media-picker', [
                            'pickerId' => 'itemCreateMediaCenter',
                            'inputId' => 'image_url',
                            'selectName' => 'media_asset_id',
                            'assets' => $mediaAssets,
                            'title' => 'Item Image Library',
                            'subtitle' => 'Browse, upload, preview, and select the image/design for this app item.',
                            'buttonLabel' => 'Open Image Library',
                            'uploadBucket' => 'item-images',
                            'uploadLabel' => 'Item Image',
                            'emptyText' => 'No item images found for this app yet. Upload one from the modal.'
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="action">
            <div class="panel full watch-source-panel">
                <h3>Watch Link Source</h3>
                <p class="watch-source-help">Optional. Use Watch Builder for the real stream/video URL while this item controls placement, image, title, layout, and style.</p>
                <div class="panel-grid">
                    <div class="field full">
                        <label for="watch_link_id">Select from Watch Builder</label>
                        <select id="watch_link_id" name="watch_link_id">
                            <option value="0">No linked watch source — use manual URL fields</option>
                            @foreach ($watchLinks as $watchLink)
                                <option value="{{ $watchLink['id'] }}" data-url="{{ e($watchLink['url']) }}" data-title="{{ e($watchLink['title']) }}" data-subtitle="{{ e($watchLink['subtitle']) }}" data-type="{{ e($watchLink['type']) }}" data-player="{{ e($watchLink['player']) }}" @selected((string) old('watch_link_id', 0) === (string) $watchLink['id'])>
                                    {{ $watchLink['title'] }} — {{ $watchLink['type'] ?: 'watch link' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="watch-source-preview full" id="watchSourcePreview"><strong>No watch source selected.</strong><span>Manual URL fields will be used until you choose a source.</span></div>
                </div>
            </div>

            <div class="panel full">
                <h3>Tap Action</h3>
                <div class="panel-grid">
                    <div class="field">
                        <label for="action_type">Action Type</label>
                        <select id="action_type" name="action_type">
                            <option value="route" @selected(old('action_type', $prefillActionType) === 'route')>Open Inner App Page</option>
                            <option value="webview" @selected(old('action_type', $prefillActionType) === 'webview')>Open Web Page Inside App</option>
                            <option value="external_url" @selected(old('action_type', $prefillActionType) === 'external_url')>Open External Website</option>
                            <option value="youtube_video" @selected(old('action_type', $prefillActionType) === 'youtube_video')>Open YouTube Video</option>
                            <option value="youtube_playlist" @selected(old('action_type', $prefillActionType) === 'youtube_playlist')>Open YouTube Playlist</option>
                            <option value="live_stream" @selected(old('action_type', $prefillActionType) === 'live_stream')>Open Live Stream</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="open_mode">Open Mode</label>
                        <select id="open_mode" name="open_mode">
                            <option value="push" @selected(old('open_mode', 'push') === 'push')>Push screen / keep back stack</option>
                            <option value="replace" @selected(old('open_mode') === 'replace')>Replace current screen</option>
                            <option value="modal" @selected(old('open_mode') === 'modal')>Open as modal</option>
                            <option value="external" @selected(old('open_mode') === 'external')>External handoff</option>
                        </select>
                    </div>

                    <div class="field">
                        <label for="route_key">Page Key / Route</label>
                        <input id="route_key" name="route_key" type="text" value="{{ old('route_key') }}" placeholder="Example: /sod/quotes, /short-videos, bible, notes">
                    </div>

                    <div class="field">
                        <label for="url">URL / Web / Live Link</label>
                        <input id="url" name="url" type="text" value="{{ old('url') }}" placeholder="https://...">
                    </div>

                    <div class="field full">
                        <label for="youtube">YouTube Link or ID</label>
                        <input id="youtube" name="youtube" type="text" value="{{ old('youtube') }}" placeholder="YouTube video or playlist link">
                    </div>
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="display">
            <div class="unified-form-grid">
                <div class="field">
                    <label for="layout_template">Item Template</label>
                    <select id="layout_template" name="layout_template">
                        @foreach ($layoutTemplates as $value => $label)
                            <option value="{{ $value }}" @selected(old('layout_template', 'card') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="card_style">Card Style</label>
                    <select id="card_style" name="card_style">
                        @foreach ($cardStyles as $value => $label)
                            <option value="{{ $value }}" @selected(old('card_style', 'auto') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="size_preset">Size Preset</label>
                    <select id="size_preset" name="size_preset">
                        @foreach ($sizePresets as $value => $label)
                            <option value="{{ $value }}" @selected(old('size_preset', 'auto') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="sort_order">Display Order</label>
                    <input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', 0) }}">
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="motion">
            <div class="unified-form-grid">
                <div class="field">
                    <label for="animation_style">Animation / Motion</label>
                    <select id="animation_style" name="animation_style">
                        @foreach ($animations as $value => $label)
                            <option value="{{ $value }}" @selected(old('animation_style', 'none') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <small>Stored for the Flutter renderer. It can be ignored safely until frontend alignment.</small>
                </div>

                <label class="check enabled">
                    <input type="checkbox" id="is_enabled" name="is_enabled" value="1" @checked(old('is_enabled', true))>
                    <span>Item enabled</span>
                </label>
            </div>
        </div>

        <div class="builder-panel" data-panel="advanced">
            <div class="builder-note">
                <strong>Advanced payload behavior</strong>
                <span>All source, display, and motion values are saved into <code>payload_json</code> under <code>item_builder</code>, <code>source</code>, and <code>display</code>. The frontend renderer can read these without database changes.</span>
            </div>
        </div>

        <div class="action-row">
            <a href="{{ $returnUrl }}" class="dxm-btn">← Back</a>
            <button type="submit" class="dxm-btn dxm-btn--primary">Create Item</button>
        </div>
    </form>
@endsection

@section('preview')
    <div class="preview-wrap">
        <div class="app-card" id="previewCard">
            <div class="preview-bg" id="itemPreviewImage"></div>
            <div class="preview-shade"></div>
            <div class="preview-badge" id="itemPreviewBadge"></div>
            <div class="preview-icon" id="itemPreviewIcon"></div>
            <div class="preview-content">
                <div class="preview-kicker" id="itemPreviewKind">General Card</div>
                <div class="preview-title" id="itemPreviewTitle">Item title preview</div>
                <div class="preview-subtitle" id="itemPreviewSubtitle"></div>
            </div>
        </div>
        <div class="preview-details">
            <div><strong>Source:</strong> <span id="itemPreviewSource">Manual</span></div>
            <div><strong>Bucket:</strong> <span id="itemPreviewBucket">—</span></div>
            <div><strong>Channel:</strong> <span id="itemPreviewChannel">—</span></div>
            <div><strong>Template:</strong> <span id="itemPreviewLayout">—</span></div>
            <div><strong>Action:</strong> <span id="itemPreviewAction"></span></div>
            <div><strong>Route:</strong> <span id="itemPreviewRoute"></span></div>
            <div><strong>Status:</strong> <span id="itemPreviewEnabled"></span></div>
        </div>
    </div>

    @include('admin.beginner.items._unified_item_builder_assets', ['iconSvgMap' => $iconSvgMap])
@endsection

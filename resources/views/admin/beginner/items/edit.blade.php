@extends('layouts.beginner')

@section('title', 'Edit Item')
@section('eyebrow', 'Beginner Builder')
@section('page_title', 'Edit Item')
@section('page_description', 'Update this item with the same unified tabbed builder used for every content type.')
@section('back_url', $returnUrl)
@section('advanced_url', '/admin/app-items/' . $item->id . '/edit')
@section('form_title', 'Unified Item Builder')
@section('form_description', 'Edit item source, media, action, display template, animation, and advanced metadata in one organized workflow.')
@section('preview_description', 'Live preview summarizes item type, source, action, layout, animation, and media.')

@php
    $payload = is_array($item->payload_json) ? $item->payload_json : [];
    $action = is_array($payload['action'] ?? null) ? $payload['action'] : [];
    $builder = is_array($payload['item_builder'] ?? null) ? $payload['item_builder'] : [];
    $source = is_array($payload['source'] ?? null) ? $payload['source'] : [];
    $display = is_array($payload['display'] ?? null) ? $payload['display'] : [];

    $resolvedActionType = old('action_type', $actionType ?? ($action['type'] ?? 'route'));
    $resolvedRouteKey = old('route_key', $routeKey ?? ($action['route_key'] ?? ''));
    $resolvedUrl = old('url', $action['url'] ?? $item->url ?? '');
    $resolvedYoutube = old('youtube', $action['youtube'] ?? '');
    $resolvedImageUrl = old('image_url', $item->image_url ?? '');

    $resolvedItemKind = old('item_kind', $builder['kind'] ?? 'card');
    $resolvedSourceType = old('source_type', $builder['source_type'] ?? $source['type'] ?? 'manual');
    $resolvedSourceBucket = old('source_bucket', $builder['source_bucket'] ?? $source['bucket'] ?? '');
    $resolvedSourceChannel = old('source_channel', $builder['source_channel'] ?? $source['channel'] ?? '');
    $resolvedContentId = old('content_id', $source['content_id'] ?? '');
    $resolvedQuoteId = old('quote_id', $source['quote_id'] ?? '');
    $resolvedShortVideoId = old('short_video_id', $source['short_video_id'] ?? '');
    $resolvedBookId = old('book_id', $source['book_id'] ?? '');
    $resolvedQuizSetId = old('quiz_set_id', $source['quiz_set_id'] ?? '');
    $resolvedLayoutTemplate = old('layout_template', $builder['layout_template'] ?? $display['layout_template'] ?? 'card');
    $resolvedCardStyle = old('card_style', $builder['card_style'] ?? $display['card_style'] ?? 'auto');
    $resolvedSizePreset = old('size_preset', $builder['size_preset'] ?? $display['size_preset'] ?? 'auto');
    $resolvedAnimationStyle = old('animation_style', $builder['animation_style'] ?? $display['animation_style'] ?? 'none');
    $resolvedOpenMode = old('open_mode', $builder['open_mode'] ?? $display['open_mode'] ?? 'push');
    $resolvedBadgeText = old('badge_text', $builder['badge_text'] ?? $display['badge_text'] ?? '');

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

    $bucketLabels = [
        'highlights' => 'Message Highlights',
        'wordification' => 'Wordification',
        'motivation' => 'Motivation',
        'inside_dunamis' => 'Inside Dunamis / Articles',
        'sod' => 'Seed of Destiny',
        'sod_quotes' => 'SOD Quotes',
    ];
    $haystack = strtolower(trim(($resolvedRouteKey ?: '') . ' ' . ($item->route ?: '') . ' ' . ($item->title ?: '') . ' ' . ($item->section?->key ?: '') . ' ' . $resolvedSourceBucket));
    $contentBucket = null;
    if (str_contains($haystack, 'highlight')) $contentBucket = 'highlights';
    elseif (str_contains($haystack, 'wordification')) $contentBucket = 'wordification';
    elseif (str_contains($haystack, 'motivation')) $contentBucket = 'motivation';
    elseif (str_contains($haystack, 'inside') || str_contains($haystack, 'article')) $contentBucket = 'inside_dunamis';
    elseif (str_contains($haystack, 'quote')) $contentBucket = $resolvedSourceBucket ?: 'sod_quotes';
    elseif (str_contains($haystack, 'sod') || str_contains($haystack, 'seed')) $contentBucket = 'sod';
    $contentBucketLabel = $contentBucket ? ($bucketLabels[$contentBucket] ?? ucfirst(str_replace('_', ' ', $contentBucket))) : null;
    $manageContentUrl = $contentBucket ? route('admin.beginner.content-posts.channel', ['bucket' => $contentBucket, 'item_id' => $item->id]) : null;
    $createContentUrl = $contentBucket ? route('admin.beginner.content-posts.create', ['bucket' => $contentBucket, 'item_id' => $item->id, 'return' => 'item']) : null;
@endphp

@section('form')
    @if (session('status'))
        <div class="dxm-alert">{{ session('status') }}</div>
    @endif

    @if ($contentBucket)
        <div class="content-workflow-box">
            <div class="workflow-copy">
                <span>Connected Content Channel</span>
                <strong>{{ $contentBucketLabel }}</strong>
                <small>Use these buttons to create or manage content under this item/channel.</small>
            </div>
            <div class="workflow-actions">
                <a href="{{ $manageContentUrl }}" class="dxm-btn">Manage Content</a>
                <a href="{{ $createContentUrl }}" class="dxm-btn dxm-btn--primary">Create Content</a>
            </div>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.beginner.items.update', $item) }}" id="itemEditorForm">
        @csrf
        @method('PUT')

        <input type="hidden" name="return" value="{{ $returnTo }}">
        <input type="hidden" name="current_tab" value="{{ $currentTab ?? $item->section->tab_key ?? 'home' }}">

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
                            <option value="{{ $section->id }}" @selected((string) old('section_id', $item->section_id) === (string) $section->id)>[{{ ucfirst($section->tab_key) }}] {{ $section->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="item_kind">Item Type</label>
                    <select id="item_kind" name="item_kind">
                        @foreach ($itemKinds as $value => $label)
                            <option value="{{ $value }}" @selected($resolvedItemKind === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title', $item->title) }}">
                </div>

                <div class="field">
                    <label for="subtitle">Subtitle</label>
                    <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $item->subtitle) }}">
                </div>

                <div class="field">
                    <label for="icon">Icon</label>
                    <select id="icon" name="icon">
                        <option value="">No icon</option>
                        @foreach ($icons as $icon)
                            <option value="{{ $icon->key }}" @selected(old('icon', $item->icon) === $icon->key)>{{ $icon->label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field">
                    <label for="badge_text">Badge / Label</label>
                    <input id="badge_text" name="badge_text" type="text" value="{{ $resolvedBadgeText }}" placeholder="Example: New, Featured, Live">
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="source">
            <div class="unified-form-grid">
                <div class="field"><label for="source_type">Content Source</label><select id="source_type" name="source_type">@foreach ($sourceTypes as $value => $label)<option value="{{ $value }}" @selected($resolvedSourceType === $value)>{{ $label }}</option>@endforeach</select></div>
                <div class="field"><label for="source_bucket">Bucket / Group / Collection</label><input id="source_bucket" name="source_bucket" type="text" value="{{ $resolvedSourceBucket }}" placeholder="Example: quote_dr_eneches_quotes, highlights, books"></div>
                <div class="field"><label for="source_channel">Channel / Short Channel</label><input id="source_channel" name="source_channel" type="text" value="{{ $resolvedSourceChannel }}" placeholder="Example: inspire-shorts, teachings, weird-facts"></div>
                <div class="field"><label for="content_id">Article / Content ID</label><input id="content_id" name="content_id" type="text" value="{{ $resolvedContentId }}"></div>
                <div class="field"><label for="quote_id">Quote ID</label><input id="quote_id" name="quote_id" type="text" value="{{ $resolvedQuoteId }}"></div>
                <div class="field"><label for="short_video_id">Short Video ID</label><input id="short_video_id" name="short_video_id" type="text" value="{{ $resolvedShortVideoId }}"></div>
                <div class="field"><label for="book_id">Book ID</label><input id="book_id" name="book_id" type="text" value="{{ $resolvedBookId }}"></div>
                <div class="field"><label for="quiz_set_id">Quiz Set / Level ID</label><input id="quiz_set_id" name="quiz_set_id" type="text" value="{{ $resolvedQuizSetId }}"></div>
            </div>
        </div>

        <div class="builder-panel" data-panel="media">
            <div class="panel full"><h3>Image / Design / Thumbnail</h3><div class="panel-grid">
                <div class="field"><label for="image_file">Quick Upload</label><input id="image_file" name="image_file" type="file" accept="image/*"><small>Use this for item cards, quote designs, thumbnails, banners, or book covers.</small></div>
                <div class="field"><label for="image_url">Image URL / Path</label><input id="image_url" name="image_url" type="text" value="{{ $resolvedImageUrl }}" placeholder="Paste URL or select from Media Library"><small>This field updates automatically when you select media.</small></div>
                <div class="field full">@include('admin.beginner.partials.media-picker', ['pickerId' => 'itemEditMediaCenter','inputId' => 'image_url','selectName' => 'media_asset_id','assets' => $mediaAssets,'title' => 'Item Image Library','subtitle' => 'Browse, upload, preview, and select the image/design for this app item.','buttonLabel' => 'Open Image Library','uploadBucket' => 'item-images','uploadLabel' => $item->title ?: 'Item Image','emptyText' => 'No item images found for this app yet. Upload one from the modal.'])</div>
                <label class="check full"><input type="checkbox" id="clear_image" name="clear_image" value="1"><span>Remove image from this item</span></label>
            </div></div>
        </div>

        <div class="builder-panel" data-panel="action">
            <div class="panel full watch-source-panel"><h3>Watch Link Source</h3><p class="watch-source-help">Optional. Use Watch Builder for the real stream/video URL while this item controls placement, image, title, layout, and style.</p><div class="panel-grid"><div class="field full"><label for="watch_link_id">Select from Watch Builder</label><select id="watch_link_id" name="watch_link_id"><option value="0">No linked watch source — use manual URL fields</option>@foreach ($watchLinks as $watchLink)<option value="{{ $watchLink['id'] }}" data-url="{{ e($watchLink['url']) }}" data-title="{{ e($watchLink['title']) }}" data-subtitle="{{ e($watchLink['subtitle']) }}" data-type="{{ e($watchLink['type']) }}" data-player="{{ e($watchLink['player']) }}" @selected((string) old('watch_link_id', $selectedWatchLinkId ?? 0) === (string) $watchLink['id'])>{{ $watchLink['title'] }} — {{ $watchLink['type'] ?: 'watch link' }}</option>@endforeach</select></div><div class="watch-source-preview full" id="watchSourcePreview"><strong>No watch source selected.</strong><span>Manual URL fields will be used until you choose a source.</span></div></div></div>
            <div class="panel full"><h3>Tap Action</h3><div class="panel-grid">
                <div class="field"><label for="action_type">Action Type</label><select id="action_type" name="action_type"><option value="route" @selected($resolvedActionType === 'route')>Open Inner App Page</option><option value="webview" @selected($resolvedActionType === 'webview')>Open Web Page Inside App</option><option value="external_url" @selected($resolvedActionType === 'external_url')>Open External Website</option><option value="youtube_video" @selected($resolvedActionType === 'youtube_video')>Open YouTube Video</option><option value="youtube_playlist" @selected($resolvedActionType === 'youtube_playlist')>Open YouTube Playlist</option><option value="live_stream" @selected($resolvedActionType === 'live_stream')>Open Live Stream</option></select></div>
                <div class="field"><label for="open_mode">Open Mode</label><select id="open_mode" name="open_mode"><option value="push" @selected($resolvedOpenMode === 'push')>Push screen / keep back stack</option><option value="replace" @selected($resolvedOpenMode === 'replace')>Replace current screen</option><option value="modal" @selected($resolvedOpenMode === 'modal')>Open as modal</option><option value="external" @selected($resolvedOpenMode === 'external')>External handoff</option></select></div>
                <div class="field"><label for="route_key">Page Key / Route</label><input id="route_key" name="route_key" type="text" value="{{ $resolvedRouteKey }}" placeholder="Example: /sod/quotes, /short-videos, bible, notes"></div>
                <div class="field"><label for="url">URL / Web / Live Link</label><input id="url" name="url" type="text" value="{{ $resolvedUrl }}" placeholder="https://..."></div>
                <div class="field full"><label for="youtube">YouTube Link or ID</label><input id="youtube" name="youtube" type="text" value="{{ $resolvedYoutube }}" placeholder="YouTube video or playlist link"></div>
            </div></div>
        </div>

        <div class="builder-panel" data-panel="display"><div class="unified-form-grid">
            <div class="field"><label for="layout_template">Item Template</label><select id="layout_template" name="layout_template">@foreach ($layoutTemplates as $value => $label)<option value="{{ $value }}" @selected($resolvedLayoutTemplate === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="card_style">Card Style</label><select id="card_style" name="card_style">@foreach ($cardStyles as $value => $label)<option value="{{ $value }}" @selected($resolvedCardStyle === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="size_preset">Size Preset</label><select id="size_preset" name="size_preset">@foreach ($sizePresets as $value => $label)<option value="{{ $value }}" @selected($resolvedSizePreset === $value)>{{ $label }}</option>@endforeach</select></div>
            <div class="field"><label for="sort_order">Display Order</label><input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $item->sort_order) }}"></div>
        </div></div>

        <div class="builder-panel" data-panel="motion"><div class="unified-form-grid">
            <div class="field"><label for="animation_style">Animation / Motion</label><select id="animation_style" name="animation_style">@foreach ($animations as $value => $label)<option value="{{ $value }}" @selected($resolvedAnimationStyle === $value)>{{ $label }}</option>@endforeach</select><small>Stored for the Flutter renderer. It can be ignored safely until frontend alignment.</small></div>
            <label class="check enabled"><input type="checkbox" id="is_enabled" name="is_enabled" value="1" @checked(old('is_enabled', $item->is_enabled))><span>Item enabled</span></label>
        </div></div>

        <div class="builder-panel" data-panel="advanced"><div class="builder-note"><strong>Advanced payload behavior</strong><span>All source, display, and motion values are saved into <code>payload_json</code> under <code>item_builder</code>, <code>source</code>, and <code>display</code>. The frontend renderer can read these without database changes.</span></div></div>

        <div class="action-row"><a href="{{ $returnUrl }}" class="dxm-btn">← Back</a><button type="submit" class="dxm-btn dxm-btn--primary">Save Changes</button></div>
    </form>

    <form method="POST" action="{{ route('admin.beginner.items.destroy', $item) }}" class="danger-box" onsubmit="return confirm('Delete this item permanently? This cannot be undone.');">
        @csrf
        @method('DELETE')
        <input type="hidden" name="return" value="{{ $returnTo }}">
        <input type="hidden" name="current_tab" value="{{ $currentTab ?? $item->section->tab_key ?? 'home' }}">
        <button type="submit" class="dxm-btn dxm-btn--danger">Delete Item</button>
    </form>
@endsection

@section('preview')
    <div class="preview-wrap"><div class="app-card" id="previewCard"><div class="preview-bg" id="itemPreviewImage"></div><div class="preview-shade"></div><div class="preview-badge" id="itemPreviewBadge"></div><div class="preview-icon" id="itemPreviewIcon"></div><div class="preview-content"><div class="preview-kicker" id="itemPreviewKind">General Card</div><div class="preview-title" id="itemPreviewTitle">Item title preview</div><div class="preview-subtitle" id="itemPreviewSubtitle"></div></div></div><div class="preview-details"><div><strong>Source:</strong> <span id="itemPreviewSource">Manual</span></div><div><strong>Bucket:</strong> <span id="itemPreviewBucket">—</span></div><div><strong>Channel:</strong> <span id="itemPreviewChannel">—</span></div><div><strong>Template:</strong> <span id="itemPreviewLayout">—</span></div><div><strong>Action:</strong> <span id="itemPreviewAction"></span></div><div><strong>Route:</strong> <span id="itemPreviewRoute"></span></div><div><strong>Status:</strong> <span id="itemPreviewEnabled"></span></div></div></div>
    @include('admin.beginner.items._unified_item_builder_assets', ['iconSvgMap' => $iconSvgMap])
@endsection

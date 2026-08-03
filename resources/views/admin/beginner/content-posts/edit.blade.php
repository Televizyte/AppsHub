@extends('layouts.beginner')

@php
    $meta = is_array($post->meta_json ?? null) ? $post->meta_json : [];

    $coverValue = old('cover_image_url', $post->cover_image_url);
    $coverSrc = null;

    if (is_string($coverValue) && trim($coverValue) !== '') {
        $coverSrc = \Illuminate\Support\Str::startsWith($coverValue, ['http://', 'https://', '/'])
            ? trim($coverValue)
            : \Illuminate\Support\Facades\Storage::disk('public')->url(trim($coverValue));
    }

    $bodyPreview = old('body_html', $post->body_html);
    $currentBucket = old('bucket', $post->bucket);
    $isShortVideo = in_array($currentBucket, ['short_videos', 'shorts', 'short', 'reels', 'reel'], true);
    $videoUrlValue = old('video_url', $meta['video_url'] ?? '');
@endphp

@section('title', 'Beginner Content Editor')
@section('eyebrow', 'Beginner Content Studio')
@section('page_title', $isShortVideo ? 'Edit Short Video' : 'Edit Content')
@section('page_description', $isShortVideo ? 'Update video details, thumbnail, author, publishing, and preview.' : 'This is the beginner editing page. It updates the same content safely, without sending you into the advanced interface.')
@section('back_url', $returnUrl)

@section('advanced_url',
    '/admin/content-posts/' . $post->id . '/edit?' . http_build_query([
        'from_beginner' => 1,
        'return' => $returnTo,
        'bucket' => old('bucket', $post->bucket),
        'item_id' => $itemId ?: null,
    ])
)

@section('form_title', $isShortVideo ? 'Short Video Details' : 'Edit Content')
@section('form_description', $isShortVideo ? 'Edit the video source, thumbnail, metadata, description, author, and publishing settings.' : 'Move through the tabs from Content to Publishing.')
@section('preview_description', $isShortVideo ? 'Video playback and thumbnail preview update here.' : 'Live preview updates as you edit.')

@section('studio_tabs')
    <button type="button" class="dxm-dock-tab is-active" data-dxm-tab="content">Content</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="media">Thumbnail</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="editor">Editor</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="author">Author</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="publishing">Publishing</button>
@endsection

@section('form')
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
        <div class="dxm-alert">
            <strong>{{ session('status') }}</strong>
        </div>
    @endif

    <form method="POST"
          action="{{ route('admin.beginner.content-posts.update', $post) }}"
          enctype="multipart/form-data"
          id="beginnerContentEditForm">

        @csrf
        @method('PUT')

        <input type="hidden" name="return" value="{{ $returnTo }}">
        <input type="hidden" name="item_id" value="{{ $itemId ?: '' }}">

        <div class="dxm-tab-panel is-active" data-dxm-tab-panel="content">
            <div class="dxm-section-card">
                <h3>{{ $isShortVideo ? 'Short Video Identity' : 'Content' }}</h3>
                <p>{{ $isShortVideo ? 'Set the title, caption, channel, and video source.' : 'Set the main title, subtitle, and content channel.' }}</p>

                <div class="dxm-grid">
                    <div class="dxm-col-6">
                        <label for="title">Title</label>
                        <input id="title" name="title" type="text" value="{{ old('title', $post->title) }}" required>
                    </div>

                    <div class="dxm-col-6">
                        <label for="subtitle">{{ $isShortVideo ? 'Caption / Summary' : 'Subtitle' }}</label>
                        <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle', $post->subtitle) }}">
                    </div>

                    <div class="dxm-col-12">
                        <label for="bucket">Content Channel</label>
                        <select id="bucket" name="bucket">
                            @foreach ($channels as $value => $label)
                                <option value="{{ $value }}" @selected(old('bucket', $post->bucket) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="dxm-section-card" id="shortVideoFields" style="{{ $isShortVideo ? 'border-color:rgba(34,211,238,.25);background:linear-gradient(135deg,rgba(8,47,73,.36),rgba(2,6,23,.46));' : 'display:none;' }}">
                <h3>Short Video Engine</h3>
                <p>Replace the local video or update the external video URL. Local upload overrides URL.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-12">
                        <label for="video_file">Replace Video File</label>
                        <input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime">
                        <small>Optional. Leave empty to keep the existing video.</small>
                    </div>

                    <div class="dxm-col-12">
                        <label for="video_url">Video URL / Existing Source</label>
                        <input id="video_url" name="video_url" type="text" value="{{ $videoUrlValue }}" placeholder="YouTube Shorts, MP4, HLS, or web embed URL">
                    </div>

                    <div class="dxm-col-6">
                        <label for="video_engine">Video Engine</label>
                        @php($videoEngine = old('video_engine', $meta['video_engine'] ?? 'auto'))
                        <select id="video_engine" name="video_engine">
                            <option value="auto" @selected($videoEngine === 'auto')>Auto Detect</option>
                            <option value="youtube" @selected($videoEngine === 'youtube')>YouTube</option>
                            <option value="mp4" @selected($videoEngine === 'mp4')>MP4</option>
                            <option value="webm" @selected($videoEngine === 'webm')>WebM</option>
                            <option value="mov" @selected($videoEngine === 'mov')>MOV</option>
                            <option value="hls" @selected($videoEngine === 'hls')>HLS</option>
                            <option value="web" @selected($videoEngine === 'web')>Web Embed</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="video_duration">Duration</label>
                        <input id="video_duration" name="video_duration" type="text" value="{{ old('video_duration', $meta['video_duration'] ?? '') }}" placeholder="Example: 0:58">
                    </div>

                    <div class="dxm-col-6">
                        <label for="video_aspect">Video Aspect</label>
                        @php($videoAspect = old('video_aspect', $meta['video_aspect'] ?? 'portrait'))
                        <select id="video_aspect" name="video_aspect">
                            <option value="portrait" @selected($videoAspect === 'portrait')>Portrait / Short Feed</option>
                            <option value="square" @selected($videoAspect === 'square')>Square</option>
                            <option value="landscape" @selected($videoAspect === 'landscape')>Landscape</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="feed_label">Feed Label</label>
                        <input id="feed_label" name="feed_label" type="text" value="{{ old('feed_label', $meta['feed_label'] ?? 'Short Feed') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="media">
            <div class="dxm-section-card">
                <h3>{{ $isShortVideo ? 'Custom Thumbnail' : 'Media Assets' }}</h3>
                <p>{{ $isShortVideo ? 'Choose or upload an attractive thumbnail for the video.' : 'Upload a new image, select from the media library, or paste a direct image URL.' }}</p>

                <div class="dxm-grid">
                    <div class="dxm-col-12">
                        <label for="cover_image_file">{{ $isShortVideo ? 'Upload Custom Thumbnail' : 'Quick Upload New Cover Image' }}</label>
                        <input type="file"
                               id="cover_image_file"
                               name="cover_image_file"
                               accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small>Optional. This becomes the official cover/thumbnail.</small>
                    </div>

                    <div class="dxm-col-12">
                        <label for="cover_image_url">Thumbnail / Cover Image URL</label>
                        <input id="cover_image_url"
                               name="cover_image_url"
                               type="text"
                               value="{{ old('cover_image_url', $post->cover_image_url) }}"
                               placeholder="Paste URL or select from Media Library">
                    </div>

                    <div class="dxm-col-12">
                        @include('admin.beginner.partials.media-picker', [
                            'pickerId' => 'contentEditMediaCenter',
                            'inputId' => 'cover_image_url',
                            'selectName' => 'media_asset_id',
                            'assets' => $mediaAssets,
                            'title' => $isShortVideo ? 'Thumbnail Library' : 'Content Cover Library',
                            'subtitle' => $isShortVideo ? 'Select a thumbnail for this short video.' : 'Browse, upload, preview, and select the cover image for this content.',
                            'buttonLabel' => $isShortVideo ? 'Open Thumbnail Library' : 'Open Cover Library',
                            'uploadBucket' => $isShortVideo ? 'content_short_videos_thumbnail' : 'content_' . old('bucket', $post->bucket),
                            'uploadLabel' => $post->title ?: 'Content Cover',
                            'emptyText' => 'No images found yet. Upload one from the modal.'
                        ])
                    </div>

                    <div class="dxm-col-12">
                        <label for="icon_key">Content Icon (Optional)</label>
                        <input id="icon_key"
                               name="icon_key"
                               type="text"
                               value="{{ old('icon_key', $meta['icon_key'] ?? '') }}">
                        <small>Example: flame, quote, book-open, sparkles.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="editor">
            <div class="dxm-section-card">
                <h3>{{ $isShortVideo ? 'Optional Description' : 'Editor' }}</h3>
                <p>{{ $isShortVideo ? 'Optional long description for this video.' : 'Write and format the body content.' }}</p>

                <div class="dxm-editor-wrap">
                    <div class="dxm-editor-top">
                        <div class="dxm-editor-title">
                            <strong>{{ $isShortVideo ? 'Description Body' : 'Content Body' }}</strong>
                            <span>Use the toolbar to format the content.</span>
                        </div>

                        @include('admin.beginner.shared.editor-toolbar')
                    </div>

                    <div class="dxm-editor-surface"
                         contenteditable="true"
                         data-dxm-rich-editor
                         data-placeholder="Start writing your content here...">{!! old('body_html', $post->body_html) !!}</div>

                    <textarea id="body_html" name="body_html" class="dxm-editor-hidden">{{ old('body_html', $post->body_html) }}</textarea>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="author">
            <div class="dxm-section-card">
                <h3>Author</h3>
                <p>Set publisher and fallback author information.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-6">
                        <label for="author_user_id">Publisher User</label>
                        <select id="author_user_id" name="author_user_id">
                            <option value="">Select a user</option>
                            @foreach($users as $user)
                                <option value="{{ $user->id }}" @selected((string) old('author_user_id', $post->author_user_id) === (string) $user->id)>
                                    {{ $user->name }} &lt;{{ $user->email }}&gt;
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="author_name">Author Name (Fallback)</label>
                        <input id="author_name" name="author_name" type="text" value="{{ old('author_name', $post->author_name) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="publishing">
            <div class="dxm-section-card">
                <h3>Publishing</h3>
                <p>Final publishing settings.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-4">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected(old('status', $post->status) === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dxm-col-4">
                        <label for="sort_order">Display Order</label>
                        <input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $post->sort_order) }}">
                    </div>

                    <div class="dxm-col-4">
                        <label class="dxm-checkbox" style="margin-top:24px;">
                            <input type="checkbox"
                                   id="is_featured"
                                   name="is_featured"
                                   value="1"
                                   @checked(old('is_featured', $post->is_featured))>
                            <span>Mark as featured</span>
                        </label>
                    </div>

                    <div class="dxm-col-6">
                        <label for="publish_at">Publish At</label>
                        <input id="publish_at"
                               name="publish_at"
                               type="datetime-local"
                               value="{{ old('publish_at', \App\Support\Scheduling\AdminScheduleTime::toLocalInput($post->publish_at ?? null)) }}">
                    </div>

                    <div class="dxm-col-6">
                        <label for="published_at">Published At</label>
                        <input id="published_at"
                               name="published_at"
                               type="datetime-local"
                               value="{{ old('published_at', \App\Support\Scheduling\AdminScheduleTime::toLocalInput($post->published_at ?? null)) }}">
                    </div>
                </div>
            </div>

            <div class="dxm-section-card" style="border-color:rgba(248,113,113,.34);background:rgba(127,29,29,.12);">
                <h3>Danger Zone</h3>
                <p>This permanently deletes this content post. Use only when the content was created by mistake.</p>

                <input type="hidden" name="_beginner_action" id="beginnerAction" value="">
                <input type="hidden" name="delete_confirmation" id="deleteConfirmation" value="">

                <button type="button"
                        class="dxm-btn"
                        style="border-color:rgba(248,113,113,.55);background:rgba(127,29,29,.35);"
                        id="deleteContentButton">
                    Delete This Content
                </button>
            </div>
        </div>

        <div class="dxm-submit">
            <a href="{{ $returnUrl }}" class="dxm-btn">Cancel</a>
            <button type="submit" class="dxm-btn dxm-btn--primary">Save Changes</button>
        </div>
    </form>
@endsection

@section('preview')
    <div class="dxm-preview">
        <div class="dxm-preview__cover" data-live="content-cover">
            @if ($coverSrc)
                <img src="{{ $coverSrc }}" alt="Cover preview">
            @else
                <div class="dxm-preview__empty">No cover image selected.</div>
            @endif
        </div>

        <div class="dxm-preview__card" data-short-video-preview style="{{ $isShortVideo ? '' : 'display:none;' }}">
            <div class="dxm-preview__title" style="font-size:16px;margin-bottom:10px;">Short Video Preview</div>

            <div id="videoPreviewPlayer" style="width:100%;max-width:260px;margin:0 auto;aspect-ratio:9/16;background:#000;border-radius:16px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                @if ($videoUrlValue)
                    <video src="{{ $videoUrlValue }}" controls muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>
                @else
                    <span style="color:rgba(255,255,255,.48);font-size:13px;">No video selected</span>
                @endif
            </div>

            <div class="dxm-preview__subtitle" data-live="video-url">{{ $videoUrlValue ?: 'No video URL yet.' }}</div>

            <div class="dxm-preview__meta">
                <span class="dxm-tag" data-live="video-engine">{{ old('video_engine', $meta['video_engine'] ?? 'auto') }}</span>
                <span class="dxm-tag" data-live="video-aspect">{{ old('video_aspect', $meta['video_aspect'] ?? 'portrait') }}</span>
                <span class="dxm-tag" data-live="video-duration">{{ old('video_duration', $meta['video_duration'] ?? 'Duration') }}</span>
            </div>
        </div>

        <div class="dxm-preview__card">
            <div class="dxm-preview__title" data-live="content-title">
                {{ old('title', $post->title) ?: 'Content title preview' }}
            </div>

            <div class="dxm-preview__subtitle" data-live="content-subtitle">
                {{ old('subtitle', $post->subtitle) }}
            </div>

            <div class="dxm-preview__meta">
                <span class="dxm-tag" data-live="content-channel">
                    {{ $channels[old('bucket', $post->bucket)] ?? ucfirst(old('bucket', $post->bucket)) }}
                </span>

                <span class="dxm-tag" data-live="content-status">
                    {{ $statusOptions[old('status', $post->status)] ?? ucfirst(old('status', $post->status)) }}
                </span>

                @if (old('is_featured', $post->is_featured))
                    <span class="dxm-tag">Featured</span>
                @endif
            </div>
        </div>

        <div class="dxm-preview__card">
            <div class="dxm-preview__title" style="font-size:16px;margin-bottom:10px;">Body Preview</div>
            <div class="dxm-preview-body" data-live="content-body">
                @if ($bodyPreview)
                    {!! $bodyPreview !!}
                @else
                    <div class="dxm-preview__empty">No body content yet.</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteButton = document.getElementById('deleteContentButton');
    const form = document.getElementById('beginnerContentEditForm');
    const actionInput = document.getElementById('beginnerAction');
    const confirmationInput = document.getElementById('deleteConfirmation');

    if (deleteButton && form && actionInput && confirmationInput) {
        deleteButton.addEventListener('click', function () {
            const firstConfirm = window.confirm('Are you sure you want to delete this content? This action cannot be undone.');

            if (!firstConfirm) return;

            const typed = window.prompt('Type DELETE to confirm permanent deletion.');

            if (typed !== 'DELETE') {
                alert('Delete cancelled. You must type DELETE exactly.');
                return;
            }

            actionInput.value = 'delete';
            confirmationInput.value = 'DELETE';
            form.submit();
        });
    }

    const bucket = document.getElementById('bucket');
    const shortFields = document.getElementById('shortVideoFields');
    const shortPreview = document.querySelector('[data-short-video-preview]');
    const videoUrl = document.getElementById('video_url');
    const videoFile = document.getElementById('video_file');
    const videoEngine = document.getElementById('video_engine');
    const videoAspect = document.getElementById('video_aspect');
    const videoDuration = document.getElementById('video_duration');
    const player = document.getElementById('videoPreviewPlayer');

    function isShortBucket(value) {
        return ['short_videos', 'shorts', 'short', 'reels', 'reel'].includes((value || '').toLowerCase());
    }

    function formatDuration(seconds) {
        seconds = Math.max(0, Math.floor(seconds || 0));
        const minutes = Math.floor(seconds / 60);
        const remaining = seconds % 60;
        return minutes + ':' + remaining.toString().padStart(2, '0');
    }

    function syncShortFields() {
        const active = isShortBucket(bucket ? bucket.value : '');

        if (shortFields) shortFields.style.display = active ? '' : 'none';
        if (shortPreview) shortPreview.style.display = active ? '' : 'none';
    }

    function renderVideoPlayer() {
        if (!player) return;

        const file = videoFile && videoFile.files && videoFile.files.length ? videoFile.files[0] : null;
        const url = videoUrl ? videoUrl.value.trim() : '';

        if (file) {
            const blobUrl = URL.createObjectURL(file);
            player.innerHTML = '<video id="previewVid" src="' + blobUrl + '" controls muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>';

            const previewVid = document.getElementById('previewVid');

            if (previewVid) {
                previewVid.addEventListener('loadedmetadata', function () {
                    if (videoDuration && previewVid.duration && Number.isFinite(previewVid.duration)) {
                        videoDuration.value = formatDuration(previewVid.duration);
                        syncVideoPreview();
                    }

                    if (videoAspect && previewVid.videoWidth && previewVid.videoHeight) {
                        if (previewVid.videoWidth > previewVid.videoHeight) {
                            videoAspect.value = 'landscape';
                        } else if (previewVid.videoWidth === previewVid.videoHeight) {
                            videoAspect.value = 'square';
                        } else {
                            videoAspect.value = 'portrait';
                        }

                        syncVideoPreview();
                    }
                });
            }

            return;
        }

        if (url.match(/\.(mp4|webm|mov|m4v)(\?.*)?$/i)) {
            player.innerHTML = '<video src="' + url.replace(/"/g, '&quot;') + '" controls muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>';
            return;
        }

        player.innerHTML = '<span style="color:rgba(255,255,255,.48);font-size:13px;">No video selected</span>';
    }

    function syncVideoPreview() {
        const urlTarget = document.querySelector('[data-live="video-url"]');
        const engineTarget = document.querySelector('[data-live="video-engine"]');
        const aspectTarget = document.querySelector('[data-live="video-aspect"]');
        const durationTarget = document.querySelector('[data-live="video-duration"]');

        if (urlTarget) urlTarget.textContent = videoUrl && videoUrl.value ? videoUrl.value : 'No video URL yet.';
        if (engineTarget) engineTarget.textContent = videoEngine && videoEngine.value ? videoEngine.value : 'auto';
        if (aspectTarget) aspectTarget.textContent = videoAspect && videoAspect.value ? videoAspect.value : 'portrait';
        if (durationTarget) durationTarget.textContent = videoDuration && videoDuration.value ? videoDuration.value : 'Duration';
    }

    function updateAllVideoUi() {
        syncShortFields();
        renderVideoPlayer();
        syncVideoPreview();
    }

    if (bucket) bucket.addEventListener('change', updateAllVideoUi);

    [videoUrl, videoFile, videoEngine, videoAspect, videoDuration].forEach(function (field) {
        if (field) {
            field.addEventListener('input', updateAllVideoUi);
            field.addEventListener('change', updateAllVideoUi);
        }
    });

    updateAllVideoUi();
});
</script>
@endpush

@once
@push('styles')
<style>
    [data-dxm-tab] { position: relative; z-index: 5; pointer-events: auto; cursor: pointer; }
    .dxm-tab-panel:not(.is-active) { display: none; }
</style>
@endpush
@endonce


@once
@push('scripts')
<script>
(function () {
    function activateDxmTab(tabName) {
        if (!tabName) return;

        document.querySelectorAll('[data-dxm-tab]').forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-dxm-tab') === tabName);
            tab.setAttribute('aria-selected', tab.getAttribute('data-dxm-tab') === tabName ? 'true' : 'false');
        });

        document.querySelectorAll('[data-dxm-tab-panel]').forEach(function (panel) {
            var isActive = panel.getAttribute('data-dxm-tab-panel') === tabName;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
            panel.style.display = isActive ? '' : 'none';
        });

        try {
            window.localStorage.setItem('dxm_beginner_studio_active_tab:' + window.location.pathname, tabName);
        } catch (e) {}
    }

    function initDxmTabs() {
        var firstActive = document.querySelector('[data-dxm-tab].is-active');
        var firstTab = document.querySelector('[data-dxm-tab]');
        var tabName = firstActive ? firstActive.getAttribute('data-dxm-tab') : (firstTab ? firstTab.getAttribute('data-dxm-tab') : null);

        try {
            var saved = window.localStorage.getItem('dxm_beginner_studio_active_tab:' + window.location.pathname);
            if (saved && document.querySelector('[data-dxm-tab="' + CSS.escape(saved) + '"]')) {
                tabName = saved;
            }
        } catch (e) {}

        activateDxmTab(tabName);
    }

    document.addEventListener('click', function (event) {
        var button = event.target && event.target.closest ? event.target.closest('[data-dxm-tab]') : null;
        if (!button) return;

        event.preventDefault();
        event.stopPropagation();
        activateDxmTab(button.getAttribute('data-dxm-tab'));
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDxmTabs);
    } else {
        initDxmTabs();
    }
})();
</script>
@endpush
@endonce


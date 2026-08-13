@extends('layouts.beginner')

@section('title', 'Beginner Content Creator')
@section('eyebrow', 'Beginner Content Studio')
@section('page_title', isset($bucketLabel) ? 'Create ' . $bucketLabel : 'Create Content')
@section('page_description', isset($item) && $item ? 'Create content directly under: ' . $item->title : 'Create content inside the beginner workspace without entering the advanced interface.')
@section('back_url', $returnUrl ?? '/admin/content-channels')
@section('form_title', isset($bucket) && $bucket === 'short_videos' ? 'Short Video Details' : 'Create Content')
@section('form_description', isset($bucket) && $bucket === 'short_videos' ? 'Upload video, set metadata, choose a thumbnail, and publish safely.' : (!empty($lockedBucket) ? 'This content channel is already selected from the card you opened.' : 'Move through the tabs from Content to Publishing.'))
@section('preview_description', isset($bucket) && $bucket === 'short_videos' ? 'Video preview, thumbnail, and upload progress appear here.' : 'Live preview updates as you type.')

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

    @if (!empty($item))
        <div class="context-box">
            <div>
                <span class="context-eyebrow">Creating under card</span>
                <strong>{{ $item->title }}</strong>
                <small>{{ $item->section?->title }} • {{ $bucketLabel ?? 'Content' }}</small>
            </div>

            <a href="{{ $returnUrl }}" class="dxm-btn">Back to Card</a>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.beginner.content-posts.store') }}" enctype="multipart/form-data" id="beginnerContentCreateForm">
        @csrf

        <input type="hidden" name="return" value="{{ $returnTo ?? (!empty($item) ? 'item' : 'channels') }}">
        <input type="hidden" name="item_id" value="{{ $itemId ?? optional($item ?? null)->id }}">

        <div class="dxm-tab-panel is-active" data-dxm-tab-panel="content">
            <div class="dxm-section-card">
                <h3>{{ isset($bucket) && $bucket === 'short_videos' ? 'Short Video Identity' : 'Content' }}</h3>
                <p>{{ isset($bucket) && $bucket === 'short_videos' ? 'Set the title, caption, and video source.' : 'Set the main title, summary, and content channel.' }}</p>

                <div class="dxm-grid">
                    <div class="dxm-col-6">
                        <label for="title">Title</label>
                        <input id="title" name="title" type="text" value="{{ old('title') }}" required>
                    </div>

                    <div class="dxm-col-6">
                        <label for="subtitle">{{ isset($bucket) && $bucket === 'short_videos' ? 'Caption / Summary' : 'Subtitle / Summary' }}</label>
                        <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle') }}">
                    </div>

                    <div class="dxm-col-12">
                        <label for="bucket">Content Channel</label>

                        @if (!empty($lockedBucket))
                            <input type="hidden" id="bucket" name="bucket" value="{{ $bucket }}">
                            <input type="text" value="{{ $bucketLabel }}" disabled>
                            <small>This is locked because you opened this from a specific channel.</small>
                        @else
                            <select id="bucket" name="bucket">
                                @foreach($channels as $k => $v)
                                    <option value="{{ $k }}" @selected(old('bucket', $bucket ?? request('bucket')) === $k)>
                                        {{ $v }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
            </div>

            <div class="dxm-section-card" id="shortVideoFields" style="display:none;border-color:rgba(34,211,238,.25);background:linear-gradient(135deg,rgba(8,47,73,.36),rgba(2,6,23,.46));">
                <h3>Short Video Engine</h3>
                <p>Upload a local video or paste a direct video URL. Local upload overrides URL.</p>

                <div class="dxm-grid">
                    <div class="dxm-col-12">
                        <label for="video_file">Upload Video</label>
                        <input id="video_file" name="video_file" type="file" accept="video/mp4,video/webm,video/quicktime">
                        <small>Supported: MP4, WebM, MOV. Server limit is 200MB.</small>
                    </div>

                    <div class="dxm-col-12">
                        <label for="video_url">Video URL / External Source</label>
                        <input id="video_url" name="video_url" type="text" value="{{ old('video_url') }}" placeholder="YouTube Shorts, MP4, HLS, or web embed URL">
                    </div>

                    <div class="dxm-col-6">
                        <label for="video_engine">Video Engine</label>
                        <select id="video_engine" name="video_engine">
                            <option value="auto" @selected(old('video_engine', 'auto') === 'auto')>Auto Detect</option>
                            <option value="youtube" @selected(old('video_engine') === 'youtube')>YouTube</option>
                            <option value="mp4" @selected(old('video_engine') === 'mp4')>MP4</option>
                            <option value="webm" @selected(old('video_engine') === 'webm')>WebM</option>
                            <option value="mov" @selected(old('video_engine') === 'mov')>MOV</option>
                            <option value="hls" @selected(old('video_engine') === 'hls')>HLS</option>
                            <option value="web" @selected(old('video_engine') === 'web')>Web Embed</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="video_duration">Duration</label>
                        <input id="video_duration" name="video_duration" type="text" value="{{ old('video_duration') }}" placeholder="Auto detected, e.g. 1:04">
                    </div>

                    <div class="dxm-col-6">
                        <label for="video_aspect">Video Aspect</label>
                        <select id="video_aspect" name="video_aspect">
                            <option value="portrait" @selected(old('video_aspect', 'portrait') === 'portrait')>Portrait / Short Feed</option>
                            <option value="square" @selected(old('video_aspect') === 'square')>Square</option>
                            <option value="landscape" @selected(old('video_aspect') === 'landscape')>Landscape</option>
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="feed_label">Feed Label</label>
                        <input id="feed_label" name="feed_label" type="text" value="{{ old('feed_label', 'Short Feed') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="media">
            <div class="dxm-section-card">
                <h3>{{ isset($bucket) && $bucket === 'short_videos' ? 'Custom Thumbnail' : 'Media Assets' }}</h3>
                <p>{{ isset($bucket) && $bucket === 'short_videos' ? 'Choose an attractive thumbnail. If empty, the video preview still works, but no official thumbnail is saved.' : 'Upload a new image, select from the media library, or paste a direct image URL.' }}</p>

                <div class="dxm-grid">
                    <div class="dxm-col-12">
                        <label for="cover_image_file">{{ isset($bucket) && $bucket === 'short_videos' ? 'Upload Custom Thumbnail' : 'Quick Upload Cover Image' }}</label>
                        <input type="file" id="cover_image_file" name="cover_image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                        <small>Optional. This becomes the official thumbnail/cover image.</small>
                    </div>

                    <div class="dxm-col-12">
                        <label for="cover_image_url">Thumbnail / Cover Image URL</label>
                        <input id="cover_image_url" name="cover_image_url" type="text" value="{{ old('cover_image_url') }}" placeholder="Paste URL or select from Media Library">
                        <small>This field updates automatically when you select an image.</small>
                    </div>

                    <div class="dxm-col-12">
                        @include('admin.beginner.partials.media-picker', [
                            'pickerId' => 'contentCreateMediaCenter',
                            'inputId' => 'cover_image_url',
                            'selectName' => 'media_asset_id',
                            'assets' => $mediaAssets,
                            'title' => isset($bucket) && $bucket === 'short_videos' ? 'Thumbnail Library' : 'Content Cover Library',
                            'subtitle' => isset($bucket) && $bucket === 'short_videos' ? 'Browse, upload, preview, and select a thumbnail for this short video.' : 'Browse, upload, preview, and select the cover image for this content.',
                            'buttonLabel' => isset($bucket) && $bucket === 'short_videos' ? 'Open Thumbnail Library' : 'Open Cover Library',
                            'uploadBucket' => isset($bucket) && $bucket === 'short_videos' ? 'content_short_videos_thumbnail' : 'content_' . ($bucket ?? request('bucket', 'content')),
                            'uploadLabel' => $bucketLabel ?? 'Content Cover',
                            'emptyText' => 'No images found yet. Upload one from the modal.'
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="editor">
            <div class="dxm-section-card">
                <h3>{{ isset($bucket) && $bucket === 'short_videos' ? 'Optional Description' : 'Editor' }}</h3>
                <p>{{ isset($bucket) && $bucket === 'short_videos' ? 'Optional long description for the short video.' : 'Write and format the body content.' }}</p>

                <div class="dxm-editor-wrap">
                    <div class="dxm-editor-top">
                        <div class="dxm-editor-title">
                            <strong>{{ isset($bucket) && $bucket === 'short_videos' ? 'Description Body' : 'Content Body' }}</strong>
                            <span>Use the toolbar to format the content.</span>
                        </div>

                        @include('admin.beginner.shared.editor-toolbar')
                    </div>

                    <div class="dxm-editor-surface" contenteditable="true" data-dxm-rich-editor data-placeholder="Start writing here..."></div>
                    <textarea id="body_html" name="body_html" class="dxm-editor-hidden">{{ old('body_html') }}</textarea>
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
                                <option value="{{ $user->id }}" @selected((string) old('author_user_id') === (string) $user->id)>
                                    {{ $user->name }} &lt;{{ $user->email }}&gt;
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dxm-col-6">
                        <label for="author_name">Author Name (Fallback)</label>
                        <input id="author_name" name="author_name" type="text" value="{{ old('author_name') }}">
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
                            @foreach($statusOptions as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', 'draft') === $k)>
                                    {{ $v }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="dxm-col-4">
                        <label for="sort_order">Display Order</label>
                        <input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', 0) }}">
                    </div>

                    <div class="dxm-col-4">
                        <label for="publish_at">Schedule At ({{ \App\Support\Scheduling\AdminScheduleTime::timezone() }})</label>
                        <input id="publish_at" name="publish_at" type="datetime-local" value="{{ old('publish_at') }}">
                    </div>

                    <div class="dxm-col-4">
                        <label class="dxm-checkbox" style="margin-top:24px;">
                            <input type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured'))>
                            <span>Mark as featured</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-submit">
            <a href="{{ $returnUrl ?? '/admin/content-channels' }}" class="dxm-btn">Cancel</a>
            <button type="submit" class="dxm-btn dxm-btn--primary" id="createSubmitButton">Create Content</button>
        </div>
    </form>
@endsection

@section('preview')
    <div class="dxm-preview">
        <div class="dxm-preview__card" id="uploadProgressCard" style="display:none;border-color:rgba(34,211,238,.38);">
            <div class="dxm-preview__title" style="font-size:15px;">Uploading...</div>
            <div style="height:10px;border-radius:999px;background:rgba(255,255,255,.08);overflow:hidden;margin-top:12px;">
                <div id="uploadProgressBar" style="width:0%;height:100%;background:linear-gradient(90deg,#22d3ee,#a855f7);transition:width .15s ease;"></div>
            </div>
            <div class="dxm-preview__subtitle" id="uploadProgressText">Preparing upload...</div>
        </div>

        <div class="dxm-preview__cover" data-live="content-cover">
            <div class="dxm-preview__empty">No thumbnail selected.</div>
        </div>

        <div class="dxm-preview__card" data-short-video-preview style="display:none;">
            <div class="dxm-preview__title" style="font-size:16px;margin-bottom:10px;">Short Video Preview</div>
            <div id="videoPreviewPlayer" style="width:100%;max-width:260px;margin:0 auto;aspect-ratio:9/16;background:#000;border-radius:16px;overflow:hidden;display:flex;align-items:center;justify-content:center;">
                <span style="color:rgba(255,255,255,.48);font-size:13px;">No video selected</span>
            </div>
            <div class="dxm-preview__subtitle" data-live="video-url">No video URL yet.</div>
            <div class="dxm-preview__subtitle" data-live="video-file-name" style="margin-top:8px;"></div>
            <div class="dxm-preview__meta">
                <span class="dxm-tag" data-live="video-engine">Auto Detect</span>
                <span class="dxm-tag" data-live="video-aspect">Portrait</span>
                <span class="dxm-tag" data-live="video-duration">Duration</span>
            </div>
        </div>

        <div class="dxm-preview__card">
            <div class="dxm-preview__title" data-live="content-title">Content title preview</div>
            <div class="dxm-preview__subtitle" data-live="content-subtitle"></div>

            <div class="dxm-preview__meta">
                <span class="dxm-tag" data-live="content-channel">{{ $bucketLabel ?? ($channels[old('bucket', request('bucket', 'motivation'))] ?? 'Channel') }}</span>
                <span class="dxm-tag" data-live="content-status">{{ $statusOptions[old('status', 'draft')] ?? 'Draft' }}</span>
            </div>
        </div>

        <div class="dxm-preview__card">
            <div class="dxm-preview__title" style="font-size:16px;margin-bottom:10px;">Body Preview</div>
            <div class="dxm-preview-body" data-live="content-body">
                <div class="dxm-preview__empty">No body content yet.</div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('beginnerContentCreateForm');
    const submitButton = document.getElementById('createSubmitButton');
    const bucket = document.getElementById('bucket');
    const shortFields = document.getElementById('shortVideoFields');
    const shortPreview = document.querySelector('[data-short-video-preview]');
    const videoUrl = document.getElementById('video_url');
    const videoFile = document.getElementById('video_file');
    const videoEngine = document.getElementById('video_engine');
    const videoAspect = document.getElementById('video_aspect');
    const videoDuration = document.getElementById('video_duration');
    const player = document.getElementById('videoPreviewPlayer');
    const progressCard = document.getElementById('uploadProgressCard');
    const progressBar = document.getElementById('uploadProgressBar');
    const progressText = document.getElementById('uploadProgressText');

    function isShortBucket(value) {
        return ['short_videos', 'shorts', 'short', 'reels', 'reel'].includes((value || '').toLowerCase());
    }

    function formatDuration(seconds) {
        seconds = Math.max(0, Math.floor(seconds || 0));
        const minutes = Math.floor(seconds / 60);
        const remaining = seconds % 60;
        return minutes + ':' + remaining.toString().padStart(2, '0');
    }

    function updateProgress(percent, text) {
        if (progressCard) progressCard.style.display = '';
        if (progressBar) progressBar.style.width = Math.max(0, Math.min(100, percent)) + '%';
        if (progressText) progressText.textContent = text || (percent + '% uploaded');
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
        const fileTarget = document.querySelector('[data-live="video-file-name"]');
        const engineTarget = document.querySelector('[data-live="video-engine"]');
        const aspectTarget = document.querySelector('[data-live="video-aspect"]');
        const durationTarget = document.querySelector('[data-live="video-duration"]');

        const file = videoFile && videoFile.files && videoFile.files.length ? videoFile.files[0] : null;

        if (urlTarget) {
            urlTarget.textContent = file ? 'Local upload selected. This will override URL.' : (videoUrl && videoUrl.value ? videoUrl.value : 'No video URL yet.');
        }

        if (fileTarget) {
            fileTarget.textContent = file ? ('Selected file: ' + file.name + ' (' + Math.round(file.size / 1024 / 1024) + 'MB)') : '';
        }

        if (engineTarget) engineTarget.textContent = videoEngine && videoEngine.value ? videoEngine.value : 'auto';
        if (aspectTarget) aspectTarget.textContent = videoAspect && videoAspect.value ? videoAspect.value : 'portrait';
        if (durationTarget) durationTarget.textContent = videoDuration && videoDuration.value ? videoDuration.value : 'Duration';
    }

    function updateShortVideoUi() {
        syncShortFields();
        renderVideoPlayer();
        syncVideoPreview();
    }

    if (bucket) bucket.addEventListener('change', updateShortVideoUi);

    [videoUrl, videoFile, videoEngine, videoAspect, videoDuration].forEach(function (field) {
        if (field) {
            field.addEventListener('input', updateShortVideoUi);
            field.addEventListener('change', updateShortVideoUi);
        }
    });

    if (form) {
        form.addEventListener('submit', function (event) {
            event.preventDefault();

            const formData = new FormData(form);
            const xhr = new XMLHttpRequest();

            updateProgress(2, 'Preparing upload...');

            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Uploading...';
            }

            xhr.open('POST', form.getAttribute('action'), true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.setRequestHeader('Accept', 'application/json');

            xhr.upload.addEventListener('progress', function (e) {
                if (!e.lengthComputable) {
                    updateProgress(15, 'Uploading...');
                    return;
                }

                const percent = Math.round((e.loaded / e.total) * 100);
                updateProgress(percent, percent + '% uploaded');
            });

            xhr.onload = function () {
                let response = null;

                try {
                    response = JSON.parse(xhr.responseText || '{}');
                } catch (e) {
                    response = null;
                }

                if (xhr.status >= 200 && xhr.status < 300 && response && response.ok) {
                    updateProgress(100, 'Upload complete. Redirecting...');

                    setTimeout(function () {
                        window.location.href = response.redirect_url || '{{ $returnUrl ?? '/admin/content-channels' }}';
                    }, 700);

                    return;
                }

                let message = 'Upload failed. Please check the form and try again.';

                if (response && response.message) {
                    message = response.message;
                }

                if (response && response.errors) {
                    message = Object.values(response.errors).flat().join(' ');
                }

                updateProgress(0, message);

                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Create Content';
                }
            };

            xhr.onerror = function () {
                updateProgress(0, 'Network/server error. Upload did not complete.');

                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Create Content';
                }
            };

            xhr.send(formData);
        });
    }

    updateShortVideoUi();
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

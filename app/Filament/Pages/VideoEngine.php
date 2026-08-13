<?php

namespace App\Filament\Pages;

use App\Models\App;
use App\Models\MediaAsset;
use App\Models\Video;
use App\Models\VideoChannel;
use App\Models\VideoPlaylist;
use App\Models\VideoPlaylistItem;
use App\Support\ActiveApp;
use App\Support\AdminAccess;
use App\Support\Video\VideoDomainGuard;
use App\Support\Video\MediaAssetPublicUrl;
use App\Support\Video\VideoPlaybackPayload;
use App\Support\Video\VideoSourceContract;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;

class VideoEngine extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-film';
    protected static ?string $navigationLabel = 'Video Engine';
    protected static ?string $navigationGroup = 'Shared Engines';
    protected static ?int $navigationSort = 14;
    protected static ?string $slug = 'video-engine';
    protected static string $view = 'filament.pages.video-engine';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('video_engine');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('video_engine');
    }

    public ?int $activeAppId = null;
    public string $activeAppName = '';
    public string $activeTab = 'overview';
    public string $workspaceMode = 'index';
    public string $editorTab = 'details';
    public string $libraryView = 'grid';
    public string $search = '';
    public string $statusFilter = 'all';
    public string $sourceFilter = 'all';
    public string $playlistVideoSearch = '';
    public ?int $editingChannelId = null;
    public ?int $editingPlaylistId = null;
    public ?int $editingVideoId = null;
    public array $channelForm = [];
    public array $playlistForm = [];
    public array $videoForm = [];

    protected $queryString = [
        'activeTab' => ['as' => 'tab', 'except' => 'overview'],
        'workspaceMode' => ['as' => 'workspace', 'except' => 'index'],
        'editorTab' => ['as' => 'editor_tab', 'except' => 'details'],
        'libraryView' => ['as' => 'view', 'except' => 'grid'],
        'search' => ['as' => 'search', 'except' => ''],
        'statusFilter' => ['as' => 'status', 'except' => 'all'],
        'sourceFilter' => ['as' => 'source', 'except' => 'all'],
        'playlistVideoSearch' => ['as' => 'video_search', 'except' => ''],
        'editingChannelId' => ['as' => 'channel', 'except' => null],
        'editingPlaylistId' => ['as' => 'playlist', 'except' => null],
        'editingVideoId' => ['as' => 'video', 'except' => null],
    ];

    public function mount(): void
    {
        $this->activeAppId = ActiveApp::selectedId();
        $this->activeAppName = $this->activeAppId
            ? (string) (App::query()->whereKey($this->activeAppId)->value('name') ?? '')
            : '';
        $this->resetForms();

        $requestedTab = (string) request()->query('tab', $this->activeTab);
        $this->activeTab = in_array($requestedTab, self::tabs(), true) ? $requestedTab : 'overview';

        if (! $this->activeAppId) {
            $this->workspaceMode = 'index';
            $this->editingChannelId = $this->editingPlaylistId = $this->editingVideoId = null;
            return;
        }

        if ($this->workspaceMode === 'channel_editor' && $this->editingChannelId) $this->editChannel($this->editingChannelId);
        if ($this->workspaceMode === 'playlist_editor' && $this->editingPlaylistId) $this->editPlaylist($this->editingPlaylistId);
        if ($this->workspaceMode === 'video_editor' && $this->editingVideoId) $this->editVideo($this->editingVideoId);
    }

    public static function tabs(): array
    {
        return ['overview', 'videos', 'channels', 'playlists', 'preview'];
    }

    public function selectTab(string $tab): void
    {
        $this->activeTab = in_array($tab, self::tabs(), true) ? $tab : 'overview';
        $this->closeEditor();
    }

    public function closeEditor(): void
    {
        $this->workspaceMode = 'index';
        $this->editingChannelId = $this->editingPlaylistId = $this->editingVideoId = null;
    }

    public function selectEditorTab(string $tab): void
    {
        $allowed = match ($this->workspaceMode) {
            'video_editor' => ['details', 'source', 'media', 'publishing', 'preview'],
            'channel_editor' => ['details', 'media', 'publishing', 'preview'],
            'playlist_editor' => ['details', 'videos', 'publishing', 'preview'],
            default => ['details'],
        };
        $this->editorTab = in_array($tab, $allowed, true) ? $tab : 'details';
    }

    public function setLibraryView(string $view): void
    {
        $this->libraryView = in_array($view, ['grid', 'list'], true) ? $view : 'grid';
    }

    public function createChannel(): void
    {
        if (! $this->requireActiveApp()) return;
        $this->resetChannelForm();
        $this->editingChannelId = null;
        $this->editorTab = 'details';
        $this->workspaceMode = 'channel_editor';
        $this->activeTab = 'channels';
    }

    public function editChannel(int $id): void
    {
        if (! $this->requireActiveApp()) return;
        $channel = $this->owned(VideoChannel::query(), $id)->first();
        if (! $channel) { $this->notFound('Video channel'); return; }
        $this->editingChannelId = $channel->id;
        $this->editorTab = in_array($this->editorTab, ['details', 'media', 'publishing', 'preview'], true) ? $this->editorTab : 'details';
        $this->channelForm = [
            'title' => $channel->title, 'slug' => $channel->slug, 'description' => $channel->description,
            'thumbnail_media_asset_id' => $channel->thumbnail_media_asset_id,
            'status' => $channel->status, 'visibility' => $channel->visibility,
            'is_featured' => (bool) $channel->is_featured, 'sort_order' => (int) $channel->sort_order,
        ];
        $this->workspaceMode = 'channel_editor';
        $this->activeTab = 'channels';
    }

    public function saveChannel(): void
    {
        if (! $this->requireActiveApp()) return;
        $data = $this->validate(['channelForm.title' => 'required|string|max:255', 'channelForm.slug' => 'nullable|string|max:255', 'channelForm.description' => 'nullable|string', 'channelForm.thumbnail_media_asset_id' => 'nullable|integer|min:1', 'channelForm.status' => 'required|in:draft,published,archived', 'channelForm.visibility' => 'required|in:public,private,unlisted', 'channelForm.is_featured' => 'boolean', 'channelForm.sort_order' => 'integer|min:0'])['channelForm'];
        $thumbnailId = $this->ownedMediaId($data['thumbnail_media_asset_id'] ?? null);
        if (($data['thumbnail_media_asset_id'] ?? null) && ! $thumbnailId) { $this->ownershipError('thumbnail'); return; }
        $channel = $this->editingChannelId ? $this->owned(VideoChannel::query(), $this->editingChannelId)->first() : new VideoChannel(['app_id' => $this->activeAppId]);
        if (! $channel) { $this->notFound('Video channel'); return; }
        $channel->fill(array_merge($data, ['slug' => trim((string) ($data['slug'] ?? '')) ?: Str::slug($data['title']), 'thumbnail_media_asset_id' => $thumbnailId]));
        $channel->settings_json = self::preserveAdvancedJson($channel->settings_json, ['last_beginner_edit' => 'video_engine']);
        $channel->save();
        $this->editingChannelId = (int) $channel->id;
        $this->workspaceMode = 'channel_editor';
        $this->activeTab = 'channels';
        $this->saved('Video channel');
        $this->dispatch('video-engine-keep-position');
    }

    public function createPlaylist(): void
    {
        if (! $this->requireActiveApp()) return;
        $this->resetPlaylistForm();
        $this->editingPlaylistId = null;
        $this->editorTab = 'details';
        $this->workspaceMode = 'playlist_editor';
        $this->activeTab = 'playlists';
    }

    public function editPlaylist(int $id): void
    {
        if (! $this->requireActiveApp()) return;
        $playlist = $this->owned(VideoPlaylist::query(), $id)->with('items')->first();
        if (! $playlist) { $this->notFound('Video playlist'); return; }
        $this->editingPlaylistId = $playlist->id;
        $this->editorTab = in_array($this->editorTab, ['details', 'videos', 'publishing', 'preview'], true) ? $this->editorTab : 'details';
        $this->playlistForm = [
            'video_channel_id' => $playlist->video_channel_id, 'title' => $playlist->title,
            'slug' => $playlist->slug, 'description' => $playlist->description,
            'provider' => $playlist->provider, 'provider_playlist_id' => $playlist->provider_playlist_id,
            'external_url' => $playlist->external_url, 'thumbnail_media_asset_id' => $playlist->thumbnail_media_asset_id,
            'status' => $playlist->status, 'visibility' => $playlist->visibility,
            'is_featured' => (bool) $playlist->is_featured, 'sort_order' => (int) $playlist->sort_order,
            'video_ids' => $playlist->items->pluck('video_id')->map(fn ($id) => (int) $id)->all(),
        ];
        $this->workspaceMode = 'playlist_editor';
        $this->activeTab = 'playlists';
    }

    public function savePlaylist(): void
    {
        if (! $this->requireActiveApp()) return;
        $data = $this->validate(['playlistForm.video_channel_id' => 'required|integer|min:1', 'playlistForm.title' => 'required|string|max:255', 'playlistForm.slug' => 'nullable|string|max:255', 'playlistForm.description' => 'nullable|string', 'playlistForm.provider' => 'nullable|string|max:80', 'playlistForm.provider_playlist_id' => 'nullable|string|max:255', 'playlistForm.external_url' => 'nullable|url:http,https|max:2048', 'playlistForm.thumbnail_media_asset_id' => 'nullable|integer|min:1', 'playlistForm.status' => 'required|in:draft,published,archived', 'playlistForm.visibility' => 'required|in:public,private,unlisted', 'playlistForm.is_featured' => 'boolean', 'playlistForm.sort_order' => 'integer|min:0', 'playlistForm.video_ids' => 'array', 'playlistForm.video_ids.*' => 'integer|min:1'])['playlistForm'];
        if (! $this->owned(VideoChannel::query(), (int) $data['video_channel_id'])->exists()) { $this->ownershipError('channel'); return; }
        $thumbnailId = $this->ownedMediaId($data['thumbnail_media_asset_id'] ?? null);
        if (($data['thumbnail_media_asset_id'] ?? null) && ! $thumbnailId) { $this->ownershipError('thumbnail'); return; }
        $videoIds = array_values(array_unique(array_map('intval', $data['video_ids'] ?? [])));
        if (count($videoIds) !== $this->owned(Video::query())->whereIn('id', $videoIds)->count()) { $this->ownershipError('playlist video'); return; }
        $playlist = $this->editingPlaylistId ? $this->owned(VideoPlaylist::query(), $this->editingPlaylistId)->first() : new VideoPlaylist(['app_id' => $this->activeAppId]);
        if (! $playlist) { $this->notFound('Video playlist'); return; }
        unset($data['video_ids']);
        $playlist->fill(array_merge($data, ['slug' => trim((string) ($data['slug'] ?? '')) ?: Str::slug($data['title']), 'thumbnail_media_asset_id' => $thumbnailId]));
        $playlist->settings_json = self::preserveAdvancedJson($playlist->settings_json, ['last_beginner_edit' => 'video_engine']);
        $playlist->save();
        $this->syncPlaylistMembership($playlist, $videoIds);
        $this->editingPlaylistId = (int) $playlist->id;
        $this->workspaceMode = 'playlist_editor';
        $this->activeTab = 'playlists';
        $this->saved('Video playlist');
        $this->dispatch('video-engine-keep-position');
    }

    public function createVideo(): void
    {
        if (! $this->requireActiveApp()) return;
        $this->resetVideoForm();
        $this->editingVideoId = null;
        $this->editorTab = 'details';
        $this->workspaceMode = 'video_editor';
        $this->activeTab = 'videos';
    }

    public function editVideo(int $id): void
    {
        if (! $this->requireActiveApp()) return;
        $video = $this->owned(Video::query(), $id)->first();
        if (! $video) { $this->notFound('Video'); return; }
        $this->editingVideoId = $video->id;
        $this->editorTab = in_array($this->editorTab, ['details', 'source', 'media', 'publishing', 'preview'], true) ? $this->editorTab : 'details';
        $playback = is_array($video->playback_settings_json) ? $video->playback_settings_json : [];
        $this->videoForm = [
            'video_channel_id' => $video->video_channel_id, 'title' => $video->title, 'slug' => $video->slug,
            'description' => $video->description, 'source_type' => $video->source_type, 'provider' => $video->provider,
            'provider_video_id' => $video->provider_video_id, 'media_asset_id' => $video->media_asset_id,
            'external_url' => $video->external_url, 'thumbnail_media_asset_id' => $video->thumbnail_media_asset_id,
            'mime_type' => $video->mime_type, 'duration_seconds' => $video->duration_seconds,
            'aspect_ratio' => $video->aspect_ratio, 'is_live' => (bool) $video->is_live,
            'status' => $video->status, 'visibility' => $video->visibility,
            'is_featured' => (bool) $video->is_featured, 'sort_order' => (int) $video->sort_order,
            'embed_allowed' => (bool) ($playback['embed_allowed'] ?? false),
            'external_open_allowed' => (bool) ($playback['external_open_allowed'] ?? false),
        ];
        $this->workspaceMode = 'video_editor';
        $this->activeTab = 'videos';
    }

    public function previewVideo(int $id): void
    {
        $this->editVideo($id);
        if ($this->editingVideoId) $this->editorTab = 'preview';
    }

    public function saveVideo(): void
    {
        if (! $this->requireActiveApp()) return;
        $data = $this->validate(['videoForm.video_channel_id' => 'required|integer|min:1', 'videoForm.title' => 'required|string|max:255', 'videoForm.slug' => 'nullable|string|max:255', 'videoForm.description' => 'nullable|string', 'videoForm.source_type' => 'required|in:uploaded_video,external_video,hls,youtube_video,web_embed', 'videoForm.provider' => 'nullable|string|max:80', 'videoForm.provider_video_id' => 'nullable|string|max:255', 'videoForm.media_asset_id' => 'nullable|integer|min:1', 'videoForm.external_url' => 'nullable|string|max:2048', 'videoForm.thumbnail_media_asset_id' => 'nullable|integer|min:1', 'videoForm.mime_type' => 'nullable|string|max:120', 'videoForm.duration_seconds' => 'nullable|integer|min:0', 'videoForm.aspect_ratio' => 'nullable|string|max:40', 'videoForm.is_live' => 'boolean', 'videoForm.status' => 'required|in:draft,published,archived', 'videoForm.visibility' => 'required|in:public,private,unlisted', 'videoForm.is_featured' => 'boolean', 'videoForm.sort_order' => 'integer|min:0', 'videoForm.embed_allowed' => 'boolean', 'videoForm.external_open_allowed' => 'boolean'])['videoForm'];
        if (! $this->owned(VideoChannel::query(), (int) $data['video_channel_id'])->exists()) { $this->ownershipError('channel'); return; }
        $source = self::canonicalSourceFields($data);
        if (! VideoSourceContract::isValid($source)) { Notification::make()->title('Invalid video source')->body('Complete only the controls required for the selected source type.')->danger()->send(); return; }
        if ($source['media_asset_id'] && ! $this->owned(MediaAsset::query(), (int) $source['media_asset_id'])->where('type', 'video')->exists()) { $this->ownershipError('video media'); return; }
        $thumbnailId = $this->ownedMediaId($data['thumbnail_media_asset_id'] ?? null);
        if (($data['thumbnail_media_asset_id'] ?? null) && ! $thumbnailId) { $this->ownershipError('thumbnail'); return; }
        $video = $this->editingVideoId ? $this->owned(Video::query(), $this->editingVideoId)->first() : new Video(['app_id' => $this->activeAppId]);
        if (! $video) { $this->notFound('Video'); return; }
        $playbackPatch = ['embed_allowed' => (bool) $data['embed_allowed'], 'external_open_allowed' => (bool) $data['external_open_allowed']];
        unset($data['embed_allowed'], $data['external_open_allowed']);
        $video->fill(array_merge($data, $source, ['slug' => trim((string) ($data['slug'] ?? '')) ?: Str::slug($data['title']), 'thumbnail_media_asset_id' => $thumbnailId]));
        $video->playback_settings_json = self::preserveAdvancedJson($video->playback_settings_json, $playbackPatch);
        $video->settings_json = self::preserveAdvancedJson($video->settings_json, ['last_beginner_edit' => 'video_engine']);
        $video->save();
        $this->editingVideoId = (int) $video->id;
        $this->workspaceMode = 'video_editor';
        $this->activeTab = 'videos';
        $this->saved('Video');
        $this->dispatch('video-engine-keep-position');
    }

    public static function canonicalSourceFields(array $input): array
    {
        $type = (string) ($input['source_type'] ?? '');
        return [
            'source_type' => $type,
            'media_asset_id' => $type === VideoSourceContract::UPLOADED_VIDEO ? self::nullablePositiveInt($input['media_asset_id'] ?? null) : null,
            'provider_video_id' => $type === VideoSourceContract::YOUTUBE_VIDEO ? self::nullableString($input['provider_video_id'] ?? null) : null,
            'external_url' => $type === VideoSourceContract::UPLOADED_VIDEO ? null : self::nullableString($input['external_url'] ?? null),
        ];
    }

    public static function preserveAdvancedJson(mixed $existing, array $beginnerPatch): array
    {
        return array_replace_recursive(is_array($existing) ? $existing : [], $beginnerPatch);
    }

    public function getOverviewProperty(): array
    {
        if (! $this->activeAppId) return ['channels' => 0, 'playlists' => 0, 'videos' => 0, 'published' => 0];
        return ['channels' => $this->owned(VideoChannel::query())->count(), 'playlists' => $this->owned(VideoPlaylist::query())->count(), 'videos' => $this->owned(Video::query())->count(), 'published' => $this->owned(Video::query())->where('status', 'published')->count()];
    }

    public function getChannelsProperty(): array
    {
        if (! $this->activeAppId) return [];
        return $this->owned(VideoChannel::query())
            ->with('thumbnailMediaAsset')->withCount(['videos', 'playlists'])
            ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%' . trim($this->search) . '%'))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('sort_order')->orderBy('title')->get()->all();
    }

    public function getPlaylistsProperty(): array
    {
        if (! $this->activeAppId) return [];
        return $this->owned(VideoPlaylist::query())
            ->with(['channel', 'thumbnailMediaAsset'])->withCount('items')
            ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%' . trim($this->search) . '%'))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('sort_order')->orderBy('title')->get()->all();
    }

    public function getVideosProperty(): array
    {
        if (! $this->activeAppId) return [];
        return $this->owned(Video::query())
            ->with(['channel', 'thumbnailMediaAsset', 'mediaAsset'])
            ->when(trim($this->search) !== '', fn ($query) => $query->where('title', 'like', '%' . trim($this->search) . '%'))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->sourceFilter !== 'all', fn ($query) => $query->where('source_type', $this->sourceFilter))
            ->orderBy('sort_order')->orderByDesc('id')->get()->all();
    }

    public function getChannelOptionsProperty(): array
    {
        if (! $this->activeAppId) return [];
        return $this->owned(VideoChannel::query())->orderBy('title')->pluck('title', 'id')->all();
    }

    public function getVideoOptionsProperty(): array
    {
        if (! $this->activeAppId) return [];
        return $this->owned(Video::query())->orderBy('title')->pluck('title', 'id')->all();
    }

    public function getAvailableVideoOptionsProperty(): array
    {
        if (! $this->activeAppId) return [];
        return $this->owned(Video::query())
            ->when(trim($this->playlistVideoSearch) !== '', fn ($query) => $query->where('title', 'like', '%' . trim($this->playlistVideoSearch) . '%'))
            ->orderBy('title')->pluck('title', 'id')->all();
    }

    public function getSelectedPlaylistVideosProperty(): array
    {
        $ids = array_values(array_map('intval', $this->playlistForm['video_ids'] ?? []));
        if (! $this->activeAppId || $ids === []) return [];
        $videos = $this->owned(Video::query())->whereIn('id', $ids)->get()->keyBy('id');
        return collect($ids)->map(fn (int $id) => $videos->get($id))->filter()->values()->all();
    }

    public function togglePlaylistVideo(int $videoId): void
    {
        if (! $this->requireActiveApp() || ! $this->owned(Video::query(), $videoId)->exists()) return;
        $ids = array_values(array_unique(array_map('intval', $this->playlistForm['video_ids'] ?? [])));
        $index = array_search($videoId, $ids, true);
        if ($index === false) $ids[] = $videoId; else array_splice($ids, $index, 1);
        $this->playlistForm['video_ids'] = $ids;
    }

    public function movePlaylistVideo(int $videoId, string $direction): void
    {
        $ids = array_values(array_map('intval', $this->playlistForm['video_ids'] ?? []));
        $index = array_search($videoId, $ids, true);
        if ($index === false) return;
        $target = $direction === 'up' ? $index - 1 : ($direction === 'down' ? $index + 1 : $index);
        if ($target < 0 || $target >= count($ids) || $target === $index) return;
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        $this->playlistForm['video_ids'] = $ids;
    }

    public function getPreviewProperty(): array
    {
        $base = ['type' => 'catalog', 'title' => 'Video catalog', 'thumbnail_url' => null, 'meta' => [], 'playback' => null, 'ready' => false];
        if (! $this->activeAppId) return $base;

        if ($this->workspaceMode === 'video_editor') {
            $playback = null;
            if ($this->editingVideoId) {
                $video = $this->owned(Video::query(), $this->editingVideoId)->with(['mediaAsset', 'thumbnailMediaAsset'])->first();
                $playback = $video ? VideoPlaybackPayload::forVideo($video) : null;
            }
            return ['type' => 'video', 'title' => (string) ($this->videoForm['title'] ?: 'Untitled video'), 'thumbnail_url' => $this->assetUrl($this->videoForm['thumbnail_media_asset_id'] ?? null, 'image'), 'meta' => [
                'Channel' => $this->channelLabel($this->videoForm['video_channel_id'] ?? null),
                'Source' => (string) ($this->videoForm['source_type'] ?? ''),
                'Status' => (string) ($this->videoForm['status'] ?? 'draft'),
                'Visibility' => (string) ($this->videoForm['visibility'] ?? 'public'),
                'Flags' => implode(' · ', array_filter([($this->videoForm['is_live'] ?? false) ? 'Live' : null, ($this->videoForm['is_featured'] ?? false) ? 'Featured' : null])) ?: 'Standard',
            ], 'playback' => $playback, 'ready' => $playback !== null];
        }

        if ($this->workspaceMode === 'channel_editor') {
            $counts = ['videos' => 0, 'playlists' => 0];
            if ($this->editingChannelId) {
                $row = $this->owned(VideoChannel::query(), $this->editingChannelId)->withCount(['videos', 'playlists'])->first();
                if ($row) $counts = ['videos' => (int) $row->videos_count, 'playlists' => (int) $row->playlists_count];
            }
            return ['type' => 'channel', 'title' => (string) ($this->channelForm['title'] ?: 'Untitled channel'), 'thumbnail_url' => $this->assetUrl($this->channelForm['thumbnail_media_asset_id'] ?? null, 'image'), 'meta' => ['Slug' => (string) ($this->channelForm['slug'] ?: 'Auto'), 'Status' => (string) $this->channelForm['status'], 'Featured' => ($this->channelForm['is_featured'] ?? false) ? 'Yes' : 'No', 'Catalog' => $counts['videos'] . ' videos · ' . $counts['playlists'] . ' playlists'], 'playback' => null, 'ready' => trim((string) ($this->channelForm['title'] ?? '')) !== ''];
        }

        if ($this->workspaceMode === 'playlist_editor') {
            return ['type' => 'playlist', 'title' => (string) ($this->playlistForm['title'] ?: 'Untitled playlist'), 'thumbnail_url' => $this->assetUrl($this->playlistForm['thumbnail_media_asset_id'] ?? null, 'image'), 'meta' => ['Channel' => $this->channelLabel($this->playlistForm['video_channel_id'] ?? null), 'Provider' => (string) ($this->playlistForm['provider'] ?: 'App catalog'), 'Status' => (string) $this->playlistForm['status'], 'Videos' => count($this->playlistForm['video_ids'] ?? [])], 'playback' => null, 'ready' => trim((string) ($this->playlistForm['title'] ?? '')) !== '' && count($this->playlistForm['video_ids'] ?? []) > 0];
        }

        return array_merge($base, ['meta' => $this->overview, 'ready' => $this->overview['videos'] > 0]);
    }

    public function getVideoMediaOptionsProperty(): array { return $this->mediaOptions('video'); }
    public function getImageMediaOptionsProperty(): array { return $this->mediaOptions('image'); }

    public function publicThumbnailUrl(?MediaAsset $asset): ?string
    {
        return $this->activeAppId ? MediaAssetPublicUrl::resolve($asset, $this->activeAppId, 'image') : null;
    }

    private function mediaOptions(string $type): array
    {
        if (! $this->activeAppId) return [];
        return $this->owned(MediaAsset::query())->where('type', $type)->where('is_active', true)->orderBy('label')->pluck('label', 'id')->all();
    }

    private function syncPlaylistMembership(VideoPlaylist $playlist, array $videoIds): void
    {
        $existing = $this->owned(VideoPlaylistItem::query())->where('video_playlist_id', $playlist->id)->get()->keyBy('video_id');
        foreach ($videoIds as $order => $videoId) {
            $item = $existing->get($videoId) ?? new VideoPlaylistItem(['app_id' => $this->activeAppId, 'video_playlist_id' => $playlist->id, 'video_id' => $videoId]);
            $item->sort_order = $order;
            $item->settings_json = self::preserveAdvancedJson($item->settings_json, []);
            $item->save();
        }
        $this->owned(VideoPlaylistItem::query())->where('video_playlist_id', $playlist->id)->whereNotIn('video_id', $videoIds ?: [0])->delete();
    }

    private function owned($query, ?int $id = null)
    {
        $query->where('app_id', $this->activeAppId ?? 0);
        return $id ? $query->whereKey($id) : $query;
    }

    private function ownedMediaId(mixed $id): ?int
    {
        $id = self::nullablePositiveInt($id);
        return $id && $this->owned(MediaAsset::query(), $id)->exists() ? $id : null;
    }

    private function assetUrl(mixed $id, string $type): ?string
    {
        $id = self::nullablePositiveInt($id);
        if (! $id || ! $this->activeAppId) return null;
        $asset = $this->owned(MediaAsset::query(), $id)->where('type', $type)->where('is_active', true)->first();
        return MediaAssetPublicUrl::resolve($asset, $this->activeAppId, $type);
    }

    private function channelLabel(mixed $id): string
    {
        $id = self::nullablePositiveInt($id);
        if (! $id || ! $this->activeAppId) return 'Not selected';
        return (string) ($this->owned(VideoChannel::query(), $id)->value('title') ?? 'Not selected');
    }

    private function requireActiveApp(): bool
    {
        if ($this->activeAppId && VideoDomainGuard::sameApp($this->activeAppId, ActiveApp::selectedId())) return true;
        Notification::make()->title('Select an active app')->body('Video Engine never falls back to another app.')->warning()->send();
        return false;
    }

    private function resetForms(): void { $this->resetChannelForm(); $this->resetPlaylistForm(); $this->resetVideoForm(); }
    private function resetChannelForm(): void { $this->channelForm = ['title' => '', 'slug' => '', 'description' => '', 'thumbnail_media_asset_id' => null, 'status' => 'draft', 'visibility' => 'public', 'is_featured' => false, 'sort_order' => 0]; }
    private function resetPlaylistForm(): void { $this->playlistForm = ['video_channel_id' => null, 'title' => '', 'slug' => '', 'description' => '', 'provider' => '', 'provider_playlist_id' => '', 'external_url' => '', 'thumbnail_media_asset_id' => null, 'status' => 'draft', 'visibility' => 'public', 'is_featured' => false, 'sort_order' => 0, 'video_ids' => []]; }
    private function resetVideoForm(): void { $this->videoForm = ['video_channel_id' => null, 'title' => '', 'slug' => '', 'description' => '', 'source_type' => VideoSourceContract::UPLOADED_VIDEO, 'provider' => '', 'provider_video_id' => '', 'media_asset_id' => null, 'external_url' => '', 'thumbnail_media_asset_id' => null, 'mime_type' => '', 'duration_seconds' => null, 'aspect_ratio' => '', 'is_live' => false, 'status' => 'draft', 'visibility' => 'public', 'is_featured' => false, 'sort_order' => 0, 'embed_allowed' => false, 'external_open_allowed' => false]; }
    private static function nullableString(mixed $value): ?string { $value = trim((string) ($value ?? '')); return $value === '' ? null : $value; }
    private static function nullablePositiveInt(mixed $value): ?int { return is_numeric($value) && (int) $value > 0 ? (int) $value : null; }
    private function saved(string $subject): void { Notification::make()->title($subject . ' saved')->success()->send(); }
    private function notFound(string $subject): void { Notification::make()->title($subject . ' not found for the active app')->danger()->send(); $this->closeEditor(); }
    private function ownershipError(string $subject): void { Notification::make()->title('Invalid ' . $subject)->body('The selected record must belong to the active app.')->danger()->send(); }
}

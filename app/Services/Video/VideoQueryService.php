<?php

namespace App\Services\Video;

use App\Models\App;
use App\Models\Video;
use App\Models\VideoChannel;
use App\Models\VideoPlaylist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class VideoQueryService
{
    public const DEFAULT_PER_PAGE = 20;
    public const MAX_PER_PAGE = 50;

    public function paginateChannels(App $app, array $filters = []): LengthAwarePaginator
    {
        $query = $this->publicChannels((int) $app->id)
            ->with(['thumbnailMediaAsset' => $this->ownedActiveMedia((int) $app->id)])
            ->withCount(['playlists' => fn (Builder $query) => $query
                ->where('app_id', (int) $app->id)
                ->publiclyVisible()]);

        $this->applyBooleanFilter($query, 'is_featured', $filters['featured'] ?? null);

        return $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage($filters['per_page'] ?? null), ['*'], 'page', $this->page($filters['page'] ?? null));
    }

    public function findChannel(App $app, string $slug): ?VideoChannel
    {
        $appId = (int) $app->id;
        $channel = $this->publicChannels($appId)
            ->where('slug', $slug)
            ->with(['thumbnailMediaAsset' => $this->ownedActiveMedia($appId)])
            ->withCount(['playlists' => fn (Builder $query) => $query
                ->where('app_id', $appId)
                ->publiclyVisible()])
            ->first();

        if (! $channel) {
            return null;
        }

        $channel->load(['playlists' => fn (Builder $query) => $query
            ->where('app_id', $appId)
            ->publiclyVisible()
            ->with(['channel', 'thumbnailMediaAsset' => $this->ownedActiveMedia($appId)])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id')]);

        return $channel;
    }

    public function paginatePlaylists(App $app, array $filters = []): LengthAwarePaginator
    {
        $appId = (int) $app->id;
        $query = $this->publicPlaylists($appId)
            ->with(['channel', 'thumbnailMediaAsset' => $this->ownedActiveMedia($appId)]);

        $channel = trim((string) ($filters['channel'] ?? ''));
        if ($channel !== '') {
            $query->whereHas('channel', fn (Builder $query) => $query
                ->where('app_id', $appId)->where('slug', $channel)->publiclyVisible());
        }
        $this->applyBooleanFilter($query, 'is_featured', $filters['featured'] ?? null);

        return $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($this->perPage($filters['per_page'] ?? null), ['*'], 'page', $this->page($filters['page'] ?? null));
    }

    public function findPlaylist(App $app, string $slug): ?VideoPlaylist
    {
        $appId = (int) $app->id;
        $playlist = $this->publicPlaylists($appId)
            ->where('slug', $slug)
            ->with(['channel', 'thumbnailMediaAsset' => $this->ownedActiveMedia($appId)])
            ->first();

        if (! $playlist) {
            return null;
        }

        $playlist->load(['items' => fn (Builder $query) => $query
            ->where('app_id', $appId)
            ->whereHas('video', fn (Builder $video) => $this->applyPublicVideoHierarchy($video, $appId))
            ->with(['video' => fn (Builder $video) => $this->applyPublicVideoHierarchy($video, $appId)
                ->with([
                    'channel',
                    'mediaAsset' => $this->ownedActiveMedia($appId),
                    'thumbnailMediaAsset' => $this->ownedActiveMedia($appId),
                ])])
            ->orderBy('sort_order')
            ->orderBy('id')]);

        return $playlist;
    }

    public function paginateVideos(App $app, array $filters = []): LengthAwarePaginator
    {
        $appId = (int) $app->id;
        $query = $this->publicVideos($appId)->with([
            'channel',
            'mediaAsset' => $this->ownedActiveMedia($appId),
            'thumbnailMediaAsset' => $this->ownedActiveMedia($appId),
        ]);

        $channel = trim((string) ($filters['channel'] ?? ''));
        if ($channel !== '') {
            $query->whereHas('channel', fn (Builder $channelQuery) => $channelQuery
                ->where('app_id', $appId)->where('slug', $channel)->publiclyVisible());
        }

        $playlist = trim((string) ($filters['playlist'] ?? ''));
        if ($playlist !== '') {
            $query->whereHas('playlistItems', fn (Builder $itemQuery) => $itemQuery
                ->where('app_id', $appId)
                ->whereHas('playlist', fn (Builder $playlistQuery) => $playlistQuery
                    ->where('app_id', $appId)
                    ->where('slug', $playlist)
                    ->publiclyVisible()
                    ->whereHas('channel', fn (Builder $owner) => $owner
                        ->where('app_id', $appId)->publiclyVisible())));
        }

        $this->applyBooleanFilter($query, 'is_featured', $filters['featured'] ?? null);
        $this->applyBooleanFilter($query, 'is_live', $filters['live'] ?? null);

        return $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderBy('id')
            ->paginate($this->perPage($filters['per_page'] ?? null), ['*'], 'page', $this->page($filters['page'] ?? null));
    }

    public function findVideo(App $app, string $slug): ?Video
    {
        return $this->findVideoQuery($app)->where('slug', $slug)->first();
    }

    public function findVideoById(App $app, int $id): ?Video
    {
        return $id > 0 ? $this->findVideoQuery($app)->whereKey($id)->first() : null;
    }

    public function perPage(mixed $value): int
    {
        if (! is_numeric($value) || (int) $value < 1) {
            return self::DEFAULT_PER_PAGE;
        }

        return min((int) $value, self::MAX_PER_PAGE);
    }

    public function page(mixed $value): int
    {
        return is_numeric($value) && (int) $value > 0 ? (int) $value : 1;
    }

    private function findVideoQuery(App $app): Builder
    {
        $appId = (int) $app->id;

        return $this->publicVideos($appId)->with([
            'channel',
            'mediaAsset' => $this->ownedActiveMedia($appId),
            'thumbnailMediaAsset' => $this->ownedActiveMedia($appId),
        ]);
    }

    private function publicChannels(int $appId): Builder
    {
        return VideoChannel::query()->where('app_id', $appId)->publiclyVisible();
    }

    private function publicPlaylists(int $appId): Builder
    {
        return VideoPlaylist::query()
            ->where('app_id', $appId)
            ->publiclyVisible()
            ->whereHas('channel', fn (Builder $query) => $query
                ->where('app_id', $appId)->publiclyVisible());
    }

    private function publicVideos(int $appId): Builder
    {
        return $this->applyPublicVideoHierarchy(Video::query(), $appId);
    }

    private function applyPublicVideoHierarchy(Builder $query, int $appId): Builder
    {
        return $query
            ->where('app_id', $appId)
            ->publiclyVisible()
            ->whereHas('channel', fn (Builder $channel) => $channel
                ->where('app_id', $appId)->publiclyVisible());
    }

    private function ownedActiveMedia(int $appId): \Closure
    {
        return fn (Builder $query) => $query->where('app_id', $appId)->where('is_active', true);
    }

    private function applyBooleanFilter(Builder $query, string $column, mixed $value): void
    {
        if (is_bool($value)) {
            $query->where($column, $value);
        }
    }
}

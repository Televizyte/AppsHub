<?php

namespace App\Support\Video;

use App\Models\MediaAsset;
use App\Models\Video;
use App\Models\VideoChannel;
use App\Models\VideoPlaylist;
use App\Models\VideoPlaylistItem;

final class VideoPayloadSerializer
{
    public static function channel(VideoChannel $channel): array
    {
        return [
            'id' => (int) $channel->id,
            'slug' => (string) $channel->slug,
            'title' => (string) $channel->title,
            'description' => self::nullableString($channel->description),
            'thumbnail' => self::thumbnail($channel->thumbnailMediaAsset, (int) $channel->app_id),
            'visibility' => (string) $channel->visibility,
            'featured' => (bool) $channel->is_featured,
            'sort_order' => (int) $channel->sort_order,
            'settings' => self::publicSettings($channel->settings_json),
            'playlist_count' => isset($channel->playlists_count) ? (int) $channel->playlists_count : null,
        ];
    }

    public static function playlist(VideoPlaylist $playlist, bool $includeItems = false): array
    {
        $payload = [
            'id' => (int) $playlist->id,
            'slug' => (string) $playlist->slug,
            'title' => (string) $playlist->title,
            'description' => self::nullableString($playlist->description),
            'channel' => self::channelSummary($playlist->channel),
            'thumbnail' => self::thumbnail($playlist->thumbnailMediaAsset, (int) $playlist->app_id),
            'provider' => self::nullableString($playlist->provider),
            'provider_playlist_id' => self::nullableString($playlist->provider_playlist_id),
            'external_url' => MediaAssetPublicUrl::sanitize($playlist->external_url),
            'featured' => (bool) $playlist->is_featured,
            'sort_order' => (int) $playlist->sort_order,
            'settings' => self::publicSettings($playlist->settings_json),
        ];

        if ($includeItems) {
            $payload['items'] = $playlist->items
                ->map(fn (VideoPlaylistItem $item): ?array => self::playlistItem($item))
                ->filter()
                ->values()
                ->all();
        }

        return $payload;
    }

    public static function video(Video $video): ?array
    {
        $playback = VideoPlaybackPayload::forVideo($video);
        if ($playback === null) {
            return null;
        }

        return [
            'id' => (int) $video->id,
            'slug' => (string) $video->slug,
            'title' => (string) $video->title,
            'description' => self::nullableString($video->description),
            'channel' => self::channelSummary($video->channel),
            'thumbnail' => self::thumbnail($video->thumbnailMediaAsset, (int) $video->app_id),
            'playback' => $playback,
            'featured' => (bool) $video->is_featured,
            'sort_order' => (int) $video->sort_order,
            'published_at' => $video->published_at?->toISOString(),
            'settings' => self::publicSettings($video->settings_json),
        ];
    }

    public static function playlistItem(VideoPlaylistItem $item): ?array
    {
        if (! $item->video || (int) $item->app_id !== (int) $item->video->app_id) {
            return null;
        }

        $video = self::video($item->video);

        return $video === null ? null : [
            'id' => (int) $item->id,
            'sort_order' => (int) $item->sort_order,
            'video' => $video,
        ];
    }

    private static function channelSummary(?VideoChannel $channel): ?array
    {
        return $channel ? [
            'id' => (int) $channel->id,
            'slug' => (string) $channel->slug,
            'title' => (string) $channel->title,
        ] : null;
    }

    private static function thumbnail(?MediaAsset $asset, int $appId): ?array
    {
        $url = MediaAssetPublicUrl::resolve($asset, $appId, 'image');

        return $url === null ? null : ['media_asset_id' => (int) $asset->id, 'url' => $url];
    }

    private static function publicSettings(mixed $value): array
    {
        $public = is_array($value) ? ($value['public'] ?? null) : null;

        return is_array($public) ? $public : [];
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}

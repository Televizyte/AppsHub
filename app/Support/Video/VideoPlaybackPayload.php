<?php

namespace App\Support\Video;

use App\Models\Video;

final class VideoPlaybackPayload
{
    public static function forVideo(Video $video): ?array
    {
        if (! VideoSourceContract::isValid($video->getAttributes())) {
            return null;
        }

        $sourceType = (string) $video->source_type;
        $url = null;
        $providerId = self::nullableString($video->provider_video_id);
        $mediaAssetId = null;
        $embedAllowed = false;
        $externalOpenAllowed = false;

        if ($sourceType === VideoSourceContract::UPLOADED_VIDEO) {
            $asset = $video->relationLoaded('mediaAsset') ? $video->mediaAsset : null;
            $url = MediaAssetPublicUrl::resolve($asset, (int) $video->app_id, 'video');
            if ($url === null) {
                return null;
            }
            $mediaAssetId = (int) $asset->id;
            $providerId = null;
        } elseif ($sourceType === VideoSourceContract::YOUTUBE_VIDEO) {
            $providerId ??= VideoSourceContract::youtubeVideoId($video->external_url);
            if ($providerId === null) {
                return null;
            }
            $url = 'https://www.youtube.com/watch?v=' . rawurlencode($providerId);
            $embedAllowed = true;
        } else {
            $url = MediaAssetPublicUrl::sanitize($video->external_url);
            if ($url === null) {
                return null;
            }

            if ($sourceType === VideoSourceContract::WEB_EMBED) {
                $settings = is_array($video->playback_settings_json) ? $video->playback_settings_json : [];
                if (($settings['embed_allowed'] ?? false) !== true) {
                    return null;
                }
                $embedAllowed = true;
            } elseif ($sourceType === VideoSourceContract::EXTERNAL_VIDEO) {
                $externalOpenAllowed = true;
            }
        }

        return [
            'source_type' => $sourceType,
            'provider' => self::provider($video, $sourceType),
            'url' => $url,
            'provider_id' => $providerId,
            'media_asset_id' => $mediaAssetId,
            'mime_type' => self::nullableString($video->mime_type),
            'is_live' => (bool) $video->is_live,
            'duration_seconds' => $video->duration_seconds !== null ? max(0, (int) $video->duration_seconds) : null,
            'aspect_ratio' => self::nullableString($video->aspect_ratio),
            'embed_allowed' => $embedAllowed,
            'external_open_allowed' => $externalOpenAllowed,
        ];
    }

    private static function provider(Video $video, string $sourceType): string
    {
        return match ($sourceType) {
            VideoSourceContract::YOUTUBE_VIDEO => 'youtube',
            VideoSourceContract::HLS => 'hls',
            VideoSourceContract::UPLOADED_VIDEO => self::nullableString($video->provider) ?? 'appshub',
            VideoSourceContract::WEB_EMBED => self::nullableString($video->provider) ?? 'web',
            default => self::nullableString($video->provider) ?? 'external',
        };
    }

    private static function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}

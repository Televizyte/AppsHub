<?php

namespace App\Support\Video;

final class VideoSourceContract
{
    public const UPLOADED_VIDEO = 'uploaded_video';
    public const EXTERNAL_VIDEO = 'external_video';
    public const HLS = 'hls';
    public const YOUTUBE_VIDEO = 'youtube_video';
    public const WEB_EMBED = 'web_embed';

    public static function sourceTypes(): array
    {
        return [
            self::UPLOADED_VIDEO,
            self::EXTERNAL_VIDEO,
            self::HLS,
            self::YOUTUBE_VIDEO,
            self::WEB_EMBED,
        ];
    }

    public static function isValidSourceType(?string $sourceType): bool
    {
        return in_array($sourceType, self::sourceTypes(), true);
    }

    public static function isValid(array $input): bool
    {
        $sourceType = trim((string) ($input['source_type'] ?? ''));
        $mediaAssetId = $input['media_asset_id'] ?? null;
        $providerVideoId = $input['provider_video_id'] ?? null;
        $externalUrl = $input['external_url'] ?? null;

        if (! self::isValidSourceType($sourceType)) {
            return false;
        }

        if ($sourceType === self::UPLOADED_VIDEO) {
            return self::positiveId($mediaAssetId)
                && self::isAbsent($providerVideoId)
                && self::isAbsent($externalUrl);
        }

        if (in_array($sourceType, [self::EXTERNAL_VIDEO, self::HLS, self::WEB_EMBED], true)) {
            return self::isAbsent($mediaAssetId)
                && self::isAbsent($providerVideoId)
                && self::isHttpUrl($externalUrl);
        }

        if (! self::isAbsent($mediaAssetId)) {
            return false;
        }

        $providerIdValid = self::isProviderVideoId($providerVideoId);
        $externalVideoId = self::youtubeVideoId($externalUrl);
        $externalUrlValid = $externalVideoId !== null;

        return ($providerIdValid || $externalUrlValid)
            && (self::isAbsent($externalUrl) || $externalUrlValid)
            && (self::isAbsent($providerVideoId) || $providerIdValid)
            && (! $providerIdValid || ! $externalUrlValid || trim((string) $providerVideoId) === $externalVideoId);
    }

    public static function isYouTubeVideoUrl(mixed $value): bool
    {
        return self::youtubeVideoId($value) !== null;
    }

    public static function youtubeVideoId(mixed $value): ?string
    {
        if (! self::isHttpUrl($value)) {
            return null;
        }

        $parts = parse_url(trim((string) $value));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = trim((string) ($parts['path'] ?? ''), '/');
        parse_str((string) ($parts['query'] ?? ''), $query);

        if (self::nonEmpty($query['list'] ?? null)) {
            return null;
        }

        if ($host === 'youtu.be' || $host === 'www.youtu.be') {
            return self::isProviderVideoId($path) ? $path : null;
        }

        if (! in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            return null;
        }

        if (str_starts_with($path, 'shorts/') || str_starts_with($path, 'embed/')) {
            $id = trim(substr($path, strpos($path, '/') + 1));

            return self::isProviderVideoId($id) ? $id : null;
        }

        $id = $query['v'] ?? null;

        return $path === 'watch' && self::isProviderVideoId($id) ? trim((string) $id) : null;
    }

    private static function positiveId(mixed $value): bool
    {
        return is_numeric($value) && (int) $value > 0;
    }

    private static function nonEmpty(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private static function isAbsent(mixed $value): bool
    {
        return $value === null || (is_string($value) && trim($value) === '');
    }

    private static function isProviderVideoId(mixed $value): bool
    {
        return self::nonEmpty($value)
            && preg_match('/^[A-Za-z0-9_-]+$/', trim($value)) === 1;
    }

    private static function isHttpUrl(mixed $value): bool
    {
        if (! self::nonEmpty($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true);
    }
}

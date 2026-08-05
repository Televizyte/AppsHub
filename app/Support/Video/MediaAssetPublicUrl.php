<?php

namespace App\Support\Video;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;

final class MediaAssetPublicUrl
{
    public static function resolve(?MediaAsset $asset, int $appId, ?string $requiredType = null): ?string
    {
        if (! $asset
            || $appId <= 0
            || (int) $asset->app_id !== $appId
            || ! (bool) $asset->is_active
            || ($requiredType !== null && (string) $asset->type !== $requiredType)) {
            return null;
        }

        $url = self::sanitizeAssetUrl((string) ($asset->url ?? ''));

        if ($url !== null) {
            return $url;
        }

        $path = trim((string) ($asset->path ?? ''));
        if ($path === '' || strtolower($path) === 'external') {
            return null;
        }

        try {
            return self::sanitizeAssetUrl((string) Storage::disk((string) ($asset->disk ?: 'public'))->url($path));
        } catch (\Throwable) {
            return null;
        }
    }

    public static function sanitize(?string $value): ?string
    {
        return self::normalize($value, false, false);
    }

    private static function sanitizeAssetUrl(?string $value): ?string
    {
        return self::normalize($value, true, true);
    }

    private static function normalize(?string $value, bool $allowStorageRelative, bool $allowSensitiveQuery): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, '/')) {
            $parts = parse_url($value);

            if (! $allowStorageRelative
                || ! is_array($parts)
                || ! str_starts_with((string) ($parts['path'] ?? ''), '/storage/')) {
                return null;
            }

            return self::pathAndQuery($parts, $allowSensitiveQuery);
        }

        if (filter_var($value, FILTER_VALIDATE_URL) === false
            || ! in_array(strtolower((string) parse_url($value, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            return null;
        }

        $parts = parse_url($value);
        if (! is_array($parts) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = (string) ($parts['host'] ?? '');
        if ($scheme === '' || $host === '') {
            return null;
        }

        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $path = self::pathAndQuery($parts, $allowSensitiveQuery);

        if ($path === null) {
            return null;
        }

        return $scheme . '://' . $host . $port . $path;
    }

    private static function pathAndQuery(array $parts, bool $allowSensitiveQuery): ?string
    {
        $path = (string) ($parts['path'] ?? '');
        $rawQuery = (string) ($parts['query'] ?? '');
        parse_str($rawQuery, $query);

        if (! $allowSensitiveQuery) {
            foreach (array_keys($query) as $key) {
                if (preg_match('/(^|[_-])(token|signature|key|secret|credential|password|expires)([_-]|$)|api[_-]?key/i', (string) $key) === 1) {
                    return null;
                }
            }
        }

        return $path . ($rawQuery !== '' ? '?' . $rawQuery : '');
    }
}

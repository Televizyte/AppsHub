<?php

namespace App\Support;

use App\Models\MediaAsset;

class AppItemImagePayload
{
    /**
     * Keep every render-facing image field in sync when an app item image changes.
     * This prevents Flutter from reading an old thumbnail_url / cover_image_url
     * while the admin editor has already changed app_items.image_url.
     */
    public static function mergeIntoPayload(array $payload, ?string $imageUrl, int $mediaAssetId = 0): array
    {
        $imageUrl = is_string($imageUrl) ? trim($imageUrl) : '';

        foreach ([
            'image_url',
            'thumbnail_url',
            'cover_image_url',
            'image',
            'featured_image',
            'poster_url',
            'background_image_url',
        ] as $key) {
            if ($imageUrl !== '') {
                $payload[$key] = $imageUrl;
            } else {
                unset($payload[$key]);
            }
        }

        if ($mediaAssetId > 0) {
            $payload['media_asset_id'] = $mediaAssetId;
            $payload['image_asset_id'] = $mediaAssetId;
        } else {
            unset($payload['media_asset_id'], $payload['image_asset_id']);
        }

        $payload['image_updated_at'] = now()->toIso8601String();

        return $payload;
    }

    public static function mediaAssetIdFromUrl(int $appId, ?string $imageUrl): int
    {
        $imageUrl = is_string($imageUrl) ? trim($imageUrl) : '';
        if ($imageUrl === '') {
            return 0;
        }

        $path = self::pathFromUrl($imageUrl);

        $asset = MediaAsset::query()
            ->where('type', 'image')
            ->where(function ($query) use ($appId) {
                $query->whereNull('app_id')->orWhere('app_id', $appId);
            })
            ->where(function ($query) use ($imageUrl, $path) {
                $query->where('url', $imageUrl);
                if ($path !== '') {
                    $query->orWhere('path', $path);
                }
            })
            ->first();

        return $asset ? (int) $asset->id : 0;
    }

    private static function pathFromUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        $path = parse_url($url, PHP_URL_PATH);
        $path = is_string($path) ? ltrim($path, '/') : '';

        if (str_starts_with($path, 'storage/')) {
            return substr($path, strlen('storage/'));
        }

        return $path;
    }
}

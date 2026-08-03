<?php

namespace App\Support;

use App\Models\App;
use App\Models\AppItem;
use App\Models\AppSection;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaAssetSync
{
    /**
     * Sync image references already stored on app_items into media_assets.
     * This does not change app_items, delete files, or overwrite frontend image URLs.
     */
    public static function syncAppItemImages(int $appId): array
    {
        if ($appId <= 0) {
            return ['scanned' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $sectionIds = AppSection::query()
            ->where('app_id', $appId)
            ->pluck('id')
            ->all();

        if (empty($sectionIds)) {
            return ['scanned' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0];
        }

        $items = AppItem::query()
            ->with('section:id,app_id,tab_key,key,title')
            ->whereIn('section_id', $sectionIds)
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $result = ['scanned' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0];

        foreach ($items as $item) {
            $values = self::imageValuesFromItem($item);

            foreach ($values as $raw) {
                $result['scanned']++;

                $synced = self::syncOneImageValue($appId, $item, $raw);

                if ($synced === 'created') {
                    $result['created']++;
                } elseif ($synced === 'updated') {
                    $result['updated']++;
                } else {
                    $result['skipped']++;
                }
            }
        }

        return $result;
    }

    public static function syncAllApps(): array
    {
        $summary = [];

        App::query()->orderBy('id')->get(['id', 'name', 'slug'])->each(function (App $app) use (&$summary) {
            $summary[(string) ($app->slug ?: ('app-' . $app->id))] = self::syncAppItemImages((int) $app->id);
        });

        return $summary;
    }

    public static function publicUrlForAsset(MediaAsset $asset): string
    {
        $url = trim((string) ($asset->url ?? ''));
        if ($url !== '') {
            return $url;
        }

        $path = trim((string) ($asset->path ?? ''));
        if ($path === '' || strtolower($path) === 'external') {
            return '';
        }

        try {
            return self::absoluteUrl(Storage::disk((string) ($asset->disk ?: 'public'))->url($path));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function imageValuesFromItem(AppItem $item): array
    {
        $values = [];

        $direct = trim((string) ($item->image_url ?? ''));
        if ($direct !== '') {
            $values[] = $direct;
        }

        $payload = is_array($item->payload_json) ? $item->payload_json : [];
        foreach (['image_url', 'thumbnail_url', 'image', 'cover_image_url', 'banner_url', 'poster_url'] as $key) {
            $value = trim((string) data_get($payload, $key, ''));
            if ($value !== '') {
                $values[] = $value;
            }
        }

        return collect($values)->filter()->unique()->values()->all();
    }

    private static function syncOneImageValue(int $appId, AppItem $item, string $raw): string
    {
        $normalized = self::normalizeImageValue($raw);

        if ($normalized['url'] === '' && $normalized['path'] === '') {
            return 'skipped';
        }

        $existing = MediaAsset::query()
            ->where('type', 'image')
            ->where(function ($query) use ($appId) {
                $query->where('app_id', $appId)->orWhereNull('app_id');
            })
            ->where(function ($query) use ($normalized, $raw) {
                $url = $normalized['url'];
                $path = $normalized['path'];

                if ($url !== '') {
                    $query->orWhere('url', $url);
                }

                if ($path !== '') {
                    $query->orWhere('path', $path);
                }

                $query->orWhere('url', $raw)->orWhere('path', $raw);
            })
            ->first();

        $label = self::labelForItem($item);
        $tags = [
            'source' => 'app_item_image_sync',
            'usage' => 'app_item',
            'item_id' => (int) $item->id,
            'section_id' => (int) $item->section_id,
            'tab_key' => (string) ($item->section?->tab_key ?? ''),
            'original' => $raw,
        ];

        if ($existing) {
            $changed = false;

            if (!$existing->app_id) {
                $existing->app_id = $appId;
                $changed = true;
            }

            if (trim((string) $existing->label) === '') {
                $existing->label = $label;
                $changed = true;
            }

            if (trim((string) $existing->bucket) === '') {
                $existing->bucket = 'item-images';
                $changed = true;
            }

            if (!$existing->is_active) {
                $existing->is_active = true;
                $changed = true;
            }

            $existingTags = is_array($existing->tags_json) ? $existing->tags_json : [];
            $existing->tags_json = array_values(array_unique(array_merge($existingTags, $tags), SORT_REGULAR));
            $changed = true;

            if ($changed) {
                $existing->save();
                return 'updated';
            }

            return 'skipped';
        }

        [$width, $height, $size, $mime] = self::localFileMeta($normalized['path']);

        MediaAsset::create([
            'app_id' => $appId,
            'type' => 'image',
            'label' => $label,
            'bucket' => 'item-images',
            'disk' => 'public',
            'path' => $normalized['path'] !== '' ? $normalized['path'] : 'external',
            'url' => $normalized['url'],
            'mime' => $mime,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'tags_json' => $tags,
            'is_active' => true,
            'sort_order' => (int) ($item->sort_order ?? 0),
        ]);

        return 'created';
    }

    private static function normalizeImageValue(string $raw): array
    {
        $value = trim($raw);

        if ($value === '') {
            return ['path' => '', 'url' => ''];
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $path = self::pathFromStorageUrl($value);

            return [
                'path' => $path,
                'url' => $value,
            ];
        }

        if (str_starts_with($value, '/storage/')) {
            $path = ltrim(Str::after($value, '/storage/'), '/');

            return [
                'path' => $path,
                'url' => self::absoluteUrl($value),
            ];
        }

        if (str_starts_with($value, 'storage/')) {
            $path = ltrim(Str::after($value, 'storage/'), '/');

            return [
                'path' => $path,
                'url' => self::absoluteUrl('/storage/' . $path),
            ];
        }

        $path = ltrim($value, '/');

        return [
            'path' => $path,
            'url' => self::storageUrlForPath($path),
        ];
    }

    private static function pathFromStorageUrl(string $url): string
    {
        $parts = parse_url($url);
        $path = isset($parts['path']) ? (string) $parts['path'] : '';

        if ($path === '') {
            return '';
        }

        if (str_contains($path, '/storage/')) {
            return ltrim(Str::after($path, '/storage/'), '/');
        }

        return '';
    }

    private static function storageUrlForPath(string $path): string
    {
        if ($path === '' || strtolower($path) === 'external') {
            return '';
        }

        try {
            return self::absoluteUrl(Storage::disk('public')->url($path));
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function absoluteUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        $base = rtrim((string) config('app.url'), '/');
        if ($base === '') {
            return $url;
        }

        return $base . '/' . ltrim($url, '/');
    }

    private static function localFileMeta(string $path): array
    {
        $path = trim($path);

        if ($path === '' || strtolower($path) === 'external') {
            return [null, null, null, null];
        }

        try {
            if (! Storage::disk('public')->exists($path)) {
                return [null, null, null, null];
            }

            $fullPath = Storage::disk('public')->path($path);
            $size = is_file($fullPath) ? (@filesize($fullPath) ?: null) : null;
            $mime = is_file($fullPath) ? (@mime_content_type($fullPath) ?: null) : null;
            $width = null;
            $height = null;

            $imageInfo = @getimagesize($fullPath);
            if (is_array($imageInfo)) {
                $width = $imageInfo[0] ?? null;
                $height = $imageInfo[1] ?? null;
            }

            return [$width, $height, $size, $mime];
        } catch (\Throwable $e) {
            return [null, null, null, null];
        }
    }

    private static function labelForItem(AppItem $item): string
    {
        $title = trim((string) ($item->title ?? ''));
        if ($title !== '') {
            return $title;
        }

        return 'Item Image #' . (int) $item->id;
    }
}

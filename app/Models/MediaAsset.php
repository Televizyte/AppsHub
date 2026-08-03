<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MediaAsset extends Model
{
    protected $table = 'media_assets';

    protected $fillable = [
        'app_id',
        'type',
        'label',
        'bucket',
        'disk',
        'path',
        'url',
        'mime',
        'size',
        'width',
        'height',
        'tags_json',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'tags_json' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'app_id' => 'integer',
    ];

    /**
     * IMPORTANT SAFETY NOTE:
     * By default we DO NOT delete underlying files automatically when a record is deleted.
     * Enable explicitly with:
     *   MEDIA_ASSET_DELETE_FILES=true
     */
    protected static function booted(): void
    {
        static::deleting(function (self $asset) {
            $deleteEnabled = (bool) env('MEDIA_ASSET_DELETE_FILES', false);
            if (!$deleteEnabled) {
                return;
            }

            $disk = (string) ($asset->disk ?? 'public');
            $path = (string) ($asset->path ?? '');
            $url  = (string) ($asset->url ?? '');

            // Never delete external-only assets
            // (URL present but no local path OR path explicitly marked external)
            if ($path === '' || strtolower($path) === 'external') {
                return;
            }

            // If URL exists and clearly points outside our own storage, treat as external and never delete.
            // We only consider local-safe deletion when URL is empty or equals our storage URL for this path.
            try {
                $storageUrl = Storage::disk($disk)->url($path);
                if ($url !== '' && $storageUrl !== '' && $url !== $storageUrl) {
                    return;
                }
            } catch (\Throwable $e) {
                // If we can't reliably compute local URL, fail safe (do not delete)
                return;
            }

            // Restrict deletion to known local asset directories only (extra safety)
            // Prevent accidental deletes outside our asset roots.
            $normalized = ltrim($path, '/');
            $allowedRoots = [
                'assets/',
                'media/',
            ];

            $isAllowed = false;
            foreach ($allowedRoots as $root) {
                if (str_starts_with($normalized, $root)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                return;
            }

            // Finally: delete local file if it exists
            try {
                if (Storage::disk($disk)->exists($path)) {
                    Storage::disk($disk)->delete($path);
                }
            } catch (\Throwable $e) {
                // ignore - deletion failures should never block DB deletion
            }
        });
    }
}

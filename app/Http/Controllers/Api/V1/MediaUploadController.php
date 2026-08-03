<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\App;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadController extends Controller
{
    /**
     * POST /api/v1/apps/{appSlug}/media/cover
     *
     * Multipart form-data:
     * - file: image (jpg/png/webp)
     *
     * Optional form-data (to auto-attach to content_posts):
     * - idOrSlug: (string) content_posts.id or content_posts.slug
     * - bucket:   (string) optional extra guard (e.g. motivation, wordification)
     *
     * Header:
     * - X-Upload-Token: must match config('app.upload_token') (fallback env('UPLOAD_TOKEN'))
     *
     * NOTE:
     * - X-APP-TOKEN is enforced elsewhere (middleware). Keep sending it in curl calls.
     */
    public function uploadCover(Request $request, string $appSlug)
    {
        // Protect upload endpoint (admin/studio usage)
        if (!$this->authorizeToken($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHORIZED',
                'message' => 'Invalid upload token.',
            ], 401);
        }

        // Confirm app exists (multi-app isolation)
        $app = App::query()->where('slug', $appSlug)->first();
        if (!$app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => "App not found: {$appSlug}",
            ], 404);
        }

        // Validate file
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'], // 5MB
        ]);

        $file = $request->file('file');
        if (!$file) {
            return response()->json([
                'ok' => false,
                'error' => 'NO_FILE',
                'message' => 'No file received.',
            ], 422);
        }

        // Store (public disk) under the centralized media library folder
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $safeExt = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'jpg';

        $name = 'cover_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(10)) . '.' . $safeExt;
        $dir  = "media/{$appSlug}/content_cover";
        $path = $file->storeAs($dir, $name, 'public');

        // Build public URL
        $publicPath = Storage::disk('public')->url($path); // /storage/...
        $url = $this->absoluteUrl($publicPath);

        // Optional: dimensions
        $width = null;
        $height = null;
        try {
            $full = Storage::disk('public')->path($path);
            $info = @getimagesize($full);
            if (is_array($info)) {
                $width = $info[0] ?? null;
                $height = $info[1] ?? null;
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Create media_assets record (centralized library)
        $mime = (string) ($file->getMimeType() ?: '');
        $size = (int) ($file->getSize() ?: 0);

        $asset = MediaAsset::create([
            'app_id' => (int) $app->id,
            'type' => 'image',
            'label' => 'Content Cover',
            'bucket' => 'content_cover',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $mime !== '' ? $mime : null,
            'size' => $size > 0 ? $size : null,
            'width' => is_int($width) ? $width : null,
            'height' => is_int($height) ? $height : null,
            'tags_json' => [],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        // Optional: auto-attach to a content post
        $attached = null;

        $idOrSlug = trim((string) $request->input('idOrSlug', ''));
        $bucket   = trim((string) $request->input('bucket', ''));

        if ($idOrSlug !== '') {
            $post = $this->findContentPost((int) $app->id, $idOrSlug, $bucket !== '' ? $bucket : null);

            if ($post) {
                $oldCoverAssetId = $this->resolveOldCoverAssetId(
                    (int) $app->id,
                    $post->meta_json ?? null,
                    $post->cover_image_url ?? null
                );

                // NOTE: content_posts has no cover_asset_id column.
                // We persist linkage inside meta_json safely (no schema change).
                $metaUpdate = DB::raw(
                    "JSON_SET(
                        COALESCE(meta_json, JSON_OBJECT()),
                        '$.cover_asset_id', " . (int) $asset->id . ",
                        '$.cover_asset_bucket', " . DB::getPdo()->quote((string) $asset->bucket) . ",
                        '$.cover_asset_path', " . DB::getPdo()->quote((string) $asset->path) . ",
                        '$.cover_asset_url', " . DB::getPdo()->quote((string) $asset->url) . ",
                        '$.cover_updated_at', " . DB::getPdo()->quote((string) now()->toISOString()) . "
                    )"
                );

                DB::transaction(function () use ($post, $url, $metaUpdate) {
                    DB::table('content_posts')
                        ->where('id', (int) $post->id)
                        ->update([
                            'cover_image_url' => $url,
                            'meta_json' => $metaUpdate,
                            'updated_at' => now(),
                        ]);
                });

                // ✅ Auto-clean old cover asset if not used anywhere else
                $this->cleanupOldCoverAssetIfSafe(
                    (int) $app->id,
                    (int) $post->id,
                    $oldCoverAssetId,
                    (int) $asset->id
                );

                $attached = [
                    'id' => (int) $post->id,
                    'slug' => (string) ($post->slug ?? ''),
                    'bucket' => (string) ($post->bucket ?? ''),
                    'cover_image_url' => $url,
                    'cover_asset_id' => (int) $asset->id,
                    'cover_asset_path' => (string) $asset->path,
                    'cover_asset_url' => (string) $asset->url,
                ];
            } else {
                $attached = [
                    'error' => 'POST_NOT_FOUND',
                    'message' => 'Upload succeeded, but the target post was not found for this app (or bucket mismatch).',
                    'idOrSlug' => $idOrSlug,
                    'bucket' => $bucket !== '' ? $bucket : null,
                ];
            }
        }

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'slug' => (string) $app->slug,
                'name' => (string) $app->name,
            ],
            'asset' => [
                'id' => (int) $asset->id,
                'bucket' => (string) $asset->bucket,
                'path' => (string) $asset->path,
                'url' => (string) $asset->url,
            ],
            'file' => [
                'path' => $path,
                'url' => $url,
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
                'width' => $width,
                'height' => $height,
            ],
            'attached' => $attached,
        ]);
    }

    /**
     * PATCH /api/v1/apps/{appSlug}/content/{idOrSlug}/cover
     *
     * JSON body:
     * - url: required (absolute URL)
     * - bucket: optional (extra guard)
     *
     * Header:
     * - X-Upload-Token: must match config('app.upload_token') (fallback env('UPLOAD_TOKEN'))
     */
    public function setCoverUrl(Request $request, string $appSlug, string $idOrSlug)
    {
        if (!$this->authorizeToken($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHORIZED',
                'message' => 'Invalid upload token.',
            ], 401);
        }

        $app = App::query()->where('slug', $appSlug)->first();
        if (!$app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => "App not found: {$appSlug}",
            ], 404);
        }

        $request->validate([
            'url' => ['required', 'string', 'max:2048', 'url'],
            'bucket' => ['nullable', 'string', 'max:255'],
        ]);

        $url = trim((string) $request->input('url'));
        $bucket = trim((string) $request->input('bucket', ''));
        $bucket = $bucket !== '' ? $bucket : null;

        $post = $this->findContentPost((int) $app->id, $idOrSlug, $bucket);
        if (!$post) {
            return response()->json([
                'ok' => false,
                'error' => 'POST_NOT_FOUND',
                'message' => 'Content post not found for this app (or bucket mismatch).',
                'idOrSlug' => $idOrSlug,
                'bucket' => $bucket,
            ], 404);
        }

        $oldCoverAssetId = $this->resolveOldCoverAssetId(
            (int) $app->id,
            $post->meta_json ?? null,
            $post->cover_image_url ?? null
        );

        // Register URL-only asset in library (so everything is centralized)
        // NOTE: external covers must NEVER be auto-deleted later.
        $asset = MediaAsset::create([
            'app_id' => (int) $app->id,
            'type' => 'image',
            'label' => 'Content Cover (URL)',
            'bucket' => 'content_cover',
            'disk' => 'public',
            'path' => 'external',
            'url' => $url,
            'mime' => null,
            'size' => null,
            'width' => null,
            'height' => null,
            'tags_json' => ['external'],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $metaUpdate = DB::raw(
            "JSON_SET(
                COALESCE(meta_json, JSON_OBJECT()),
                '$.cover_asset_id', " . (int) $asset->id . ",
                '$.cover_asset_bucket', " . DB::getPdo()->quote((string) $asset->bucket) . ",
                '$.cover_asset_path', " . DB::getPdo()->quote((string) $asset->path) . ",
                '$.cover_asset_url', " . DB::getPdo()->quote((string) $asset->url) . ",
                '$.cover_updated_at', " . DB::getPdo()->quote((string) now()->toISOString()) . "
            )"
        );

        DB::transaction(function () use ($post, $url, $metaUpdate) {
            DB::table('content_posts')
                ->where('id', (int) $post->id)
                ->update([
                    'cover_image_url' => $url,
                    'meta_json' => $metaUpdate,
                    'updated_at' => now(),
                ]);
        });

        // ✅ Auto-clean old cover asset if not used anywhere else
        $this->cleanupOldCoverAssetIfSafe(
            (int) $app->id,
            (int) $post->id,
            $oldCoverAssetId,
            (int) $asset->id
        );

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'slug' => (string) $app->slug,
                'name' => (string) $app->name,
            ],
            'post' => [
                'id' => (int) $post->id,
                'slug' => (string) ($post->slug ?? ''),
                'bucket' => (string) ($post->bucket ?? ''),
                'cover_image_url' => $url,
                'cover_asset_id' => (int) $asset->id,
            ],
        ]);
    }

    // -----------------------
    // Helpers
    // -----------------------

    private function authorizeToken(Request $request): bool
    {
        $expected = (string) config('app.upload_token', env('UPLOAD_TOKEN'));
        $given = (string) $request->header('X-Upload-Token');

        if ($expected === '') return false;

        return hash_equals($expected, $given);
    }

    /**
     * Find post and include meta_json + cover_image_url so we can detect previous cover asset.
     */
    private function findContentPost(int $appId, string $idOrSlug, ?string $bucket = null): ?object
    {
        $idOrSlug = trim($idOrSlug);
        if ($idOrSlug === '') return null;

        $q = DB::table('content_posts')
            ->select(['id', 'slug', 'bucket', 'meta_json', 'cover_image_url'])
            ->where('app_id', $appId);

        if (ctype_digit($idOrSlug)) {
            $q->where('id', (int) $idOrSlug);
        } else {
            $q->where('slug', $idOrSlug);
        }

        if (is_string($bucket) && trim($bucket) !== '') {
            $q->where('bucket', trim($bucket));
        }

        return $q->first();
    }

    /**
     * Resolve old cover asset id from:
     * 1) meta_json.cover_asset_id (preferred)
     * 2) matching cover_image_url -> media_assets.url (fallback)
     */
    private function resolveOldCoverAssetId(int $appId, $metaJson, ?string $coverImageUrl): ?int
    {
        $fromMeta = $this->extractCoverAssetIdFromMeta($metaJson);
        if ($fromMeta && $fromMeta > 0) {
            return $fromMeta;
        }

        $coverImageUrl = is_string($coverImageUrl) ? trim($coverImageUrl) : '';
        if ($coverImageUrl === '') return null;

        $asset = MediaAsset::query()
            ->where('app_id', $appId)
            ->where('bucket', 'content_cover')
            ->where('url', $coverImageUrl)
            ->first();

        return $asset ? (int) $asset->id : null;
    }

    private function extractCoverAssetIdFromMeta($metaJson): ?int
    {
        if ($metaJson === null) return null;

        if (is_array($metaJson)) {
            $val = $metaJson['cover_asset_id'] ?? null;
            $id = is_numeric($val) ? (int) $val : null;
            return $id && $id > 0 ? $id : null;
        }

        if (is_string($metaJson) && trim($metaJson) !== '') {
            $decoded = json_decode($metaJson, true);
            if (is_array($decoded)) {
                $val = $decoded['cover_asset_id'] ?? null;
                $id = is_numeric($val) ? (int) $val : null;
                return $id && $id > 0 ? $id : null;
            }
        }

        return null;
    }

    /**
     * Deletes old cover asset (and file via model hook) ONLY if no other post still references it.
     *
     * Extra safety:
     * - Never delete "external" assets (path === 'external' OR tags contain 'external')
     * - Type-proof: compares JSON_UNQUOTE(meta_json.cover_asset_id) as string to old asset id as string.
     */
    private function cleanupOldCoverAssetIfSafe(int $appId, int $postId, ?int $oldAssetId, int $newAssetId): void
    {
        if (!$oldAssetId || $oldAssetId <= 0) return;
        if ($oldAssetId === $newAssetId) return;

        try {
            // Load old asset first (and limit to content_cover for safety)
            $old = MediaAsset::query()
                ->where('app_id', $appId)
                ->where('id', $oldAssetId)
                ->where('bucket', 'content_cover')
                ->first();

            if (!$old) return;

            // ✅ Hard lock: never delete URL-only / external covers
            $oldPath = is_string($old->path) ? trim($old->path) : '';

            $oldTags = [];
            if (is_array($old->tags_json)) {
                $oldTags = $old->tags_json;
            } elseif (is_string($old->tags_json) && trim($old->tags_json) !== '') {
                $decodedTags = json_decode($old->tags_json, true);
                if (is_array($decodedTags)) {
                    $oldTags = $decodedTags;
                }
            }

            if ($oldPath === 'external' || in_array('external', $oldTags, true)) {
                return;
            }

            $oldUrl = is_string($old->url) ? trim($old->url) : '';
            $oldIdStr = (string) ((int) $oldAssetId);

            // Is the old asset referenced by any other post?
            $count = DB::table('content_posts')
                ->where('app_id', $appId)
                ->where('id', '!=', $postId)
                ->where(function ($q) use ($oldIdStr, $oldUrl) {
                    // meta_json reference (type-proof)
                    $q->whereRaw(
                        "JSON_UNQUOTE(JSON_EXTRACT(meta_json,'$.cover_asset_id')) = ?",
                        [$oldIdStr]
                    );

                    // fallback / legacy reference (cover_image_url directly equals asset url)
                    if ($oldUrl !== '') {
                        $q->orWhere('cover_image_url', $oldUrl);
                    }
                })
                ->count();

            if ($count > 0) {
                return; // still in use somewhere else
            }

            // Safe to delete
            $old->delete(); // triggers file delete via MediaAsset::deleting()
        } catch (\Throwable $e) {
            // fail-safe: never break uploads because cleanup failed
        }
    }

    private function absoluteUrl(string $maybeRelative): string
    {
        if (preg_match('#^https?://#i', $maybeRelative)) {
            return $maybeRelative;
        }

        $base = rtrim((string) config('app.url'), '/');
        if ($base === '') {
            return $maybeRelative;
        }

        return $base . $maybeRelative;
    }
}

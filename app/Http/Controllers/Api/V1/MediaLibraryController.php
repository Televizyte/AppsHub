<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\App;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaLibraryController extends Controller
{
    /**
     * GET /api/v1/apps/{appSlug}/media
     *
     * Query:
     * - q: search label/path/url
     * - bucket: filter
     * - type: filter (image, video, doc... )
     * - per_page: default 24, max 60
     */
    public function index(Request $request, string $appSlug)
    {
        $app = App::query()->where('slug', $appSlug)->first();

        if (!$app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => "App not found: {$appSlug}",
            ], 404);
        }

        $q = trim((string) $request->query('q', ''));
        $bucket = trim((string) $request->query('bucket', ''));
        $type = trim((string) $request->query('type', ''));

        $perPage = (int) $request->query('per_page', 24);
        if ($perPage < 1) $perPage = 24;
        if ($perPage > 60) $perPage = 60;

        $query = MediaAsset::query()
            ->where('app_id', $app->id)
            ->where('is_active', true);

        if ($bucket !== '') {
            $query->where('bucket', $bucket);
        }

        if ($type !== '') {
            $query->where('type', $type);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('label', 'like', '%' . $q . '%')
                    ->orWhere('path', 'like', '%' . $q . '%')
                    ->orWhere('url', 'like', '%' . $q . '%');
            });
        }

        $assets = $query
            ->orderByDesc('id')
            ->paginate($perPage);

        $assets->getCollection()->transform(function (MediaAsset $asset) {
            return $this->normalizeAsset($asset);
        });

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'slug' => (string) $app->slug,
                'name' => (string) $app->name,
            ],
            'assets' => $assets,
        ]);
    }

    /**
     * GET /api/v1/apps/{appSlug}/media/{assetId}
     */
    public function show(Request $request, string $appSlug, int $assetId)
    {
        $app = App::query()->where('slug', $appSlug)->first();

        if (!$app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => "App not found: {$appSlug}",
            ], 404);
        }

        $asset = MediaAsset::query()
            ->where('app_id', $app->id)
            ->where('id', $assetId)
            ->first();

        if (!$asset) {
            return response()->json([
                'ok' => false,
                'error' => 'ASSET_NOT_FOUND',
                'message' => 'Media asset not found.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'asset' => $this->normalizeAsset($asset),
        ]);
    }

    /**
     * POST /api/v1/apps/{appSlug}/media
     *
     * Multipart form-data:
     * - file: required
     * - label: optional
     * - bucket: optional (branding, hub, content_cover, etc.)
     * - tags: optional comma-separated string
     *
     * Headers:
     * - X-Upload-Token: must match config('app.upload_token')
     */
    public function upload(Request $request, string $appSlug)
    {
        if (!$this->authorizeUploadToken($request)) {
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
            'file' => ['required', 'file', 'max:10240'], // 10MB
            'label' => ['nullable', 'string', 'max:180'],
            'bucket' => ['nullable', 'string', 'max:80'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        $file = $request->file('file');
        if (!$file) {
            return response()->json([
                'ok' => false,
                'error' => 'NO_FILE',
                'message' => 'No file received.',
            ], 422);
        }

        $bucket = trim((string) $request->input('bucket', ''));
        $bucket = $bucket !== '' ? $bucket : 'library';

        $label = trim((string) $request->input('label', ''));
        $label = $label !== '' ? $label : null;

        $mime = (string) ($file->getMimeType() ?: '');
        $size = (int) ($file->getSize() ?: 0);

        $type = Str::startsWith($mime, 'image/') ? 'image'
            : (Str::startsWith($mime, 'video/') ? 'video'
                : (Str::startsWith($mime, 'audio/') ? 'audio' : 'file'));

        $ext = strtolower($file->getClientOriginalExtension() ?: '');
        if ($ext === '') {
            $ext = $type === 'image' ? 'jpg' : 'bin';
        }

        $name = $bucket . '_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(10)) . '.' . $ext;

        // ✅ Locked convention
        $dir  = "media/{$appSlug}/{$bucket}";
        $path = $file->storeAs($dir, $name, 'public');

        $publicPath = Storage::disk('public')->url($path); // /storage/...
        $url = $this->absoluteUrl($publicPath);

        $width = null;
        $height = null;

        if ($type === 'image') {
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
        }

        $tags = trim((string) $request->input('tags', ''));
        $tagsArr = [];
        if ($tags !== '') {
            $tagsArr = collect(explode(',', $tags))
                ->map(fn ($t) => trim($t))
                ->filter()
                ->values()
                ->all();
        }

        $asset = MediaAsset::create([
            'app_id' => $app->id,
            'type' => $type,
            'label' => $label,
            'bucket' => $bucket,
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $mime !== '' ? $mime : null,
            'size' => $size > 0 ? $size : null,
            'width' => is_int($width) ? $width : null,
            'height' => is_int($height) ? $height : null,
            'tags_json' => $tagsArr,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'slug' => (string) $app->slug,
                'name' => (string) $app->name,
            ],
            'asset' => $this->normalizeAsset($asset),
        ]);
    }

    /**
     * DELETE /api/v1/apps/{appSlug}/media/{assetId}
     */
    public function destroy(Request $request, string $appSlug, int $assetId)
    {
        if (!$this->authorizeUploadToken($request)) {
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

        $asset = MediaAsset::query()
            ->where('app_id', $app->id)
            ->where('id', $assetId)
            ->first();

        if (!$asset) {
            return response()->json([
                'ok' => false,
                'error' => 'ASSET_NOT_FOUND',
                'message' => 'Media asset not found.',
            ], 404);
        }

        $asset->delete();

        return response()->json([
            'ok' => true,
            'deleted' => true,
            'asset_id' => $assetId,
        ]);
    }

    // -----------------------
    // Helpers
    // -----------------------

    private function authorizeUploadToken(Request $request): bool
    {
        $expected = (string) config('app.upload_token', '');
        $given = (string) $request->header('X-Upload-Token');

        if ($expected === '') return false;

        return hash_equals($expected, $given);
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

    private function normalizeAsset(MediaAsset $asset): array
    {
        $publicUrl = $asset->url;

        if (!$publicUrl || !preg_match('#^https?://#i', $publicUrl)) {
            $disk = (string) ($asset->disk ?: 'public');
            $path = (string) ($asset->path ?: '');

            if ($path !== '' && Storage::disk($disk)->exists($path)) {
                $publicUrl = $this->absoluteUrl(Storage::disk($disk)->url($path));
            }
        }

        return [
            'id' => (int) $asset->id,
            'app_id' => (int) ($asset->app_id ?? 0),
            'type' => (string) $asset->type,
            'label' => $asset->label,
            'bucket' => $asset->bucket,
            'disk' => (string) $asset->disk,
            'path' => (string) $asset->path,
            'url' => $asset->url,
            'public_url' => $publicUrl,
            'mime' => $asset->mime,
            'size' => $asset->size,
            'width' => $asset->width,
            'height' => $asset->height,
            'tags' => is_array($asset->tags_json) ? $asset->tags_json : [],
            'is_active' => (bool) $asset->is_active,
            'sort_order' => (int) $asset->sort_order,
            'created_at' => $asset->created_at,
            'updated_at' => $asset->updated_at,
        ];
    }
}

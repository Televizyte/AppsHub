<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\App;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppBrandingController extends Controller
{
    /**
     * PATCH /api/v1/apps/{appSlug}/branding/assets
     *
     * Headers:
     * - X-Upload-Token: must match env('UPLOAD_TOKEN')
     *
     * JSON body (any of these are optional):
     * - logo_asset_id:      int
     * - banner_asset_id:    int
     * - splash_asset_id:    int
     * - app_icon_asset_id:  int
     *
     * OR direct paths (storage path on public disk) OR URLs:
     * - logo_path:     string
     * - banner_path:   string
     * - splash_path:   string
     * - app_icon_path: string
     *
     * Notes:
     * - If you pass an *_asset_id, we store the asset->path into branding_json.*_path
     * - If you pass a *_path:
     *   - if it's http(s), we store it directly (works with external CDN)
     *   - if it's a relative storage path, we store it as-is (recommended)
     */
    public function setAssets(Request $request, string $appSlug)
    {
        if (!$this->authorizeUploadToken($request)) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHORIZED',
                'message' => 'Invalid upload token.',
            ], 401);
        }

        /** @var \App\Models\App|null $appFromMiddleware */
        $appFromMiddleware = $request->attributes->get('app');
        $app = $appFromMiddleware instanceof App
            ? $appFromMiddleware
            : App::query()->where('slug', $appSlug)->first();

        if (!$app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => "App not found: {$appSlug}",
            ], 404);
        }

        $request->validate([
            'logo_asset_id' => ['nullable', 'integer', 'min:1'],
            'banner_asset_id' => ['nullable', 'integer', 'min:1'],
            'splash_asset_id' => ['nullable', 'integer', 'min:1'],
            'app_icon_asset_id' => ['nullable', 'integer', 'min:1'],

            'logo_path' => ['nullable', 'string', 'max:2048'],
            'banner_path' => ['nullable', 'string', 'max:2048'],
            'splash_path' => ['nullable', 'string', 'max:2048'],
            'app_icon_path' => ['nullable', 'string', 'max:2048'],
        ]);

        $updates = [];

        // Asset ID -> path mapping
        $assetMap = [
            'logo_asset_id' => 'logo_path',
            'banner_asset_id' => 'banner_path',
            'splash_asset_id' => 'splash_path',
            'app_icon_asset_id' => 'app_icon_path',
        ];

        foreach ($assetMap as $assetIdKey => $brandingKey) {
            $id = (int) $request->input($assetIdKey, 0);
            if ($id > 0) {
                $asset = $this->findAllowedAsset($app->id, $id);
                if (!$asset) {
                    return response()->json([
                        'ok' => false,
                        'error' => 'ASSET_NOT_FOUND',
                        'message' => "Asset not found or not allowed for this app: {$id}",
                        'asset_id' => $id,
                    ], 404);
                }

                $updates[$brandingKey] = (string) $asset->path;
            }
        }

        // Direct path/url fields
        $pathFields = ['logo_path', 'banner_path', 'splash_path', 'app_icon_path'];
        foreach ($pathFields as $k) {
            if ($request->has($k)) {
                $val = trim((string) $request->input($k, ''));
                if ($val === '') {
                    // Allow clearing by sending empty string
                    $updates[$k] = null;
                } else {
                    // Store as-is. (If URL, keep URL; if storage path, keep storage path)
                    $updates[$k] = $val;
                }
            }
        }

        if (empty($updates)) {
            return response()->json([
                'ok' => false,
                'error' => 'NO_UPDATES',
                'message' => 'No branding asset fields provided.',
            ], 422);
        }

        // Apply JSON updates safely
        $json = is_array($app->branding_json) ? $app->branding_json : [];
        foreach ($updates as $key => $value) {
            // We store into branding_json[key]
            // (logo_path/banner_path/splash_path/app_icon_path)
            $json[$key] = $value;
        }

        DB::table('apps')
            ->where('id', (int) $app->id)
            ->update([
                'branding_json' => json_encode($json, JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);

        // Reload fresh
        $fresh = App::query()->where('id', (int) $app->id)->first();

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'slug' => (string) $app->slug,
                'name' => (string) $app->name,
            ],
            'branding' => [
                'raw' => is_array($fresh?->branding_json) ? $fresh->branding_json : $json,
                'assets' => [
                    'logo_url' => $this->resolvePublicUrl(data_get($fresh?->branding_json, 'logo_path')),
                    'logo_path' => data_get($fresh?->branding_json, 'logo_path'),
                    'banner_url' => $this->resolvePublicUrl(data_get($fresh?->branding_json, 'banner_path')),
                    'banner_path' => data_get($fresh?->branding_json, 'banner_path'),
                    'splash_url' => $this->resolvePublicUrl(data_get($fresh?->branding_json, 'splash_path')),
                    'splash_path' => data_get($fresh?->branding_json, 'splash_path'),
                    'app_icon_url' => $this->resolvePublicUrl(data_get($fresh?->branding_json, 'app_icon_path')),
                    'app_icon_path' => data_get($fresh?->branding_json, 'app_icon_path'),
                ],
            ],
            'updated' => $updates,
        ]);
    }

    // -----------------------
    // Helpers
    // -----------------------

    private function authorizeUploadToken(Request $request): bool
    {
        $expected = (string) config('app.upload_token', env('UPLOAD_TOKEN'));
        $given = (string) $request->header('X-Upload-Token');

        if ($expected === '') return false;

        return hash_equals($expected, $given);
    }

    /**
     * Allow assets that belong to this app OR shared (app_id null).
     */
    private function findAllowedAsset(int $appId, int $assetId): ?MediaAsset
    {
        return MediaAsset::query()
            ->where('id', $assetId)
            ->where(function ($q) use ($appId) {
                $q->whereNull('app_id')->orWhere('app_id', $appId);
            })
            ->first();
    }

    private function resolvePublicUrl($val): ?string
    {
        if (!is_string($val) || trim($val) === '') return null;

        $val = trim($val);

        if (Str::startsWith($val, ['http://', 'https://'])) {
            return $val;
        }

        // Storage path on public disk
        return Storage::disk('public')->exists($val)
            ? Storage::disk('public')->url($val)
            : Storage::disk('public')->url($val); // still return a URL even if file missing, for debugging
    }
}

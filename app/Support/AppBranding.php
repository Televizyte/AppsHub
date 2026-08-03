<?php

namespace App\Support;

use App\Models\App;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppBranding
{
    public static function branding(App $app): array
    {
        return is_array($app->branding_json) ? $app->branding_json : [];
    }

    public static function resolveUrl(mixed $value): ?string
    {
        $raw = is_string($value) ? trim($value) : '';

        if ($raw === '') {
            return null;
        }

        if (Str::startsWith($raw, ['http://', 'https://'])) {
            return $raw;
        }

        try {
            return Storage::disk('public')->exists($raw)
                ? Storage::disk('public')->url($raw)
                : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function assetUrl(App $app, string $name): ?string
    {
        $branding = self::branding($app);

        $url = self::resolveUrl(data_get($branding, $name . '_url'));
        if ($url !== null) {
            return $url;
        }

        $path = self::resolveUrl(data_get($branding, $name . '_path'));
        if ($path !== null) {
            return $path;
        }

        $assetId = data_get($branding, $name . '_asset_id');
        $assetId = is_numeric($assetId) ? (int) $assetId : 0;

        if ($assetId <= 0) {
            return null;
        }

        try {
            $asset = MediaAsset::query()->find($assetId);
            if (! $asset) {
                return null;
            }

            $assetUrl = self::resolveUrl($asset->url ?? null);
            if ($assetUrl !== null) {
                return $assetUrl;
            }

            return self::resolveUrl($asset->path ?? null);
        } catch (\Throwable $e) {
            return null;
        }
    }

    public static function logoUrl(?App $app): ?string
    {
        return $app ? self::assetUrl($app, 'logo') : null;
    }

    public static function bannerUrl(?App $app): ?string
    {
        return $app ? self::assetUrl($app, 'banner') : null;
    }

    public static function splashUrl(?App $app): ?string
    {
        return $app ? self::assetUrl($app, 'splash') : null;
    }

    public static function appIconUrl(?App $app): ?string
    {
        return $app ? self::assetUrl($app, 'app_icon') : null;
    }

    public static function assetsPayload(App $app): array
    {
        $branding = self::branding($app);

        return [
            'logo_url' => self::logoUrl($app),
            'logo_path' => data_get($branding, 'logo_path'),
            'logo_asset_id' => data_get($branding, 'logo_asset_id'),
            'banner_url' => self::bannerUrl($app),
            'banner_path' => data_get($branding, 'banner_path'),
            'banner_asset_id' => data_get($branding, 'banner_asset_id'),
            'splash_url' => self::splashUrl($app),
            'splash_path' => data_get($branding, 'splash_path'),
            'splash_asset_id' => data_get($branding, 'splash_asset_id'),
            'app_icon_url' => self::appIconUrl($app),
            'app_icon_path' => data_get($branding, 'app_icon_path'),
            'app_icon_asset_id' => data_get($branding, 'app_icon_asset_id'),
        ];
    }

    public static function themePayload(App $app): array
    {
        $branding = self::branding($app);

        return [
            'primary_color' => data_get($branding, 'primary_color'),
            'accent_color' => data_get($branding, 'accent_color'),
            'background_color' => data_get($branding, 'background_color'),
            'text_color' => data_get($branding, 'text_color'),
            'theme_mode' => data_get($branding, 'theme_mode', 'dark'),
            'font_family' => data_get($branding, 'font_family', 'system'),
            'button_style' => data_get($branding, 'button_style', 'rounded'),
            'gradient_presets' => data_get($branding, 'gradient_presets', []),
        ];
    }
}

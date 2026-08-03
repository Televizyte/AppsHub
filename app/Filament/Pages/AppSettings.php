<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\MediaAsset;
use App\Support\ActiveApp;
use App\Support\AppBranding;
use App\Support\AdminMode;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AppSettings extends Page
{
    use WithFileUploads;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'App Settings';
    protected static ?string $navigationGroup = 'Workspace';
    protected static ?int $navigationSort = 93;
    protected static ?string $slug = 'app-settings';

    protected static string $view = 'filament.pages.app-settings';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('app_settings');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('app_settings');
    }

    public ?App $activeApp = null;

    public array $profile = [
        'display_name' => '',
        'tagline' => '',
        'about' => '',
    ];



    /**
     * Temporary image uploads for the graphics cards.
     * Keys: logo, banner, splash, app_icon.
     */
    public array $graphicUploads = [];

    /**
     * Selected Media Library asset IDs for each graphics card.
     * Keys: logo, banner, splash, app_icon.
     */
    public array $mediaLibrarySelection = [
        'logo' => '',
        'banner' => '',
        'splash' => '',
        'app_icon' => '',
    ];

    /**
     * Compact image picker list loaded from the active app + shared Media Library.
     */
    public array $mediaLibraryAssets = [];

    public array $graphics = [
        'logo_path' => '',
        'logo_url' => '',
        'logo_asset_id' => '',
        'banner_path' => '',
        'banner_url' => '',
        'banner_asset_id' => '',
        'splash_path' => '',
        'splash_url' => '',
        'splash_asset_id' => '',
        'app_icon_path' => '',
        'app_icon_url' => '',
        'app_icon_asset_id' => '',
    ];

    public array $theme = [
        'primary_color' => '',
        'accent_color' => '',
        'background_color' => '',
        'text_color' => '',
        'theme_mode' => 'dark',
        'font_family' => 'system',
        'button_style' => 'rounded',
    ];

    public array $legal = [
        'privacy_url' => '',
        'terms_url' => '',
    ];

    public array $store = [
        'play_store_url' => '',
        'package_name' => '',
        'version_name' => '',
        'version_code' => '',
    ];

    /**
     * Dynamic public contact/support channels.
     * Each row: key, label, type, value, description, icon, enabled.
     */
    public array $supportChannels = [];

    /**
     * Dynamic official/public links.
     * Each row: key, label, type, value, description, icon, enabled.
     */
    public array $officialLinks = [];

    /**
     * Dynamic custom frontend settings.
     * Each row: key, label, type, value, description, group, enabled.
     */
    public array $customSettings = [];


    public function mount(): void
    {
        $this->loadSettings();
    }

    public function loadSettings(): void
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        $this->activeApp = $activeAppId > 0
            ? App::query()->find($activeAppId)
            : null;

        if (! $this->activeApp) {
            return;
        }

        $this->graphicUploads = [
            'logo' => null,
            'banner' => null,
            'splash' => null,
            'app_icon' => null,
        ];

        $branding = is_array($this->activeApp->branding_json)
            ? $this->activeApp->branding_json
            : [];

        $this->profile = [
            'display_name' => (string) data_get($branding, 'display_name', $this->activeApp->name),
            'tagline' => (string) data_get($branding, 'tagline', ''),
            'about' => (string) data_get($branding, 'about', ''),
        ];

        $assets = AppBranding::assetsPayload($this->activeApp);
        $this->graphics = [
            'logo_path' => (string) data_get($branding, 'logo_path', ''),
            'logo_url' => (string) data_get($assets, 'logo_url', ''),
            'logo_asset_id' => (string) data_get($branding, 'logo_asset_id', ''),
            'banner_path' => (string) data_get($branding, 'banner_path', ''),
            'banner_url' => (string) data_get($assets, 'banner_url', ''),
            'banner_asset_id' => (string) data_get($branding, 'banner_asset_id', ''),
            'splash_path' => (string) data_get($branding, 'splash_path', ''),
            'splash_url' => (string) data_get($assets, 'splash_url', ''),
            'splash_asset_id' => (string) data_get($branding, 'splash_asset_id', ''),
            'app_icon_path' => (string) data_get($branding, 'app_icon_path', ''),
            'app_icon_url' => (string) data_get($assets, 'app_icon_url', ''),
            'app_icon_asset_id' => (string) data_get($branding, 'app_icon_asset_id', ''),
        ];

        $this->mediaLibrarySelection = [
            'logo' => (string) ($this->graphics['logo_asset_id'] ?? ''),
            'banner' => (string) ($this->graphics['banner_asset_id'] ?? ''),
            'splash' => (string) ($this->graphics['splash_asset_id'] ?? ''),
            'app_icon' => (string) ($this->graphics['app_icon_asset_id'] ?? ''),
        ];

        $this->loadMediaLibraryAssets($this->activeApp);

        $this->theme = [
            'primary_color' => (string) data_get($branding, 'primary_color', ''),
            'accent_color' => (string) data_get($branding, 'accent_color', ''),
            'background_color' => (string) data_get($branding, 'background_color', ''),
            'text_color' => (string) data_get($branding, 'text_color', ''),
            'theme_mode' => (string) data_get($branding, 'theme_mode', 'dark'),
            'font_family' => (string) data_get($branding, 'font_family', 'system'),
            'button_style' => (string) data_get($branding, 'button_style', 'rounded'),
        ];

        $this->legal = [
            'privacy_url' => (string) data_get($branding, 'legal.privacy_url', data_get($branding, 'store.privacy_policy_url', '')),
            'terms_url' => (string) data_get($branding, 'legal.terms_url', ''),
        ];

        $this->store = [
            'play_store_url' => (string) data_get($branding, 'store.play_store_url', ''),
            'package_name' => (string) data_get($branding, 'store.package_name', ''),
            'version_name' => (string) data_get($branding, 'store.version_name', ''),
            'version_code' => (string) data_get($branding, 'store.version_code', ''),
        ];

        $this->supportChannels = $this->normalizeRows(
            data_get($branding, 'support_channels', []),
            $this->legacySupportRows($branding),
            'support'
        );

        $this->officialLinks = $this->normalizeRows(
            data_get($branding, 'official_links', []),
            $this->legacyOfficialLinkRows($branding),
            'link'
        );

        $this->customSettings = $this->normalizeCustomRows(
            data_get($branding, 'custom_public_settings', [])
        );
    }

    public function save(): void
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        $app = $activeAppId > 0
            ? App::query()->find($activeAppId)
            : null;

        if (! $app) {
            Notification::make()
                ->title('No active app selected')
                ->body('Select an active app first, then try again.')
                ->danger()
                ->send();

            return;
        }

        $branding = is_array($app->branding_json) ? $app->branding_json : [];

        $branding['display_name'] = $this->cleanText($this->profile['display_name'] ?? $app->name);
        $branding['tagline'] = $this->cleanText($this->profile['tagline'] ?? '');
        $branding['about'] = $this->cleanText($this->profile['about'] ?? '');

        foreach (['logo', 'banner', 'splash', 'app_icon'] as $assetKey) {
            $branding[$assetKey . '_path'] = $this->cleanAssetValue($this->graphics[$assetKey . '_path'] ?? '');
            $branding[$assetKey . '_url'] = $this->cleanAssetValue($this->graphics[$assetKey . '_url'] ?? '');
            $assetId = $this->cleanText($this->graphics[$assetKey . '_asset_id'] ?? '');
            $branding[$assetKey . '_asset_id'] = is_numeric($assetId) && (int) $assetId > 0 ? (int) $assetId : null;
        }

        $branding['primary_color'] = $this->cleanText($this->theme['primary_color'] ?? '');
        $branding['accent_color'] = $this->cleanText($this->theme['accent_color'] ?? '');
        $branding['background_color'] = $this->cleanText($this->theme['background_color'] ?? '');
        $branding['text_color'] = $this->cleanText($this->theme['text_color'] ?? '');
        $branding['theme_mode'] = in_array((string) ($this->theme['theme_mode'] ?? 'dark'), ['dark', 'light', 'system'], true)
            ? (string) $this->theme['theme_mode']
            : 'dark';
        $branding['font_family'] = $this->cleanText($this->theme['font_family'] ?? 'system') ?: 'system';
        $branding['button_style'] = in_array((string) ($this->theme['button_style'] ?? 'rounded'), ['rounded', 'pill', 'square', 'soft'], true)
            ? (string) $this->theme['button_style']
            : 'rounded';

        $branding['legal'] = array_merge(
            is_array(data_get($branding, 'legal')) ? data_get($branding, 'legal') : [],
            [
                'privacy_url' => $this->cleanUrl($this->legal['privacy_url'] ?? ''),
                'terms_url' => $this->cleanUrl($this->legal['terms_url'] ?? ''),
            ]
        );

        $branding['store'] = array_merge(
            is_array(data_get($branding, 'store')) ? data_get($branding, 'store') : [],
            [
                'play_store_url' => $this->cleanUrl($this->store['play_store_url'] ?? ''),
                'package_name' => $this->cleanText($this->store['package_name'] ?? ''),
                'version_name' => $this->cleanText($this->store['version_name'] ?? ''),
                'version_code' => $this->cleanText($this->store['version_code'] ?? ''),
                'privacy_policy_url' => $this->cleanUrl($this->legal['privacy_url'] ?? ''),
            ]
        );

        $branding['support_channels'] = $this->sanitizeRows($this->supportChannels, 'support');
        $branding['official_links'] = $this->sanitizeRows($this->officialLinks, 'link');
        $branding['custom_public_settings'] = $this->sanitizeCustomRows($this->customSettings);

        // Keep legacy keys filled for backward compatibility with older frontend builds.
        $primaryWebsite = $this->firstEnabledValue($branding['official_links'], ['website', 'main_website'])
            ?: $this->firstEnabledValue($branding['support_channels'], ['website', 'main_website']);

        $branding['website_url'] = $primaryWebsite ?: (string) data_get($branding, 'website_url', '');
        $branding['support_email'] = $this->firstTypeValue($branding['support_channels'], 'email')
            ?: (string) data_get($branding, 'support_email', '');
        $branding['support_phone'] = $this->firstTypeValue($branding['support_channels'], 'phone')
            ?: (string) data_get($branding, 'support_phone', '');
        $branding['contact_address'] = $this->firstTypeValue($branding['support_channels'], 'address')
            ?: (string) data_get($branding, 'contact_address', '');

        $branding['social'] = array_merge(
            [
                'youtube' => '',
                'facebook' => '',
                'instagram' => '',
                'x' => '',
            ],
            is_array(data_get($branding, 'social')) ? data_get($branding, 'social') : [],
            [
                'youtube' => $this->firstEnabledValue($branding['official_links'], ['youtube']),
                'facebook' => $this->firstEnabledValue($branding['official_links'], ['facebook']),
                'instagram' => $this->firstEnabledValue($branding['official_links'], ['instagram']),
                'x' => $this->firstEnabledValue($branding['official_links'], ['x', 'twitter']),
            ]
        );

        $app->branding_json = $branding;
        $app->save();

        $this->activeApp = $app->fresh();
        $this->loadSettings();

        Notification::make()
            ->title('App settings saved')
            ->body($app->name . ' dynamic public settings were updated.')
            ->success()
            ->send();
    }

    public function seedRecommendedDefaults(): void
    {
        if (! $this->activeApp) {
            $this->loadSettings();
        }

        $displayName = $this->activeApp?->name ?: 'Selected App';

        $this->profile['display_name'] = $this->profile['display_name'] ?: $displayName;
        $this->profile['tagline'] = $this->profile['tagline'] ?: 'Watch, learn and get inspired';
        $this->profile['about'] = $this->profile['about'] ?: $displayName . ' brings inspiring messages, worship, devotionals, live programs, and faith-building content to viewers everywhere.';

        if (count($this->supportChannels) === 0) {
            $this->supportChannels = [
                $this->makeRow('technical_support', 'Technical Support', 'email', '', 'For app support and technical issues.', 'support', true),
                $this->makeRow('ministry_support', 'Church / Ministry Support', 'phone', '', 'For ministry and church-related enquiries.', 'church', true),
            ];
        }

        if (count($this->officialLinks) === 0) {
            $this->officialLinks = [
                $this->makeRow('website', 'Website', 'url', '', 'Official website.', 'website', true),
                $this->makeRow('youtube', 'YouTube', 'url', '', 'Official YouTube channel.', 'youtube', true),
                $this->makeRow('facebook', 'Facebook', 'url', '', 'Official Facebook page.', 'facebook', true),
                $this->makeRow('instagram', 'Instagram', 'url', '', 'Official Instagram page.', 'instagram', true),
                $this->makeRow('x', 'X / Twitter', 'url', '', 'Official X account.', 'x', true),
            ];
        }

        Notification::make()
            ->title('Defaults prepared')
            ->body('Review the dynamic rows, add official values, then click Save App Settings.')
            ->success()
            ->send();
    }



    public function uploadGraphic(string $assetKey): void
    {
        $assetKey = $this->normalizeGraphicKey($assetKey);

        if ($assetKey === '') {
            Notification::make()
                ->title('Unsupported graphic type')
                ->danger()
                ->send();

            return;
        }

        if (! $this->activeApp) {
            $this->loadSettings();
        }

        $app = $this->activeApp;
        if (! $app) {
            Notification::make()
                ->title('No active app selected')
                ->body('Select an active app first, then upload the graphic again.')
                ->danger()
                ->send();

            return;
        }

        $file = $this->graphicUploads[$assetKey] ?? null;

        // Livewire normally stores a single TemporaryUploadedFile here, but some browser/livewire
        // states can briefly present nested arrays. Support both so the Upload / Replace button
        // does not silently fail after a valid file was selected.
        if (is_array($file)) {
            $file = collect($file)->first(fn ($item) => $item instanceof TemporaryUploadedFile);
        }

        if (! $file instanceof TemporaryUploadedFile) {
            Notification::make()
                ->title('Choose an image first')
                ->body('Select a PNG, JPG, JPEG, WEBP, or GIF image before clicking Upload / Replace.')
                ->warning()
                ->send();

            return;
        }

        $mime = strtolower((string) ($file->getMimeType() ?? ''));
        if ($mime !== '' && ! str_starts_with($mime, 'image/')) {
            Notification::make()
                ->title('Only image files are allowed')
                ->body('Please upload a logo, banner, splash image, or app icon image.')
                ->danger()
                ->send();

            return;
        }

        $maxBytes = 8 * 1024 * 1024;
        if ((int) ($file->getSize() ?? 0) > $maxBytes) {
            Notification::make()
                ->title('Image is too large')
                ->body('Use an image below 8MB for app graphics.')
                ->danger()
                ->send();

            return;
        }

        $bucket = $this->graphicBucket($assetKey);
        $slug = $this->activeAppSlug($app);
        $dir = "assets/{$slug}/{$bucket}";

        $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
        $ext = preg_replace('/[^a-z0-9]+/i', '', $ext) ?: 'png';
        $filename = $assetKey . '_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(8)) . '.' . $ext;

        try {
            $storedPath = $file->storeAs($dir, $filename, 'public');
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Upload failed')
                ->body('The image could not be stored. Check storage permissions and try again.')
                ->danger()
                ->send();

            return;
        }

        if (! is_string($storedPath) || $storedPath === '') {
            Notification::make()
                ->title('Upload failed')
                ->body('The image was not saved. Please choose the file again and retry.')
                ->danger()
                ->send();

            return;
        }

        $publicUrl = Storage::disk('public')->url($storedPath);

        $assetId = null;
        try {
            $fullPath = Storage::disk('public')->path($storedPath);
            $imageInfo = is_file($fullPath) ? @getimagesize($fullPath) : null;

            $asset = MediaAsset::query()->create([
                'app_id' => $app->id,
                'type' => 'image',
                'label' => $this->graphicLabel($assetKey) . ' - ' . $app->name,
                'bucket' => $bucket,
                'disk' => 'public',
                'path' => $storedPath,
                'url' => $publicUrl,
                'mime' => is_file($fullPath) ? (@mime_content_type($fullPath) ?: $mime ?: null) : ($mime ?: null),
                'size' => is_file($fullPath) ? (@filesize($fullPath) ?: null) : null,
                'width' => is_array($imageInfo) ? ($imageInfo[0] ?? null) : null,
                'height' => is_array($imageInfo) ? ($imageInfo[1] ?? null) : null,
                'tags_json' => [$slug, 'app-settings', $assetKey],
                'is_active' => true,
                'sort_order' => 0,
            ]);

            $assetId = $asset->id;
        } catch (\Throwable $e) {
            $assetId = null;
        }

        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $branding[$assetKey . '_path'] = $storedPath;
        $branding[$assetKey . '_url'] = $publicUrl;
        $branding[$assetKey . '_asset_id'] = $assetId;

        $app->branding_json = $branding;
        $app->save();

        $this->activeApp = $app->fresh();
        $this->loadSettings();

        Notification::make()
            ->title($this->graphicLabel($assetKey) . ' uploaded')
            ->body('The app graphic has been replaced and will be delivered through the AppsHub API.')
            ->success()
            ->send();
    }

    public function applyGraphicFromLibraryId(string $assetKey, int|string $assetId): void
    {
        $assetKey = $this->normalizeGraphicKey($assetKey);

        if ($assetKey === '') {
            Notification::make()
                ->title('Unsupported graphic type')
                ->danger()
                ->send();

            return;
        }

        $assetId = (int) $assetId;

        if ($assetId <= 0) {
            Notification::make()
                ->title('Choose a library image first')
                ->body('Open the Media Library picker and select an image first.')
                ->warning()
                ->send();

            return;
        }

        $this->mediaLibrarySelection[$assetKey] = (string) $assetId;
        $this->applyGraphicFromLibrary($assetKey);
    }

    public function applyGraphicFromLibrary(string $assetKey): void
    {
        $assetKey = $this->normalizeGraphicKey($assetKey);

        if ($assetKey === '') {
            Notification::make()
                ->title('Unsupported graphic type')
                ->danger()
                ->send();

            return;
        }

        if (! $this->activeApp) {
            $this->loadSettings();
        }

        $app = $this->activeApp;
        if (! $app) {
            Notification::make()
                ->title('No active app selected')
                ->body('Select an active app first, then choose from the Media Library.')
                ->danger()
                ->send();

            return;
        }

        $assetId = (int) ($this->mediaLibrarySelection[$assetKey] ?? 0);
        if ($assetId <= 0) {
            Notification::make()
                ->title('Choose a library image first')
                ->body('Select an image from the Media Library dropdown before clicking Use Selected.')
                ->warning()
                ->send();

            return;
        }

        $asset = MediaAsset::query()
            ->whereKey($assetId)
            ->where(function ($query) use ($app) {
                $query->whereNull('app_id')->orWhere('app_id', $app->id);
            })
            ->where(function ($query) {
                $query->where('type', 'image')->orWhere('mime', 'like', 'image/%');
            })
            ->first();

        if (! $asset) {
            Notification::make()
                ->title('Image not found')
                ->body('The selected image is not available to this app or is not an image asset.')
                ->danger()
                ->send();

            return;
        }

        $path = (string) ($asset->path ?? '');
        $url = $this->mediaAssetPublicUrl($asset);

        if ($path === '' && $url === '') {
            Notification::make()
                ->title('Image has no usable path or URL')
                ->body('Update the Media Library item first, then select it again.')
                ->danger()
                ->send();

            return;
        }

        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $branding[$assetKey . '_path'] = $path;
        $branding[$assetKey . '_url'] = $url;
        $branding[$assetKey . '_asset_id'] = $asset->id;

        $app->branding_json = $branding;
        $app->save();

        $this->activeApp = $app->fresh();
        $this->loadSettings();

        Notification::make()
            ->title($this->graphicLabel($assetKey) . ' selected from Media Library')
            ->body('The app graphic now uses the selected library image and will be delivered through the AppsHub API.')
            ->success()
            ->send();
    }

    public function clearGraphic(string $assetKey): void
    {
        $assetKey = $this->normalizeGraphicKey($assetKey);

        if ($assetKey === '') {
            return;
        }

        if (! $this->activeApp) {
            $this->loadSettings();
        }

        $app = $this->activeApp;
        if (! $app) {
            return;
        }

        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $branding[$assetKey . '_path'] = '';
        $branding[$assetKey . '_url'] = '';
        $branding[$assetKey . '_asset_id'] = null;

        $app->branding_json = $branding;
        $app->save();

        $this->activeApp = $app->fresh();
        $this->loadSettings();

        Notification::make()
            ->title($this->graphicLabel($assetKey) . ' cleared')
            ->body('The setting was cleared only. Existing media files were not deleted.')
            ->success()
            ->send();
    }

    public function addSupportChannel(): void
    {
        $index = count($this->supportChannels) + 1;

        $this->supportChannels[] = $this->makeRow(
            'support_' . $index,
            'Support Channel ' . $index,
            'email',
            '',
            '',
            'support',
            true
        );
    }

    public function removeSupportChannel(int $index): void
    {
        if (isset($this->supportChannels[$index])) {
            unset($this->supportChannels[$index]);
            $this->supportChannels = array_values($this->supportChannels);
        }
    }

    public function addOfficialLink(): void
    {
        $index = count($this->officialLinks) + 1;

        $this->officialLinks[] = $this->makeRow(
            'official_link_' . $index,
            'Official Link ' . $index,
            'url',
            '',
            '',
            'link',
            true
        );
    }

    public function removeOfficialLink(int $index): void
    {
        if (isset($this->officialLinks[$index])) {
            unset($this->officialLinks[$index]);
            $this->officialLinks = array_values($this->officialLinks);
        }
    }

    public function addCustomSetting(): void
    {
        $index = count($this->customSettings) + 1;

        $this->customSettings[] = [
            'key' => 'custom_setting_' . $index,
            'label' => 'Custom Setting ' . $index,
            'type' => 'text',
            'value' => '',
            'description' => '',
            'group' => 'general',
            'enabled' => true,
        ];
    }

    public function removeCustomSetting(int $index): void
    {
        if (isset($this->customSettings[$index])) {
            unset($this->customSettings[$index]);
            $this->customSettings = array_values($this->customSettings);
        }
    }



    private function loadMediaLibraryAssets(App $app): void
    {
        try {
            $this->mediaLibraryAssets = MediaAsset::query()
                ->where(function ($query) use ($app) {
                    $query->whereNull('app_id')->orWhere('app_id', $app->id);
                })
                ->where(function ($query) {
                    $query->where('type', 'image')->orWhere('mime', 'like', 'image/%');
                })
                ->where(function ($query) {
                    $query->where('is_active', true)->orWhereNull('is_active');
                })
                ->latest('updated_at')
                ->limit(120)
                ->get()
                ->map(function (MediaAsset $asset) use ($app) {
                    $label = trim((string) ($asset->label ?? '')) ?: ('Media Asset #' . $asset->id);
                    $scope = (int) ($asset->app_id ?? 0) === (int) $app->id ? 'App' : 'Shared';
                    $bucket = (string) ($asset->bucket ?? 'misc');
                    $url = $this->mediaAssetPublicUrl($asset);

                    return [
                        'id' => (int) $asset->id,
                        'label' => $label,
                        'bucket' => $bucket,
                        'scope' => $scope,
                        'path' => (string) ($asset->path ?? ''),
                        'url' => $url,
                        'summary' => '[' . $scope . '] ' . $label . ' · ' . $bucket . ' · #' . $asset->id,
                    ];
                })
                ->values()
                ->all();
        } catch (\Throwable $e) {
            $this->mediaLibraryAssets = [];
        }
    }

    private function mediaAssetPublicUrl(MediaAsset $asset): string
    {
        $url = (string) ($asset->url ?? '');
        if ($url !== '') {
            return $url;
        }

        $disk = (string) ($asset->disk ?? 'public');
        $path = (string) ($asset->path ?? '');

        if ($path === '') {
            return '';
        }

        try {
            return (string) Storage::disk($disk ?: 'public')->url($path);
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function normalizeGraphicKey(string $assetKey): string
    {
        return in_array($assetKey, ['logo', 'banner', 'splash', 'app_icon'], true) ? $assetKey : '';
    }

    private function graphicBucket(string $assetKey): string
    {
        return match ($assetKey) {
            'logo', 'app_icon' => 'logos',
            'banner' => 'banners',
            'splash' => 'splash',
            default => 'misc',
        };
    }

    private function graphicLabel(string $assetKey): string
    {
        return match ($assetKey) {
            'logo' => 'Logo',
            'banner' => 'Banner',
            'splash' => 'Splash Screen',
            'app_icon' => 'App Icon',
            default => 'Graphic',
        };
    }

    private function activeAppSlug(App $app): string
    {
        $slug = (string) ($app->slug ?? 'app');
        $slug = Str::slug($slug) ?: 'app-' . (int) $app->id;

        return $slug;
    }

    private function legacySupportRows(array $branding): array
    {
        $rows = [];

        $website = (string) data_get($branding, 'website_url', '');
        $email = (string) data_get($branding, 'support_email', '');
        $phone = (string) data_get($branding, 'support_phone', '');
        $address = (string) data_get($branding, 'contact_address', '');

        if ($website !== '') {
            $rows[] = $this->makeRow('website', 'Website', 'url', $website, 'Official website.', 'website', true);
        }

        if ($email !== '') {
            $rows[] = $this->makeRow('technical_support', 'Technical Support', 'email', $email, 'For app support and technical issues.', 'support', true);
        }

        if ($phone !== '') {
            $rows[] = $this->makeRow('ministry_support', 'Church / Ministry Support', 'phone', $phone, 'For ministry and church-related enquiries.', 'church', true);
        }

        if ($address !== '') {
            $rows[] = $this->makeRow('contact_address', 'Contact Address', 'address', $address, 'Official contact address.', 'location', true);
        }

        return $rows;
    }

    private function legacyOfficialLinkRows(array $branding): array
    {
        $rows = [];

        $social = is_array(data_get($branding, 'social')) ? data_get($branding, 'social') : [];

        $map = [
            'youtube' => ['YouTube', 'youtube'],
            'facebook' => ['Facebook', 'facebook'],
            'instagram' => ['Instagram', 'instagram'],
            'x' => ['X / Twitter', 'x'],
        ];

        foreach ($map as $key => [$label, $icon]) {
            $value = (string) data_get($social, $key, '');
            if ($value !== '') {
                $rows[] = $this->makeRow($key, $label, 'url', $value, 'Official ' . $label . ' link.', $icon, true);
            }
        }

        return $rows;
    }

    private function normalizeRows(mixed $rows, array $fallbackRows, string $purpose): array
    {
        $rows = is_array($rows) ? $rows : [];

        if (count($rows) === 0) {
            $rows = $fallbackRows;
        }

        return array_values(array_map(function ($row) use ($purpose) {
            $row = is_array($row) ? $row : [];

            return $this->makeRow(
                (string) data_get($row, 'key', $purpose . '_' . uniqid()),
                (string) data_get($row, 'label', 'Untitled'),
                (string) data_get($row, 'type', $purpose === 'link' ? 'url' : 'text'),
                (string) data_get($row, 'value', ''),
                (string) data_get($row, 'description', ''),
                (string) data_get($row, 'icon', $purpose),
                (bool) data_get($row, 'enabled', true)
            );
        }, $rows));
    }

    private function normalizeCustomRows(mixed $rows): array
    {
        $rows = is_array($rows) ? $rows : [];

        return array_values(array_map(function ($row) {
            $row = is_array($row) ? $row : [];

            return [
                'key' => $this->cleanKey((string) data_get($row, 'key', 'custom_' . uniqid())),
                'label' => $this->cleanText(data_get($row, 'label', 'Custom Setting')),
                'type' => $this->cleanText(data_get($row, 'type', 'text')),
                'value' => $this->cleanText(data_get($row, 'value', '')),
                'description' => $this->cleanText(data_get($row, 'description', '')),
                'group' => $this->cleanKey((string) data_get($row, 'group', 'general')),
                'enabled' => (bool) data_get($row, 'enabled', true),
            ];
        }, $rows));
    }

    private function sanitizeRows(array $rows, string $purpose): array
    {
        $out = [];

        foreach ($rows as $row) {
            $row = is_array($row) ? $row : [];

            $key = $this->cleanKey((string) data_get($row, 'key', ''));
            $label = $this->cleanText(data_get($row, 'label', ''));
            $type = $this->cleanText(data_get($row, 'type', $purpose === 'link' ? 'url' : 'text'));
            $value = $type === 'url'
                ? $this->cleanUrl(data_get($row, 'value', ''))
                : $this->cleanText(data_get($row, 'value', ''));

            if ($key === '' && $label === '' && $value === '') {
                continue;
            }

            if ($key === '') {
                $key = $this->cleanKey($label);
            }

            if ($label === '') {
                $label = str($key)->replace('_', ' ')->headline()->toString();
            }

            $out[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'value' => $value,
                'description' => $this->cleanText(data_get($row, 'description', '')),
                'icon' => $this->cleanText(data_get($row, 'icon', $purpose)),
                'enabled' => (bool) data_get($row, 'enabled', true),
            ];
        }

        return array_values($out);
    }

    private function sanitizeCustomRows(array $rows): array
    {
        $out = [];

        foreach ($rows as $row) {
            $row = is_array($row) ? $row : [];

            $key = $this->cleanKey((string) data_get($row, 'key', ''));
            $label = $this->cleanText(data_get($row, 'label', ''));
            $value = $this->cleanText(data_get($row, 'value', ''));

            if ($key === '' && $label === '' && $value === '') {
                continue;
            }

            if ($key === '') {
                $key = $this->cleanKey($label);
            }

            if ($label === '') {
                $label = str($key)->replace('_', ' ')->headline()->toString();
            }

            $out[] = [
                'key' => $key,
                'label' => $label,
                'type' => $this->cleanText(data_get($row, 'type', 'text')),
                'value' => $value,
                'description' => $this->cleanText(data_get($row, 'description', '')),
                'group' => $this->cleanKey((string) data_get($row, 'group', 'general')),
                'enabled' => (bool) data_get($row, 'enabled', true),
            ];
        }

        return array_values($out);
    }

    private function makeRow(
        string $key,
        string $label,
        string $type,
        string $value,
        string $description,
        string $icon,
        bool $enabled
    ): array {
        return [
            'key' => $this->cleanKey($key),
            'label' => $label,
            'type' => $type,
            'value' => $value,
            'description' => $description,
            'icon' => $icon,
            'enabled' => $enabled,
        ];
    }

    private function firstEnabledValue(array $rows, array $keys): string
    {
        foreach ($rows as $row) {
            if (! (bool) data_get($row, 'enabled', true)) {
                continue;
            }

            if (in_array((string) data_get($row, 'key'), $keys, true)) {
                $value = (string) data_get($row, 'value', '');
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function firstTypeValue(array $rows, string $type): string
    {
        foreach ($rows as $row) {
            if (! (bool) data_get($row, 'enabled', true)) {
                continue;
            }

            if ((string) data_get($row, 'type') === $type) {
                $value = (string) data_get($row, 'value', '');
                if ($value !== '') {
                    return $value;
                }
            }
        }

        return '';
    }

    private function cleanText(mixed $value): string
    {
        return trim((string) $value);
    }

    private function cleanAssetValue(mixed $value): string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return '';
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return ltrim($value, '/');
    }

    private function cleanUrl(mixed $value): string
    {
        $url = trim((string) $value);

        if ($url === '') {
            return '';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        if (str_starts_with($url, 'www.')) {
            return 'https://' . $url;
        }

        return $url;
    }

    private function cleanKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?: '';
        $value = trim($value, '_');

        return $value;
    }
}

<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Support\AppCapabilities as AppCapabilitiesSupport;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AppCapabilities extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'App Capabilities';
    protected static ?string $navigationGroup = 'Workspace';
    protected static ?int $navigationSort = 95;

    protected static ?string $slug = 'app-capabilities';

    protected static string $view = 'filament.pages.app-capabilities';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('app_capabilities');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('app_capabilities');
    }

    public ?App $activeApp = null;

    public array $capabilities = [];

    public array $groups = [];

    public function mount(): void
    {
        $this->reloadState();
    }


    public function reloadState(): void
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        $this->activeApp = $activeAppId > 0
            ? App::query()->find($activeAppId)
            : null;

        $this->groups = AppCapabilitiesSupport::groups();

        $this->capabilities = AppCapabilitiesSupport::forApp($this->activeApp);
    }

    public function enableGroup(string $groupKey): void
    {
        $items = $this->groups[$groupKey]['items'] ?? [];

        foreach (array_keys($items) as $key) {
            $this->capabilities[$key] = true;
        }
    }

    public function disableGroup(string $groupKey): void
    {
        $items = $this->groups[$groupKey]['items'] ?? [];

        foreach (array_keys($items) as $key) {
            $this->capabilities[$key] = false;
        }
    }

    public function resetToDefaults(): void
    {
        $this->capabilities = AppCapabilitiesSupport::defaults();

        Notification::make()
            ->title('Default capabilities restored on this form')
            ->body('Click Save Capabilities to store them for the selected app.')
            ->success()
            ->send();
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

        $clean = AppCapabilitiesSupport::clean($this->capabilities);
        $branding['capabilities'] = $clean;

        $legacyFlags = is_array($branding['flags'] ?? null) ? $branding['flags'] : [];
        $branding['flags'] = array_merge($legacyFlags, $this->legacyFlagsFromCapabilities($clean));

        $app->branding_json = $branding;
        $app->save();

        $this->activeApp = $app->fresh();
        $this->capabilities = AppCapabilitiesSupport::forApp($this->activeApp);

        Notification::make()
            ->title('Capabilities saved')
            ->body($app->name . ' workspace capabilities were updated.')
            ->success()
            ->send();
    }

    public function frontendPayload(): array
    {
        $clean = AppCapabilitiesSupport::clean($this->capabilities);

        return [
            'app' => [
                'id' => $this->activeApp?->id,
                'name' => $this->activeApp?->name,
                'slug' => $this->activeApp?->slug,
            ],
            'capabilities' => $clean,
            'feature_flags' => $clean,
            'legacy_flags_preview' => $this->legacyFlagsFromCapabilities($clean),
            'api_note' => 'Bootstrap now sends capabilities as both top-level capabilities and merged feature_flags for frontend compatibility.',
        ];
    }

    private function legacyFlagsFromCapabilities(array $capabilities): array
    {
        return [
            'enable_watch' => (bool) (($capabilities['watch_links'] ?? false) || ($capabilities['watch_manager'] ?? false)),
            'enable_content_studio' => (bool) ($capabilities['content_channels'] ?? false),
            'enable_ads' => (bool) ($capabilities['ads'] ?? false),
            'enable_push' => (bool) ($capabilities['notifications'] ?? false),
            'enable_auth' => (bool) ($capabilities['user_auth'] ?? false),
        ];
    }

}

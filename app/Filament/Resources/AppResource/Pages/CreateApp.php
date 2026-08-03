<?php

namespace App\Filament\Resources\AppResource\Pages;

use App\Filament\Resources\AppResource;
use App\Models\App;
use App\Support\AppCapabilities;
use App\Support\AppTemplateCloner;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateApp extends CreateRecord
{
    protected static string $resource = AppResource::class;

    protected ?int $cloneFromAppId = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['api_token'] = $data['api_token'] ?? Str::random(60);

        $cloneFrom = $data['clone_from_app_id'] ?? null;
        $this->cloneFromAppId = is_numeric($cloneFrom) ? (int) $cloneFrom : null;

        unset($data['clone_from_app_id']);

        $branding = is_array($data['branding_json'] ?? null) ? $data['branding_json'] : [];

        if (! isset($branding['display_name']) || trim((string) $branding['display_name']) === '') {
            $branding['display_name'] = (string) ($data['name'] ?? '');
        }

        if (! isset($branding['capabilities']) || ! is_array($branding['capabilities'])) {
            $sourceCapabilities = null;

            if ($this->cloneFromAppId && $this->cloneFromAppId > 0) {
                $sourceApp = App::query()->find($this->cloneFromAppId);
                $sourceBranding = is_array($sourceApp?->branding_json) ? $sourceApp->branding_json : [];
                $sourceCapabilities = $sourceBranding['capabilities'] ?? null;
            }

            $branding['capabilities'] = AppCapabilities::clean(
                is_array($sourceCapabilities) ? $sourceCapabilities : AppCapabilities::defaults()
            );
        }

        $data['branding_json'] = $branding;

        return $data;
    }

    public function afterCreate(): void
    {
        if (! $this->record) {
            return;
        }

        session(['active_app_id' => (int) $this->record->id]);

        if ($this->cloneFromAppId && $this->cloneFromAppId > 0) {
            try {
                $result = AppTemplateCloner::clone(
                    $this->cloneFromAppId,
                    (int) $this->record->id,
                    false
                );

                Notification::make()
                    ->title('App created from existing app')
                    ->body('Copied structure: '
                        . $result['cloned']['tabs'] . ' tabs, '
                        . $result['cloned']['routes'] . ' routes, '
                        . $result['cloned']['sections'] . ' sections, '
                        . $result['cloned']['items'] . ' items. The new app is now the active workspace.')
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('App created, but clone failed')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }

            return;
        }

        Notification::make()
            ->title('App created')
            ->body('The new app is now the active workspace. Use App Capabilities to enable only the tools this app needs.')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return '/admin/beginner-dashboard';
    }
}

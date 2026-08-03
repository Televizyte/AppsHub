<?php

namespace App\Filament\Resources\PushNotificationResource\Pages;

use App\Filament\Resources\PushNotificationResource;
use App\Support\ActiveApp;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPushNotification extends EditRecord
{
    protected static string $resource = PushNotificationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Ensure app_id stays present even if older records were missing it
        $data['app_id'] = $data['app_id']
            ?? ($this->record?->app_id ?: ActiveApp::get());

        return PushNotificationResource::normalizeAndPersist($data);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

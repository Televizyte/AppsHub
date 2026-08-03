<?php

namespace App\Filament\Resources\PushNotificationResource\Pages;

use App\Filament\Resources\PushNotificationResource;
use App\Jobs\SendPushNotificationJob;
use App\Support\ActiveApp;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePushNotification extends CreateRecord
{
    protected static string $resource = PushNotificationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Force app scope always (prevents Edit 404 later)
        $data['app_id'] = $data['app_id'] ?? ActiveApp::get();

        // Normalize + persist upload into MediaAsset (resource helper)
        return PushNotificationResource::normalizeAndPersist($data);
    }

    public function afterCreate(): void
    {
        if (! $this->record) {
            return;
        }

        $sendNow = (bool) ($this->data['send_now'] ?? false);

        if ($sendNow) {
            $this->record->status = 'queued';
            $this->record->scheduled_for = now();
            $this->record->save();

            SendPushNotificationJob::dispatch((int) $this->record->id);

            Notification::make()
                ->title('Queued')
                ->body('Notification queued for sending.')
                ->success()
                ->send();
        }
    }
}

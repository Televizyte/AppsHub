<?php

namespace App\Filament\Resources\VideoChannelResource\Pages;

use App\Filament\Resources\VideoChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVideoChannel extends EditRecord
{
    protected static string $resource = VideoChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = VideoChannelResource::lockAppKeyOnSave($data);

        if (in_array($data['status'] ?? '', ['published', 'publish', 'active'], true)) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        return $data;
    }
}

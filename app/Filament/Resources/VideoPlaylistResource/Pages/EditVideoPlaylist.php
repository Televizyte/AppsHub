<?php

namespace App\Filament\Resources\VideoPlaylistResource\Pages;

use App\Filament\Resources\VideoPlaylistResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVideoPlaylist extends EditRecord
{
    protected static string $resource = VideoPlaylistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = VideoPlaylistResource::lockAppKeyOnSave($data);

        if (in_array($data['status'] ?? '', ['published', 'publish', 'active'], true)) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        return $data;
    }
}

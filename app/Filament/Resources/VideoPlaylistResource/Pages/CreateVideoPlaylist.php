<?php

namespace App\Filament\Resources\VideoPlaylistResource\Pages;

use App\Filament\Resources\VideoPlaylistResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoPlaylist extends CreateRecord
{
    protected static string $resource = VideoPlaylistResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = VideoPlaylistResource::applyActiveAppToCreateData($data);

        $data['status'] = $data['status'] ?? 'draft';
        $data['visibility'] = $data['visibility'] ?? 'public';

        if (in_array($data['status'], ['published', 'publish', 'active'], true)) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        return $data;
    }
}

<?php

namespace App\Filament\Resources\VideoChannelResource\Pages;

use App\Filament\Resources\VideoChannelResource;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoChannel extends CreateRecord
{
    protected static string $resource = VideoChannelResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data = VideoChannelResource::applyActiveAppToCreateData($data);

        $data['status'] = $data['status'] ?? 'draft';
        $data['visibility'] = $data['visibility'] ?? 'public';

        if (in_array($data['status'], ['published', 'publish', 'active'], true)) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        return $data;
    }
}

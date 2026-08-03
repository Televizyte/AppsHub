<?php

namespace App\Filament\Resources\AppItemResource\Pages;

use App\Filament\Resources\AppItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppItem extends CreateRecord
{
    protected static string $resource = AppItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AppItemResource::applyActiveAppToCreateData($data);
    }
}

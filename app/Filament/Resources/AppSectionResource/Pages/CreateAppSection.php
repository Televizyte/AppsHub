<?php

namespace App\Filament\Resources\AppSectionResource\Pages;

use App\Filament\Resources\AppSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppSection extends CreateRecord
{
    protected static string $resource = AppSectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return AppSectionResource::applyActiveAppToCreateData($data);
    }
}

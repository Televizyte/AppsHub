<?php

namespace App\Filament\Resources\AppSectionResource\Pages;

use App\Filament\Resources\AppSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppSection extends EditRecord
{
    protected static string $resource = AppSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return AppSectionResource::lockAppKeyOnSave($data);
    }
}

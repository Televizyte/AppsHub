<?php

namespace App\Filament\Resources\IconPresetResource\Pages;

use App\Filament\Resources\IconPresetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditIconPreset extends EditRecord
{
    protected static string $resource = IconPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

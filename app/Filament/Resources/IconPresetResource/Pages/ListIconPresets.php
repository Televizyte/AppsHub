<?php

namespace App\Filament\Resources\IconPresetResource\Pages;

use App\Filament\Resources\IconPresetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListIconPresets extends ListRecords
{
    protected static string $resource = IconPresetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

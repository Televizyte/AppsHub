<?php

namespace App\Filament\Resources\AppSectionResource\Pages;

use App\Filament\Resources\AppSectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppSections extends ListRecords
{
    protected static string $resource = AppSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

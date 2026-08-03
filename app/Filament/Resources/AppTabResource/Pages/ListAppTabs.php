<?php

namespace App\Filament\Resources\AppTabResource\Pages;

use App\Filament\Resources\AppTabResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppTabs extends ListRecords
{
    protected static string $resource = AppTabResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AppItemResource\Pages;

use App\Filament\Resources\AppItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppItems extends ListRecords
{
    protected static string $resource = AppItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AppRouteResource\Pages;

use App\Filament\Resources\AppRouteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppRoutes extends ListRecords
{
    protected static string $resource = AppRouteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

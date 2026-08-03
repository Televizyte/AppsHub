<?php

namespace App\Filament\Resources\WatchLinkResource\Pages;

use App\Filament\Resources\WatchLinkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWatchLinks extends ListRecords
{
    protected static string $resource = WatchLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

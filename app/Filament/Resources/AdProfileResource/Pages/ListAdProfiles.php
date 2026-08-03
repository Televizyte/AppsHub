<?php

namespace App\Filament\Resources\AdProfileResource\Pages;

use App\Filament\Resources\AdProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdProfiles extends ListRecords
{
    protected static string $resource = AdProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\WatchLinkResource\Pages;

use App\Filament\Resources\WatchLinkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWatchLink extends CreateRecord
{
    protected static string $resource = WatchLinkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return WatchLinkResource::mutateFormDataBeforeCreate($data);
    }
}

<?php

namespace App\Filament\Resources\WatchLinkResource\Pages;

use App\Filament\Resources\WatchLinkResource;
use Filament\Resources\Pages\EditRecord;

class EditWatchLink extends EditRecord
{
    protected static string $resource = WatchLinkResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return WatchLinkResource::mutateFormDataBeforeSave($data);
    }
}

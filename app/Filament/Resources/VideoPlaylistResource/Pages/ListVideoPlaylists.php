<?php

namespace App\Filament\Resources\VideoPlaylistResource\Pages;

use App\Filament\Resources\VideoPlaylistResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideoPlaylists extends ListRecords
{
    protected static string $resource = VideoPlaylistResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\VideoChannelResource\Pages;

use App\Filament\Resources\VideoChannelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideoChannels extends ListRecords
{
    protected static string $resource = VideoChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AppTabResource\Pages;

use App\Filament\Resources\AppTabResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions;

class EditAppTab extends EditRecord
{
    protected static string $resource = AppTabResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

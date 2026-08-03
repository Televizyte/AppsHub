<?php

namespace App\Filament\Resources\AdProfileResource\Pages;

use App\Filament\Resources\AdProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdProfile extends EditRecord
{
    protected static string $resource = AdProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

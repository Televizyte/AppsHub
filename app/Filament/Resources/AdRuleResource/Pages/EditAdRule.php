<?php

namespace App\Filament\Resources\AdRuleResource\Pages;

use App\Filament\Resources\AdRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdRule extends EditRecord
{
    protected static string $resource = AdRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

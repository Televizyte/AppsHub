<?php

namespace App\Filament\Resources\AdRuleResource\Pages;

use App\Filament\Resources\AdRuleResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions;

class ListAdRules extends ListRecords
{
    protected static string $resource = AdRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\AppResource\Pages;

use App\Filament\Resources\AppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApps extends ListRecords
{
    protected static string $resource = AppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('controlCenter')
                ->label('Control Center')
                ->icon('heroicon-o-squares-2x2')
                ->color('gray')
                ->url('/admin/control-center'),

            Actions\Action::make('templateLibrary')
                ->label('Template Library')
                ->icon('heroicon-o-rectangle-group')
                ->color('gray')
                ->url('/admin/template-library'),

            Actions\CreateAction::make()
                ->label('Create New App')
                ->icon('heroicon-o-plus'),
        ];
    }
}

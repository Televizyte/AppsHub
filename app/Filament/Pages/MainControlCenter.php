<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use Filament\Pages\Page;

class MainControlCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationLabel = 'Control Center';
    protected static ?string $navigationGroup = 'Main Control';
    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'control-center';

    protected static string $view = 'filament.pages.main-control-center';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('control_center');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('control_center');
    }
}

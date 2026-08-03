<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use Filament\Pages\Page;

class EngineRoom extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?string $navigationLabel = 'Engine Room';
    protected static ?string $navigationGroup = 'Main Control';
    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'engine-room';

    protected static string $view = 'filament.pages.engine-room';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('engine_room');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('engine_room');
    }
}

<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Support\AdminMode;
use Filament\Pages\Page;

class BeginnerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Workspace';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.beginner-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('app_workspace');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('app_workspace');
    }

}

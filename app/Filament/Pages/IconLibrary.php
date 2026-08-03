<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Support\AppCapabilities;
use Filament\Pages\Page;

class IconLibrary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'Icon Library';
    protected static ?string $navigationGroup = 'Media & Assets';
    protected static ?int $navigationSort = 38;
    protected static ?string $slug = 'icon-library';

    protected static string $view = 'filament.pages.icon-library';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('icon_library');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('icon_library');
    }

}

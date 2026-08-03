<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Support\AppCapabilities;
use Filament\Pages\Page;

class MediaLibrary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationLabel = 'Media Library';
    protected static ?string $navigationGroup = 'Media & Assets';
    protected static ?int $navigationSort = 39;
    protected static ?string $slug = 'media-library';

    protected static string $view = 'filament.pages.media-library';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('media_library');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('media_library');
    }

}

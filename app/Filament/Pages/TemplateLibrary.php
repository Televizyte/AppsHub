<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use Filament\Pages\Page;

class TemplateLibrary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationLabel = 'Template Library';
    protected static ?string $navigationGroup = 'Main Control';
    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'template-library';

    protected static string $view = 'filament.pages.template-library';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('template_library');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('template_library');
    }
}

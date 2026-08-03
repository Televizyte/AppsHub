<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Support\AppCapabilities;
use Filament\Pages\Page;

class PushNotifications extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';
    protected static ?string $navigationLabel = 'Push Notifications';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'push-center';
    protected static string $view = 'filament.pages.push-notifications';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('push_notifications');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('push_notifications');
    }

}

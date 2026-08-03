<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Support\AppCapabilities;
use Filament\Pages\Page;

class AdsMonetization extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';
    protected static ?string $navigationLabel = 'Ads & Monetization';
    protected static ?string $navigationGroup = 'Ads & Monetization';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'ads-monetization';

    protected static string $view = 'filament.pages.ads-monetization';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('ads_monetization');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('ads_monetization');
    }

}

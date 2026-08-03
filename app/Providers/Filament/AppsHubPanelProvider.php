<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureActiveApp;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppsHubPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('appshub')
            ->path('admin')
            ->login()
            ->homeUrl('/admin/control-center')
            ->colors([
                'primary' => Color::Cyan,
            ])
            ->assets([
                Css::make('dxm-filament', asset('css/dxm-filament.css')),
                Css::make('dxm-builder-flow', asset('css/dxm-builder-flow.css')),
                Js::make('dxm-admin-flow', asset('js/dxm-admin-flow.js')),
            ])
            ->sidebarCollapsibleOnDesktop()

            ->navigationGroups([
                NavigationGroup::make()->label('Main Control'),
                NavigationGroup::make()->label('Workspace'),
                NavigationGroup::make()->label('Watch Builder'),
                NavigationGroup::make()->label('Shared Engines'),
                NavigationGroup::make()->label('Content Studio'),
                NavigationGroup::make()->label('Media & Assets'),
                NavigationGroup::make()->label('Engagement'),
                NavigationGroup::make()->label('Ads & Monetization'),
                NavigationGroup::make()->label('System'),
            ])

            ->renderHook('panels::topbar.start', fn (): string => view('filament.hooks.active-app-switcher')->render())
            ->renderHook('panels::sidebar.nav.start', fn (): string => view('filament.hooks.staff-app-switcher')->render())
            ->renderHook('panels::topbar.end', fn (): string => view('filament.hooks.mode-switcher')->render())

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')

            /**
             * Keep the admin landing page controlled by MainControlCenter.
             * Do not register Filament\Pages\Dashboard here, otherwise a second
             * generic "Dashboard" appears above the app-specific Workspace Dashboard.
             */
            ->pages([])

            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
            ])

            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                EnsureActiveApp::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

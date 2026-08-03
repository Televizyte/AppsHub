<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdProfileResource\Pages;
use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Models\AdProfile;
use App\Support\ActiveApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class AdProfileResource extends ActiveAppScopedResource
{
    protected static ?string $model = AdProfile::class;

    protected static ?string $navigationIcon = 'heroicon-o-speaker-wave';
    protected static ?string $navigationGroup = 'Ads & Monetization';
    protected static ?string $navigationLabel = 'Ad Settings';
    protected static ?int $navigationSort = 10;

    
    public static function shouldRegisterNavigation(): bool
    {
        // Beginner mode uses the visual /admin/ads-monetization page.
        // The technical resource remains available in Advanced mode and by direct URL.
        return ! \App\Support\AdminMode::isBeginner();
    }
protected static ?string $modelLabel = 'Ad Setup';
    protected static ?string $pluralModelLabel = 'Ad Setups';

    public static function form(Form $form): Form
    {
        $activeAppId = (int) ActiveApp::ensureId();

        return $form->schema([
            Forms\Components\Section::make('Ad Provider Setup')
                ->description('Set the active ad provider and unit IDs for the currently selected app. These settings are app-specific and do not affect other apps.')
                ->schema([
                    Forms\Components\TextInput::make('app_id')
                        ->label('Active App ID')
                        ->default($activeAppId)
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->numeric(),

                    Forms\Components\Toggle::make('ads_enabled')
                        ->label('Enable ads for this app')
                        ->helperText('Master switch. When OFF, all ads are disabled for this app even if rules are enabled.')
                        ->default(true),

                    Forms\Components\Select::make('meta_json.provider')
                        ->label('Ad Provider')
                        ->helperText('Start with AdMob. More providers can be added later without changing the app structure.')
                        ->options([
                            'admob' => 'Google AdMob',
                            'none' => 'No provider / Disabled',
                            'custom' => 'Custom provider',
                        ])
                        ->default('admob')
                        ->searchable()
                        ->native(false),

                    Forms\Components\Placeholder::make('provider_note')
                        ->label('How this works')
                        ->content('The mobile app reads these IDs from the bootstrap API. Ad Rules decide where each ad type is allowed: tab, page, tool, or route.'),

                    Forms\Components\Grid::make(1)
                        ->schema([
                            Forms\Components\TextInput::make('banner_unit_id')
                                ->label('AdMob Banner Unit ID')
                                ->placeholder('ca-app-pub-xxxxxxxxxxxxxxxx/yyyyyyyyyy')
                                ->helperText('Used for bottom/banner ads.')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('interstitial_unit_id')
                                ->label('AdMob Interstitial Unit ID')
                                ->placeholder('ca-app-pub-xxxxxxxxxxxxxxxx/yyyyyyyyyy')
                                ->helperText('Used for full-screen ads. Timing is controlled globally below.')
                                ->maxLength(255),

                            Forms\Components\TextInput::make('native_unit_id')
                                ->label('AdMob Native Advanced Unit ID')
                                ->placeholder('ca-app-pub-xxxxxxxxxxxxxxxx/yyyyyyyyyy')
                                ->helperText('Used for native/in-feed ads inside lists, quote libraries, notes, articles, and future tools.')
                                ->maxLength(255),
                        ]),
                ])
                ->columns(1)
                ->collapsible(),

            Forms\Components\Section::make('Global Format Controls')
                ->description('Choose which ad formats are generally available. Ad Rules can still turn each format on/off per tab or page.')
                ->schema([
                    Forms\Components\Toggle::make('meta_json.ad_formats.banner')
                        ->label('Allow Banner Ads')
                        ->default(true),

                    Forms\Components\Toggle::make('meta_json.ad_formats.native')
                        ->label('Allow Native Ads')
                        ->default(true),

                    Forms\Components\Toggle::make('meta_json.ad_formats.interstitial')
                        ->label('Allow Interstitial Ads')
                        ->default(true),
                ])
                ->columns(3)
                ->collapsible(),

            Forms\Components\Section::make('Global Interstitial Control')
                ->description('This global setting controls interstitial timing across the whole app. Route/tab rules only decide whether interstitial is allowed; they do not control the cooldown.')
                ->schema([
                    Forms\Components\TextInput::make('meta_json.interstitial.cooldown_seconds')
                        ->label('Global Interstitial Cooldown (seconds)')
                        ->numeric()
                        ->default(120)
                        ->helperText('Example: 120 means at least 2 minutes must pass before another interstitial can show anywhere in the app.'),

                    Forms\Components\TextInput::make('meta_json.interstitial.every_n_safe_actions')
                        ->label('Show after every N safe actions')
                        ->numeric()
                        ->default(4)
                        ->helperText('Reserved for safe navigation/action counting. Keep 4–6 for user-friendly behavior.'),

                    Forms\Components\Placeholder::make('interstitial_policy_note')
                        ->label('Important')
                        ->content('For policy safety, keep interstitial OFF on Watch, WebView, embedded streams, and thin-content pages. Use Ad Rules only to allow/deny interstitial per area. The cooldown stays global.'),
                ])
                ->columns(2)
                ->collapsible(),

            Forms\Components\Section::make('Native Ads in Lists')
                ->description('Controls how native ads appear inside lists such as articles, notes, quote library, highlights, and future list pages.')
                ->schema([
                    Forms\Components\Toggle::make('meta_json.native_in_list.enabled')
                        ->label('Enable Native Ads in Lists')
                        ->default(true),

                    Forms\Components\TextInput::make('meta_json.native_in_list.start_after')
                        ->label('Start after item number')
                        ->numeric()
                        ->default(4)
                        ->helperText('Example: 4 means first native ad can appear after the 4th item.'),

                    Forms\Components\TextInput::make('meta_json.native_in_list.every')
                        ->label('Repeat every')
                        ->numeric()
                        ->default(4)
                        ->helperText('Example: 4 means repeat every 4 eligible content items.'),
                ])
                ->columns(3)
                ->collapsible(),

            Forms\Components\Section::make('Policy Safety Notes')
                ->description('Use Ad Rules to turn ads off on risky or policy-sensitive pages.')
                ->schema([
                    Forms\Components\Placeholder::make('policy_note')
                        ->label('Recommended safety setup')
                        ->content('Keep Watch, WebView, embedded streams, mirrored/external content, and thin-content pages OFF. Use ads mainly on Home, original Inspire content, safe Explore tools, and approved list pages.'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('app_id')
                    ->label('App ID')
                    ->sortable(),

                Tables\Columns\IconColumn::make('ads_enabled')
                    ->boolean()
                    ->label('Ads Active'),

                Tables\Columns\TextColumn::make('meta_json.provider')
                    ->label('Provider')
                    ->default('admob')
                    ->badge(),

                Tables\Columns\TextColumn::make('banner_unit_id')
                    ->label('Banner')
                    ->limit(28)
                    ->placeholder('Not set'),

                Tables\Columns\TextColumn::make('interstitial_unit_id')
                    ->label('Interstitial')
                    ->limit(28)
                    ->placeholder('Not set'),

                Tables\Columns\TextColumn::make('native_unit_id')
                    ->label('Native')
                    ->limit(28)
                    ->placeholder('Not set'),

                Tables\Columns\TextColumn::make('meta_json.interstitial.cooldown_seconds')
                    ->label('Cooldown')
                    ->suffix('s')
                    ->placeholder('120'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdProfiles::route('/'),
            'create' => Pages\CreateAdProfile::route('/create'),
            'edit' => Pages\EditAdProfile::route('/{record}/edit'),
        ];
    }
}

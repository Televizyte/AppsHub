<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdRuleResource\Pages;
use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Models\AdRule;
use App\Support\ActiveApp;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\Facades\DB;

class AdRuleResource extends ActiveAppScopedResource
{
    protected static ?string $model = AdRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationGroup = 'Ads & Monetization';
    protected static ?int $navigationSort = 20;

    
    public static function shouldRegisterNavigation(): bool
    {
        // Beginner mode uses the visual /admin/ads-monetization page.
        // The technical resource remains available in Advanced mode and by direct URL.
        return ! \App\Support\AdminMode::isBeginner();
    }
public static function form(Form $form): Form
    {
        $activeAppId = (int) ActiveApp::ensureId();

        return $form->schema([
            Section::make('Ad Rule')
                ->description('Scope = tab or route. Watch should remain OFF; add route rules for exceptions like SOD.')
                ->schema([

                    // ✅ LOCKED
                    TextInput::make('app_id')
                        ->label('App ID (Active App)')
                        ->default($activeAppId)
                        ->disabled()
                        ->dehydrated()
                        ->required()
                        ->numeric(),

                    Grid::make(2)->schema([
                        Select::make('scope_type')
                            ->options([
                                'tab' => 'Tab',
                                'route' => 'Route Key (inner page)',
                                'bucket_list' => 'Bucket List (future)',
                            ])
                            ->required()
                            ->default('tab')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('scope_key', '');
                                $set('route_key_picker', null);
                                $set('route_tab_picker', null);
                                $set('tab_key_picker', null);
                            }),
                    ]),

                    Select::make('tab_key_picker')
                        ->label('Tab')
                        ->options([
                            'home' => 'home',
                            'watch' => 'watch',
                            'inspire' => 'inspire',
                            'explore' => 'explore',
                            'more' => 'more',
                        ])
                        ->helperText('Select a tab. Example: watch should remain OFF.')
                        ->visible(fn (callable $get) => $get('scope_type') === 'tab')
                        ->live()
                        ->afterStateHydrated(function (callable $get, callable $set) {
                            if ((string) ($get('scope_type') ?? 'tab') === 'tab') {
                                $set('tab_key_picker', (string) ($get('scope_key') ?? ''));
                            }
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('scope_key', is_string($state) ? trim($state) : '');
                        })
                        ->dehydrated(false),

                    Grid::make(2)
                        ->visible(fn (callable $get) => $get('scope_type') === 'route')
                        ->schema([

                            Select::make('route_key_picker')
                                ->label('Inner Page (Route Key)')
                                ->searchable()
                                ->preload()
                                ->options(function () use ($activeAppId) {
                                    if ($activeAppId <= 0) return [];

                                    $rows = DB::table('app_routes')
                                        ->where('app_id', $activeAppId)
                                        ->where('is_enabled', 1)
                                        ->orderBy('tab_key')
                                        ->orderBy('key')
                                        ->get(['key', 'title', 'tab_key']);

                                    $out = [];
                                    foreach ($rows as $r) {
                                        $k = (string) $r->key;
                                        $t = (string) ($r->title ?? '');
                                        $tab = (string) ($r->tab_key ?? '');
                                        $label = $k;
                                        if ($t !== '') $label .= ' — ' . $t;
                                        if ($tab !== '') $label .= ' (' . $tab . ')';
                                        $out[$k] = $label;
                                    }

                                    return $out;
                                })
                                ->required()
                                ->live()
                                ->afterStateHydrated(function (callable $get, callable $set) {
                                    if ((string) ($get('scope_type') ?? '') !== 'route') return;

                                    $scopeKey = (string) ($get('scope_key') ?? '');
                                    if ($scopeKey === '') return;

                                    if (str_contains($scopeKey, ':')) {
                                        [$tab, $rk] = array_pad(explode(':', $scopeKey, 2), 2, '');
                                        $set('route_tab_picker', $tab !== '' ? $tab : null);
                                        $set('route_key_picker', $rk !== '' ? $rk : null);
                                    } else {
                                        $set('route_tab_picker', null);
                                        $set('route_key_picker', $scopeKey);
                                    }
                                })
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $rk = is_string($state) ? trim($state) : '';
                                    $tab = is_string($get('route_tab_picker')) ? trim((string) $get('route_tab_picker')) : '';

                                    $set('scope_key', $rk === '' ? '' : ($tab !== '' ? ($tab . ':' . $rk) : $rk));
                                })
                                ->dehydrated(false),

                            Select::make('route_tab_picker')
                                ->label('Tab Specific (Optional)')
                                ->options([
                                    '' => '— none (global route key) —',
                                    'home' => 'home',
                                    'watch' => 'watch',
                                    'inspire' => 'inspire',
                                    'explore' => 'explore',
                                    'more' => 'more',
                                ])
                                ->helperText('Use tab-specific rule like inspire:sod. Leave empty to match sod globally.')
                                ->live()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $rk = is_string($get('route_key_picker')) ? trim((string) $get('route_key_picker')) : '';
                                    $tab = is_string($state) ? trim($state) : '';

                                    $set('scope_key', $rk === '' ? '' : ($tab !== '' ? ($tab . ':' . $rk) : $rk));
                                })
                                ->dehydrated(false),
                        ]),

                    Hidden::make('scope_key')
                        ->required()
                        ->dehydrated(true),

                    TextInput::make('scope_key_preview')
                        ->label('Scope Key (stored)')
                        ->helperText('Stored value. For route: route_key OR tab:route_key (e.g. inspire:sod).')
                        ->disabled()
                        ->formatStateUsing(fn (callable $get) => (string) ($get('scope_key') ?? ''))
                        ->dehydrated(false),

                    Grid::make(4)->schema([
                        Toggle::make('is_enabled')->label('Ads Enabled (for this scope)')->default(true),
                        Toggle::make('banner_enabled')->default(true),
                        Toggle::make('native_enabled')->default(true),
                        Toggle::make('interstitial_enabled')->default(false),
                    ]),

                    TextInput::make('interstitial_cooldown_seconds')
                        ->label('Interstitial Cooldown (seconds)')
                        ->numeric()
                        ->default(120),

                    Section::make('Backend Placement Behaviour')
                        ->description('Flutter reads these values from the bootstrap API. Do not hardcode placement frequency or interstitial timing in the app.')
                        ->schema([
                            Select::make('settings_json.banner.placement')
                                ->label('Banner placement')
                                ->options([
                                    'shell_bottom' => 'Root tab shell bottom',
                                    'page_bottom' => 'Inner page bottom',
                                    'page_top' => 'Inner page top',
                                    'disabled' => 'Disabled',
                                ])
                                ->default('page_bottom'),

                            Grid::make(3)->schema([
                                TextInput::make('settings_json.native.start_after')
                                    ->label('Native starts after item')
                                    ->numeric()->minValue(0)->default(4),
                                TextInput::make('settings_json.native.every')
                                    ->label('Repeat native every N')
                                    ->numeric()->minValue(1)->default(4),
                                TextInput::make('settings_json.native.max_per_list')
                                    ->label('Maximum native per list')
                                    ->numeric()->minValue(0)->default(0),
                            ]),

                            Grid::make(4)->schema([
                                TextInput::make('settings_json.interstitial.every_n_safe_actions')
                                    ->label('Every N safe actions')
                                    ->numeric()->minValue(1)->default(4),
                                TextInput::make('settings_json.interstitial.cooldown_seconds')
                                    ->label('Cooldown seconds')
                                    ->numeric()->minValue(1)->default(120),
                                TextInput::make('settings_json.interstitial.minimum_launch_delay_seconds')
                                    ->label('Minimum launch delay')
                                    ->numeric()->minValue(0)->default(20),
                                TextInput::make('settings_json.interstitial.maximum_per_session')
                                    ->label('Maximum per session')
                                    ->numeric()->minValue(0)->default(4),
                            ]),

                            TextInput::make('settings_json.interstitial.minimum_page_dwell_seconds')
                                ->label('Minimum page dwell before eligible')
                                ->numeric()->minValue(0)->default(0),
                        ])
                        ->columns(1)
                        ->collapsible(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('scope_type')->label('Type')->sortable(),
                TextColumn::make('scope_key')->label('Key')->searchable(),
                ToggleColumn::make('is_enabled')->label('Enabled'),
                ToggleColumn::make('banner_enabled')->label('Banner')->toggleable(),
                ToggleColumn::make('native_enabled')->label('Native')->toggleable(),
                ToggleColumn::make('interstitial_enabled')->label('Inter')->toggleable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdRules::route('/'),
            'create' => Pages\CreateAdRule::route('/create'),
            'edit' => Pages\EditAdRule::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppRouteResource\Pages;
use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Models\AppRoute;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Support\Icons\SvgIconRegistry;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Validation\Rule;

class AppRouteResource extends ActiveAppScopedResource
{
    protected static ?string $model = AppRoute::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationGroup = 'App Structure';
    protected static ?string $navigationLabel = 'Pages';
    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'Page';
    protected static ?string $pluralModelLabel = 'Pages';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminMode::isAdvanced();
    }

    public static function form(Form $form): Form
    {
        $activeAppId = (int) (ActiveApp::get() ?? 0);

        return $form->schema([
            Section::make('Page Details')
                ->description('Create a page or inner destination inside the selected app. Use simple names users can recognize.')
                ->schema([
                    TextInput::make('app_id')
                        ->default($activeAppId > 0 ? $activeAppId : null)
                        ->disabled()
                        ->dehydrated()
                        ->numeric()
                        ->required()
                        ->label('Active App ID'),

                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->label('Page Title')
                            ->required()
                            ->maxLength(120)
                            ->helperText('Example: Seed of Destiny, Wordification, Live Watch.'),

                        TextInput::make('key')
                            ->label('Page Key')
                            ->required()
                            ->maxLength(80)
                            ->helperText('Stable internal key. Example: inspire_sod, watch_live.')
                            ->rule(function ($get, $record) {
                                $appId = (int) ($get('app_id') ?? 0);
                                if ($appId <= 0) {
                                    return null;
                                }

                                return Rule::unique('app_routes', 'key')
                                    ->where('app_id', $appId)
                                    ->ignore($record?->id);
                            }),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('route')
                            ->label('Page Path')
                            ->required()
                            ->maxLength(190)
                            ->helperText('Must start with "/". Example: /watch/live or /inspire/sod.')
                            ->rules(['regex:/^\\//']),

                        Select::make('tab_key')
                            ->label('Main Tab (Optional)')
                            ->options([
                                'home' => 'Home',
                                'watch' => 'Watch',
                                'inspire' => 'Inspire',
                                'explore' => 'Explore',
                                'more' => 'More',
                            ])
                            ->helperText('Choose this if the page belongs under one of the bottom tabs.'),
                    ]),

                    Toggle::make('is_enabled')
                        ->label('Page Enabled')
                        ->default(true)
                        ->required(),
                ])
                ->collapsible(),

            Section::make('Page Appearance')
                ->description('Optional visual settings for how this page is represented in the app.')
                ->schema([
                    Select::make('icon_key')
                        ->label('Page Icon (Optional)')
                        ->options(SvgIconRegistry::options())
                        ->searchable()
                        ->preload()
                        ->helperText('Optional override. If left empty, the app can fall back to default mapping.')
                        ->afterStateHydrated(function (callable $get, callable $set) {
                            $meta = self::decodeMetaStatic($get('meta_json') ?? null);
                            $set('icon_key', $meta['icon_key'] ?? null);
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $meta = self::decodeMetaStatic($get('meta_json') ?? null);

                            $key = is_string($state) ? trim($state) : '';
                            if ($key !== '') {
                                $meta['icon_key'] = $key;
                            } else {
                                unset($meta['icon_key']);
                            }

                            $set(
                                'meta_json',
                                empty($meta) ? '' : json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
                            );
                        })
                        ->dehydrated(false),
                ])
                ->collapsible(),

            Section::make('Advanced Page Settings')
                ->description('Only edit these if you already know the page needs extra runtime configuration.')
                ->schema([
                    Textarea::make('meta_json')
                        ->label('Advanced Meta JSON')
                        ->rows(10)
                        ->helperText('Optional runtime config. The icon dropdown above already manages icon_key for you.')
                        ->formatStateUsing(function ($state) {
                            if (is_array($state)) {
                                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                            }

                            if (is_string($state)) {
                                $string = trim($state);
                                if ($string === '' || strtolower($string) === 'null') {
                                    return '';
                                }

                                return $string;
                            }

                            return '';
                        })
                        ->dehydrateStateUsing(function ($state) {
                            if (! is_string($state)) {
                                return null;
                            }

                            $string = trim($state);
                            if ($string === '' || strtolower($string) === 'null') {
                                return null;
                            }

                            $json = json_decode($string, true);
                            return is_array($json) ? $json : null;
                        }),
                ])
                ->collapsed()
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('key')
            ->columns([
                TextColumn::make('key')->label('Key')->searchable()->sortable(),
                TextColumn::make('title')->label('Title')->searchable()->sortable(),
                TextColumn::make('route')->label('Path')->searchable()->sortable(),
                TextColumn::make('tab_key')->label('Tab')->sortable()->toggleable(),
                ToggleColumn::make('is_enabled')->label('Enabled'),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tab_key')
                    ->label('Tab')
                    ->options([
                        'home' => 'home',
                        'watch' => 'watch',
                        'inspire' => 'inspire',
                        'explore' => 'explore',
                        'more' => 'more',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppRoutes::route('/'),
            'create' => Pages\CreateAppRoute::route('/create'),
            'edit' => Pages\EditAppRoute::route('/{record}/edit'),
        ];
    }

    private static function decodeMetaStatic($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return [];
        }

        $string = trim($value);
        if ($string === '' || strtolower($string) === 'null') {
            return [];
        }

        $json = json_decode($string, true);
        return is_array($json) ? $json : [];
    }
}

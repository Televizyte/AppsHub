<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppTabResource\Pages;
use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Models\AppTab;
use App\Models\IconPreset;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class AppTabResource extends ActiveAppScopedResource
{
    protected static ?string $model = AppTab::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationGroup = 'App Structure';
    protected static ?string $navigationLabel = 'Tabs (Bottom Menu)';
    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Tab';
    protected static ?string $pluralModelLabel = 'Tabs';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminMode::isAdvanced();
    }

    public static function form(Form $form): Form
    {
        $activeAppId = (int) (ActiveApp::get() ?? 0);

        return $form->schema([
            Section::make('Tab Configuration')
                ->schema([
                    TextInput::make('app_id')
                        ->default($activeAppId > 0 ? $activeAppId : null)
                        ->disabled()
                        ->dehydrated()
                        ->numeric()
                        ->required()
                        ->label('App ID (Active App)'),

                    Grid::make(2)->schema([
                        TextInput::make('key')
                            ->required()
                            ->maxLength(60)
                            ->rule(function ($get, $record) {
                                $appId = (int) ($get('app_id') ?? 0);
                                if ($appId <= 0) {
                                    return null;
                                }

                                return Rule::unique('app_tabs', 'key')
                                    ->where('app_id', $appId)
                                    ->ignore($record?->id);
                            }),

                        TextInput::make('title')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('icon')
                            ->label('Icon (SVG Preset)')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(function () {
                                return IconPreset::query()
                                    ->where('is_active', true)
                                    ->orderBy('group')
                                    ->orderBy('sort_order')
                                    ->get()
                                    ->groupBy('group')
                                    ->map(function ($items) {
                                        return $items->mapWithKeys(fn ($item) => [$item->key => $item->label]);
                                    })
                                    ->toArray();
                            })
                            ->helperText('Choose from predefined SVG presets.'),
                    ]),

                    Placeholder::make('icon_preview')
                        ->label('Preview')
                        ->content(function (callable $get) {
                            $key = $get('icon');
                            if (! $key) {
                                return 'No icon selected';
                            }

                            $svg = IconPreset::where('key', $key)->value('svg');
                            if (! $svg) {
                                return 'Icon not found';
                            }

                            return new HtmlString(
                                '<div style="width:48px;height:48px;display:grid;place-items:center;border-radius:14px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);">'
                                . $svg .
                                '</div>'
                            );
                        }),

                    Grid::make(2)->schema([
                        TextInput::make('sort_order')->numeric()->default(0),
                        Toggle::make('is_enabled')->default(true),
                    ]),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->searchable(),
                TextColumn::make('icon')->label('Icon')->toggleable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                ToggleColumn::make('is_enabled'),
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
            'index' => Pages\ListAppTabs::route('/'),
            'create' => Pages\CreateAppTab::route('/create'),
            'edit' => Pages\EditAppTab::route('/{record}/edit'),
        ];
    }
}

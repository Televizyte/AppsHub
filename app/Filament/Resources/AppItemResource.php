<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppItemResource\Pages;
use App\Models\AppItem;
use App\Models\AppSection;
use App\Models\IconPreset;
use App\Models\MediaAsset;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class AppItemResource extends Resource
{
    protected static ?string $model = AppItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationGroup = 'App Structure';
    protected static ?string $navigationLabel = 'Content Blocks';
    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'Content Block';
    protected static ?string $pluralModelLabel = 'Content Blocks';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminMode::isAdvanced();
    }

    /**
     * app_items has NO app_id column in DB.
     * Scope by active app via relationship: app_items.section_id -> app_sections.app_id
     */
    public static function getEloquentQuery(): Builder
    {
        $appId = (int) (ActiveApp::ensure() ?? 0);

        $query = parent::getEloquentQuery();

        if ($appId <= 0) {
            return $query;
        }

        return $query->whereHas('section', function (Builder $query) use ($appId) {
            $query->where('app_id', $appId);
        });
    }

    public static function form(Form $form): Form
    {
        $activeAppId = (int) (ActiveApp::ensure() ?? 0);

        return $form->schema([
            Section::make('Content Basics')
                ->description('Create a clickable content card or block inside a selected section.')
                ->schema([
                    Hidden::make('type')
                        ->default('link')
                        ->dehydrated(true)
                        ->required(),

                    Select::make('section_id')
                        ->label('Place Inside Section')
                        ->options(function () use ($activeAppId) {
                            if ($activeAppId <= 0) {
                                return [];
                            }

                            return AppSection::where('app_id', $activeAppId)
                                ->orderBy('tab_key')
                                ->orderBy('sort_order')
                                ->pluck('title', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->required()
                        ->helperText('Only sections from the active app are shown here.'),

                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->label('Title')
                            ->required(),

                        TextInput::make('subtitle')
                            ->label('Subtitle (Optional)'),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('icon')
                            ->label('Icon (Optional)')
                            ->searchable()
                            ->preload()
                            ->options(
                                IconPreset::where('is_active', true)
                                    ->orderBy('label')
                                    ->pluck('label', 'key')
                                    ->toArray()
                            ),

                        Select::make('image_url')
                            ->label('Image from Media Library')
                            ->searchable()
                            ->options(function () use ($activeAppId) {
                                return MediaAsset::where('type', 'image')
                                    ->where('is_active', true)
                                    ->where(function ($query) use ($activeAppId) {
                                        $query->whereNull('app_id')
                                            ->orWhere('app_id', $activeAppId);
                                    })
                                    ->orderBy('label')
                                    ->pluck('label', 'url')
                                    ->toArray();
                            })
                            ->helperText('Choose a shared or app-specific image from the media library.'),
                    ]),

                    Placeholder::make('icon_preview')
                        ->label('Icon Preview')
                        ->content(function (callable $get) {
                            $key = $get('icon');
                            if (! $key) {
                                return null;
                            }

                            $svg = IconPreset::where('key', $key)->value('svg');
                            if (! $svg) {
                                return null;
                            }

                            return new HtmlString('<div style="width:42px;height:42px;display:grid;place-items:center;">' . $svg . '</div>');
                        }),

                    Grid::make(2)->schema([
                        TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0),

                        Toggle::make('is_enabled')
                            ->label('Content Enabled')
                            ->default(true),
                    ]),
                ])
                ->collapsible(),

            Section::make('Tap Action')
                ->description('Choose what happens when users tap this content block.')
                ->columns(12)
                ->schema([
                    Select::make('_action_type')
                        ->label('Action Type')
                        ->options([
                            'route' => 'Open Inner App Page',
                            'webview' => 'Open Web Page Inside App',
                            'external_url' => 'Open External Website',
                            'youtube_video' => 'Open YouTube Video',
                            'youtube_playlist' => 'Open YouTube Playlist',
                            'live_stream' => 'Open Live Stream',
                        ])
                        ->required()
                        ->default('route')
                        ->live()
                        ->helperText('This choice controls which field you fill below.')
                        ->afterStateHydrated(function (callable $get, callable $set) {
                            $payload = self::decodePayload($get('payload_json'));
                            $type = (string) ($payload['action']['type'] ?? 'route');
                            $set('_action_type', $type !== '' ? $type : 'route');

                            $set('_route_key', (string) ($payload['action']['route_key'] ?? ''));
                            $set('_url', (string) ($payload['action']['url'] ?? ''));
                            $set('_youtube', (string) ($payload['action']['youtube'] ?? ''));
                        })
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            self::syncPayload($get, $set);
                        })
                        ->columnSpan(6),

                    TextInput::make('_route_key')
                        ->label('Page Key')
                        ->placeholder('Example: sod, wordification, motivation, watch_live')
                        ->visible(fn (callable $get) => (string) $get('_action_type') === 'route')
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            self::syncPayload($get, $set);
                        })
                        ->columnSpan(6),

                    TextInput::make('_url')
                        ->label('URL')
                        ->placeholder('https://...')
                        ->visible(fn (callable $get) => in_array((string) $get('_action_type'), ['webview', 'external_url', 'live_stream'], true))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            self::syncPayload($get, $set);
                        })
                        ->helperText('Webview opens inside the app. External URL opens in the device browser. Live Stream is for HLS, DASH, or live links.')
                        ->columnSpan(12),

                    TextInput::make('_youtube')
                        ->label('YouTube Link or ID')
                        ->placeholder('Paste full link or just ID')
                        ->visible(fn (callable $get) => in_array((string) $get('_action_type'), ['youtube_video', 'youtube_playlist'], true))
                        ->live()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            self::syncPayload($get, $set);
                        })
                        ->helperText('For video or playlist, you can paste the full link or just the ID.')
                        ->columnSpan(12),
                ])
                ->collapsible(),

            Section::make('Advanced Action Payload')
                ->description('This is generated automatically from the action settings above. Only edit it manually if you know exactly what you are doing.')
                ->schema([
                    Textarea::make('payload_json')
                        ->label('Payload JSON')
                        ->rows(6)
                        ->helperText('Advanced runtime payload. Usually auto-built for you.'),
                ])
                ->collapsed()
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('title')->label('Title')->searchable(),
                TextColumn::make('type')->label('Type')->toggleable(),
                TextColumn::make('icon')->label('Icon')->toggleable(),
                TextColumn::make('image_url')->label('Image')->limit(40)->toggleable(),
                ToggleColumn::make('is_enabled')->label('Enabled'),
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
            'index' => Pages\ListAppItems::route('/'),
            'create' => Pages\CreateAppItem::route('/create'),
            'edit' => Pages\EditAppItem::route('/{record}/edit'),
        ];
    }

    private static function decodePayload($value): array
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

    private static function syncPayload(callable $get, callable $set): void
    {
        $type = (string) ($get('_action_type') ?? 'route');
        $routeKey = trim((string) ($get('_route_key') ?? ''));
        $url = trim((string) ($get('_url') ?? ''));
        $youtube = trim((string) ($get('_youtube') ?? ''));

        $payload = self::decodePayload($get('payload_json'));
        $payload['action'] = $payload['action'] ?? [];
        $payload['action']['type'] = $type;

        unset($payload['action']['route_key'], $payload['action']['url'], $payload['action']['youtube']);

        if ($type === 'route' && $routeKey !== '') {
            $payload['action']['route_key'] = $routeKey;
        }

        if (in_array($type, ['webview', 'external_url', 'live_stream'], true) && $url !== '') {
            $payload['action']['url'] = $url;
        }

        if (in_array($type, ['youtube_video', 'youtube_playlist'], true) && $youtube !== '') {
            $payload['action']['youtube'] = $youtube;
        }

        $set('payload_json', json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

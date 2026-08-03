<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WatchLinkResource\Pages;
use App\Models\App;
use App\Models\MediaAsset;
use App\Models\WatchLink;
use App\Support\ActiveApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WatchLinkResource extends Resource
{
    protected static ?string $model = WatchLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationGroup = 'Watch Builder';
    protected static ?int $navigationSort = 10;

    
    public static function shouldRegisterNavigation(): bool
    {
        return ! \App\Support\AdminMode::isBeginner();
    }

public static function getEloquentQuery(): Builder
    {
        $appId = (int) (ActiveApp::ensure() ?? 0);

        $query = parent::getEloquentQuery()->with('app');

        if ($appId <= 0) {
            return $query;
        }

        return $query->where('app_id', $appId);
    }

    public static function form(Form $form): Form
    {
        $activeAppId = (int) (ActiveApp::ensure() ?? 0);

        return $form->schema([
            Forms\Components\Section::make('Watch Link Configuration')
                ->description('Manage Watch cards, streams, grouped channels, card images and playback behavior from backend.')
                ->schema([
                    Forms\Components\Select::make('app_id')
                        ->label('App')
                        ->options(fn () => App::query()->orderBy('name')->pluck('name', 'id')->toArray())
                        ->default($activeAppId > 0 ? $activeAppId : null)
                        ->searchable()
                        ->required(),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('type')
                            ->required()
                            ->options([
                                'live_hls' => 'live_hls',
                                'live_youtube' => 'live_youtube',
                                'commanding_day' => 'commanding_day',
                                'video' => 'video',
                                'channel' => 'channel',
                                'web' => 'web',
                            ])
                            ->default('video')
                            ->native(false),
                    ]),

                    Forms\Components\Textarea::make('url')
                        ->label('Link / Stream URL')
                        ->rows(3)
                        ->helperText('Examples: HLS (.m3u8), YouTube video/playlist, or web/iframe link.'),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('meta_json.subtitle')
                            ->label('Subtitle')
                            ->placeholder('Optional card subtitle')
                            ->default(''),

                        Forms\Components\TextInput::make('meta_json.label')
                            ->label('Badge Label')
                            ->placeholder('LIVE, SERVICE, PRAYER, CHANNELS...')
                            ->default(''),
                    ]),

                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Select::make('meta_json.player')
                            ->label('Player')
                            ->options([
                                'hls' => 'hls',
                                'youtube' => 'youtube',
                                'web' => 'web',
                            ])
                            ->default('youtube')
                            ->native(false),

                        Forms\Components\Select::make('meta_json.group')
                            ->label('Group')
                            ->options([
                                '' => 'none',
                                'other_channels' => 'other_channels',
                                'video' => 'video',
                                'sermons' => 'sermons',
                                'programs' => 'programs',
                                'events' => 'events',
                            ])
                            ->default('')
                            ->native(false)
                            ->helperText('Use other_channels for the inner channel page list. Other custom groups will also render dynamically.'),

                        Forms\Components\Toggle::make('is_enabled')
                            ->default(true),
                    ]),

                    Forms\Components\Select::make('meta_json.image_url')
                        ->label('Card Image Asset')
                        ->searchable()
                        ->options(function () use ($activeAppId) {
                            return MediaAsset::query()
                                ->where('type', 'image')
                                ->where('is_active', true)
                                ->where(function ($q) use ($activeAppId) {
                                    if ($activeAppId > 0) {
                                        $q->whereNull('app_id')
                                            ->orWhere('app_id', $activeAppId);
                                    } else {
                                        $q->whereNull('app_id');
                                    }
                                })
                                ->orderBy('label')
                                ->pluck('label', 'url')
                                ->toArray();
                        })
                        ->helperText('This controls the Watch card image from backend.')
                        ->default(''),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),

                        Forms\Components\Hidden::make('meta_json')
                            ->default([]),
                    ]),

                    Forms\Components\Textarea::make('_meta_preview')
                        ->label('Meta Preview')
                        ->rows(8)
                        ->disabled()
                        ->dehydrated(false)
                        ->live()
                        ->formatStateUsing(function (Get $get) {
                            $meta = [];

                            $subtitle = trim((string) ($get('meta_json.subtitle') ?? ''));
                            $label = trim((string) ($get('meta_json.label') ?? ''));
                            $player = trim((string) ($get('meta_json.player') ?? ''));
                            $group = trim((string) ($get('meta_json.group') ?? ''));
                            $imageUrl = trim((string) ($get('meta_json.image_url') ?? ''));

                            if ($subtitle !== '') {
                                $meta['subtitle'] = $subtitle;
                            }

                            if ($label !== '') {
                                $meta['label'] = $label;
                            }

                            if ($player !== '') {
                                $meta['player'] = $player;
                            }

                            if ($group !== '') {
                                $meta['group'] = $group;
                            }

                            if ($imageUrl !== '') {
                                $meta['image_url'] = $imageUrl;
                            }

                            return json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        }),
                ])
                ->collapsible(),
        ]);
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['title'] = trim((string) ($data['title'] ?? ''));
        $data['type'] = trim((string) ($data['type'] ?? ''));
        $data['url'] = trim((string) ($data['url'] ?? ''));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_enabled'] = (bool) ($data['is_enabled'] ?? false);

        $meta = is_array($data['meta_json'] ?? null) ? $data['meta_json'] : [];

        foreach (['subtitle', 'label', 'player', 'group', 'image_url'] as $key) {
            $value = trim((string) ($meta[$key] ?? ''));
            if ($value === '') {
                unset($meta[$key]);
            } else {
                $meta[$key] = $value;
            }
        }

        $data['meta_json'] = $meta;
        unset($data['_meta_preview']);

        return $data;
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        $data['title'] = trim((string) ($data['title'] ?? ''));
        $data['type'] = trim((string) ($data['type'] ?? ''));
        $data['url'] = trim((string) ($data['url'] ?? ''));
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_enabled'] = (bool) ($data['is_enabled'] ?? false);

        $meta = is_array($data['meta_json'] ?? null) ? $data['meta_json'] : [];

        foreach (['subtitle', 'label', 'player', 'group', 'image_url'] as $key) {
            $value = trim((string) ($meta[$key] ?? ''));
            if ($value === '') {
                unset($meta[$key]);
            } else {
                $meta[$key] = $value;
            }
        }

        $data['meta_json'] = $meta;
        unset($data['_meta_preview']);

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->label('#')
                    ->width('70px'),

                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('meta_group')
                    ->label('Group')
                    ->badge()
                    ->state(fn (WatchLink $record) => (string) data_get($record->meta_json, 'group', '-') ?: '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('meta_player')
                    ->label('Player')
                    ->badge()
                    ->state(fn (WatchLink $record) => (string) data_get($record->meta_json, 'player', '-') ?: '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('meta_label')
                    ->label('Badge')
                    ->state(fn (WatchLink $record) => (string) data_get($record->meta_json, 'label', '-') ?: '-')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('meta_subtitle')
                    ->label('Subtitle')
                    ->state(fn (WatchLink $record) => (string) data_get($record->meta_json, 'subtitle', ''))
                    ->limit(40)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('url')
                    ->limit(55)
                    ->toggleable(),

                Tables\Columns\ToggleColumn::make('is_enabled')
                    ->label('Enabled'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),
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
            'index' => Pages\ListWatchLinks::route('/'),
            'create' => Pages\CreateWatchLink::route('/create'),
            'edit' => Pages\EditWatchLink::route('/{record}/edit'),
        ];
    }
}

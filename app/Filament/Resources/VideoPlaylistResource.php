<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Filament\Resources\VideoPlaylistResource\Pages;
use App\Filament\Resources\VideoPlaylistResource\RelationManagers\ItemsRelationManager;
use App\Models\MediaAsset;
use App\Models\VideoChannel;
use App\Models\VideoPlaylist;
use App\Support\ActiveApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class VideoPlaylistResource extends ActiveAppScopedResource
{
    protected static ?string $model = VideoPlaylist::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Video Playlists';
    protected static ?string $modelLabel = 'Video Playlist';
    protected static ?string $pluralModelLabel = 'Video Playlists';

    public static function form(Form $form): Form
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        return $form
            ->schema([
                Forms\Components\Section::make('Playlist')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('video_channel_id')
                            ->label('Channel')
                            ->options(
                                VideoChannel::query()
                                    ->where('app_id', $activeAppId)
                                    ->orderBy('sort_order')
                                    ->orderBy('title')
                                    ->pluck('title', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->required()
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('provider')
                            ->placeholder('youtube, appshub, external...')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('provider_playlist_id')
                            ->label('Provider Playlist ID')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('external_url')
                            ->label('External Playlist URL')
                            ->url()
                            ->columnSpan(4),

                        Forms\Components\Select::make('thumbnail_media_asset_id')
                            ->label('Thumbnail')
                            ->options(
                                MediaAsset::query()
                                    ->where('app_id', $activeAppId)
                                    ->where('type', 'image')
                                    ->where('is_active', true)
                                    ->orderByDesc('id')
                                    ->limit(300)
                                    ->pluck('id', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->nullable()
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\Toggle::make('is_featured')
                            ->default(false)
                            ->columnSpan(3),
                    ]),

                Forms\Components\Section::make('Publishing')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                                'active' => 'Active',
                            ])
                            ->default('draft')
                            ->required()
                            ->columnSpan(4),

                        Forms\Components\Select::make('visibility')
                            ->options([
                                'public' => 'Public',
                                'private' => 'Private',
                            ])
                            ->default('public')
                            ->required()
                            ->columnSpan(4),

                        Forms\Components\DateTimePicker::make('publish_at')
                            ->timezone('Africa/Lagos')
                            ->columnSpan(4),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->timezone('Africa/Lagos')
                            ->columnSpan(4),
                    ]),

                Forms\Components\Section::make('Advanced')
                    ->collapsed()
                    ->schema([
                        Forms\Components\KeyValue::make('settings_json')
                            ->label('Settings'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('channel.title')
                    ->label('Channel')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->sortable(),

                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('visibility')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone('Africa/Lagos')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('video_channel_id')
                    ->label('Channel')
                    ->options(fn (): array => VideoChannel::query()
                        ->where('app_id', (int) (ActiveApp::ensureId() ?? 0))
                        ->orderBy('title')
                        ->pluck('title', 'id')
                        ->all()),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'active' => 'Active',
                    ]),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
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

    public static function getRelations(): array
    {
        return [
            ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideoPlaylists::route('/'),
            'create' => Pages\CreateVideoPlaylist::route('/create'),
            'edit' => Pages\EditVideoPlaylist::route('/{record}/edit'),
        ];
    }
}

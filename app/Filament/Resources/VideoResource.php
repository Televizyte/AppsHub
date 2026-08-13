<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Filament\Resources\VideoResource\Pages;
use App\Models\MediaAsset;
use App\Models\Video;
use App\Models\VideoChannel;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Support\Video\VideoSourceContract;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class VideoResource extends ActiveAppScopedResource
{
    protected static ?string $model = Video::class;

    protected static ?string $navigationIcon = 'heroicon-o-play-circle';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Videos';
    protected static ?string $modelLabel = 'Video';
    protected static ?string $pluralModelLabel = 'Videos';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminMode::isAdvanced();
    }

    public static function form(Form $form): Form
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        return $form
            ->schema([
                Forms\Components\Section::make('Video')
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
                            ->required()
                            ->searchable()
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
                    ]),

                Forms\Components\Section::make('Playback Source')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('source_type')
                            ->options([
                                VideoSourceContract::UPLOADED_VIDEO => 'Uploaded Video',
                                VideoSourceContract::EXTERNAL_VIDEO => 'External Video',
                                VideoSourceContract::HLS => 'HLS Stream',
                                VideoSourceContract::YOUTUBE_VIDEO => 'YouTube Video',
                                VideoSourceContract::WEB_EMBED => 'Web Embed',
                            ])
                            ->required()
                            ->live()
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('provider')
                            ->helperText('Examples: appshub, youtube, hls, web, external')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('provider_video_id')
                            ->label('YouTube / Provider Video ID')
                            ->visible(fn (callable $get): bool =>
                                $get('source_type') === VideoSourceContract::YOUTUBE_VIDEO
                            )
                            ->columnSpan(4),

                        Forms\Components\Select::make('media_asset_id')
                            ->label('Uploaded Video MediaAsset')
                            ->options(
                                MediaAsset::query()
                                    ->where('app_id', $activeAppId)
                                    ->where('type', 'video')
                                    ->where('is_active', true)
                                    ->orderByDesc('id')
                                    ->limit(300)
                                    ->pluck('id', 'id')
                                    ->all()
                            )
                            ->searchable()
                            ->visible(fn (callable $get): bool =>
                                $get('source_type') === VideoSourceContract::UPLOADED_VIDEO
                            )
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('external_url')
                            ->label('External / Playback URL')
                            ->url()
                            ->visible(fn (callable $get): bool =>
                                in_array($get('source_type'), [
                                    VideoSourceContract::EXTERNAL_VIDEO,
                                    VideoSourceContract::HLS,
                                    VideoSourceContract::YOUTUBE_VIDEO,
                                    VideoSourceContract::WEB_EMBED,
                                ], true)
                            )
                            ->helperText('YouTube accepts a normal video URL. Playlists belong in Video Playlists, not here.')
                            ->columnSpan(6),

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

                        Forms\Components\TextInput::make('mime_type')
                            ->placeholder('video/mp4')
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('duration_seconds')
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('aspect_ratio')
                            ->placeholder('16:9')
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('is_live')
                            ->label('Live Stream')
                            ->default(false)
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(2),
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

                Forms\Components\Section::make('Playback & Advanced Settings')
                    ->collapsed()
                    ->schema([
                        Forms\Components\KeyValue::make('playback_settings_json')
                            ->label('Playback Settings')
                            ->helperText('For web_embed, set embed_allowed = true.'),

                        Forms\Components\KeyValue::make('settings_json')
                            ->label('Public / Advanced Settings'),
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

                Tables\Columns\TextColumn::make('source_type')
                    ->label('Source')
                    ->badge(),

                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->toggleable(),

                Tables\Columns\IconColumn::make('is_live')
                    ->label('Live')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('visibility')
                    ->badge(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone('Africa/Lagos')
                    ->sortable()
                    ->toggleable(),

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

                Tables\Filters\SelectFilter::make('source_type')
                    ->options([
                        VideoSourceContract::UPLOADED_VIDEO => 'Uploaded Video',
                        VideoSourceContract::EXTERNAL_VIDEO => 'External Video',
                        VideoSourceContract::HLS => 'HLS',
                        VideoSourceContract::YOUTUBE_VIDEO => 'YouTube',
                        VideoSourceContract::WEB_EMBED => 'Web Embed',
                    ]),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'active' => 'Active',
                    ]),

                Tables\Filters\TernaryFilter::make('is_live')
                    ->label('Live'),

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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideos::route('/'),
            'create' => Pages\CreateVideo::route('/create'),
            'edit' => Pages\EditVideo::route('/{record}/edit'),
        ];
    }
}

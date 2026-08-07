<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Filament\Resources\VideoChannelResource\Pages;
use App\Models\MediaAsset;
use App\Models\VideoChannel;
use App\Support\ActiveApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class VideoChannelResource extends ActiveAppScopedResource
{
    protected static ?string $model = VideoChannel::class;

    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Video Channels';
    protected static ?string $modelLabel = 'Video Channel';
    protected static ?string $pluralModelLabel = 'Video Channels';

    public static function form(Form $form): Form
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        return $form
            ->schema([
                Forms\Components\Section::make('Channel')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Stable API identifier, for example: messages')
                            ->columnSpan(6),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),

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
                            ->helperText('Image MediaAsset ID from the active app.')
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('sort_order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->columnSpan(3),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
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
                            ->label('Publish At')
                            ->timezone('Africa/Lagos')
                            ->columnSpan(4),

                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('Published At')
                            ->timezone('Africa/Lagos')
                            ->columnSpan(4),
                    ]),

                Forms\Components\Section::make('Advanced')
                    ->collapsed()
                    ->schema([
                        Forms\Components\KeyValue::make('notification_settings_json')
                            ->label('Notification Settings'),

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

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('playlists_count')
                    ->label('Playlists')
                    ->counts('playlists')
                    ->sortable(),

                Tables\Columns\TextColumn::make('videos_count')
                    ->label('Videos')
                    ->counts('videos')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('visibility')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('publish_at')
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'active' => 'Active',
                    ]),

                Tables\Filters\SelectFilter::make('visibility')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVideoChannels::route('/'),
            'create' => Pages\CreateVideoChannel::route('/create'),
            'edit' => Pages\EditVideoChannel::route('/{record}/edit'),
        ];
    }
}

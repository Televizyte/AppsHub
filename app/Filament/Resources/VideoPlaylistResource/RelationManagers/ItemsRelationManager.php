<?php

namespace App\Filament\Resources\VideoPlaylistResource\RelationManagers;

use App\Models\Video;
use App\Support\ActiveApp;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Playlist Videos';

    protected static ?string $modelLabel = 'Playlist Video';

    protected static ?string $pluralModelLabel = 'Playlist Videos';

    public function form(Form $form): Form
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        return $form
            ->schema([
                Forms\Components\Hidden::make('app_id')
                    ->default($appId),

                Forms\Components\Select::make('video_id')
                    ->label('Video')
                    ->options(function () use ($appId): array {
                        if ($appId <= 0) {
                            return [];
                        }

                        return Video::query()
                            ->where('app_id', $appId)
                            ->orderByDesc('is_featured')
                            ->orderBy('sort_order')
                            ->orderBy('title')
                            ->get()
                            ->mapWithKeys(function (Video $video): array {
                                $source = trim((string) $video->source_type);
                                $status = trim((string) $video->status);

                                $label = $video->title;

                                if ($source !== '') {
                                    $label .= ' — ' . $source;
                                }

                                if ($status !== '') {
                                    $label .= ' [' . $status . ']';
                                }

                                return [(int) $video->id => $label];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\TextInput::make('sort_order')
                    ->label('Order')
                    ->numeric()
                    ->minValue(0)
                    ->default(0)
                    ->required(),

                Forms\Components\KeyValue::make('settings_json')
                    ->label('Item Settings')
                    ->helperText('Optional per-playlist placement settings.')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('video.title')
                    ->label('Video')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('video.source_type')
                    ->label('Source')
                    ->badge(),

                Tables\Columns\IconColumn::make('video.is_live')
                    ->label('Live')
                    ->boolean(),

                Tables\Columns\TextColumn::make('video.status')
                    ->label('Status')
                    ->badge(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M j, Y g:i A')
                    ->timezone('Africa/Lagos')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Video')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['app_id'] = (int) (ActiveApp::ensureId() ?? 0);

                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        unset($data['app_id']);

                        return $data;
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query): Builder {
                $appId = (int) (ActiveApp::ensureId() ?? 0);

                if ($appId <= 0) {
                    return $query->whereRaw('1 = 0');
                }

                return $query
                    ->where('video_playlist_items.app_id', $appId)
                    ->whereHas('video', fn (Builder $video) =>
                        $video->where('app_id', $appId)
                    );
            });
    }
}

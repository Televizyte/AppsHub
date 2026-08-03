<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MediaAssetResource\Pages;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaAssetResource extends Resource
{
    protected static ?string $model = MediaAsset::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Media & Assets';
    protected static ?string $navigationLabel = 'Media Assets (Advanced)';
    protected static ?int $navigationSort = 40;

    
    public static function shouldRegisterNavigation(): bool
    {
        // Beginner mode uses the visual /admin/media-library page.
        // The technical resource remains available in Advanced mode and by direct URL.
        return ! \App\Support\AdminMode::isBeginner();
    }
/**
     * IMPORTANT:
     * Do NOT hard-scope here.
     * Scoping must be controlled by the Table "Scope" filter (default: Active App + Shared).
     * This avoids breaking "All Apps" filter option.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    protected static function activeAppId(): int
    {
        $activeAppId = session('active_app_id');
        return is_numeric($activeAppId) ? (int) $activeAppId : 0;
    }

    protected static function appOptions(): array
    {
        return DB::table('apps')->orderBy('name')->pluck('name', 'id')->toArray();
    }

    protected static function getPublicUrl(MediaAsset $record): string
    {
        $url = (string) ($record->url ?? '');
        if ($url !== '') {
            return $url;
        }

        $disk = (string) ($record->disk ?? 'public');
        $path = (string) ($record->path ?? '');

        if ($path === '') {
            return '';
        }

        try {
            return (string) Storage::disk($disk)->url($path);
        } catch (\Throwable $e) {
            return '';
        }
    }

    protected static function isImage(MediaAsset $record): bool
    {
        $mime = strtolower((string) ($record->mime ?? ''));
        if ($mime !== '' && str_starts_with($mime, 'image/')) {
            return true;
        }

        $type = strtolower((string) ($record->type ?? ''));
        return $type === 'image';
    }

    protected static function humanSize(?int $bytes): string
    {
        $bytesValue = (int) ($bytes ?? 0);
        if ($bytesValue <= 0) {
            return '-';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $index = 0;
        $value = (float) $bytesValue;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . ' ' . $units[$index];
    }

    public static function form(Form $form): Form
    {
        $apps = static::appOptions();

        $activeAppId = static::activeAppId();
        $activeAppId = $activeAppId > 0 ? $activeAppId : null;

        return $form->schema([
            Forms\Components\Section::make('Media Asset')
                ->description('Upload and manage reusable media for the active app or shared library.')
                ->columns(12)
                ->schema([
                    Forms\Components\Select::make('app_id')
                        ->label('App (Optional)')
                        ->options($apps)
                        ->searchable()
                        ->placeholder('Shared / Global')
                        ->default($activeAppId)
                        ->columnSpan(4),

                    Forms\Components\Select::make('bucket')
                        ->label('Category')
                        ->options([
                            'logos' => 'logos',
                            'banners' => 'banners',
                            'covers' => 'covers',
                            'thumbnails' => 'thumbnails',
                            'misc' => 'misc',
                        ])
                        ->default('misc')
                        ->required()
                        ->columnSpan(4),

                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options([
                            'image' => 'image',
                            'video' => 'video',
                            'audio' => 'audio',
                            'file'  => 'file',
                            'pdf'   => 'pdf',
                        ])
                        ->default('image')
                        ->required()
                        ->columnSpan(2),

                    Forms\Components\Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('sort_order')
                        ->label('Display Order')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('label')
                        ->label('Label')
                        ->maxLength(180)
                        ->placeholder('Optional label')
                        ->columnSpan(10),

                    Forms\Components\Hidden::make('disk')->default('public'),
                    Forms\Components\Hidden::make('path'),
                    Forms\Components\Hidden::make('mime'),
                    Forms\Components\Hidden::make('size'),
                    Forms\Components\Hidden::make('width'),
                    Forms\Components\Hidden::make('height'),

                    Forms\Components\FileUpload::make('upload')
                        ->label('Upload File')
                        ->disk('public')
                        ->preserveFilenames(false)
                        ->acceptedFileTypes([
                            'image/*',
                            'video/*',
                            'audio/*',
                            'application/pdf',
                            'text/plain',
                        ])
                        ->maxSize(20480)
                        ->columnSpan(12)
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(false)
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file, callable $get, callable $set) {
                            $disk = 'public';

                            $appId = (int) ($get('app_id') ?? 0);
                            $appSlug = $appId ? (string) DB::table('apps')->where('id', $appId)->value('slug') : 'shared';
                            $bucket = (string) ($get('bucket') ?? 'misc');

                            $appSlug = $appSlug ?: 'shared';
                            $bucket = $bucket ?: 'misc';

                            $dir = "assets/{$appSlug}/{$bucket}";

                            $ext = strtolower($file->getClientOriginalExtension() ?: 'bin');
                            $safe = preg_replace('/[^a-z0-9]+/i', '', $ext) ?: 'bin';
                            $name = 'asset_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(10)) . '.' . $safe;

                            $storedPath = $file->storeAs($dir, $name, $disk);

                            $set('disk', $disk);
                            $set('path', $storedPath);
                            $set('url', Storage::disk($disk)->url($storedPath));

                            try {
                                $full = Storage::disk($disk)->path($storedPath);
                                if (is_file($full)) {
                                    $set('size', @filesize($full) ?: null);
                                    $set('mime', @mime_content_type($full) ?: null);

                                    $info = @getimagesize($full);
                                    if (is_array($info)) {
                                        $set('width', $info[0] ?? null);
                                        $set('height', $info[1] ?? null);
                                    } else {
                                        $set('width', null);
                                        $set('height', null);
                                    }
                                }
                            } catch (\Throwable $e) {
                            }

                            return $storedPath;
                        }),

                    Forms\Components\TextInput::make('url')
                        ->label('Public URL')
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(12),

                    Forms\Components\TagsInput::make('tags_json')
                        ->label('Tags')
                        ->placeholder('Add tags…')
                        ->separator(',')
                        ->columnSpan(12)
                        ->helperText('Example: dunamis, banner, home'),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $apps = static::appOptions();
        $activeAppId = static::activeAppId();

        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('preview')
                    ->label('Preview')
                    ->square()
                    ->getStateUsing(function (MediaAsset $record) {
                        if (!static::isImage($record)) {
                            return null;
                        }
                        $url = static::getPublicUrl($record);
                        return $url !== '' ? $url : null;
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight(FontWeight::SemiBold),

                Tables\Columns\TextColumn::make('bucket')
                    ->label('Category')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('app_id')
                    ->label('App')
                    ->formatStateUsing(fn ($state) => $state ? ($apps[(int) $state] ?? ('App #' . (int) $state)) : 'Shared')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('meta')
                    ->label('Meta')
                    ->getStateUsing(function (MediaAsset $record) {
                        $parts = [];

                        $mime = (string) ($record->mime ?? '');
                        if ($mime !== '') {
                            $parts[] = $mime;
                        }

                        $size = static::humanSize((int) ($record->size ?? 0));
                        if ($size !== '-') {
                            $parts[] = $size;
                        }

                        $width = (int) ($record->width ?? 0);
                        $height = (int) ($record->height ?? 0);
                        if ($width > 0 && $height > 0) {
                            $parts[] = "{$width}×{$height}";
                        }

                        return implode(' • ', $parts) ?: '-';
                    })
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->wrap(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('scope')
                    ->label('Scope')
                    ->options([
                        'active' => 'Active App + Shared',
                        'all'    => 'All Apps',
                        'shared' => 'Shared Only',
                    ])
                    ->default('active')
                    ->query(function (Builder $query, array $data) use ($activeAppId) {
                        $scope = $data['value'] ?? 'active';

                        if ($scope === 'all') {
                            return $query;
                        }

                        if ($scope === 'shared') {
                            return $query->whereNull('app_id');
                        }

                        if ($activeAppId > 0) {
                            return $query->where(function (Builder $innerQuery) use ($activeAppId) {
                                $innerQuery->whereNull('app_id')
                                    ->orWhere('app_id', $activeAppId);
                            });
                        }

                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('bucket')->label('Category')->options([
                    'logos' => 'logos',
                    'banners' => 'banners',
                    'covers' => 'covers',
                    'thumbnails' => 'thumbnails',
                    'misc' => 'misc',
                ]),

                Tables\Filters\SelectFilter::make('type')->options([
                    'image' => 'image',
                    'video' => 'video',
                    'audio' => 'audio',
                    'file'  => 'file',
                    'pdf'   => 'pdf',
                ]),

                Tables\Filters\SelectFilter::make('app_id')
                    ->label('App')
                    ->options($apps)
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('copy_url')
                    ->label('Copy URL')
                    ->icon('heroicon-o-clipboard-document')
                    ->color('gray')
                    ->visible(fn (MediaAsset $record) => static::getPublicUrl($record) !== '')
                    ->extraAttributes(fn (MediaAsset $record) => [
                        'x-on:click' => "navigator.clipboard.writeText(" . json_encode(static::getPublicUrl($record)) . ")",
                    ]),

                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (MediaAsset $record) => static::getPublicUrl($record) ?: '#', shouldOpenInNewTab: true)
                    ->visible(fn (MediaAsset $record) => static::getPublicUrl($record) !== ''),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMediaAssets::route('/'),
            'create' => Pages\CreateMediaAsset::route('/create'),
            'edit' => Pages\EditMediaAsset::route('/{record}/edit'),
        ];
    }
}

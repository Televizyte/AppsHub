<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PushNotificationResource\Pages;
use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Jobs\SendPushNotificationJob;
use App\Models\App;
use App\Models\MediaAsset;
use App\Models\PushNotification;
use App\Support\ActiveApp;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class PushNotificationResource extends ActiveAppScopedResource
{
    protected static ?string $model = PushNotification::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?string $navigationLabel = 'Push Notifications';
    protected static ?int $navigationSort = 20;

    
    public static function shouldRegisterNavigation(): bool
    {
        if (! \App\Support\AdminMode::isBeginner()) {
            return true;
        }

        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);

        $activeApp = $activeAppId > 0
            ? \App\Models\App::query()->find($activeAppId)
            : null;

        return false;
    }
/**
     * Filament BaseFileUpload in some versions expects an array state while editing,
     * even for single-file fields. Normalize for safety.
     */
    private static function normalizeUploadStateForUi($state): array
    {
        if (is_array($state)) {
            return $state;
        }
        if (is_string($state) && trim($state) !== '') {
            return [$state];
        }
        return [];
    }

    private static function normalizeUploadStateForDb($state): ?string
    {
        if (is_array($state)) {
            $value = $state[0] ?? null;
            return is_string($value) && trim($value) !== '' ? $value : null;
        }
        if (is_string($state) && trim($state) !== '') {
            return $state;
        }
        return null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Message')
                ->description('Create a message for the currently selected app. Image support is optional.')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(120),

                        TextInput::make('image_url')
                            ->label('Image URL (Optional)')
                            ->helperText('Paste a URL or upload an image below. If uploaded, this will auto-fill.')
                            ->url()
                            ->maxLength(500),
                    ]),

                    Hidden::make('media_asset_id'),

                    FileUpload::make('image_upload')
                        ->label('Upload Image (Optional)')
                        ->image()
                        ->disk('public')
                        ->visibility('public')
                        ->maxSize(5120)
                        ->imageEditor()
                        ->directory(fn (): string => self::pushDirectory())
                        ->formatStateUsing(fn ($state) => self::normalizeUploadStateForUi($state))
                        ->dehydrateStateUsing(fn ($state) => self::normalizeUploadStateForDb($state))
                        ->saveUploadedFileUsing(function (TemporaryUploadedFile $file) {
                            return $file->storePublicly(self::pushDirectory(), 'public');
                        })
                        ->afterStateHydrated(function (callable $set, $record) {
                            $path = $record?->mediaAsset?->path;
                            if (is_string($path) && trim($path) !== '') {
                                $set('image_upload', [$path]);
                                return;
                            }

                            $set('image_upload', []);
                        })
                        ->afterStateUpdated(function ($state, callable $set) {
                            $path = self::normalizeUploadStateForDb($state);

                            if (! $path) {
                                return;
                            }

                            $set('image_url', Storage::disk('public')->url($path));
                        })
                        ->helperText('Uploads to public storage. On save, a reusable Media Asset is also created.'),

                    Actions::make([
                        FormAction::make('clearPushImage')
                            ->label('Clear Image')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->action(function (callable $set) {
                                $set('image_upload', []);
                                $set('image_url', null);
                                $set('media_asset_id', null);

                                Notification::make()
                                    ->title('Cleared')
                                    ->body('Image cleared. Save to persist.')
                                    ->success()
                                    ->send();
                            }),
                    ])->columnSpanFull(),

                    Textarea::make('body')
                        ->label('Message Body')
                        ->required()
                        ->rows(4)
                        ->maxLength(600),

                    Grid::make(2)->schema([
                        TextInput::make('deep_link_url')
                            ->label('Deep Link URL (Optional)')
                            ->helperText('Example: /watch, /inspire/article/123, https://...')
                            ->maxLength(600),

                        TextInput::make('click_action')
                            ->label('Click Action (Optional)')
                            ->helperText('Defaults to FLUTTER_NOTIFICATION_CLICK.')
                            ->maxLength(120),
                    ]),
                ])
                ->collapsible(),

            Section::make('Target')
                ->description('App-scoped targeting. Topics are recommended. Tokens are also supported.')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('target_type')
                            ->required()
                            ->options([
                                'topic' => 'Topic (Recommended)',
                                'all'   => 'All Users (maps to app topic)',
                                'token' => 'Single Device Token',
                            ])
                            ->default('topic'),

                        TextInput::make('target_value')
                            ->label('Target Value')
                            ->helperText('If Topic: enter topic name. If empty, uses "app-{appSlug}". If Token: paste token.')
                            ->maxLength(255),
                    ]),
                ])
                ->collapsible(),

            Section::make('Schedule + Recurrence')
                ->description('Supports one-time and recurring schedules.')
                ->schema([
                    Grid::make(3)->schema([
                        DateTimePicker::make('scheduled_for')
                            ->label('Scheduled For (Optional)')
                            ->helperText('If empty, it becomes due immediately once queued.'),

                        TextInput::make('timezone')
                            ->label('Timezone')
                            ->default(fn () => config('push.default_timezone') ?: config('app.timezone') ?: 'Africa/Lagos')
                            ->helperText('Example: Africa/Lagos.')
                            ->maxLength(60),

                        Select::make('status')
                            ->options([
                                'draft' => 'Draft',
                                'scheduled' => 'Scheduled',
                                'queued' => 'Queued',
                                'sending' => 'Sending',
                                'sent' => 'Sent',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
                            ->required(),
                    ]),

                    Grid::make(2)->schema([
                        Select::make('recurrence_type')
                            ->label('Repeat')
                            ->options([
                                'none' => 'None (One-time)',
                                'daily' => 'Daily',
                                'weekly' => 'Weekly',
                                'monthly' => 'Monthly',
                            ])
                            ->default('none')
                            ->live(),

                        Toggle::make('send_now')
                            ->label('Send Now (on save)')
                            ->helperText('Queues and dispatches immediately.')
                            ->dehydrated(false)
                            ->default(false),
                    ]),

                    Grid::make(4)->schema([
                        TextInput::make('recurrence_hour')
                            ->label('Hour (0-23)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(23)
                            ->visible(fn ($get) => in_array($get('recurrence_type'), ['daily', 'weekly', 'monthly'], true)),

                        TextInput::make('recurrence_minute')
                            ->label('Minute (0-59)')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(59)
                            ->visible(fn ($get) => in_array($get('recurrence_type'), ['daily', 'weekly', 'monthly'], true)),

                        TextInput::make('recurrence_month_day')
                            ->label('Month Day (1-31)')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(31)
                            ->visible(fn ($get) => $get('recurrence_type') === 'monthly'),

                        DateTimePicker::make('ends_at')
                            ->label('Ends At (Optional)')
                            ->visible(fn ($get) => in_array($get('recurrence_type'), ['daily', 'weekly', 'monthly'], true)),
                    ]),

                    CheckboxList::make('recurrence_weekdays')
                        ->label('Weekdays (Weekly)')
                        ->options([
                            1 => 'Mon',
                            2 => 'Tue',
                            3 => 'Wed',
                            4 => 'Thu',
                            5 => 'Fri',
                            6 => 'Sat',
                            7 => 'Sun',
                        ])
                        ->columns(7)
                        ->visible(fn ($get) => $get('recurrence_type') === 'weekly'),

                    Grid::make(2)->schema([
                        TextInput::make('max_runs')
                            ->label('Max Runs (Optional)')
                            ->numeric()
                            ->minValue(1)
                            ->helperText('If set, recurrence stops after this many sends.')
                            ->visible(fn ($get) => in_array($get('recurrence_type'), ['daily', 'weekly', 'monthly'], true)),

                        Placeholder::make('runs_count_preview')
                            ->label('Runs Count')
                            ->content(fn ($record) => $record?->runs_count ?? 0),
                    ]),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->wrap(),
                TextColumn::make('target_type')->label('Target')->sortable(),
                TextColumn::make('scheduled_for')->dateTime()->sortable(),
                TextColumn::make('recurrence_type')->label('Repeat')->sortable(),
                BadgeColumn::make('status')
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'scheduled',
                        'primary' => 'queued',
                        'info' => 'sending',
                        'success' => 'sent',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ])
                    ->sortable(),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('sendNow')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'scheduled', 'failed'], true))
                    ->action(function ($record) {
                        $record->status = 'queued';
                        $record->scheduled_for = now();
                        $record->save();

                        SendPushNotificationJob::dispatch((int) $record->id);

                        Notification::make()
                            ->title('Queued')
                            ->body('Notification queued for sending.')
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['draft', 'scheduled', 'queued'], true))
                    ->action(function ($record) {
                        $record->status = 'cancelled';
                        $record->save();

                        Notification::make()
                            ->title('Cancelled')
                            ->body('Notification cancelled.')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    /**
     * Public helper used by Create/Edit pages.
     */
    public static function normalizeAndPersist(array $data): array
    {
        if (empty($data['timezone'])) {
            $data['timezone'] = config('push.default_timezone') ?: config('app.timezone') ?: 'Africa/Lagos';
        }
        if (empty($data['click_action'])) {
            $data['click_action'] = config('push.default_click_action', 'FLUTTER_NOTIFICATION_CLICK');
        }

        if (!empty($data['deep_link_url']) && is_string($data['deep_link_url'])) {
            $deepLink = trim($data['deep_link_url']);
            if (!Str::startsWith($deepLink, ['/', 'http://', 'https://'])) {
                $deepLink = '/' . $deepLink;
            }
            $data['deep_link_url'] = $deepLink;
        }

        $data = self::persistUploadToMediaAsset($data);

        if (($data['recurrence_type'] ?? 'none') !== 'none' && empty($data['scheduled_for'])) {
            $data['status'] = ($data['status'] ?? 'draft') === 'draft' ? 'scheduled' : ($data['status'] ?? 'scheduled');
            $data['scheduled_for'] = now()->addMinute();
        }

        if (!empty($data['scheduled_for']) && ($data['status'] ?? 'draft') === 'draft') {
            $data['status'] = 'scheduled';
        }

        return $data;
    }

    private static function pushDirectory(): string
    {
        $appId = ActiveApp::get();
        $slug = 'app-' . (string) $appId;

        if ($appId) {
            $app = App::query()->select('slug')->find($appId);
            if ($app && !empty($app->slug)) {
                $slug = $app->slug;
            }
        }

        return "media/{$slug}/push";
    }

    private static function persistUploadToMediaAsset(array $data): array
    {
        if (empty($data['app_id'])) {
            unset($data['image_upload']);
            return $data;
        }

        if (empty($data['image_upload'])) {
            return $data;
        }

        $path = self::normalizeUploadStateForDb($data['image_upload']);

        unset($data['image_upload']);

        if (! $path) {
            return $data;
        }

        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return $data;
        }

        $url = $disk->url($path);

        $absolutePath = $disk->path($path);
        $size = @filesize($absolutePath) ?: null;
        $mime = @mime_content_type($absolutePath) ?: null;

        $width = null;
        $height = null;
        $imageInfo = @getimagesize($absolutePath);
        if (is_array($imageInfo) && isset($imageInfo[0], $imageInfo[1])) {
            $width = (int) $imageInfo[0];
            $height = (int) $imageInfo[1];
        }

        $label = basename($path);

        $media = MediaAsset::create([
            'app_id' => (int) $data['app_id'],
            'type' => 'image',
            'label' => $label,
            'bucket' => 'push',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $mime,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'tags_json' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        $data['media_asset_id'] = $media->id;
        $data['image_url'] = $url;

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPushNotifications::route('/'),
            'create' => Pages\CreatePushNotification::route('/create'),
            'edit' => Pages\EditPushNotification::route('/{record}/edit'),
        ];
    }
}

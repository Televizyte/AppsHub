<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppResource\Pages;
use App\Models\App;
use App\Models\MediaAsset;
use Filament\Forms\Form;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class AppResource extends Resource
{
    protected static ?string $model = App::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $navigationGroup = 'Apps';
    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'App';
    protected static ?string $pluralModelLabel = 'Apps';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    private static function resolvePublicUrlFromPath(?string $pathOrUrl): ?string
    {
        $value = is_string($pathOrUrl) ? trim($pathOrUrl) : '';

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        try {
            return Storage::disk('public')->exists($value)
                ? Storage::disk('public')->url($value)
                : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private static function mediaOptionsForApp(int $appId, array $buckets = []): array
    {
        $query = MediaAsset::query()
            ->where('is_active', true)
            ->orderByDesc('updated_at');

        if (! empty($buckets)) {
            $query->whereIn('bucket', $buckets);
        }

        if ($appId > 0) {
            $query->where(function ($innerQuery) use ($appId) {
                $innerQuery->whereNull('app_id')
                    ->orWhere('app_id', $appId);
            });
        } else {
            $query->whereNull('app_id');
        }

        $rows = $query->limit(400)->get(['id', 'label', 'bucket', 'path', 'url']);

        $output = [];

        foreach ($rows as $row) {
            $label = trim((string) ($row->label ?? ''));
            $bucket = trim((string) ($row->bucket ?? ''));
            $path = trim((string) ($row->path ?? ''));
            $fallback = $label !== '' ? $label : ($path !== '' ? $path : ('Asset #' . (int) $row->id));

            $output[(int) $row->id] = '[' . ($bucket !== '' ? $bucket : 'misc') . '] ' . $fallback . ' (ID ' . (int) $row->id . ')';
        }

        return $output;
    }

    private static function applyPickedAssetToBranding(
        array $data,
        callable $set,
        string $pathKey,
        string $urlKey,
        string $assetIdKey,
        array $allowedBuckets = []
    ): void {
        $id = (int) ($data['media_asset_id'] ?? 0);

        if ($id <= 0) {
            Notification::make()->title('No asset selected')->danger()->send();
            return;
        }

        $asset = MediaAsset::query()->find($id);

        if (! $asset) {
            Notification::make()->title('Selected asset not found')->danger()->send();
            return;
        }

        $bucket = (string) ($asset->bucket ?? '');

        if (! empty($allowedBuckets) && ! in_array($bucket, $allowedBuckets, true)) {
            Notification::make()
                ->title('Invalid bucket')
                ->body('Selected asset bucket "' . $bucket . '" is not allowed for this field.')
                ->danger()
                ->send();

            return;
        }

        $path = (string) ($asset->path ?? '');
        $url = (string) ($asset->url ?? '');

        if ($path === '' && $url === '') {
            Notification::make()
                ->title('Asset missing path/url')
                ->body('This MediaAsset has no path and no url.')
                ->danger()
                ->send();

            return;
        }

        $set($assetIdKey, $asset->id);
        $set($pathKey, $path !== '' ? $path : $url);
        $set($urlKey, $url !== '' ? $url : self::resolvePublicUrlFromPath($path));

        Notification::make()
            ->title('Asset selected')
            ->body('Branding asset updated from Media Library.')
            ->success()
            ->send();
    }

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

    private static function wrapUrlForUi(?string $url): HtmlString
    {
        $safe = e($url ?: 'Not set.');

        return new HtmlString('<div class="text-xs leading-snug break-all whitespace-normal">' . $safe . '</div>');
    }

    private static function assetPickerAction(
        string $name,
        string $label,
        string $modalHeading,
        string $pathKey,
        string $urlKey,
        string $assetIdKey,
        array $allowedBuckets
    ): FormAction {
        return FormAction::make($name)
            ->label($label)
            ->icon('heroicon-o-photo')
            ->color('info')
            ->modalHeading($modalHeading)
            ->modalDescription('Select from Media Library. The path, URL, and asset ID will be stored in branding_json.')
            ->form([
                Select::make('media_asset_id')
                    ->label('Media Asset')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->options(function ($record) use ($allowedBuckets) {
                        $appId = $record?->id ? (int) $record->id : 0;

                        return self::mediaOptionsForApp($appId, $allowedBuckets);
                    }),
            ])
            ->action(function (array $data, callable $set) use ($pathKey, $urlKey, $assetIdKey, $allowedBuckets) {
                self::applyPickedAssetToBranding(
                    $data,
                    $set,
                    $pathKey,
                    $urlKey,
                    $assetIdKey,
                    $allowedBuckets
                );
            });
    }

    private static function clearAssetAction(string $name, string $label, string $pathKey, string $urlKey, string $assetIdKey): FormAction
    {
        return FormAction::make($name)
            ->label($label)
            ->icon('heroicon-o-x-circle')
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (callable $set) use ($pathKey, $urlKey, $assetIdKey, $label) {
                $set($assetIdKey, null);
                $set($pathKey, null);
                $set($urlKey, null);

                Notification::make()->title($label . ' completed')->success()->send();
            });
    }

    private static function brandingPreview(string $label, string $pathKey, string $urlKey): Placeholder
    {
        return Placeholder::make(str_replace('.', '_', $pathKey) . '_preview')
            ->label($label)
            ->content(function ($record) use ($pathKey, $urlKey) {
                $branding = $record?->branding_json ?? [];
                $raw = data_get($branding, $urlKey) ?: data_get($branding, $pathKey);

                return self::wrapUrlForUi(self::resolvePublicUrlFromPath($raw) ?: null);
            });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('App Settings')
                ->persistTabInQueryString()
                ->columnSpanFull()
                ->tabs([
                    Tab::make('Identity')
                        ->icon('heroicon-o-identification')
                        ->schema([
                            Section::make('App Identity')
                                ->description('Basic identity used by AppsHub, the mobile app, and the frontend bootstrap API.')
                                ->columns(12)
                                ->schema([
                                    TextInput::make('name')
                                        ->label('App Name')
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state)))
                                        ->columnSpan(6),

                                    TextInput::make('slug')
                                        ->label('App Slug')
                                        ->required()
                                        ->disabled()
                                        ->dehydrated()
                                        ->maxLength(255)
                                        ->helperText('Used in API URLs and frontend app config.')
                                        ->columnSpan(6),

                                    TextInput::make('branding_json.display_name')
                                        ->label('Display Name')
                                        ->maxLength(255)
                                        ->placeholder('Defaults to app name if empty')
                                        ->columnSpan(6),

                                    TextInput::make('branding_json.tagline')
                                        ->label('Tagline')
                                        ->maxLength(160)
                                        ->placeholder('E.g. Inspire. Watch. Grow.')
                                        ->columnSpan(6),

                                    Select::make('branding_json.app_type')
                                        ->label('App Type')
                                        ->options([
                                            'church_tv' => 'Church / Ministry TV App',
                                            'general_tv' => 'General TV App',
                                            'content_app' => 'Content / Reading App',
                                            'radio_app' => 'Radio App',
                                            'education_app' => 'Education App',
                                            'custom' => 'Custom App',
                                        ])
                                        ->default('church_tv')
                                        ->native(false)
                                        ->columnSpan(6),

                                    Toggle::make('is_active')
                                        ->label('App Active')
                                        ->default(true)
                                        ->helperText('Inactive apps should not be used by frontend clients.')
                                        ->columnSpan(6),

                                    Placeholder::make('api_token')
                                        ->label('API Token')
                                        ->content(fn ($record) => $record?->api_token ?? 'Will be generated automatically after creation.')
                                        ->columnSpan(12),
                                ]),

                            Section::make('Create From Existing App')
                                ->description('Use an existing app as a starting structure. This copies Tabs, Routes, Sections, and Items during app creation only.')
                                ->visible(fn ($livewire) => $livewire instanceof \App\Filament\Resources\AppResource\Pages\CreateApp)
                                ->schema([
                                    Select::make('clone_from_app_id')
                                        ->label('Clone From App')
                                        ->options(fn () => App::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                        ->searchable()
                                        ->preload()
                                        ->helperText('Recommended: copy structure only, then add unique content for the new app.')
                                        ->dehydrated(true),
                                ])
                                ->collapsible(),
                        ]),

                    Tab::make('Branding Assets')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            Section::make('Logo')
                                ->description('Use this for in-app headers, about page, and dynamic branding.')
                                ->columns(12)
                                ->schema([
                                    FileUpload::make('branding_json.logo_path')
                                        ->label('Logo Upload')
                                        ->disk('public')
                                        ->directory('apps/logos')
                                        ->visibility('public')
                                        ->image()
                                        ->imageEditor()
                                        ->formatStateUsing(fn ($state) => self::normalizeUploadStateForUi($state))
                                        ->dehydrateStateUsing(fn ($state) => self::normalizeUploadStateForDb($state))
                                        ->helperText('Uploads to public storage and saves the PATH into branding_json.logo_path.')
                                        ->columnSpan(6),

                                    Group::make()
                                        ->schema([
                                            Actions::make([
                                                self::assetPickerAction(
                                                    'pickLogoFromLibrary',
                                                    'Choose Logo from Media Library',
                                                    'Select a Logo from Media Library',
                                                    'branding_json.logo_path',
                                                    'branding_json.logo_url',
                                                    'branding_json.logo_asset_id',
                                                    ['logos', 'branding', 'misc']
                                                ),
                                                self::clearAssetAction(
                                                    'clearLogoSelection',
                                                    'Clear Logo',
                                                    'branding_json.logo_path',
                                                    'branding_json.logo_url',
                                                    'branding_json.logo_asset_id'
                                                ),
                                            ]),

                                            Placeholder::make('logo_preview')
                                                ->label('Logo URL')
                                                ->content(fn ($record) => self::wrapUrlForUi($record?->logo_url)),
                                        ])
                                        ->columnSpan(6),
                                ]),

                            Section::make('Banner / Splash / App Icon')
                                ->description('Store separate assets so frontend, splash, launcher icon, and store assets do not get mixed up.')
                                ->columns(12)
                                ->schema([
                                    Group::make()
                                        ->schema([
                                            TextInput::make('branding_json.banner_path')->label('Banner Path or URL')->maxLength(255),
                                            Actions::make([
                                                self::assetPickerAction('pickBannerFromLibrary', 'Pick Banner', 'Select a Banner from Media Library', 'branding_json.banner_path', 'branding_json.banner_url', 'branding_json.banner_asset_id', ['banners', 'branding', 'misc']),
                                                self::clearAssetAction('clearBanner', 'Clear Banner', 'branding_json.banner_path', 'branding_json.banner_url', 'branding_json.banner_asset_id'),
                                            ]),
                                            self::brandingPreview('Banner URL', 'banner_path', 'banner_url'),
                                        ])
                                        ->columnSpan(4),

                                    Group::make()
                                        ->schema([
                                            TextInput::make('branding_json.splash_path')->label('Splash Path or URL')->maxLength(255),
                                            Actions::make([
                                                self::assetPickerAction('pickSplashFromLibrary', 'Pick Splash', 'Select a Splash from Media Library', 'branding_json.splash_path', 'branding_json.splash_url', 'branding_json.splash_asset_id', ['covers', 'branding', 'misc', 'thumbnails']),
                                                self::clearAssetAction('clearSplash', 'Clear Splash', 'branding_json.splash_path', 'branding_json.splash_url', 'branding_json.splash_asset_id'),
                                            ]),
                                            self::brandingPreview('Splash URL', 'splash_path', 'splash_url'),
                                        ])
                                        ->columnSpan(4),

                                    Group::make()
                                        ->schema([
                                            TextInput::make('branding_json.app_icon_path')->label('App Icon Path or URL')->maxLength(255),
                                            Actions::make([
                                                self::assetPickerAction('pickAppIconFromLibrary', 'Pick App Icon', 'Select an App Icon from Media Library', 'branding_json.app_icon_path', 'branding_json.app_icon_url', 'branding_json.app_icon_asset_id', ['icons', 'logos', 'branding', 'misc']),
                                                self::clearAssetAction('clearAppIcon', 'Clear App Icon', 'branding_json.app_icon_path', 'branding_json.app_icon_url', 'branding_json.app_icon_asset_id'),
                                            ]),
                                            self::brandingPreview('App Icon URL', 'app_icon_path', 'app_icon_url'),
                                        ])
                                        ->columnSpan(4),
                                ]),
                        ]),

                    Tab::make('Theme')
                        ->icon('heroicon-o-swatch')
                        ->schema([
                            Section::make('Theme & Appearance')
                                ->description('Flutter should use these values instead of hardcoded colors.')
                                ->columns(12)
                                ->schema([
                                    ColorPicker::make('branding_json.primary_color')->label('Primary')->columnSpan(3),
                                    ColorPicker::make('branding_json.accent_color')->label('Accent')->columnSpan(3),
                                    ColorPicker::make('branding_json.background_color')->label('Background')->columnSpan(3),
                                    ColorPicker::make('branding_json.text_color')->label('Text')->columnSpan(3),

                                    Select::make('branding_json.theme_mode')
                                        ->label('Theme Mode')
                                        ->options([
                                            'dark' => 'Dark',
                                            'light' => 'Light',
                                            'system' => 'System',
                                        ])
                                        ->default('dark')
                                        ->native(false)
                                        ->columnSpan(4),

                                    TextInput::make('branding_json.font_family')
                                        ->label('Font Family')
                                        ->placeholder('system')
                                        ->maxLength(120)
                                        ->columnSpan(4),

                                    Select::make('branding_json.button_style')
                                        ->label('Button Style')
                                        ->options([
                                            'rounded' => 'Rounded',
                                            'pill' => 'Pill',
                                            'square' => 'Square',
                                            'soft' => 'Soft Card',
                                        ])
                                        ->default('rounded')
                                        ->native(false)
                                        ->columnSpan(4),

                                    Textarea::make('branding_json.gradient_presets')
                                        ->label('Gradient Presets JSON')
                                        ->rows(5)
                                        ->helperText('Optional. Example: [{"from":"#280061","to":"#9e56fc"}]')
                                        ->formatStateUsing(function ($state) {
                                            if (is_array($state)) {
                                                return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                            }

                                            if (is_string($state)) {
                                                $string = trim($state);
                                                return $string === '' || strtolower($string) === 'null' ? '' : $string;
                                            }

                                            return '';
                                        })
                                        ->dehydrateStateUsing(function ($state) {
                                            if (! is_string($state)) {
                                                return [];
                                            }

                                            $string = trim($state);
                                            if ($string === '' || strtolower($string) === 'null') {
                                                return [];
                                            }

                                            $decoded = json_decode($string, true);
                                            return is_array($decoded) ? $decoded : [];
                                        })
                                        ->columnSpan(12),
                                ]),
                        ]),

                    Tab::make('Store / Build')
                        ->icon('heroicon-o-rocket-launch')
                        ->schema([
                            Section::make('Store / Build Metadata')
                                ->description('These values prepare the app for future build/export and Play Store publishing flows.')
                                ->columns(12)
                                ->schema([
                                    TextInput::make('branding_json.store.package_name')
                                        ->label('Package Name')
                                        ->placeholder('com.digitxtra.dunamistv')
                                        ->maxLength(255)
                                        ->columnSpan(6),

                                    TextInput::make('branding_json.store.play_store_name')
                                        ->label('Play Store App Name')
                                        ->maxLength(255)
                                        ->columnSpan(6),

                                    TextInput::make('branding_json.store.version_name')
                                        ->label('Version Name')
                                        ->placeholder('1.0.0')
                                        ->maxLength(50)
                                        ->columnSpan(4),

                                    TextInput::make('branding_json.store.version_code')
                                        ->label('Version Code')
                                        ->numeric()
                                        ->columnSpan(4),

                                    TextInput::make('branding_json.store.privacy_policy_url')
                                        ->label('Privacy Policy URL')
                                        ->url()
                                        ->maxLength(255)
                                        ->columnSpan(4),

                                    Textarea::make('branding_json.store.short_description')
                                        ->label('Short Description')
                                        ->rows(3)
                                        ->maxLength(500)
                                        ->columnSpan(12),

                                    Textarea::make('branding_json.store.full_description')
                                        ->label('Full Description')
                                        ->rows(7)
                                        ->maxLength(5000)
                                        ->columnSpan(12),
                                ]),

                            Placeholder::make('build_note')
                                ->label('Build Note')
                                ->content('Dynamic in-app logo, colors, splash, and settings can come from the API. Launcher icons, package name, and store metadata may still require build-time preparation until the app export pipeline is added.'),
                        ]),

                    Tab::make('Contact / Social')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            Section::make('About / Support')
                                ->description('Displayed in the app More/About/Support areas.')
                                ->columns(12)
                                ->schema([
                                    Textarea::make('branding_json.about')
                                        ->label('About')
                                        ->rows(5)
                                        ->maxLength(2000)
                                        ->placeholder('Short description/about text.')
                                        ->columnSpan(12),

                                    TextInput::make('branding_json.support_email')
                                        ->label('Support Email')
                                        ->email()
                                        ->maxLength(255)
                                        ->columnSpan(6),

                                    TextInput::make('branding_json.support_phone')
                                        ->label('Support Phone')
                                        ->maxLength(50)
                                        ->columnSpan(6),

                                    TextInput::make('branding_json.website_url')
                                        ->label('Website URL')
                                        ->url()
                                        ->maxLength(255)
                                        ->columnSpan(6),

                                    TextInput::make('branding_json.contact_address')
                                        ->label('Address')
                                        ->maxLength(255)
                                        ->columnSpan(6),
                                ]),

                            Section::make('Social Links')
                                ->columns(12)
                                ->schema([
                                    TextInput::make('branding_json.social.instagram')->label('Instagram URL')->url()->maxLength(255)->columnSpan(6),
                                    TextInput::make('branding_json.social.youtube')->label('YouTube URL')->url()->maxLength(255)->columnSpan(6),
                                    TextInput::make('branding_json.social.facebook')->label('Facebook URL')->url()->maxLength(255)->columnSpan(6),
                                    TextInput::make('branding_json.social.x')->label('X / Twitter URL')->url()->maxLength(255)->columnSpan(6),
                                ]),
                        ]),

                    Tab::make('Capabilities / API')
                        ->icon('heroicon-o-adjustments-horizontal')
                        ->schema([
                            Section::make('Enabled Features / Engines')
                                ->description('Use the dedicated capability page for the new app-builder capability system. These legacy flags are kept for API backward compatibility.')
                                ->columns(12)
                                ->schema([
                                    Placeholder::make('capabilities_link')
                                        ->label('App Capabilities')
                                        ->content(fn () => new HtmlString('<a href="/admin/app-capabilities" class="fi-btn fi-btn-size-md fi-color-custom fi-btn-color-primary">Open App Capabilities</a>'))
                                        ->columnSpan(12),

                                    Toggle::make('branding_json.flags.enable_auth')->label('Enable Auth')->default(true)->columnSpan(4),
                                    Toggle::make('branding_json.flags.enable_watch')->label('Enable Watch')->default(true)->columnSpan(4),
                                    Toggle::make('branding_json.flags.enable_content_studio')->label('Enable Content Studio')->default(true)->columnSpan(4),
                                    Toggle::make('branding_json.flags.enable_ads')->label('Enable Ads')->default(false)->columnSpan(4),
                                    Toggle::make('branding_json.flags.ads_home_only')->label('Ads Home Only')->default(true)->helperText('When enabled, show ads only in Home, not Watch.')->columnSpan(4),
                                    Toggle::make('branding_json.flags.enable_push')->label('Enable Push Notifications')->default(false)->columnSpan(4),
                                ]),

                            Section::make('Frontend API Config')
                                ->columns(12)
                                ->schema([
                                    Placeholder::make('frontend_urls')
                                        ->label('Frontend Endpoints')
                                        ->content(function ($record) {
                                            if (! $record?->slug) {
                                                return 'Save this app first to generate API URLs.';
                                            }

                                            $slug = e((string) $record->slug);

                                            return new HtmlString(
                                                '<div class="text-xs leading-6 break-all">'
                                                . '<strong>Bootstrap:</strong> /api/v1/apps/' . $slug . '/bootstrap<br>'
                                                . '<strong>Hub:</strong> /api/v1/apps/' . $slug . '/hub<br>'
                                                . '<strong>Routes:</strong> /api/v1/apps/' . $slug . '/routes<br>'
                                                . '<strong>Watch:</strong> /api/v1/apps/' . $slug . '/watch<br>'
                                                . '<strong>Content:</strong> /api/v1/apps/' . $slug . '/content'
                                                . '</div>'
                                            );
                                        })
                                        ->columnSpan(12),
                                ]),
                        ]),

                    Tab::make('Advanced')
                        ->icon('heroicon-o-code-bracket-square')
                        ->schema([
                            Section::make('Read Only JSON Preview')
                                ->description('Raw editing is disabled to avoid breaking the app payload.')
                                ->schema([
                                    Placeholder::make('branding_json_preview')
                                        ->label('branding_json')
                                        ->content(function ($record) {
                                            $array = $record?->branding_json ?? [];

                                            if (! is_array($array)) {
                                                return (string) $array;
                                            }

                                            return json_encode($array, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                        }),
                                ])
                                ->collapsible(),
                        ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo_url')->label('Logo')->circular()->toggleable(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable()->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApps::route('/'),
            'create' => Pages\CreateApp::route('/create'),
            'edit' => Pages\EditApp::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Filament\Resources\ContentPostResource\Pages;
use App\Models\ContentPost;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\ActiveApp;
use App\Support\Icons\SvgIconRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ContentPostResource extends ActiveAppScopedResource
{
    protected static ?string $model = ContentPost::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content Studio';
    protected static ?string $navigationLabel = 'Advanced Content Table';
    protected static ?string $modelLabel = 'Advanced Content';
    protected static ?string $pluralModelLabel = 'Advanced Content Table';
    protected static ?int $navigationSort = 10;

    
    public static function shouldRegisterNavigation(): bool
    {
        if (\App\Support\AdminMode::isBeginner()) {
            return false;
        }

        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);
        $activeApp = $activeAppId > 0
            ? \App\Models\App::query()->find($activeAppId)
            : null;

        return \App\Support\AppCapabilities::enabled($activeApp, 'content_channels');
    }
public static function form(Form $form): Form
    {
        $activeAppId = (int) ActiveApp::ensureId();

        return $form
            ->schema([
                Forms\Components\Section::make('Content Basics')
                    ->description('Create or edit a content entry for the currently selected app.')
                    ->columns(12)
                    ->schema([
                        Forms\Components\TextInput::make('app_id')
                            ->label('Active App ID')
                            ->default(fn () => (int) ActiveApp::ensureId())
                            ->disabled()
                            ->dehydrated()
                            ->required()
                            ->numeric()
                            ->columnSpan(4),

                        Forms\Components\Select::make('bucket')
                            ->label('Content Channel')
                            ->options([
                                'motivation' => 'Motivation',
                                'wordification' => 'Wordification',
                                'highlights' => 'Highlights',
                                'inside_dunamis' => 'Inside Dunamis',
                                'sod' => 'Seed of Destiny',
                                'articles' => 'Articles',
                            ])
                            ->required()
                            ->columnSpan(4),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'draft' => 'Draft',
                                'published' => 'Published',
                            ])
                            ->required()
                            ->default('draft')
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(8),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Leave blank to auto-generate on save.')
                            ->maxLength(255)
                            ->columnSpan(4),

                        Forms\Components\TextInput::make('subtitle')
                            ->label('Subtitle')
                            ->maxLength(255)
                            ->columnSpan(12),

                        Forms\Components\Select::make('author_user_id')
                            ->label('Publisher User')
                            ->searchable()
                            ->preload()
                            ->options(function () {
                                return User::query()
                                    ->orderBy('name')
                                    ->orderBy('email')
                                    ->get(['id', 'name', 'email'])
                                    ->mapWithKeys(function (User $user) {
                                        $label = trim($user->name . ' <' . $user->email . '>');
                                        return [$user->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (! $state) {
                                    return;
                                }

                                $user = User::query()->find((int) $state);
                                if ($user) {
                                    $set('author_name', (string) $user->name);
                                }
                            })
                            ->helperText('Links the content to a real publisher user.')
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('author_name')
                            ->label('Author Name (Fallback)')
                            ->maxLength(255)
                            ->helperText('Auto-filled from Publisher User when selected.')
                            ->columnSpan(6),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false)
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->columnSpan(3),

                        Forms\Components\Select::make('icon_key')
                            ->label('Content Icon (Optional)')
                            ->options(SvgIconRegistry::options())
                            ->searchable()
                            ->preload()
                            ->placeholder('Auto (use channel icon)')
                            ->helperText('Stored in meta_json.icon_key. Leave empty to let the app decide.')
                            ->afterStateHydrated(function (Forms\Components\Select $component, $state, $record) {
                                if (! $record) {
                                    return;
                                }

                                $meta = $record->meta_json ?? null;

                                if (is_string($meta) && trim($meta) !== '') {
                                    $decoded = json_decode($meta, true);
                                    $meta = is_array($decoded) ? $decoded : null;
                                }

                                $iconKey = is_array($meta) ? ($meta['icon_key'] ?? null) : null;
                                $component->state(is_string($iconKey) ? $iconKey : null);
                            })
                            ->dehydrated(false)
                            ->columnSpan(6),
                    ]),

                Forms\Components\Section::make('Cover Image')
                    ->description('Upload or choose a cover image and confirm it visually before saving.')
                    ->columns(12)
                    ->schema([
                        Forms\Components\FileUpload::make('cover_upload')
                            ->label('Upload Cover')
                            ->image()
                            ->imageEditor()
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory(function () use ($activeAppId) {
                                $slug = DB::table('apps')->where('id', $activeAppId)->value('slug') ?: 'unknown-app';
                                return "content-covers/{$slug}";
                            })
                            ->dehydrateStateUsing(fn () => null)
                            ->afterStateUpdated(function ($state, callable $set) use ($activeAppId) {
                                if (! $state) {
                                    return;
                                }

                                if ($state instanceof TemporaryUploadedFile) {
                                    $ext = strtolower($state->getClientOriginalExtension() ?: 'jpg');
                                    $safe = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true) ? $ext : 'jpg';

                                    $slug = DB::table('apps')->where('id', $activeAppId)->value('slug') ?: 'unknown-app';
                                    $dir = "content-covers/{$slug}";
                                    $filename = 'cover_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(10)) . '.' . $safe;

                                    $path = $state->storeAs($dir, $filename, 'public');
                                    $set('cover_image_url', (string) $path);
                                    return;
                                }

                                if (is_string($state) && trim($state) !== '') {
                                    $set('cover_image_url', trim($state));
                                }
                            })
                            ->columnSpan(6),

                        Forms\Components\TextInput::make('cover_image_url')
                            ->label('Cover URL or Storage Path')
                            ->placeholder('https://.../cover.jpg OR content-covers/app/cover.jpg')
                            ->maxLength(255)
                            ->columnSpan(6),

                        Forms\Components\Placeholder::make('cover_preview')
                            ->label('Cover Preview')
                            ->content(function (callable $get) {
                                $value = trim((string) ($get('cover_image_url') ?? ''));
                                if ($value === '') {
                                    return 'No cover selected.';
                                }

                                $src = Str::startsWith($value, ['http://', 'https://'])
                                    ? $value
                                    : Storage::disk('public')->url($value);

                                return new \Illuminate\Support\HtmlString(
                                    '<div style="display:flex;gap:12px;align-items:flex-start;">
                                        <img src="' . e($src) . '" style="width:240px;height:auto;border-radius:10px;border:1px solid rgba(255,255,255,0.08);" />
                                        <div style="opacity:0.8;font-size:12px;line-height:1.4;word-break:break-all;">' . e($src) . '</div>
                                    </div>'
                                );
                            })
                            ->columnSpan(12),
                    ]),

                Forms\Components\Section::make('Body')
                    ->description('Write the article body. Quick insert tools are safe helpers; they do not change your backend structure.')
                    ->columns(12)
                    ->schema([
                        Forms\Components\Select::make('_quick_insert')
                            ->label('Quick Insert')
                            ->options([
                                'divider' => 'Divider (HR)',
                                'callout_info' => 'Callout: Info',
                                'callout_success' => 'Callout: Success',
                                'callout_warning' => 'Callout: Warning',
                                'callout_note' => 'Callout: Note',
                                'scripture' => 'Scripture Highlight Block',
                                'quote' => 'Quote Block',
                            ])
                            ->searchable()
                            ->placeholder('Choose a block to insert...')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                if (! $state) {
                                    return;
                                }

                                $body = static::normalizeEditorBody($get('body_html'));

                                $snippets = [
                                    'divider' => '<hr />',
                                    'callout_info' => '<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(0,240,255,.25);background:rgba(0,240,255,.06);"><strong>Info:</strong> Replace this text…</div>',
                                    'callout_success' => '<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(34,197,94,.25);background:rgba(34,197,94,.06);"><strong>Success:</strong> Replace this text…</div>',
                                    'callout_warning' => '<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(245,158,11,.25);background:rgba(245,158,11,.06);"><strong>Warning:</strong> Replace this text…</div>',
                                    'callout_note' => '<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(255,43,214,.20);background:rgba(255,43,214,.06);"><strong>Note:</strong> Replace this text…</div>',
                                    'scripture' => '<div style="padding:14px 16px;border-radius:14px;border:1px solid rgba(124,58,237,.25);background:rgba(124,58,237,.08);">
                                                        <div style="font-weight:800;margin-bottom:6px;">Scripture</div>
                                                        <div style="opacity:.92;">“Paste scripture here…”</div>
                                                        <div style="opacity:.7;margin-top:6px;font-size:12px;">— Reference (e.g. John 3:16)</div>
                                                    </div>',
                                    'quote' => '<blockquote style="border-left:4px solid rgba(0,240,255,.5);padding-left:12px;margin:10px 0;opacity:.92;">
                                                    <p><strong>Quote:</strong> Replace this text…</p>
                                                </blockquote>',
                                ];

                                $insert = $snippets[$state] ?? null;
                                if (! $insert) {
                                    $set('_quick_insert', null);
                                    return;
                                }

                                $body = trim($body);
                                $body = $body === '' ? $insert : ($body . "\n\n" . $insert);

                                $set('body_html', $body);
                                $set('_quick_insert', null);
                            })
                            ->columnSpan(6),

                        Forms\Components\Select::make('_insert_media_asset_id')
                            ->label('Insert Image from Media Library')
                            ->placeholder('Select an image asset...')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->options(function () use ($activeAppId) {
                                return MediaAsset::query()
                                    ->where('is_active', true)
                                    ->where('type', 'image')
                                    ->where(function ($query) use ($activeAppId) {
                                        $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
                                    })
                                    ->orderByRaw("CASE WHEN app_id IS NULL THEN 0 ELSE 1 END ASC")
                                    ->orderBy('bucket')
                                    ->orderBy('sort_order')
                                    ->orderByDesc('id')
                                    ->limit(400)
                                    ->get()
                                    ->mapWithKeys(function ($asset) {
                                        $label = trim((string) ($asset->label ?? ''));
                                        $bucket = trim((string) ($asset->bucket ?? ''));
                                        $fallbackName = is_string($asset->path ?? null) ? basename((string) $asset->path) : ('Asset #' . $asset->id);
                                        $nice = ($bucket ? "{$bucket} • " : '') . ($label !== '' ? $label : $fallbackName);
                                        return [$asset->id => $nice];
                                    })
                                    ->toArray();
                            })
                            ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                if ($state === null || $state === '') {
                                    return;
                                }

                                if (is_array($state)) {
                                    $state = $state['id']
                                        ?? $state['value']
                                        ?? $state['key']
                                        ?? (count($state) === 1 ? reset($state) : null);
                                }

                                if (is_object($state)) {
                                    if (method_exists($state, '__toString')) {
                                        $state = (string) $state;
                                    } else {
                                        $state = null;
                                    }
                                }

                                if ($state === null || $state === '') {
                                    $set('_insert_media_asset_id', null);
                                    return;
                                }

                                $assetId = is_numeric($state) ? (int) $state : 0;
                                if ($assetId <= 0) {
                                    $set('_insert_media_asset_id', null);
                                    return;
                                }

                                $asset = MediaAsset::query()->find($assetId);

                                if (! $asset) {
                                    $set('_insert_media_asset_id', null);
                                    return;
                                }

                                $url = '';

                                if (is_string($asset->url) && trim($asset->url) !== '') {
                                    $url = trim($asset->url);
                                } elseif (is_string($asset->path) && trim($asset->path) !== '') {
                                    try {
                                        $url = Storage::disk($asset->disk ?? 'public')->url($asset->path);
                                    } catch (\Throwable $e) {
                                        $url = '';
                                    }
                                }

                                if ($url === '') {
                                    $set('_insert_media_asset_id', null);
                                    return;
                                }

                                $alt = is_string($asset->label ?? null)
                                    ? trim((string) $asset->label)
                                    : '';

                                $body = static::normalizeEditorBody($get('body_html'));
                                $image = '<p><img src="' . e($url) . '" alt="' . e($alt) . '" style="max-width:100%;height:auto;border-radius:12px;" /></p>';

                                $body = trim($body);
                                $body = $body === '' ? $image : ($body . "\n\n" . $image);

                                $set('body_html', $body);
                                $set('_insert_media_asset_id', null);
                            })
                            ->dehydrated(false)
                            ->helperText('Images come from Media Assets so the content stays reusable and organized.')
                            ->columnSpan(6),

                        TiptapEditor::make('body_html')
                            ->label('Content Body')
                            ->profile('default')
                            ->dehydrateStateUsing(fn ($state) => static::normalizeEditorBody($state))
                            ->formatStateUsing(fn ($state) => static::normalizeEditorBody($state))
                            ->columnSpan(12),
                    ]),

                Forms\Components\Section::make('Publish Settings')
                    ->columns(12)
                    ->schema([
                        Forms\Components\DateTimePicker::make('publish_at')->label('Publish At (Lagos time)')->timezone('Africa/Lagos')->columnSpan(6),
                        Forms\Components\DateTimePicker::make('published_at')->label('Published At (Lagos time)')->timezone('Africa/Lagos')->columnSpan(6),
                    ]),
            ])
            ->columns(1);
    }

    protected static function normalizeEditorBody($value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_string($value)) {
            return trim($value);
        }

        if (is_object($value)) {
            if ($value instanceof \JsonSerializable) {
                $value = $value->jsonSerialize();
            } elseif (method_exists($value, 'toArray')) {
                $value = $value->toArray();
            } elseif (method_exists($value, '__toString')) {
                return trim((string) $value);
            } else {
                return '';
            }
        }

        if (! is_array($value)) {
            return '';
        }

        if (isset($value['html']) && is_string($value['html'])) {
            return trim($value['html']);
        }

        if (isset($value['content']) && is_string($value['content'])) {
            return trim($value['content']);
        }

        if (isset($value['body_html']) && is_string($value['body_html'])) {
            return trim($value['body_html']);
        }

        if (($value['type'] ?? null) === 'doc' || isset($value['content'])) {
            return trim(static::renderTiptapNodes($value['content'] ?? []));
        }

        return '';
    }

    protected static function renderTiptapNodes($nodes): string
    {
        if (! is_array($nodes)) {
            return '';
        }

        $html = '';

        foreach ($nodes as $node) {
            if (! is_array($node)) {
                continue;
            }

            $type = (string) ($node['type'] ?? '');
            $content = $node['content'] ?? [];
            $attrs = is_array($node['attrs'] ?? null) ? $node['attrs'] : [];

            switch ($type) {
                case 'text':
                    $text = e((string) ($node['text'] ?? ''));
                    $marks = is_array($node['marks'] ?? null) ? $node['marks'] : [];

                    foreach ($marks as $mark) {
                        $markType = (string) ($mark['type'] ?? '');
                        $markAttrs = is_array($mark['attrs'] ?? null) ? $mark['attrs'] : [];

                        if ($markType === 'bold') {
                            $text = '<strong>' . $text . '</strong>';
                        } elseif ($markType === 'italic') {
                            $text = '<em>' . $text . '</em>';
                        } elseif ($markType === 'strike') {
                            $text = '<s>' . $text . '</s>';
                        } elseif ($markType === 'underline') {
                            $text = '<u>' . $text . '</u>';
                        } elseif ($markType === 'link') {
                            $href = e((string) ($markAttrs['href'] ?? ''));
                            if ($href !== '') {
                                $text = '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $text . '</a>';
                            }
                        }
                    }

                    $html .= $text;
                    break;

                case 'paragraph':
                    $html .= '<p>' . static::renderTiptapNodes($content) . '</p>';
                    break;

                case 'heading':
                    $level = (int) ($attrs['level'] ?? 2);
                    if ($level < 1 || $level > 6) {
                        $level = 2;
                    }
                    $html .= '<h' . $level . '>' . static::renderTiptapNodes($content) . '</h' . $level . '>';
                    break;

                case 'blockquote':
                    $html .= '<blockquote>' . static::renderTiptapNodes($content) . '</blockquote>';
                    break;

                case 'bulletList':
                    $html .= '<ul>' . static::renderTiptapNodes($content) . '</ul>';
                    break;

                case 'orderedList':
                    $html .= '<ol>' . static::renderTiptapNodes($content) . '</ol>';
                    break;

                case 'listItem':
                    $html .= '<li>' . static::renderTiptapNodes($content) . '</li>';
                    break;

                case 'hardBreak':
                    $html .= '<br />';
                    break;

                case 'horizontalRule':
                    $html .= '<hr />';
                    break;

                case 'image':
                    $src = e((string) ($attrs['src'] ?? ''));
                    $alt = e((string) ($attrs['alt'] ?? ''));
                    if ($src !== '') {
                        $html .= '<p><img src="' . $src . '" alt="' . $alt . '" style="max-width:100%;height:auto;border-radius:12px;" /></p>';
                    }
                    break;

                default:
                    $html .= static::renderTiptapNodes($content);
                    break;
            }
        }

        return $html;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('cover_image_src')->label('Cover')->square()->toggleable(),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable()->limit(50),
                Tables\Columns\TextColumn::make('bucket')->label('Channel')->badge()->sortable(),
                Tables\Columns\TextColumn::make('author_name')->label('Publisher')->limit(30)->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\IconColumn::make('is_featured')->boolean()->label('Featured')->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('bucket')->label('Channel')->options([
                    'motivation' => 'Motivation',
                    'wordification' => 'Wordification',
                    'highlights' => 'Highlights',
                    'inside_dunamis' => 'Inside Dunamis',
                    'sod' => 'Seed of Destiny',
                    'articles' => 'Articles',
                ]),
                Tables\Filters\SelectFilter::make('status')->label('Status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                ]),
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
            'index' => Pages\ListContentPosts::route('/'),
            'create' => Pages\CreateContentPost::route('/create'),
            'edit' => Pages\EditContentPost::route('/{record}/edit'),
        ];
    }
}

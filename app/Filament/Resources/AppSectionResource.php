<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppSectionResource\Pages;
use App\Filament\Resources\Base\ActiveAppScopedResource;
use App\Models\AppSection;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class AppSectionResource extends ActiveAppScopedResource
{
    protected static ?string $model = AppSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationGroup = 'App Structure';
    protected static ?string $navigationLabel = 'Layout Sections';
    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'Section';
    protected static ?string $pluralModelLabel = 'Sections';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminMode::isAdvanced();
    }

    private static function templateOptions(): array
    {
        return [
            'banners' => 'banners',
            'daily' => 'daily',
            'hero' => 'hero',
            'banner_carousel' => 'banner_carousel',
            'featured_grid' => 'featured_grid',
            'horizontal_list' => 'horizontal_list',
            'vertical_list' => 'vertical_list',
            'grid' => 'grid',
            'category_row' => 'category_row',
            'quick_actions' => 'quick_actions',
            'short_video_feed' => 'short_video_feed',
            'quote' => 'quote',
            'scripture' => 'scripture',
            'text_block' => 'text_block',
            'ad_slot' => 'ad_slot',
        ];
    }

    public static function form(Form $form): Form
    {
        $activeAppId = (int) (ActiveApp::get() ?? 0);

        return $form->schema([
            Section::make('Section Basics')
                ->description('Sections are layout rows or blocks inside a tab or page.')
                ->schema([
                    TextInput::make('app_id')
                        ->default($activeAppId)
                        ->disabled()
                        ->dehydrated()
                        ->numeric()
                        ->required()
                        ->label('Active App ID'),

                    Grid::make(2)->schema([
                        Select::make('tab_key')
                            ->label('Main Tab')
                            ->options([
                                'home' => 'Home',
                                'watch' => 'Watch',
                                'inspire' => 'Inspire',
                                'explore' => 'Explore',
                                'more' => 'More',
                            ])
                            ->required()
                            ->helperText('Choose the main tab where this section belongs.'),

                        TextInput::make('route_key')
                            ->label('Page Key (Optional)')
                            ->maxLength(120)
                            ->helperText('Set this only when the section belongs to a specific inner page, not the main tab screen.'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('title')
                            ->label('Section Title')
                            ->required()
                            ->maxLength(140),

                        TextInput::make('subtitle')
                            ->label('Section Subtitle (Optional)')
                            ->maxLength(180),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('key')
                            ->label('Section Key')
                            ->required()
                            ->maxLength(120)
                            ->helperText('Stable internal key for this section.'),

                        Select::make('template')
                            ->label('Layout Type')
                            ->options(static::templateOptions())
                            ->required()
                            ->helperText('This controls how the mobile app displays the section.'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('sort_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->required(),

                        Toggle::make('is_enabled')
                            ->label('Section Enabled')
                            ->default(true)
                            ->required(),
                    ]),
                ])
                ->collapsible(),

            Section::make('Advanced Section Settings')
                ->description('Optional layout and visibility settings. Leave these alone unless you know the section needs custom behavior.')
                ->schema([
                    Grid::make(2)->schema([
                        Textarea::make('meta_json')
                            ->label('Layout Meta JSON')
                            ->rows(6)
                            ->helperText('Optional design and behavior config for this section.'),

                        Textarea::make('visibility_json')
                            ->label('Visibility JSON')
                            ->rows(6)
                            ->helperText('Optional rules controlling when this section shows.'),
                    ]),

                    Textarea::make('empty_state_json')
                        ->label('Empty State JSON')
                        ->rows(6)
                        ->helperText('Optional empty state config when the section has no content.'),
                ])
                ->collapsed()
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('tab_key')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('tab_key')->label('Tab')->sortable()->searchable(),
                TextColumn::make('route_key')->label('Page Key')->sortable()->toggleable()->searchable(),
                TextColumn::make('key')->label('Key')->sortable()->searchable(),
                TextColumn::make('template')->label('Layout')->sortable()->searchable(),
                TextColumn::make('title')->label('Title')->sortable()->searchable(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                ToggleColumn::make('is_enabled')->label('Enabled'),
                TextColumn::make('updated_at')->since()->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tab_key')
                    ->label('Tab')
                    ->options([
                        'home' => 'home',
                        'watch' => 'watch',
                        'inspire' => 'inspire',
                        'explore' => 'explore',
                        'more' => 'more',
                    ]),
                Tables\Filters\SelectFilter::make('template')
                    ->label('Template')
                    ->options(static::templateOptions()),
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
            'index' => Pages\ListAppSections::route('/'),
            'create' => Pages\CreateAppSection::route('/create'),
            'edit' => Pages\EditAppSection::route('/{record}/edit'),
        ];
    }
}

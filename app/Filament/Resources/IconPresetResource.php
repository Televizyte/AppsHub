<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IconPresetResource\Pages;
use App\Models\IconPreset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class IconPresetResource extends Resource
{
    protected static ?string $model = IconPreset::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationGroup = 'Media & Assets';
    protected static ?int $navigationSort = 10;

    
    public static function shouldRegisterNavigation(): bool
    {
        // Beginner mode uses the visual /admin/icon-library page.
        // The technical resource remains available in Advanced mode and by direct URL.
        return ! \App\Support\AdminMode::isBeginner();
    }
public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Icon')
                ->columns(12)
                ->schema([
                    Forms\Components\TextInput::make('key')
                        ->required()
                        ->maxLength(120)
                        ->helperText('Unique key used across the system. Example: icon_book, icon_play.')
                        ->columnSpan(4),

                    Forms\Components\TextInput::make('label')
                        ->required()
                        ->maxLength(160)
                        ->columnSpan(5),

                    Forms\Components\TextInput::make('group')
                        ->maxLength(120)
                        ->placeholder('Navigation / Tools / Content')
                        ->columnSpan(3),

                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->columnSpan(3),

                    Forms\Components\TextInput::make('sort_order')
                        ->numeric()
                        ->default(0)
                        ->columnSpan(3),

                    Forms\Components\Textarea::make('svg')
                        ->label('SVG Markup')
                        ->rows(14)
                        ->required()
                        ->helperText('Paste the full <svg ...>...</svg> here.')
                        ->columnSpan(12),

                    Forms\Components\Placeholder::make('preview')
                        ->label('Preview')
                        ->content(function ($record, callable $get) {
                            $svg = (string) ($get('svg') ?? ($record?->svg ?? ''));
                            if (trim($svg) === '') return 'No SVG';

                            // Safe-ish: we expect trusted admin input
                            return new HtmlString(
                                '<div style="display:flex; gap:14px; align-items:center;">
                                    <div style="width:44px;height:44px;display:grid;place-items:center;border-radius:12px;border:1px solid rgba(255,255,255,0.10);background:rgba(255,255,255,0.03);">
                                        ' . $svg . '
                                    </div>
                                    <div style="opacity:.8;font-size:12px;line-height:1.4;">Inline SVG preview</div>
                                </div>'
                            );
                        })
                        ->columnSpan(12),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('group')
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('group')
                    ->label('Group')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->sortable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->since()
                    ->label('Updated'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
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
            'index' => Pages\ListIconPresets::route('/'),
            'create' => Pages\CreateIconPreset::route('/create'),
            'edit' => Pages\EditIconPreset::route('/{record}/edit'),
        ];
    }
}

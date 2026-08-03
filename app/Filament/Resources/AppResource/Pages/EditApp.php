<?php

namespace App\Filament\Resources\AppResource\Pages;

use App\Filament\Resources\AppResource;
use App\Models\App;
use App\Support\AppCapabilities;
use App\Support\AppTemplateCloner;
use Filament\Actions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditApp extends EditRecord
{
    protected static string $resource = AppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('openWorkspace')
                ->label('Open Workspace')
                ->icon('heroicon-o-home')
                ->color('success')
                ->action(function () {
                    session(['active_app_id' => (int) $this->record->id]);

                    return redirect('/admin/beginner-dashboard');
                }),

            Actions\Action::make('appCapabilities')
                ->label('Capabilities')
                ->icon('heroicon-o-adjustments-horizontal')
                ->color('gray')
                ->url('/admin/app-capabilities'),

            Actions\Action::make('cloneTemplate')
                ->label('Clone From App')
                ->icon('heroicon-o-squares-2x2')
                ->color('info')
                ->modalHeading('Clone Existing App Structure')
                ->modalDescription('Copies Tabs, Routes, Sections, and Items from another app into this app. Content/media/users are not copied.')
                ->form([
                    Select::make('source_app_id')
                        ->label('Source App')
                        ->options(fn () => App::query()
                            ->where('id', '!=', $this->record->id)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live(),

                    Toggle::make('copy_capabilities')
                        ->label('Copy source app capabilities')
                        ->default(true)
                        ->helperText('Recommended when cloning similar apps, e.g. Dunamis TV to Celebration TV.'),

                    Toggle::make('wipe_target_first')
                        ->label('Wipe target structure first')
                        ->default(false)
                        ->helperText('Only enable if you want to replace this app’s current Tabs, Routes, Sections, and Items.'),

                    Placeholder::make('source_counts')
                        ->label('Source App Structure')
                        ->content(function (callable $get) {
                            $source = (int) ($get('source_app_id') ?? 0);

                            if ($source <= 0) {
                                return 'Select a source app to view counts.';
                            }

                            $counts = AppTemplateCloner::counts($source);

                            return "Tabs: {$counts['tabs']}, Routes: {$counts['routes']}, Sections: {$counts['sections']}, Items: {$counts['items']}";
                        }),

                    Placeholder::make('current_counts')
                        ->label('Current Target Structure')
                        ->content(function () {
                            $counts = AppTemplateCloner::counts((int) $this->record->id);

                            return "Tabs: {$counts['tabs']}, Routes: {$counts['routes']}, Sections: {$counts['sections']}, Items: {$counts['items']}";
                        }),
                ])
                ->action(function (array $data) {
                    $source = (int) ($data['source_app_id'] ?? 0);
                    $wipe = (bool) ($data['wipe_target_first'] ?? false);
                    $copyCapabilities = (bool) ($data['copy_capabilities'] ?? true);
                    $target = (int) $this->record->id;

                    try {
                        $result = AppTemplateCloner::clone($source, $target, $wipe);

                        if ($copyCapabilities) {
                            $sourceApp = App::query()->find($source);
                            $targetApp = App::query()->find($target);

                            $sourceBranding = is_array($sourceApp?->branding_json) ? $sourceApp->branding_json : [];
                            $targetBranding = is_array($targetApp?->branding_json) ? $targetApp->branding_json : [];

                            $targetBranding['capabilities'] = AppCapabilities::clean(
                                is_array($sourceBranding['capabilities'] ?? null)
                                    ? $sourceBranding['capabilities']
                                    : AppCapabilities::defaults()
                            );

                            if ($targetApp) {
                                $targetApp->branding_json = $targetBranding;
                                $targetApp->save();
                            }
                        }

                        session(['active_app_id' => $target]);

                        Notification::make()
                            ->title('Clone completed')
                            ->body(
                                ($wipe ? 'Wiped target first. ' : '')
                                . 'Copied structure: '
                                . $result['cloned']['tabs'] . ' tabs, '
                                . $result['cloned']['routes'] . ' routes, '
                                . $result['cloned']['sections'] . ' sections, '
                                . $result['cloned']['items'] . ' items.'
                                . ($copyCapabilities ? ' Capabilities copied.' : '')
                            )
                            ->success()
                            ->send();

                        $this->refreshFormData(['name', 'slug', 'branding_json', 'is_active']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Clone failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),

            Actions\DeleteAction::make(),
        ];
    }
}

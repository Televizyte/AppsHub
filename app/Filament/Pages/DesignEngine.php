<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\DesignProject;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DesignEngine extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-swatch';
    protected static ?string $navigationLabel = 'Design Engine';
    protected static ?string $navigationGroup = 'Shared Engines';
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'design-engine';

    protected static string $view = 'filament.pages.design-engine';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('design_engine');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('design_engine');
    }

    public ?App $activeApp = null;

    public string $designTitle = 'Untitled Design';
    public string $designType = 'general';
    public string $status = 'draft';
    public bool $isTemplate = false;
    public string $fabricJson = '';
    public string $previewJson = '';

    public array $recentDesigns = [];


    public function mount(): void
    {
        $this->activeApp = $this->resolveActiveApp();
        $this->loadRecentDesigns();
    }

    public function saveStudioDesign(): void
    {
        $this->activeApp = $this->resolveActiveApp();

        $decoded = json_decode($this->fabricJson, true);

        if (! is_array($decoded)) {
            Notification::make()
                ->title('Nothing to save yet')
                ->body('Create or edit something on the canvas first, then save again.')
                ->warning()
                ->send();

            return;
        }

        $canvas = [
            'engine' => 'fabric',
            'version' => 'studio-v1',
            'width' => (int) data_get($decoded, 'width', 1080),
            'height' => (int) data_get($decoded, 'height', 1080),
            'background' => data_get($decoded, 'background', []),
        ];

        $objects = data_get($decoded, 'objects', []);
        $layers = is_array($objects) ? $objects : [];

        $project = DesignProject::query()->create([
            'app_id' => $this->activeApp?->id,
            'scope' => $this->activeApp?->id ? 'app' : 'global',
            'design_type' => $this->designType ?: 'general',
            'template_category' => $this->designType ?: 'general',
            'title' => trim($this->designTitle) !== '' ? trim($this->designTitle) : 'Untitled Design',
            'subtitle' => 'Created in DXM Design Studio',
            'slug' => Str::slug(trim($this->designTitle) !== '' ? trim($this->designTitle) : 'Untitled Design') . '-' . Str::lower(Str::random(6)),
            'status' => $this->status ?: 'draft',
            'is_template' => $this->isTemplate,
            'is_active' => true,
            'canvas_json' => $canvas,
            'layers_json' => $layers,
            'settings_json' => [
                'fabric_json' => $decoded,
                'source' => 'dxm_design_studio',
                'saved_at' => now()->toDateTimeString(),
            ],
            'preview_json' => json_decode($this->previewJson, true) ?: [],
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
            'sort_order' => 0,
        ]);

        $this->loadRecentDesigns();

        Notification::make()
            ->title('Design saved')
            ->body($project->title . ' was saved to the Design Engine.')
            ->success()
            ->send();
    }

    public function loadRecentDesigns(): void
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        $this->recentDesigns = DesignProject::query()
            ->when($activeAppId > 0, function ($query) use ($activeAppId) {
                $query->where(function ($inner) use ($activeAppId) {
                    $inner->where('app_id', $activeAppId)->orWhere('scope', 'global');
                });
            })
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (DesignProject $project) => [
                'id' => $project->id,
                'title' => $project->title,
                'type' => $project->design_type,
                'status' => $project->status,
                'layers' => count($project->layers_json ?? []),
                'created_at' => optional($project->created_at)->format('M d, H:i'),
            ])
            ->values()
            ->all();
    }

    private function resolveActiveApp(): ?App
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        if ($activeAppId <= 0) {
            return App::query()->orderBy('id')->first();
        }

        return App::query()->find($activeAppId) ?: App::query()->orderBy('id')->first();
    }
}

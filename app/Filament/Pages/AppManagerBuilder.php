<?php

namespace App\Filament\Pages;

use App\Models\App;
use App\Support\ActiveApp;
use App\Support\AdminAccess;
use App\Support\AppCapabilities;
use App\Support\AppTemplateCloner;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class AppManagerBuilder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';
    protected static ?string $navigationLabel = 'App Manager';
    protected static ?string $navigationGroup = 'Main Control';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'app-manager';
    protected static string $view = 'filament.pages.app-manager-builder';

    public string $workspace = 'list';
    public string $builder_tab = 'identity';

    public array $createForm = [
        'name' => '',
        'slug' => '',
        'display_name' => '',
        'tagline' => '',
        'app_type' => 'church_tv',
        'primary_color' => '#0f172a',
        'accent_color' => '#06b6d4',
        'background_color' => '#020617',
        'text_color' => '#ffffff',
        'theme_mode' => 'dark',
        'store_package' => '',
        'play_store_url' => '',
        'support_email' => '',
        'website_url' => '',
        'is_active' => true,
        'clone_from_app_id' => '',
        'clone_structure' => false,
        'copy_capabilities' => true,
        'capabilities' => [],
    ];

    public array $cloneForm = [
        'source_app_id' => '',
        'target_mode' => 'new',
        'target_app_id' => '',
        'new_name' => '',
        'new_slug' => '',
        'copy_capabilities' => true,
        'wipe_target_first' => false,
    ];

    protected $queryString = [
        'workspace' => ['except' => 'list'],
        'builder_tab' => ['except' => 'identity'],
    ];

    public function mount(): void
    {
        if (! AdminAccess::super() && ! AdminAccess::has(['apps.view', 'apps.create'])) {
            abort(403);
        }

        if (empty($this->createForm['capabilities'])) {
            $this->createForm['capabilities'] = AppCapabilities::defaults();
        }

        if (! in_array($this->workspace, ['list', 'create', 'clone'], true)) {
            $this->workspace = 'list';
        }

        if (! in_array($this->builder_tab, ['identity', 'branding', 'theme', 'store', 'contact', 'engines', 'review'], true)) {
            $this->builder_tab = 'identity';
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::super() || AdminAccess::has(['apps.view', 'apps.create']);
    }

    public static function canAccess(): bool
    {
        return AdminAccess::super() || AdminAccess::has(['apps.view', 'apps.create']);
    }

    public function setWorkspace(string $workspace): void
    {
        if (in_array($workspace, ['list', 'create', 'clone'], true)) {
            $this->workspace = $workspace;
        }
    }

    public function setBuilderTab(string $tab): void
    {
        if (in_array($tab, ['identity', 'branding', 'theme', 'store', 'contact', 'engines', 'review'], true)) {
            $this->builder_tab = $tab;
        }
    }

    public function updatedCreateFormName($value): void
    {
        if (trim((string) ($this->createForm['slug'] ?? '')) === '') {
            $this->createForm['slug'] = Str::slug((string) $value);
        }

        if (trim((string) ($this->createForm['display_name'] ?? '')) === '') {
            $this->createForm['display_name'] = (string) $value;
        }
    }

    public function updatedCloneFormNewName($value): void
    {
        if (trim((string) ($this->cloneForm['new_slug'] ?? '')) === '') {
            $this->cloneForm['new_slug'] = Str::slug((string) $value);
        }
    }

    public function makeActive(int $appId): mixed
    {
        abort_unless(AdminAccess::canUseApp($appId), 403);

        ActiveApp::set($appId);

        Notification::make()->title('Active app switched')->success()->send();

        return redirect('/admin/app-manager?workspace=list');
    }

    public function createApp(): mixed
    {
        abort_unless(AdminAccess::super() || AdminAccess::has('apps.create'), 403);

        $name = trim((string) ($this->createForm['name'] ?? ''));
        $slug = Str::slug((string) ($this->createForm['slug'] ?: $name));

        if ($name === '' || $slug === '') {
            Notification::make()->title('App name and slug are required')->danger()->send();
            return null;
        }

        if (App::query()->where('slug', $slug)->exists()) {
            Notification::make()->title('Slug already exists')->body('Choose a unique app slug.')->danger()->send();
            return null;
        }

        $branding = $this->brandingPayload($name);

        $columns = Schema::getColumnListing('apps');
        $now = now();

        $payload = [
            'name' => $name,
            'slug' => $slug,
            'is_active' => (bool) ($this->createForm['is_active'] ?? true),
            'api_token' => Str::random(60),
            'branding_json' => json_encode($branding, JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $payload = array_intersect_key($payload, array_flip($columns));

        $newId = DB::table('apps')->insertGetId($payload);
        $newApp = App::query()->find((int) $newId);

        if ($newApp && in_array('branding_json', $columns, true)) {
            $newApp->branding_json = $branding;
            $newApp->save();
        }

        ActiveApp::set((int) $newId);

        $cloneFrom = (int) ($this->createForm['clone_from_app_id'] ?? 0);
        $cloneStructure = (bool) ($this->createForm['clone_structure'] ?? false);

        if ($cloneStructure && $cloneFrom > 0) {
            try {
                AppTemplateCloner::clone($cloneFrom, (int) $newId, false);

                if ((bool) ($this->createForm['copy_capabilities'] ?? true)) {
                    $this->copyCapabilities($cloneFrom, (int) $newId);
                }

                Notification::make()
                    ->title('App created and structure cloned')
                    ->body($name . ' is now the active app.')
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Notification::make()
                    ->title('App created, but clone failed')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        } else {
            Notification::make()
                ->title('App created')
                ->body($name . ' is now the active app.')
                ->success()
                ->send();
        }

        return redirect('/admin/app-manager?workspace=list');
    }

    public function cloneApp(): mixed
    {
        abort_unless(AdminAccess::super() || AdminAccess::has('apps.create'), 403);

        $source = (int) ($this->cloneForm['source_app_id'] ?? 0);

        if ($source <= 0) {
            Notification::make()->title('Select a source app')->danger()->send();
            return null;
        }

        $targetMode = (string) ($this->cloneForm['target_mode'] ?? 'new');
        $target = (int) ($this->cloneForm['target_app_id'] ?? 0);

        if ($targetMode === 'new') {
            $name = trim((string) ($this->cloneForm['new_name'] ?? ''));
            $slug = Str::slug((string) ($this->cloneForm['new_slug'] ?: $name));

            if ($name === '' || $slug === '') {
                Notification::make()->title('New app name and slug are required')->danger()->send();
                return null;
            }

            if (App::query()->where('slug', $slug)->exists()) {
                Notification::make()->title('Slug already exists')->danger()->send();
                return null;
            }

            $sourceApp = App::query()->find($source);
            $sourceBranding = is_array($sourceApp?->branding_json) ? $sourceApp->branding_json : [];

            $columns = Schema::getColumnListing('apps');
            $payload = [
                'name' => $name,
                'slug' => $slug,
                'is_active' => true,
                'api_token' => Str::random(60),
                'branding_json' => json_encode(array_merge($sourceBranding, [
                    'display_name' => $name,
                    'cloned_from_app_id' => $source,
                ]), JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $target = (int) DB::table('apps')->insertGetId(array_intersect_key($payload, array_flip($columns)));
        }

        if ($target <= 0 || $source === $target) {
            Notification::make()->title('Invalid target app')->danger()->send();
            return null;
        }

        try {
            $result = AppTemplateCloner::clone($source, $target, (bool) ($this->cloneForm['wipe_target_first'] ?? false));

            if ((bool) ($this->cloneForm['copy_capabilities'] ?? true)) {
                $this->copyCapabilities($source, $target);
            }

            ActiveApp::set($target);

            Notification::make()
                ->title('Clone completed')
                ->body('Copied ' . $result['cloned']['tabs'] . ' tabs, ' . $result['cloned']['routes'] . ' routes, ' . $result['cloned']['sections'] . ' sections, and ' . $result['cloned']['items'] . ' items.')
                ->success()
                ->send();

            return redirect('/admin/app-manager?workspace=list');
        } catch (\Throwable $e) {
            Notification::make()->title('Clone failed')->body($e->getMessage())->danger()->send();
            return null;
        }
    }

    public function apps(): array
    {
        return App::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->map(function (App $app) {
                $counts = AppTemplateCloner::counts((int) $app->id);
                return [
                    'id' => (int) $app->id,
                    'name' => (string) $app->name,
                    'slug' => (string) $app->slug,
                    'logo_url' => (string) ($app->logo_url ?? ''),
                    'is_active' => (bool) $app->is_active,
                    'is_current' => (int) ActiveApp::ensureId() === (int) $app->id,
                    'counts' => $counts,
                ];
            })
            ->all();
    }

    public function sourceApps(): array
    {
        return App::query()->where('is_active', 1)->orderBy('name')->pluck('name', 'id')->toArray();
    }

    public function targetApps(): array
    {
        $source = (int) ($this->cloneForm['source_app_id'] ?? 0);

        return App::query()
            ->where('is_active', 1)
            ->when($source > 0, fn ($query) => $query->where('id', '!=', $source))
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function capabilityGroups(): array
    {
        return AppCapabilities::groups();
    }

    protected function brandingPayload(string $name): array
    {
        $capabilities = is_array($this->createForm['capabilities'] ?? null)
            ? $this->createForm['capabilities']
            : AppCapabilities::defaults();

        $capabilities = AppCapabilities::clean($capabilities);

        return [
            'display_name' => trim((string) ($this->createForm['display_name'] ?? '')) ?: $name,
            'tagline' => trim((string) ($this->createForm['tagline'] ?? '')),
            'app_type' => (string) ($this->createForm['app_type'] ?? 'church_tv'),
            'theme' => [
                'primary' => (string) ($this->createForm['primary_color'] ?? '#0f172a'),
                'accent' => (string) ($this->createForm['accent_color'] ?? '#06b6d4'),
                'background' => (string) ($this->createForm['background_color'] ?? '#020617'),
                'text' => (string) ($this->createForm['text_color'] ?? '#ffffff'),
                'mode' => (string) ($this->createForm['theme_mode'] ?? 'dark'),
            ],
            'store' => [
                'package' => trim((string) ($this->createForm['store_package'] ?? '')),
                'play_store_url' => trim((string) ($this->createForm['play_store_url'] ?? '')),
            ],
            'support' => [
                'email' => trim((string) ($this->createForm['support_email'] ?? '')),
                'website_url' => trim((string) ($this->createForm['website_url'] ?? '')),
            ],
            'capabilities' => $capabilities,
            'flags' => [
                'enable_auth' => (bool) ($capabilities['user_auth'] ?? true),
                'enable_watch' => (bool) ($capabilities['watch_manager'] ?? true),
                'enable_content_studio' => (bool) ($capabilities['content_studio'] ?? true),
                'enable_ads' => (bool) ($capabilities['ads'] ?? false),
                'enable_push' => (bool) ($capabilities['notifications'] ?? false),
            ],
        ];
    }

    protected function copyCapabilities(int $sourceAppId, int $targetAppId): void
    {
        $source = App::query()->find($sourceAppId);
        $target = App::query()->find($targetAppId);

        if (! $source || ! $target) {
            return;
        }

        $sourceBranding = is_array($source->branding_json) ? $source->branding_json : [];
        $targetBranding = is_array($target->branding_json) ? $target->branding_json : [];

        $targetBranding['capabilities'] = AppCapabilities::clean(
            is_array($sourceBranding['capabilities'] ?? null)
                ? $sourceBranding['capabilities']
                : AppCapabilities::defaults()
        );

        $target->branding_json = $targetBranding;
        $target->save();
    }
}

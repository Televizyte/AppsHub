<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\AppItem;
use App\Models\AppRoute;
use App\Models\AppSection;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use Filament\Pages\Page;

class DestinationBuilder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';
    protected static ?string $navigationLabel = 'Destination Builder';
    protected static ?string $navigationGroup = 'Workspace';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.destination-builder';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('destination_builder');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('destination_builder');
    }

    public ?App $currentApp = null;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $destinations = [];


    public function mount(): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        $this->currentApp = $appId > 0
            ? App::query()->find($appId)
            : null;

        $this->destinations = $this->buildDestinations($appId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function buildDestinations(int $appId): array
    {
        $tabs = [
            'home' => 'Home',
            'watch' => 'Watch',
            'inspire' => 'Inspire',
            'explore' => 'Explore',
            'more' => 'More',
        ];

        if ($appId <= 0) {
            return collect($tabs)
                ->map(function (string $label, string $key): array {
                    return [
                        'key' => $key,
                        'label' => $label,
                        'routes' => [],
                        'section_count' => 0,
                        'item_count' => 0,
                    ];
                })
                ->values()
                ->all();
        }

        $routes = AppRoute::query()
            ->where('app_id', $appId)
            ->orderBy('tab_key')
            ->orderBy('title')
            ->get([
                'id',
                'app_id',
                'key',
                'title',
                'route',
                'tab_key',
                'is_enabled',
            ]);

        $sections = AppSection::query()
            ->where('app_id', $appId)
            ->orderBy('tab_key')
            ->orderBy('sort_order')
            ->get([
                'id',
                'app_id',
                'tab_key',
                'route_key',
                'key',
                'title',
                'subtitle',
                'template',
                'sort_order',
                'is_enabled',
            ]);

        $sectionIds = $sections->pluck('id')->all();

        $items = empty($sectionIds)
            ? collect()
            : AppItem::query()
                ->whereIn('section_id', $sectionIds)
                ->orderBy('sort_order')
                ->get([
                    'id',
                    'section_id',
                    'title',
                    'subtitle',
                    'type',
                    'sort_order',
                    'is_enabled',
                ]);

        $itemsBySection = $items->groupBy('section_id');
        $routesByTab = $routes->groupBy(function ($route) {
            return $route->tab_key ?: 'unassigned';
        });
        $sectionsByTab = $sections->groupBy(function ($section) {
            return $section->tab_key ?: 'unassigned';
        });

        return collect($tabs)
            ->map(function (string $label, string $key) use ($routesByTab, $sectionsByTab, $itemsBySection): array {
                $tabRoutes = $routesByTab->get($key, collect());
                $tabSections = $sectionsByTab->get($key, collect());

                $routeMap = [];

                foreach ($tabRoutes as $route) {
                    $routeMap[$route->key] = [
                        'id' => (int) $route->id,
                        'title' => (string) ($route->title ?: $route->key),
                        'key' => (string) $route->key,
                        'path' => (string) ($route->route ?? ''),
                        'is_enabled' => (bool) $route->is_enabled,
                        'sections' => [],
                        'section_count' => 0,
                        'item_count' => 0,
                    ];
                }

                $tabHomeKey = '__tab_home__';

                $routeMap[$tabHomeKey] = [
                    'id' => null,
                    'title' => $label . ' Main Screen',
                    'key' => '',
                    'path' => '',
                    'is_enabled' => true,
                    'sections' => [],
                    'section_count' => 0,
                    'item_count' => 0,
                ];

                foreach ($tabSections as $section) {
                    $routeKey = is_string($section->route_key) && trim($section->route_key) !== ''
                        ? trim($section->route_key)
                        : $tabHomeKey;

                    if (! array_key_exists($routeKey, $routeMap)) {
                        $routeMap[$routeKey] = [
                            'id' => null,
                            'title' => 'Linked Page: ' . $routeKey,
                            'key' => $routeKey,
                            'path' => '',
                            'is_enabled' => true,
                            'sections' => [],
                            'section_count' => 0,
                            'item_count' => 0,
                        ];
                    }

                    $sectionItems = $itemsBySection->get($section->id, collect())
                        ->map(function ($item): array {
                            return [
                                'id' => (int) $item->id,
                                'title' => (string) ($item->title ?: 'Untitled content'),
                                'subtitle' => (string) ($item->subtitle ?? ''),
                                'type' => (string) ($item->type ?? 'link'),
                                'sort_order' => (int) ($item->sort_order ?? 0),
                                'is_enabled' => (bool) $item->is_enabled,
                                'edit_url' => '/admin/app-items/' . $item->id . '/edit',
                            ];
                        })
                        ->values()
                        ->all();

                    $routeMap[$routeKey]['sections'][] = [
                        'id' => (int) $section->id,
                        'title' => (string) ($section->title ?: $section->key),
                        'key' => (string) $section->key,
                        'subtitle' => (string) ($section->subtitle ?? ''),
                        'template' => (string) ($section->template ?? ''),
                        'sort_order' => (int) ($section->sort_order ?? 0),
                        'is_enabled' => (bool) $section->is_enabled,
                        'items' => $sectionItems,
                        'item_count' => count($sectionItems),
                        'edit_url' => '/admin/app-sections/' . $section->id . '/edit',
                    ];

                    $routeMap[$routeKey]['section_count']++;
                    $routeMap[$routeKey]['item_count'] += count($sectionItems);
                }

                $routesList = collect($routeMap)
                    ->values()
                    ->sortBy(function (array $route): string {
                        return ($route['title'] ?? '') . '|' . ($route['key'] ?? '');
                    })
                    ->map(function (array $route): array {
                        $route['sections'] = collect($route['sections'])
                            ->sortBy('sort_order')
                            ->values()
                            ->all();

                        return $route;
                    })
                    ->values()
                    ->all();

                $sectionCount = (int) collect($routesList)->sum('section_count');
                $itemCount = (int) collect($routesList)->sum('item_count');

                return [
                    'key' => $key,
                    'label' => $label,
                    'routes' => $routesList,
                    'section_count' => $sectionCount,
                    'item_count' => $itemCount,
                ];
            })
            ->values()
            ->all();
    }

    protected function getViewData(): array
    {
        return [
            'currentApp' => $this->currentApp,
            'destinations' => $this->destinations,
        ];
    }
}

<?php

namespace App\Support;

use App\Models\App;
use App\Models\AppSection;
use App\Models\BuilderTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BuilderTemplateCaptureService
{
    private array $allowedTabs = ['home', 'watch', 'inspire', 'explore', 'more'];

    public function captureSection(AppSection $section, ?string $title = null, string $category = 'sections'): BuilderTemplate
    {
        $category = $this->cleanCategory($category);

        $section->load(['items' => function ($query) {
            $query->orderBy('sort_order')->orderBy('id');
        }]);

        $payload = [
            'target_tab' => $this->cleanTab($section->tab_key),
            'sections' => [
                $this->sectionToPayload($section),
            ],
        ];

        $templateTitle = $this->cleanTitle($title ?: $section->title . ' Template');

        return $this->storeTemplate([
            'category' => $category,
            'title' => $templateTitle,
            'subtitle' => 'Saved from ' . ucfirst($this->cleanTab($section->tab_key)) . ' section',
            'description' => 'Reusable section template saved from the active app destination builder.',
            'badge' => $category === 'cards' ? 'Card Template' : ($category === 'widgets' ? 'Widget Template' : 'Saved Section'),
            'glyph' => $this->glyphForTemplate((string) $section->template, (string) $section->title),
            'tone' => 'cyan',
            'status' => 'Saved',
            'apply_mode' => 'append',
            'payload_json' => $payload,
            'preview_json' => [
                'layout' => (string) ($section->template ?: 'grid'),
                'cards' => $section->items->count(),
                'what_it_creates' => [
                    '1 saved section',
                    $section->items->count() . ' saved item(s)',
                    'Append-only reusable template',
                ],
                'best_for' => [ucfirst($this->cleanTab($section->tab_key)), 'Reusable blocks'],
            ],
            'sort_order' => $this->nextSortOrder($category),
            'is_active' => true,
        ]);
    }

    public function captureTab(int $appId, string $tab, ?string $title = null): BuilderTemplate
    {
        $tab = $this->cleanTab($tab);
        $app = App::query()->find($appId);

        if (! $app) {
            throw new \RuntimeException('Active app not found.');
        }

        $sections = AppSection::query()
            ->with(['items' => function ($query) {
                $query->orderBy('sort_order')->orderBy('id');
            }])
            ->where('app_id', $appId)
            ->where('tab_key', $tab)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($sections->isEmpty()) {
            throw new \RuntimeException('This tab has no sections to save as a template.');
        }

        $payloadSections = $sections->map(fn (AppSection $section) => $this->sectionToPayload($section))->values()->all();
        $itemCount = $sections->sum(fn (AppSection $section) => $section->items->count());
        $templateTitle = $this->cleanTitle($title ?: $app->name . ' ' . ucfirst($tab) . ' Page Template');

        return $this->storeTemplate([
            'category' => 'pages',
            'title' => $templateTitle,
            'subtitle' => 'Saved ' . ucfirst($tab) . ' page structure',
            'description' => 'Reusable page template saved from all sections under the selected app tab.',
            'badge' => 'Saved Page',
            'glyph' => $this->glyphForTab($tab),
            'tone' => 'purple',
            'status' => 'Saved',
            'apply_mode' => 'append',
            'payload_json' => [
                'target_tab' => $tab,
                'sections' => $payloadSections,
            ],
            'preview_json' => [
                'layout' => $tab . '_page',
                'cards' => $itemCount,
                'what_it_creates' => [
                    $sections->count() . ' saved section(s)',
                    $itemCount . ' saved item(s)',
                    'Append-only reusable page',
                ],
                'best_for' => [ucfirst($tab), 'Cross-app reuse'],
            ],
            'sort_order' => $this->nextSortOrder('pages'),
            'is_active' => true,
        ]);
    }

    private function sectionToPayload(AppSection $section): array
    {
        return [
            'tab_key' => $this->cleanTab($section->tab_key),
            'route_key' => $section->route_key,
            'key' => $this->makeTemplateKey((string) $section->key),
            'title' => $section->title,
            'subtitle' => $section->subtitle,
            'template' => $section->template ?: 'grid',
            'is_enabled' => true,
            'visibility_json' => $this->asArray($section->visibility_json),
            'empty_state_json' => $this->asArray($section->empty_state_json),
            'meta_json' => array_merge($this->asArray($section->meta_json), [
                'source' => 'saved_template',
                'saved_from_section_id' => (int) $section->id,
            ]),
            'items' => $section->items->map(function ($item) {
                return [
                    'type' => $item->type ?: 'link',
                    'title' => $item->title,
                    'subtitle' => $item->subtitle,
                    'icon' => $item->icon,
                    'image_url' => $item->image_url,
                    'route' => $item->route,
                    'url' => $item->url,
                    'payload_json' => $this->asArray($item->payload_json),
                    'sort_order' => (int) $item->sort_order,
                    'is_enabled' => (bool) $item->is_enabled,
                ];
            })->values()->all(),
        ];
    }

    private function storeTemplate(array $data): BuilderTemplate
    {
        return DB::transaction(function () use ($data) {
            return BuilderTemplate::query()->create(array_merge($data, [
                'key' => $this->uniqueTemplateKey($data['title'] ?? 'saved-template'),
            ]));
        });
    }

    private function asArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function cleanTab(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $this->allowedTabs, true) ? $value : 'home';
    }

    private function cleanCategory(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, ['pages', 'sections', 'cards', 'forms', 'widgets'], true) ? $value : 'sections';
    }

    private function cleanTitle(string $title): string
    {
        $title = trim($title);
        return $title !== '' ? Str::limit($title, 160, '') : 'Saved Template';
    }

    private function makeTemplateKey(string $key): string
    {
        $key = Str::slug($key, '_');
        return $key !== '' ? 'saved_' . Str::limit($key, 100, '') : 'saved_section';
    }

    private function uniqueTemplateKey(string $title): string
    {
        $base = 'saved_' . (Str::slug($title, '_') ?: 'template');
        $base = Str::limit($base, 95, '');
        $key = $base;
        $count = 2;

        while (BuilderTemplate::query()->where('key', $key)->exists()) {
            $suffix = '_' . $count;
            $key = Str::limit($base, 120 - strlen($suffix), '') . $suffix;
            $count++;
        }

        return $key;
    }

    private function nextSortOrder(string $category): int
    {
        $max = (int) BuilderTemplate::query()
            ->where('category', $category)
            ->max('sort_order');

        return $max + 10;
    }

    private function glyphForTab(string $tab): string
    {
        return match ($tab) {
            'home' => '⌂',
            'watch' => '▻',
            'inspire' => '✦',
            'explore' => '▦',
            'more' => '☰',
            default => '▣',
        };
    }

    private function glyphForTemplate(string $template, string $title): string
    {
        $text = strtolower($template . ' ' . $title);

        if (str_contains($text, 'banner')) return '▭';
        if (str_contains($text, 'video') || str_contains($text, 'watch')) return '▶';
        if (str_contains($text, 'scripture') || str_contains($text, 'daily')) return '✚';
        if (str_contains($text, 'quote')) return '❝';
        if (str_contains($text, 'book')) return '▤';
        if (str_contains($text, 'form')) return '✉';

        return '▦';
    }
}

<?php

namespace App\Support;

use App\Models\App;
use App\Models\AppItem;
use App\Models\AppSection;
use App\Models\BuilderTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BuilderTemplateApplyService
{
    private array $allowedTabs = ['home', 'watch', 'inspire', 'explore', 'more'];

    /**
     * Safe template apply engine.
     *
     * Current rule:
     * - APPEND only.
     * - Never wipes existing app sections/items.
     * - Never touches other apps.
     * - Creates real app_sections/app_items records so the current frontend payload can render them.
     */
    public function apply(BuilderTemplate $template, int $targetAppId, ?string $targetTab = null): array
    {
        $targetAppId = (int) $targetAppId;

        if ($targetAppId < 1) {
            throw new \InvalidArgumentException('No active app selected.');
        }

        $app = App::query()->find($targetAppId);

        if (! $app) {
            throw new \RuntimeException('Target app not found.');
        }

        if (! $template->is_active) {
            throw new \RuntimeException('This template is not active.');
        }

        $payload = is_array($template->payload_json) ? $template->payload_json : [];
        $sections = $payload['sections'] ?? [];

        if (! is_array($sections) || count($sections) < 1) {
            throw new \RuntimeException('This template has no section payload to apply.');
        }

        $targetTab = $this->cleanTab($targetTab ?: ($payload['target_tab'] ?? null));

        return DB::transaction(function () use ($template, $targetAppId, $sections, $targetTab) {
            $createdSections = 0;
            $createdItems = 0;
            $createdSectionIds = [];

            foreach ($sections as $sectionPayload) {
                if (! is_array($sectionPayload)) {
                    continue;
                }

                $tabKey = $this->cleanTab($sectionPayload['tab_key'] ?? $targetTab);
                $title = $this->cleanText($sectionPayload['title'] ?? $template->title, 140, 'Untitled Section');
                $templateName = $this->cleanText($sectionPayload['template'] ?? 'grid', 120, 'grid');
                $sortOrder = $this->nextSectionSortOrder($targetAppId, $tabKey);

                $sectionKeyBase = $this->cleanKey(
                    $sectionPayload['key'] ?? $template->key . '_' . $tabKey
                );

                $section = AppSection::query()->create([
                    'app_id' => $targetAppId,
                    'tab_key' => $tabKey,
                    'route_key' => $this->nullableText($sectionPayload['route_key'] ?? null, 120),
                    'key' => $this->uniqueSectionKey($targetAppId, $sectionKeyBase),
                    'title' => $title,
                    'subtitle' => $this->nullableText($sectionPayload['subtitle'] ?? null, 180),
                    'template' => $templateName,
                    'sort_order' => $sortOrder,
                    'is_enabled' => (bool) ($sectionPayload['is_enabled'] ?? true),
                    'visibility_json' => $this->arrayOrNull($sectionPayload['visibility_json'] ?? null),
                    'empty_state_json' => $this->arrayOrNull($sectionPayload['empty_state_json'] ?? null),
                    'meta_json' => $this->normalizeSectionMeta($sectionPayload, $template),
                ]);

                $createdSections++;
                $createdSectionIds[] = (int) $section->id;

                $items = $sectionPayload['items'] ?? [];
                if (! is_array($items)) {
                    $items = [];
                }

                foreach ($items as $itemPayload) {
                    if (! is_array($itemPayload)) {
                        continue;
                    }

                    AppItem::query()->create([
                        'section_id' => (int) $section->id,
                        'type' => $this->cleanText($itemPayload['type'] ?? 'link', 80, 'link'),
                        'title' => $this->cleanText($itemPayload['title'] ?? 'New Item', 255, 'New Item'),
                        'subtitle' => $this->nullableText($itemPayload['subtitle'] ?? null, 255),
                        'icon' => $this->nullableText($itemPayload['icon'] ?? null, 120),
                        'image_url' => $this->nullableText($itemPayload['image_url'] ?? null, 1000),
                        'route' => $this->nullableText($itemPayload['route'] ?? null, 255),
                        'url' => $this->nullableText($itemPayload['url'] ?? null, 1000),
                        'payload_json' => $this->normalizeItemPayload($itemPayload, $sectionPayload, $template),
                        'sort_order' => (int) ($itemPayload['sort_order'] ?? ($createdItems + 1) * 10),
                        'is_enabled' => (bool) ($itemPayload['is_enabled'] ?? true),
                    ]);

                    $createdItems++;
                }
            }

            if ($createdSections < 1) {
                throw new \RuntimeException('Template apply created no section. Nothing was saved.');
            }

            return [
                'template_id' => (int) $template->id,
                'template_title' => (string) $template->title,
                'app_id' => $targetAppId,
                'created_sections' => $createdSections,
                'created_items' => $createdItems,
                'created_section_ids' => $createdSectionIds,
                'tab' => $targetTab,
            ];
        });
    }

    private function normalizeSectionMeta(array $sectionPayload, BuilderTemplate $template): ?array
    {
        $meta = $this->arrayOrNull($sectionPayload['meta_json'] ?? null) ?? [];

        $layout = $meta['layout_type']
            ?? $meta['layout_variant']
            ?? $sectionPayload['layout_type']
            ?? $sectionPayload['layout_variant']
            ?? $sectionPayload['template']
            ?? 'vertical_list';

        $meta['builder_schema'] = $meta['builder_schema'] ?? 'destination_builder_v2_1';
        $meta['source'] = $meta['source'] ?? 'builder_template';
        $meta['template_key'] = $meta['template_key'] ?? (string) $template->key;
        $meta['template_category'] = $meta['template_category'] ?? (string) $template->category;
        $meta['layout_type'] = $meta['layout_type'] ?? $layout;
        $meta['layout_variant'] = $meta['layout_variant'] ?? $layout;
        $meta['placement_type'] = $meta['placement_type'] ?? ($sectionPayload['placement_type'] ?? 'main_tab');
        $meta['source_type'] = $meta['source_type'] ?? ($sectionPayload['source_type'] ?? 'manual_items');

        foreach (['source_bucket', 'source_category', 'source_channel', 'target_route', 'insert_target_bucket', 'card_style', 'size_preset'] as $key) {
            if (! array_key_exists($key, $meta) && array_key_exists($key, $sectionPayload)) {
                $value = trim((string) $sectionPayload[$key]);
                if ($value !== '') {
                    $meta[$key] = Str::limit($value, 160, '');
                }
            }
        }

        if (! array_key_exists('insert_after_items', $meta) && array_key_exists('insert_after_items', $sectionPayload)) {
            $meta['insert_after_items'] = max(0, (int) $sectionPayload['insert_after_items']);
        }

        if (! array_key_exists('columns', $meta) && array_key_exists('columns', $sectionPayload)) {
            $meta['columns'] = max(1, min(6, (int) $sectionPayload['columns']));
        }

        return $meta;
    }

    private function normalizeItemPayload(array $itemPayload, array $sectionPayload, BuilderTemplate $template): ?array
    {
        $payload = $this->arrayOrNull($itemPayload['payload_json'] ?? null) ?? [];
        $payload['builder_schema'] = $payload['builder_schema'] ?? 'destination_builder_item_v2_1';
        $payload['template_key'] = $payload['template_key'] ?? (string) $template->key;
        $payload['template_category'] = $payload['template_category'] ?? (string) $template->category;
        $payload['item_kind'] = $payload['item_kind'] ?? ($itemPayload['item_kind'] ?? $itemPayload['type'] ?? 'link');
        $payload['card_style'] = $payload['card_style'] ?? ($itemPayload['card_style'] ?? $sectionPayload['card_style'] ?? null);
        $payload['layout_template'] = $payload['layout_template'] ?? ($itemPayload['layout_template'] ?? $sectionPayload['template'] ?? null);
        $payload['size_preset'] = $payload['size_preset'] ?? ($itemPayload['size_preset'] ?? $sectionPayload['size_preset'] ?? null);
        $payload['open_mode'] = $payload['open_mode'] ?? ($itemPayload['open_mode'] ?? 'push');

        foreach (['source_type', 'source_bucket', 'source_channel', 'content_id', 'quote_id', 'short_video_id', 'book_id', 'quiz_set_id', 'badge_text'] as $key) {
            if (! array_key_exists($key, $payload) && array_key_exists($key, $itemPayload)) {
                $value = trim((string) $itemPayload[$key]);
                if ($value !== '') {
                    $payload[$key] = Str::limit($value, 255, '');
                }
            }
        }

        if (! isset($payload['action']) || ! is_array($payload['action'])) {
            $route = trim((string) ($itemPayload['route'] ?? ''));
            $url = trim((string) ($itemPayload['url'] ?? ''));
            if ($route !== '') {
                $payload['action'] = ['type' => 'route', 'route_key' => $route];
            } elseif ($url !== '') {
                $payload['action'] = ['type' => 'external_url', 'url' => $url];
            }
        }

        return $payload;
    }

    private function cleanTab(?string $value): string
    {
        $value = strtolower(trim((string) $value));
        return in_array($value, $this->allowedTabs, true) ? $value : 'home';
    }

    private function cleanKey(?string $value): string
    {
        $value = Str::slug((string) $value, '_');
        return $value !== '' ? Str::limit($value, 110, '') : 'template_section';
    }

    private function cleanText($value, int $max, string $fallback): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            $value = $fallback;
        }
        return Str::limit($value, $max, '');
    }

    private function nullableText($value, int $max): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : Str::limit($value, $max, '');
    }

    private function arrayOrNull($value): ?array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : null;
        }

        return null;
    }

    private function nextSectionSortOrder(int $appId, string $tab): int
    {
        $max = (int) AppSection::query()
            ->where('app_id', $appId)
            ->where('tab_key', $tab)
            ->max('sort_order');

        return $max + 10;
    }

    private function uniqueSectionKey(int $appId, string $baseKey): string
    {
        $baseKey = $this->cleanKey($baseKey);
        $key = $baseKey;
        $count = 2;

        while (AppSection::query()->where('app_id', $appId)->where('key', $key)->exists()) {
            $suffix = '_' . $count;
            $key = Str::limit($baseKey, 120 - strlen($suffix), '') . $suffix;
            $count++;
        }

        return $key;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\App;
use App\Models\AppItem;
use App\Models\AppSection;
use Illuminate\Console\Command;

class CleanDuplicateHubStructure extends Command
{
    protected $signature = 'dxm:clean-duplicates {--apply : Apply cleanup} {--delete-empty : Delete duplicate sections only when they have no items}';

    protected $description = 'Safely clean duplicate hub sections/items without damaging active frontend content.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $deleteEmpty = (bool) $this->option('delete-empty');

        $this->info($apply ? 'Running duplicate cleanup...' : 'DRY RUN ONLY. Nothing will be changed.');

        foreach (App::query()->orderBy('id')->get() as $app) {
            $this->line('');
            $this->line("App {$app->id}: {$app->name}");

            $this->cleanSections((int) $app->id, $apply, $deleteEmpty);
            $this->cleanItems((int) $app->id, $apply);
        }

        $this->info('');
        $this->info($apply ? 'Duplicate cleanup completed.' : 'Dry run completed. Add --apply to change data.');

        return self::SUCCESS;
    }

    private function cleanSections(int $appId, bool $apply, bool $deleteEmpty): void
    {
        $sections = AppSection::query()
            ->withCount('items')
            ->where('app_id', $appId)
            ->orderBy('tab_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groups = $sections->groupBy(function ($section) {
            return strtolower(trim((string) $section->tab_key)) . '|' . $this->normalizeTitle((string) $section->title);
        });

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $keeper = $group->sortByDesc(function ($section) {
                return
                    ($this->isCanonicalKey((string) $section->key) ? 100000 : 0) +
                    ((int) $section->items_count * 1000) +
                    ((bool) $section->is_enabled ? 100 : 0) +
                    (int) $section->id;
            })->first();

            foreach ($group as $section) {
                if ((int) $section->id === (int) $keeper->id) {
                    continue;
                }

                $label = "{$section->tab_key} → {$section->title} (#{$section->id})";

                if ((int) $section->items_count === 0 && $deleteEmpty) {
                    $this->warn("  DELETE empty duplicate section: {$label}");

                    if ($apply) {
                        $section->delete();
                    }

                    continue;
                }

                $this->warn("  DISABLE duplicate section: {$label}");

                if ($apply) {
                    $meta = is_array($section->meta_json) ? $section->meta_json : [];
                    $meta['archived_reason'] = 'duplicate_section';
                    $meta['kept_section_id'] = (int) $keeper->id;

                    $section->update([
                        'is_enabled' => false,
                        'sort_order' => 9999,
                        'meta_json' => $meta,
                    ]);
                }
            }
        }
    }

    private function cleanItems(int $appId, bool $apply): void
    {
        $items = AppItem::query()
            ->with('section:id,app_id,tab_key,title')
            ->whereHas('section', function ($query) use ($appId) {
                $query->where('app_id', $appId);
            })
            ->orderBy('section_id')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $groups = $items->groupBy(function ($item) {
            return (int) $item->section_id . '|' . $this->normalizeTitle((string) $item->title);
        });

        foreach ($groups as $group) {
            if ($group->count() < 2) {
                continue;
            }

            $keeper = $group->sortByDesc(function ($item) {
                return
                    (! empty($item->image_url) ? 10000 : 0) +
                    (! empty($item->route) ? 5000 : 0) +
                    (! empty($item->url) ? 3000 : 0) +
                    (! empty($item->payload_json) ? 2000 : 0) +
                    ((bool) $item->is_enabled ? 100 : 0) +
                    (int) $item->id;
            })->first();

            foreach ($group as $item) {
                if ((int) $item->id === (int) $keeper->id) {
                    continue;
                }

                $sectionTitle = $item->section->title ?? 'Unknown Section';
                $this->warn("  DISABLE duplicate item: {$sectionTitle} → {$item->title} (#{$item->id})");

                if ($apply) {
                    $payload = is_array($item->payload_json) ? $item->payload_json : [];
                    $payload['archived_reason'] = 'duplicate_item';
                    $payload['kept_item_id'] = (int) $keeper->id;

                    $item->update([
                        'is_enabled' => false,
                        'sort_order' => 9999,
                        'payload_json' => $payload,
                    ]);
                }
            }
        }
    }

    private function normalizeTitle(string $title): string
    {
        $title = strtolower(trim($title));
        $title = str_replace(['/', '&'], ' ', $title);
        $title = preg_replace('/\s+/', ' ', $title) ?: $title;

        return trim($title);
    }

    private function isCanonicalKey(string $key): bool
    {
        return str_starts_with($key, 'home_')
            || str_starts_with($key, 'watch_')
            || str_starts_with($key, 'inspire_')
            || str_starts_with($key, 'explore_')
            || str_starts_with($key, 'more_');
    }
}

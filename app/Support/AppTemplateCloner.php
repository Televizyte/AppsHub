<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class AppTemplateCloner
{
    /**
     * Clone Tabs, Routes, Sections, Items from source app into target app.
     *
     * Safety rules:
     * - Never wipe target if source has no config.
     * - If wipe is enabled but clone results in zero rows, rollback.
     *
     * NOTE:
     * - app_items table has NO app_id column (items belong to app via section_id).
     * - We scope items by sections belonging to the app.
     */
    public static function clone(int $sourceAppId, int $targetAppId, bool $wipeTargetFirst = false): array
    {
        if ($sourceAppId <= 0 || $targetAppId <= 0) {
            throw new \InvalidArgumentException('Invalid app id(s) supplied.');
        }

        if ($sourceAppId === $targetAppId) {
            throw new \InvalidArgumentException('Source and target app cannot be the same.');
        }

        $sourceExists = DB::table('apps')->where('id', $sourceAppId)->exists();
        $targetExists = DB::table('apps')->where('id', $targetAppId)->exists();

        if (! $sourceExists) {
            throw new \RuntimeException("Source app (id={$sourceAppId}) not found.");
        }

        if (! $targetExists) {
            throw new \RuntimeException("Target app (id={$targetAppId}) not found.");
        }

        // Pre-check: if source has no config, do NOT allow wipe+clone to proceed.
        $sourceCounts = self::counts($sourceAppId);
        $sourceTotal = (int) $sourceCounts['tabs'] + (int) $sourceCounts['routes'] + (int) $sourceCounts['sections'] + (int) $sourceCounts['items'];

        if ($sourceTotal <= 0) {
            throw new \RuntimeException('Source app has no Tabs/Routes/Sections/Items to clone. Aborting to prevent wiping the target.');
        }

        $now = now();

        return DB::transaction(function () use ($sourceAppId, $targetAppId, $wipeTargetFirst, $now) {
            $deleted = [
                'tabs' => 0,
                'routes' => 0,
                'sections' => 0,
                'items' => 0,
            ];

            // ---- Read source data FIRST (so wipe never happens if source is empty) ----
            $sourceTabs = DB::table('app_tabs')
                ->where('app_id', $sourceAppId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            $sourceRoutes = DB::table('app_routes')
                ->where('app_id', $sourceAppId)
                ->orderBy('key')
                ->orderBy('id')
                ->get();

            $sourceSections = DB::table('app_sections')
                ->where('app_id', $sourceAppId)
                ->orderBy('tab_key')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get();

            if ($sourceTabs->count() === 0 && $sourceRoutes->count() === 0 && $sourceSections->count() === 0) {
                throw new \RuntimeException('Source app has no Tabs/Routes/Sections to clone. Aborting to protect the target.');
            }

            // ---- Wipe target (ONLY target) ----
            if ($wipeTargetFirst) {
                $targetSectionIds = DB::table('app_sections')
                    ->where('app_id', $targetAppId)
                    ->pluck('id')
                    ->all();

                if (! empty($targetSectionIds)) {
                    $deleted['items'] = DB::table('app_items')->whereIn('section_id', $targetSectionIds)->delete();
                }

                $deleted['sections'] = DB::table('app_sections')->where('app_id', $targetAppId)->delete();
                $deleted['routes']   = DB::table('app_routes')->where('app_id', $targetAppId)->delete();
                $deleted['tabs']     = DB::table('app_tabs')->where('app_id', $targetAppId)->delete();
            }

            // ---- Clone Tabs ----
            $tabCount = 0;
            foreach ($sourceTabs as $t) {
                DB::table('app_tabs')->insert([
                    'app_id'      => $targetAppId,
                    'key'         => $t->key,
                    'title'       => $t->title,
                    'icon'        => $t->icon,
                    'sort_order'  => $t->sort_order ?? 0,
                    'is_enabled'  => (int) ($t->is_enabled ?? 1),
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $tabCount++;
            }

            // ---- Clone Routes ----
            $routeCount = 0;
            foreach ($sourceRoutes as $r) {
                DB::table('app_routes')->insert([
                    'app_id'      => $targetAppId,
                    'key'         => $r->key,
                    'title'       => $r->title,
                    'route'       => $r->route,
                    'tab_key'     => $r->tab_key,
                    'is_enabled'  => (int) ($r->is_enabled ?? 1),
                    'meta_json'   => $r->meta_json,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                $routeCount++;
            }

            // ---- Clone Sections (build map old_section_id => new_section_id) ----
            $sectionIdMap = [];
            $sectionCount = 0;

            foreach ($sourceSections as $s) {
                $newId = DB::table('app_sections')->insertGetId([
                    'app_id'           => $targetAppId,
                    'tab_key'          => $s->tab_key,
                    'route_key'        => $s->route_key,
                    'key'              => $s->key,
                    'title'            => $s->title,
                    'subtitle'         => $s->subtitle,
                    'template'         => $s->template,
                    'sort_order'       => $s->sort_order ?? 0,
                    'is_enabled'       => (int) ($s->is_enabled ?? 1),
                    'visibility_json'  => $s->visibility_json,
                    'empty_state_json' => $s->empty_state_json,
                    'meta_json'        => $s->meta_json,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);

                $sectionIdMap[(int) $s->id] = (int) $newId;
                $sectionCount++;
            }

            // ---- Clone Items (by section mapping) ----
            $sourceSectionIds = array_keys($sectionIdMap);
            $itemCount = 0;

            if (! empty($sourceSectionIds)) {
                $sourceItems = DB::table('app_items')
                    ->whereIn('section_id', $sourceSectionIds)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get();

                foreach ($sourceItems as $i) {
                    $newSectionId = $sectionIdMap[(int) $i->section_id] ?? null;
                    if (! $newSectionId) {
                        continue;
                    }

                    DB::table('app_items')->insert([
                        'section_id'   => $newSectionId,
                        'type'         => $i->type,
                        'title'        => $i->title,
                        'subtitle'     => $i->subtitle,
                        'icon'         => $i->icon,
                        'image_url'    => $i->image_url,
                        'route'        => $i->route,
                        'url'          => $i->url,
                        'payload_json' => $i->payload_json,
                        'sort_order'   => $i->sort_order ?? 0,
                        'is_enabled'   => (int) ($i->is_enabled ?? 1),
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]);
                    $itemCount++;
                }
            }

            // Final safety: if wipe was requested but nothing cloned, rollback the whole transaction.
            if ($wipeTargetFirst && ($tabCount + $routeCount + $sectionCount + $itemCount) === 0) {
                throw new \RuntimeException('Clone produced zero rows. Rolling back to prevent data loss.');
            }

            return [
                'wipe' => $wipeTargetFirst,
                'deleted' => $deleted,
                'cloned' => [
                    'tabs' => $tabCount,
                    'routes' => $routeCount,
                    'sections' => $sectionCount,
                    'items' => $itemCount,
                ],
            ];
        });
    }

    /**
     * Quick helper: get counts for an app (tabs/routes/sections/items).
     */
    public static function counts(int $appId): array
    {
        $sectionIds = DB::table('app_sections')->where('app_id', $appId)->pluck('id')->all();

        return [
            'tabs' => (int) DB::table('app_tabs')->where('app_id', $appId)->count(),
            'routes' => (int) DB::table('app_routes')->where('app_id', $appId)->count(),
            'sections' => (int) DB::table('app_sections')->where('app_id', $appId)->count(),
            'items' => empty($sectionIds) ? 0 : (int) DB::table('app_items')->whereIn('section_id', $sectionIds)->count(),
        ];
    }
}

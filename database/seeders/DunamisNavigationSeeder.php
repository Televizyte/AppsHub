<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DunamisNavigationSeeder extends Seeder
{
    public function run(): void
    {
        $appId = 1; // Dunamis TV

        DB::transaction(function () use ($appId) {

            // 1) Tabs (locked to your agreed structure)
            // Home, Watch, Inspire, Explore, More
            $tabs = [
                ['app_id' => $appId, 'key' => 'home',    'title' => 'Home',    'icon' => 'home',    'sort_order' => 10, 'is_enabled' => 1],
                ['app_id' => $appId, 'key' => 'watch',   'title' => 'Watch',   'icon' => 'play',    'sort_order' => 20, 'is_enabled' => 1],
                ['app_id' => $appId, 'key' => 'inspire', 'title' => 'Inspire', 'icon' => 'book',    'sort_order' => 30, 'is_enabled' => 1],
                ['app_id' => $appId, 'key' => 'explore', 'title' => 'Explore', 'icon' => 'grid',    'sort_order' => 40, 'is_enabled' => 1],
                ['app_id' => $appId, 'key' => 'more',    'title' => 'More',    'icon' => 'menu',    'sort_order' => 50, 'is_enabled' => 1],
            ];

            // Clear existing (safe because it's currently empty, but keeps seed repeatable)
            DB::table('app_tabs')->where('app_id', $appId)->delete();
            DB::table('app_routes')->where('app_id', $appId)->delete();

            $sectionIds = DB::table('app_sections')->where('app_id', $appId)->pluck('id')->all();
            if (!empty($sectionIds)) {
                DB::table('app_items')->whereIn('section_id', $sectionIds)->delete();
            }
            DB::table('app_sections')->where('app_id', $appId)->delete();

            DB::table('app_tabs')->insert(array_map(function ($t) {
                $t['created_at'] = now();
                $t['updated_at'] = now();
                return $t;
            }, $tabs));

            // 2) Routes (minimal but functional — you can extend later)
            $routes = [
                ['app_id' => $appId, 'key' => 'home',          'title' => 'Home',          'route' => '/home',          'tab_key' => 'home',    'is_enabled' => 1, 'meta_json' => json_encode([])],
                ['app_id' => $appId, 'key' => 'watch',         'title' => 'Watch',         'route' => '/watch',         'tab_key' => 'watch',   'is_enabled' => 1, 'meta_json' => json_encode([])],
                ['app_id' => $appId, 'key' => 'live_stream',   'title' => 'Live Stream',   'route' => '/watch/live',    'tab_key' => 'watch',   'is_enabled' => 1, 'meta_json' => json_encode([])],
                ['app_id' => $appId, 'key' => 'inspire',       'title' => 'Inspire',       'route' => '/inspire',       'tab_key' => 'inspire', 'is_enabled' => 1, 'meta_json' => json_encode([])],
                ['app_id' => $appId, 'key' => 'wordification', 'title' => 'Wordification', 'route' => '/wordification', 'tab_key' => 'inspire', 'is_enabled' => 1, 'meta_json' => json_encode([])],
                ['app_id' => $appId, 'key' => 'motivation',    'title' => 'Motivation',    'route' => '/motivation',    'tab_key' => 'inspire', 'is_enabled' => 1, 'meta_json' => json_encode([])],
                ['app_id' => $appId, 'key' => 'explore',       'title' => 'Explore',       'route' => '/explore',       'tab_key' => 'explore', 'is_enabled' => 1, 'meta_json' => json_encode([])],
                ['app_id' => $appId, 'key' => 'more',          'title' => 'More',          'route' => '/more',          'tab_key' => 'more',    'is_enabled' => 1, 'meta_json' => json_encode([])],
            ];

            DB::table('app_routes')->insert(array_map(function ($r) {
                $r['created_at'] = now();
                $r['updated_at'] = now();
                return $r;
            }, $routes));

            // 3) Sections (basic homepage sections + inspire sections)
            $sections = [
                // Home
                ['app_id' => $appId, 'tab_key' => 'home', 'route_key' => 'home', 'key' => 'home_banners', 'title' => 'Banners', 'subtitle' => null, 'template' => 'banners', 'sort_order' => 10, 'is_enabled' => 1],
                ['app_id' => $appId, 'tab_key' => 'home', 'route_key' => 'home', 'key' => 'home_daily',   'title' => 'Daily',   'subtitle' => null, 'template' => 'daily',   'sort_order' => 20, 'is_enabled' => 1],

                // Watch
                ['app_id' => $appId, 'tab_key' => 'watch', 'route_key' => 'watch', 'key' => 'watch_live', 'title' => 'Live Stream', 'subtitle' => null, 'template' => 'live', 'sort_order' => 10, 'is_enabled' => 1],

                // Inspire
                ['app_id' => $appId, 'tab_key' => 'inspire', 'route_key' => 'inspire', 'key' => 'inspire_featured', 'title' => 'Featured Articles', 'subtitle' => null, 'template' => 'grid', 'sort_order' => 10, 'is_enabled' => 1],
                ['app_id' => $appId, 'tab_key' => 'inspire', 'route_key' => 'wordification', 'key' => 'wordification_list', 'title' => 'Wordification', 'subtitle' => null, 'template' => 'articles', 'sort_order' => 20, 'is_enabled' => 1],
                ['app_id' => $appId, 'tab_key' => 'inspire', 'route_key' => 'motivation', 'key' => 'motivation_list', 'title' => 'Motivation', 'subtitle' => null, 'template' => 'articles', 'sort_order' => 30, 'is_enabled' => 1],

                // Explore
                ['app_id' => $appId, 'tab_key' => 'explore', 'route_key' => 'explore', 'key' => 'explore_tools', 'title' => 'Tools', 'subtitle' => null, 'template' => 'grid', 'sort_order' => 10, 'is_enabled' => 1],

                // More
                ['app_id' => $appId, 'tab_key' => 'more', 'route_key' => 'more', 'key' => 'more_account', 'title' => 'Account & Settings', 'subtitle' => null, 'template' => 'list', 'sort_order' => 10, 'is_enabled' => 1],
            ];

            $sectionRows = [];
            foreach ($sections as $s) {
                $sectionRows[] = array_merge($s, [
                    'visibility_json' => json_encode([]),
                    'empty_state_json' => json_encode([]),
                    'meta_json' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Insert and map section keys => ids
            $sectionKeyToId = [];
            foreach ($sectionRows as $row) {
                $id = DB::table('app_sections')->insertGetId($row);
                $sectionKeyToId[$row['key']] = $id;
            }

            // 4) Items (minimal placeholders, so UI isn't blank)
            $items = [
                // Explore tools
                ['section_key' => 'explore_tools', 'type' => 'tool', 'title' => 'Quote Creator', 'subtitle' => null, 'icon' => 'quote', 'image_url' => null, 'route' => '/explore/quotes', 'url' => null, 'payload_json' => json_encode([]), 'sort_order' => 10, 'is_enabled' => 1],
                ['section_key' => 'explore_tools', 'type' => 'tool', 'title' => 'Notes',         'subtitle' => null, 'icon' => 'note',  'image_url' => null, 'route' => '/explore/notes',  'url' => null, 'payload_json' => json_encode([]), 'sort_order' => 20, 'is_enabled' => 1],
                ['section_key' => 'explore_tools', 'type' => 'tool', 'title' => 'Bible',         'subtitle' => null, 'icon' => 'bible', 'image_url' => null, 'route' => '/explore/bible',  'url' => null, 'payload_json' => json_encode([]), 'sort_order' => 30, 'is_enabled' => 1],

                // More
                ['section_key' => 'more_account', 'type' => 'link', 'title' => 'Profile',  'subtitle' => null, 'icon' => 'user', 'image_url' => null, 'route' => '/more/profile',  'url' => null, 'payload_json' => json_encode([]), 'sort_order' => 10, 'is_enabled' => 1],
                ['section_key' => 'more_account', 'type' => 'link', 'title' => 'Settings', 'subtitle' => null, 'icon' => 'cog',  'image_url' => null, 'route' => '/more/settings', 'url' => null, 'payload_json' => json_encode([]), 'sort_order' => 20, 'is_enabled' => 1],
            ];

            foreach ($items as $i) {
                $sectionId = $sectionKeyToId[$i['section_key']] ?? null;
                if (!$sectionId) {
                    continue;
                }

                DB::table('app_items')->insert([
                    'section_id'   => $sectionId,
                    'type'         => $i['type'],
                    'title'        => $i['title'],
                    'subtitle'     => $i['subtitle'],
                    'icon'         => $i['icon'],
                    'image_url'    => $i['image_url'],
                    'route'        => $i['route'],
                    'url'          => $i['url'],
                    'payload_json' => $i['payload_json'],
                    'sort_order'   => $i['sort_order'],
                    'is_enabled'   => $i['is_enabled'],
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        });
    }
}

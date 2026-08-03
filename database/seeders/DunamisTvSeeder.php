<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DunamisTvSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // Avoid duplicate seed runs
            $existing = DB::table('apps')->where('slug', 'dunamis-tv')->first();
            if ($existing) {
                return;
            }

            // 1) Create app
            $appId = DB::table('apps')->insertGetId([
                'name'        => 'Dunamis TV',
                'slug'        => 'dunamis-tv',
                'is_active'   => true,
                'api_token'   => Str::random(60),
                'branding_json' => json_encode([
                    'logo_url'        => null,
                    'banner_url'      => null,
                    'primary_color'   => '#0A1F44',
                    'accent_color'    => '#E11D48',
                    'gradient_preset' => 'navy-purple',
                ]),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            // 2) Tabs (editable)
            $tabs = [
                ['key' => 'home',    'title' => 'Home',    'icon' => 'heroicon-o-home'],
                ['key' => 'watch',   'title' => 'Watch',   'icon' => 'heroicon-o-play'],
                ['key' => 'inspire', 'title' => 'Inspire', 'icon' => 'heroicon-o-book-open'],
                ['key' => 'explore', 'title' => 'Explore', 'icon' => 'heroicon-o-squares-2x2'],
                ['key' => 'more',    'title' => 'More',    'icon' => 'heroicon-o-ellipsis-horizontal'],
            ];

            foreach ($tabs as $i => $tab) {
                DB::table('app_tabs')->insert([
                    'app_id'     => $appId,
                    'key'        => $tab['key'],
                    'title'      => $tab['title'],
                    'icon'       => $tab['icon'],
                    'sort_order' => $i,
                    'is_enabled' => true,
                    'meta_json'  => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 3) Routes (inner pages / registry)
            $routes = [
                // Watch
                ['key' => 'watch_live',   'title' => 'Live Stream',      'tab_key' => 'watch'],
                ['key' => 'watch_backup', 'title' => 'Backup Streams',   'tab_key' => 'watch'],

                // Inspire buckets
                ['key' => 'inspire_articles_list',       'title' => 'Articles',        'tab_key' => 'inspire'],
                ['key' => 'inspire_highlights_list',     'title' => 'Highlights',      'tab_key' => 'inspire'],
                ['key' => 'inspire_wordification_list',  'title' => 'Wordification',   'tab_key' => 'inspire'],
                ['key' => 'inspire_motivation_list',     'title' => 'Motivation',      'tab_key' => 'inspire'],
                ['key' => 'inspire_sod_list',            'title' => 'Seeds of Destiny','tab_key' => 'inspire'],

                // Explore tools
                ['key' => 'explore_quote_creator', 'title' => 'Quote Creator', 'tab_key' => 'explore'],
                ['key' => 'explore_notes',         'title' => 'Notes',         'tab_key' => 'explore'],
                ['key' => 'explore_bible',         'title' => 'Bible',         'tab_key' => 'explore'],
                ['key' => 'explore_entertainment', 'title' => 'Entertainment', 'tab_key' => 'explore'],
                ['key' => 'explore_games',         'title' => 'Games',         'tab_key' => 'explore'],

                // More
                ['key' => 'more_account',  'title' => 'Account',  'tab_key' => 'more'],
                ['key' => 'more_settings', 'title' => 'Settings', 'tab_key' => 'more'],
            ];

            foreach ($routes as $r) {
                DB::table('app_routes')->insert([
                    'app_id'     => $appId,
                    'key'        => $r['key'],
                    'title'      => $r['title'],
                    'route'      => null,
                    'tab_key'    => $r['tab_key'],
                    'is_enabled' => true,
                    'meta_json'  => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // 4) Global Ads Profile (unit IDs can be set later in admin UI)
            DB::table('ad_profiles')->insert([
                'app_id'               => $appId,
                'ads_enabled'          => true,
                'banner_unit_id'       => null,
                'native_unit_id'       => null,
                'interstitial_unit_id' => null,
                'meta_json'            => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);

            // 5) Default Ad Rules
            // Watch tab: OFF by default (locked requirement)
            DB::table('ad_rules')->insert([
                'app_id'                        => $appId,
                'scope_type'                    => 'tab',
                'scope_key'                     => 'watch',
                'is_enabled'                    => false,
                'banner_enabled'                => false,
                'native_enabled'                => false,
                'interstitial_enabled'          => false,
                'interstitial_cooldown_seconds' => 120,
                'created_at'                    => now(),
                'updated_at'                    => now(),
            ]);

            // All other tabs: ON (banner/native), interstitial optional later
            foreach (['home', 'inspire', 'explore', 'more'] as $tabKey) {
                DB::table('ad_rules')->insert([
                    'app_id'                        => $appId,
                    'scope_type'                    => 'tab',
                    'scope_key'                     => $tabKey,
                    'is_enabled'                    => true,
                    'banner_enabled'                => true,
                    'native_enabled'                => true,
                    'interstitial_enabled'          => false,
                    'interstitial_cooldown_seconds' => 120,
                    'created_at'                    => now(),
                    'updated_at'                    => now(),
                ]);
            }
        });
    }
}

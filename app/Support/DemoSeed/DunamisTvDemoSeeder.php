<?php

namespace App\Support\DemoSeed;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class DunamisTvDemoSeeder
{
    public static function run(int $appId): array
    {
        if (!Schema::hasTable('app_sections') || !Schema::hasTable('app_items')) {
            return [
                'ok' => false,
                'error' => 'MISSING_TABLES',
                'message' => 'app_sections/app_items tables not found.',
            ];
        }

        // Detect columns safely
        $sectionsCols = Schema::getColumnListing('app_sections');
        $itemsCols = Schema::getColumnListing('app_items');

        $hasSectionAppId   = in_array('app_id', $sectionsCols, true);
        $hasSectionKey     = in_array('key', $sectionsCols, true);
        $hasSectionTabKey  = in_array('tab_key', $sectionsCols, true);
        $hasSectionTitle   = in_array('title', $sectionsCols, true);
        $hasSectionSort    = in_array('sort_order', $sectionsCols, true);
        $hasSectionEnabled = in_array('is_enabled', $sectionsCols, true);
        $hasSectionTemplate = in_array('template', $sectionsCols, true);

        $hasItemSectionId = in_array('section_id', $itemsCols, true);
        $hasItemType      = in_array('type', $itemsCols, true);
        $hasItemTitle     = in_array('title', $itemsCols, true);
        $hasItemSubtitle  = in_array('subtitle', $itemsCols, true);
        $hasItemIcon      = in_array('icon', $itemsCols, true);
        $hasItemImageUrl  = in_array('image_url', $itemsCols, true);
        $hasItemRoute     = in_array('route', $itemsCols, true);
        $hasItemUrl       = in_array('url', $itemsCols, true);
        $hasItemPayload   = in_array('payload_json', $itemsCols, true);
        $hasItemSort      = in_array('sort_order', $itemsCols, true);
        $hasItemEnabled   = in_array('is_enabled', $itemsCols, true);

        if (!$hasSectionAppId || !$hasItemSectionId) {
            return [
                'ok' => false,
                'error' => 'SCHEMA_MISMATCH',
                'message' => 'Expected app_sections.app_id and app_items.section_id.',
            ];
        }

        // Optional: content_posts demo
        $hasContentPosts = Schema::hasTable('content_posts');
        $contentCols = $hasContentPosts ? Schema::getColumnListing('content_posts') : [];

        /**
         * Section templates (based on your DB’s existing templates)
         * - inspire_blocks should be grid (top featured grid feel)
         * - more_links should be vertical_list
         */
        $templateInspire = 'grid';
        $templateLinks   = 'vertical_list';

        // --- 1) Create / get Inspire section ---
        $inspireSection = self::upsertSection(
            appId: $appId,
            key: 'inspire_blocks',
            tabKey: 'inspire',
            title: 'Inspire Blocks',
            template: $templateInspire,
            sortOrder: 10,
            enabled: true,
            hasKey: $hasSectionKey,
            hasTabKey: $hasSectionTabKey,
            hasTitle: $hasSectionTitle,
            hasTemplate: $hasSectionTemplate,
            hasSort: $hasSectionSort,
            hasEnabled: $hasSectionEnabled
        );

        // --- 2) Create / get More links section ---
        $moreLinksSection = self::upsertSection(
            appId: $appId,
            key: 'more_links',
            tabKey: 'more',
            title: 'More Links',
            template: $templateLinks,
            sortOrder: 50,
            enabled: true,
            hasKey: $hasSectionKey,
            hasTabKey: $hasSectionTabKey,
            hasTitle: $hasSectionTitle,
            hasTemplate: $hasSectionTemplate,
            hasSort: $hasSectionSort,
            hasEnabled: $hasSectionEnabled
        );

        // Clear existing items in these sections (so re-seed keeps clean)
        DB::table('app_items')->where('section_id', $inspireSection['id'])->delete();
        DB::table('app_items')->where('section_id', $moreLinksSection['id'])->delete();

        // --- Inspire Blocks order (YOUR EXACT ORDER) ---
        // 1) Seed of Destiny list (inner: Read SOD, Watch SOD)
        // 2) Message Highlights list
        // 3) Wordification list
        // 4) Inside Dunamis list
        // 5) Motivation list

        $order = 0;

        // 1) Seed of Destiny (opens inner screen in Flutter)
        self::insertItem($inspireSection['id'], [
            'type' => 'hub_block',
            'title' => 'Seed of Destiny',
            'subtitle' => 'Daily devotional (Read / Watch)',
            'icon' => 'auto_stories',
            'image_url' => null,
            'route' => '/inspire/sod',
            'url' => null,
            'payload_json' => json_encode([
                'screen' => 'inspire_sod_hub',
                'key' => 'seed_of_destiny',
                'inner' => [
                    [
                        'title' => 'Read SOD',
                        'type' => 'web',
                        'route' => '/inspire/sod/read',
                        'url' => 'https://www.dunamisgospel.org/seed-of-destiny/'
                    ],
                    [
                        'title' => 'Watch SOD',
                        'type' => 'youtube',
                        'route' => '/inspire/sod/watch',
                        'url' => 'https://www.youtube.com/@drpaulenenche'
                    ]
                ]
            ]),
            'sort_order' => $order++,
            'is_enabled' => 1,
        ], compact(
            'hasItemType','hasItemTitle','hasItemSubtitle','hasItemIcon','hasItemImageUrl',
            'hasItemRoute','hasItemUrl','hasItemPayload','hasItemSort','hasItemEnabled'
        ));

        // 2) Message Highlights
        self::insertItem($inspireSection['id'], [
            'type' => 'content_list',
            'title' => 'Message Highlights',
            'subtitle' => 'Key takeaways & moments',
            'icon' => 'auto_awesome',
            'route' => '/inspire/highlights',
            'payload_json' => json_encode([
                'screen' => 'content_list',
                'bucket' => 'highlights',
                'title' => 'Message Highlights'
            ]),
            'sort_order' => $order++,
            'is_enabled' => 1,
        ], compact(
            'hasItemType','hasItemTitle','hasItemSubtitle','hasItemIcon','hasItemImageUrl',
            'hasItemRoute','hasItemUrl','hasItemPayload','hasItemSort','hasItemEnabled'
        ));

        // 3) Wordification
        self::insertItem($inspireSection['id'], [
            'type' => 'content_list',
            'title' => 'Wordification',
            'subtitle' => 'Short teachings & insights',
            'icon' => 'menu_book',
            'route' => '/inspire/wordification',
            'payload_json' => json_encode([
                'screen' => 'content_list',
                'bucket' => 'wordification',
                'title' => 'Wordification'
            ]),
            'sort_order' => $order++,
            'is_enabled' => 1,
        ], compact(
            'hasItemType','hasItemTitle','hasItemSubtitle','hasItemIcon','hasItemImageUrl',
            'hasItemRoute','hasItemUrl','hasItemPayload','hasItemSort','hasItemEnabled'
        ));

        // 4) Inside Dunamis
        self::insertItem($inspireSection['id'], [
            'type' => 'content_list',
            'title' => 'Inside Dunamis',
            'subtitle' => 'Updates, stories & behind-the-scenes',
            'icon' => 'church',
            'route' => '/inspire/inside-dunamis',
            'payload_json' => json_encode([
                'screen' => 'content_list',
                'bucket' => 'inside_dunamis',
                'title' => 'Inside Dunamis'
            ]),
            'sort_order' => $order++,
            'is_enabled' => 1,
        ], compact(
            'hasItemType','hasItemTitle','hasItemSubtitle','hasItemIcon','hasItemImageUrl',
            'hasItemRoute','hasItemUrl','hasItemPayload','hasItemSort','hasItemEnabled'
        ));

        // 5) Motivation
        self::insertItem($inspireSection['id'], [
            'type' => 'content_list',
            'title' => 'Motivation',
            'subtitle' => 'Faith, courage & inspiration',
            'icon' => 'bolt',
            'route' => '/inspire/motivation',
            'payload_json' => json_encode([
                'screen' => 'content_list',
                'bucket' => 'motivation',
                'title' => 'Motivation'
            ]),
            'sort_order' => $order++,
            'is_enabled' => 1,
        ], compact(
            'hasItemType','hasItemTitle','hasItemSubtitle','hasItemIcon','hasItemImageUrl',
            'hasItemRoute','hasItemUrl','hasItemPayload','hasItemSort','hasItemEnabled'
        ));

        // --- More Links ---
        $links = [
            ['Privacy Policy', 'Read our privacy policy', 'privacy_tip', 'privacy', 'https://admin.appshub.digitxtramedia.com/privacy'],
            ['Terms of Use', 'Read terms and conditions', 'gavel', 'terms', 'https://admin.appshub.digitxtramedia.com/terms'],
            ['Support', 'Help center & support', 'support_agent', 'support', 'https://admin.appshub.digitxtramedia.com/support'],
            ['Website', 'Visit official website', 'public', 'website', 'https://admin.appshub.digitxtramedia.com'],
        ];

        $order = 0;
        foreach ($links as $lnk) {
            self::insertItem($moreLinksSection['id'], [
                'type' => 'link',
                'title' => $lnk[0],
                'subtitle' => $lnk[1],
                'icon' => $lnk[2],
                'route' => '/more/' . $lnk[3],
                'url' => $lnk[4],
                'payload_json' => json_encode([
                    'screen' => 'web',
                    'title' => $lnk[0],
                    'url' => $lnk[4],
                    'key' => $lnk[3],
                ]),
                'sort_order' => $order++,
                'is_enabled' => 1,
            ], compact(
                'hasItemType','hasItemTitle','hasItemSubtitle','hasItemIcon','hasItemImageUrl',
                'hasItemRoute','hasItemUrl','hasItemPayload','hasItemSort','hasItemEnabled'
            ));
        }

        // --- Demo content_posts (for lists) ---
        if ($hasContentPosts && in_array('app_id', $contentCols, true) && in_array('bucket', $contentCols, true)) {
            self::seedContentPosts($appId, $contentCols);
        }

        return [
            'ok' => true,
            'message' => 'Dunamis demo content seeded.',
            'sections' => [
                'inspire' => $inspireSection,
                'more_links' => $moreLinksSection,
            ],
        ];
    }

    private static function upsertSection(
        int $appId,
        string $key,
        string $tabKey,
        string $title,
        string $template,
        int $sortOrder,
        bool $enabled,
        bool $hasKey,
        bool $hasTabKey,
        bool $hasTitle,
        bool $hasTemplate,
        bool $hasSort,
        bool $hasEnabled
    ): array {
        $qb = DB::table('app_sections')->where('app_id', $appId);

        /**
         * Find section deterministically without referencing columns that may not exist
         */
        if ($hasKey) {
            $qb->where('key', $key);
        } elseif ($hasTabKey) {
            $qb->where('tab_key', $tabKey);
            if ($hasTitle) {
                $qb->where('title', $title);
            }
        } elseif ($hasTitle) {
            $qb->where('title', $title);
        }

        $row = $qb->first();

        $data = ['app_id' => $appId];

        if ($hasKey) $data['key'] = $key;
        if ($hasTabKey) $data['tab_key'] = $tabKey;
        if ($hasTitle) $data['title'] = $title;
        if ($hasTemplate) $data['template'] = $template;
        if ($hasSort) $data['sort_order'] = $sortOrder;
        if ($hasEnabled) $data['is_enabled'] = $enabled ? 1 : 0;

        $now = now();
        if (Schema::hasColumn('app_sections', 'updated_at')) $data['updated_at'] = $now;
        if (Schema::hasColumn('app_sections', 'created_at')) $data['created_at'] = $now;

        if ($row) {
            DB::table('app_sections')->where('id', $row->id)->update($data);
            return [
                'id' => (int) $row->id,
                'key' => $key,
                'tab_key' => $tabKey,
                'template' => $template,
            ];
        }

        $id = (int) DB::table('app_sections')->insertGetId($data);

        return [
            'id' => $id,
            'key' => $key,
            'tab_key' => $tabKey,
            'template' => $template,
        ];
    }

    private static function insertItem(int $sectionId, array $data, array $flags): void
    {
        $row = ['section_id' => $sectionId];

        if ($flags['hasItemType'] && array_key_exists('type', $data)) $row['type'] = $data['type'];
        if ($flags['hasItemTitle'] && array_key_exists('title', $data)) $row['title'] = $data['title'];
        if ($flags['hasItemSubtitle'] && array_key_exists('subtitle', $data)) $row['subtitle'] = $data['subtitle'];
        if ($flags['hasItemIcon'] && array_key_exists('icon', $data)) $row['icon'] = $data['icon'];
        if ($flags['hasItemImageUrl'] && array_key_exists('image_url', $data)) $row['image_url'] = $data['image_url'];
        if ($flags['hasItemRoute'] && array_key_exists('route', $data)) $row['route'] = $data['route'];
        if ($flags['hasItemUrl'] && array_key_exists('url', $data)) $row['url'] = $data['url'];
        if ($flags['hasItemPayload'] && array_key_exists('payload_json', $data)) $row['payload_json'] = $data['payload_json'];
        if ($flags['hasItemSort'] && array_key_exists('sort_order', $data)) $row['sort_order'] = $data['sort_order'];
        if ($flags['hasItemEnabled'] && array_key_exists('is_enabled', $data)) $row['is_enabled'] = $data['is_enabled'];

        $now = now();
        if (Schema::hasColumn('app_items', 'updated_at')) $row['updated_at'] = $now;
        if (Schema::hasColumn('app_items', 'created_at')) $row['created_at'] = $now;

        DB::table('app_items')->insert($row);
    }

    private static function seedContentPosts(int $appId, array $contentCols): void
    {
        $buckets = ['highlights', 'wordification', 'inside_dunamis', 'motivation'];

        DB::table('content_posts')
            ->where('app_id', $appId)
            ->whereIn('bucket', $buckets)
            ->delete();

        $now = now();
        $rows = [];

        foreach ($buckets as $bucket) {
            for ($i = 1; $i <= 8; $i++) {
                $title = match ($bucket) {
                    'highlights' => "Message Highlight #{$i}",
                    'wordification' => "Wordification #{$i}",
                    'inside_dunamis' => "Inside Dunamis #{$i}",
                    'motivation' => "Motivation #{$i}",
                    default => "Post #{$i}",
                };

                $slug = Str::slug($title) . '-' . Str::lower(Str::random(6));

                $row = [];
                $row['app_id'] = $appId;
                $row['bucket'] = $bucket;

                if (in_array('title', $contentCols, true)) $row['title'] = $title;
                if (in_array('subtitle', $contentCols, true)) $row['subtitle'] = 'Demo content (replace from admin later)';
                if (in_array('slug', $contentCols, true)) $row['slug'] = $slug;
                if (in_array('status', $contentCols, true)) $row['status'] = 'published';
                if (in_array('is_featured', $contentCols, true)) $row['is_featured'] = ($i <= 2) ? 1 : 0;
                if (in_array('published_at', $contentCols, true)) $row['published_at'] = $now;
                if (in_array('publish_at', $contentCols, true)) $row['publish_at'] = $now;

                if (in_array('content_html', $contentCols, true)) {
                    $row['content_html'] = "<p><strong>{$title}</strong></p><p>This is demo content. Replace from AppsHub Admin.</p>";
                } elseif (in_array('body_html', $contentCols, true)) {
                    $row['body_html'] = "<p><strong>{$title}</strong></p><p>This is demo content. Replace from AppsHub Admin.</p>";
                }

                if (in_array('created_at', $contentCols, true)) $row['created_at'] = $now;
                if (in_array('updated_at', $contentCols, true)) $row['updated_at'] = $now;

                $rows[] = $row;
            }
        }

        DB::table('content_posts')->insert($rows);
    }
}

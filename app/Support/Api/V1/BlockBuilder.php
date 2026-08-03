<?php

namespace App\Support\Api\V1;

use Illuminate\Support\Facades\DB;

class BlockBuilder
{
    public function buildHub(string $appSlug, string $tabKey): array
    {
        $app = DB::table('apps')->where('slug', $appSlug)->first();
        if (!$app) return ['ok' => false];

        $sections = DB::table('app_sections')
            ->where('app_id', $app->id)
            ->where('tab_key', $tabKey)
            ->where('is_enabled', 1)
            ->orderBy('sort_order')
            ->get();

        $sectionIds = $sections->pluck('id');

        $items = DB::table('app_items')
            ->whereIn('section_id', $sectionIds)
            ->where('is_enabled', 1)
            ->orderBy('sort_order')
            ->get();

        $grouped = [];

        foreach ($items as $i) {
            $payload = json_decode($i->payload_json, true) ?? [];

            $grouped[$i->section_id][] = [
                'title' => $i->title,
                'subtitle' => $i->subtitle,
                'image_url' => $i->image_url,
                'url' => $i->url ?? ($payload['action']['url'] ?? ''),
                'type' => $payload['action']['type'] ?? '',
            ];
        }

        // ================= WATCH =================

        if ($tabKey === 'watch') {

            $cards = [];

            foreach ($sections as $s) {
                foreach ($grouped[$s->id] ?? [] as $item) {
                    $cards[] = $item;
                }
            }

            return [
                'ok' => true,
                'watch' => [
                    'cards' => $cards
                ]
            ];
        }

        // ================= HOME =================

        return [
            'ok' => true,
            'home' => []
        ];
    }
}

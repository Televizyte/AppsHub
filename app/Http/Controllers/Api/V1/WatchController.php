<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class WatchController extends Controller
{
    public function index(Request $request, string $appSlug)
    {
        $traceId = 'watch_' . bin2hex(random_bytes(6));

        try {
            $currentApp = $request->attributes->get('current_app');

            if ($currentApp instanceof App) {
                $app = (object) [
                    'id' => (int) $currentApp->id,
                    'name' => (string) $currentApp->name,
                    'slug' => (string) $currentApp->slug,
                    'is_active' => (bool) $currentApp->is_active,
                ];
            } else {
                $app = DB::table('apps')
                    ->select(['id', 'name', 'slug', 'is_active'])
                    ->where('slug', $appSlug)
                    ->where('is_active', 1)
                    ->first();
            }

            if (! $app) {
                return response()->json([
                    'ok' => false,
                    'error' => 'APP_NOT_FOUND',
                    'message' => 'App not found for slug: ' . $appSlug,
                    'trace_id' => $traceId,
                ], 404);
            }

            if (! Schema::hasTable('watch_links')) {
                return response()->json([
                    'ok' => false,
                    'error' => 'WATCH_TABLE_MISSING',
                    'message' => 'watch_links table not found.',
                    'trace_id' => $traceId,
                ], 500);
            }

            $cols = $this->tableColumns('watch_links');
            $includeDisabled = $this->truthy($request->query('include_disabled'));
            $scope = $this->detectScopeColumn($cols);

            $select = [];
            foreach (['id', 'group', 'title', 'type', 'url', 'is_enabled', 'sort_order', 'subtitle', 'thumbnail_url', 'meta_json'] as $c) {
                if (in_array($c, $cols, true)) {
                    $select[] = $c;
                }
            }
            if (empty($select)) {
                $select = ['id'];
            }

            $q = DB::table('watch_links')->select($select);

            if ($scope['col'] && $scope['mode'] === 'id') {
                $q->where($scope['col'], (int) $app->id);
            } elseif ($scope['col'] && $scope['mode'] === 'slug') {
                $q->where($scope['col'], (string) $app->slug);
            }

            if (! $includeDisabled && in_array('is_enabled', $cols, true)) {
                $q->where('is_enabled', 1);
            }

            if (in_array('sort_order', $cols, true)) {
                $q->orderBy('sort_order');
            }
            $q->orderBy('id');

            $items = [];
            $groups = [];
            $liveHls = null;
            $liveYoutube = null;
            $commandingDay = null;
            $primaryStreamUrl = null;

            foreach ($q->get() as $r) {
                $meta = null;
                if (in_array('meta_json', $cols, true)) {
                    $meta = $this->decodeMeta($r->meta_json ?? null);
                }
                $meta = is_array($meta) ? $meta : [];

                $rawType = (property_exists($r, 'type') && $r->type)
                    ? strtolower(trim((string) $r->type))
                    : '';
                $player = strtolower(trim((string) ($meta['player'] ?? '')));
                $title = property_exists($r, 'title') ? trim((string) $r->title) : '';
                $url = property_exists($r, 'url') ? trim((string) ($r->url ?? '')) : '';
                $subtitle = property_exists($r, 'subtitle')
                    ? trim((string) ($r->subtitle ?? ''))
                    : trim((string) ($meta['subtitle'] ?? ''));
                $imageUrl = property_exists($r, 'thumbnail_url')
                    ? trim((string) ($r->thumbnail_url ?? ''))
                    : trim((string) ($meta['image_url'] ?? ''));
                if ($imageUrl === '') {
                    $imageUrl = trim((string) ($meta['image_url'] ?? ''));
                }

                $type = $this->normalizeType($rawType, $player, $url);
                $groupKey = trim((string) ((property_exists($r, 'group') ? $r->group : null) ?? ($meta['group'] ?? '')));
                if ($groupKey === '') {
                    $groupKey = $this->inferGroupKey($rawType, $type, $title);
                }

                $item = [
                    'id' => isset($r->id) ? (int) $r->id : null,
                    'title' => $title !== '' ? $title : null,
                    'raw_type' => $rawType !== '' ? $rawType : null,
                    'type' => $type,
                    'url' => $url !== '' ? $url : null,
                    'enabled' => property_exists($r, 'is_enabled') ? (bool) $r->is_enabled : true,
                    'sort_order' => property_exists($r, 'sort_order') ? (int) $r->sort_order : 0,
                    'subtitle' => $subtitle !== '' ? $subtitle : null,
                    'image_url' => $imageUrl !== '' ? $imageUrl : null,
                    'group' => $groupKey !== '' ? $groupKey : null,
                    'meta' => !empty($meta) ? $meta : null,
                ];

                $items[] = $item;

                if ($title === '' || $url === '') {
                    continue;
                }

                $simple = [
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'type' => $type,
                    'url' => $url,
                    'image_url' => $imageUrl,
                ];

                if ($type === 'live_hls' && $liveHls === null) {
                    $liveHls = $simple;
                    $primaryStreamUrl = $url;
                    continue;
                }

                if ($type === 'live_youtube' && $liveYoutube === null) {
                    $liveYoutube = $simple;
                    continue;
                }

                if ($type === 'commanding_day' && $commandingDay === null) {
                    $commandingDay = $simple;
                    continue;
                }

                $groups[$groupKey] ??= [];
                $groups[$groupKey][] = $simple;
            }

            return response()->json([
                'ok' => true,
                'app' => [
                    'id' => (int) $app->id,
                    'name' => (string) $app->name,
                    'slug' => (string) $app->slug,
                ],
                'scope' => [
                    'tab' => 'watch',
                    'route_key' => 'watch',
                    'watch_links_scoped' => (bool) $scope['col'],
                    'watch_links_scope_column' => $scope['col'],
                    'watch_links_scope_mode' => $scope['mode'],
                ],
                'ads' => [
                    'screen' => [
                        'enabled' => false,
                        'banner_enabled' => false,
                        'native_enabled' => false,
                        'interstitial_enabled' => false,
                    ],
                    'native_in_list' => [
                        'enabled' => false,
                    ],
                ],
                'meta' => [
                    'api_version' => 'v1.1',
                    'screen' => 'watch',
                    'total_groups' => count($groups),
                    'total_items' => count($items),
                    'stable_groups' => ['live_hls', 'live_youtube', 'commanding_day', 'video', 'other_channels'],
                ],
                'primary_stream_url' => $primaryStreamUrl,
                'live_hls' => $liveHls,
                'live_youtube' => $liveYoutube,
                'commanding_day' => $commandingDay,
                'videos' => $groups['video'] ?? [],
                'other_channels' => $groups['other_channels'] ?? [],
                'groups' => $groups,
                'items' => $items,
                'trace_id' => $traceId,
            ]);
        } catch (Throwable $e) {
            Log::error('[WATCH_ENDPOINT_FAILED] ' . $traceId, [
                'appSlug' => $appSlug,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'ok' => false,
                'error' => 'SERVER_ERROR',
                'message' => 'Watch endpoint failed.',
                'trace_id' => $traceId,
            ], 500);
        }
    }

    private function normalizeType(string $rawType, string $player, string $url): string
    {
        $t = strtolower(trim($rawType));
        $p = strtolower(trim($player));
        $u = strtolower(trim($url));

        if ($t === 'commanding_day' || str_contains($t, 'commanding')) {
            return 'commanding_day';
        }

        if ($t === 'live_hls' || $p === 'hls' || str_ends_with($u, '.m3u8')) {
            return 'live_hls';
        }

        if ($t === 'live_youtube') {
            return 'live_youtube';
        }

        if ($t === 'channel') {
            return 'channel';
        }

        if (in_array($t, ['video', 'youtube_video', 'vod', 'playlist'], true)) {
            return 'video';
        }

        if ($p === 'youtube' || str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be')) {
            return 'youtube';
        }

        return $t !== '' ? $t : 'web';
    }

    private function inferGroupKey(string $rawType, string $type, string $title): string
    {
        $t = strtolower(trim($rawType));
        $s = strtolower(trim($title));

        if ($type === 'channel' || $t === 'channel') {
            return 'other_channels';
        }

        if ($type === 'video' || in_array($t, ['video', 'youtube_video', 'vod', 'playlist'], true)) {
            return 'video';
        }

        if ($type === 'commanding_day' || str_contains($s, 'commanding the day')) {
            return 'commanding_day';
        }

        if ($type === 'live_hls') {
            return 'live_hls';
        }

        if ($type === 'live_youtube') {
            return 'live_youtube';
        }

        return $type !== '' ? $type : 'other_channels';
    }

    private function detectScopeColumn(array $cols): array
    {
        if (in_array('app_id', $cols, true)) {
            return ['col' => 'app_id', 'mode' => 'id'];
        }

        foreach (['app_slug', 'slug', 'app_key'] as $c) {
            if (in_array($c, $cols, true)) {
                return ['col' => $c, 'mode' => 'slug'];
            }
        }

        return ['col' => null, 'mode' => null];
    }

    private function tableColumns(string $table): array
    {
        try {
            $rows = DB::select("SHOW COLUMNS FROM `{$table}`");

            return array_values(array_map(fn ($r) => $r->Field, $rows));
        } catch (Throwable $e) {
            return Schema::hasTable($table) ? Schema::getColumnListing($table) : [];
        }
    }

    private function decodeMeta($meta): ?array
    {
        if (is_array($meta)) {
            return $meta;
        }

        if (! is_string($meta) || trim($meta) === '') {
            return null;
        }

        $decoded = json_decode($meta, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function truthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return ((int) $value) === 1;
        }

        $v = strtolower(trim((string) $value));

        return in_array($v, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}

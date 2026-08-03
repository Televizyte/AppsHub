<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ContentPost;
use App\Models\ContentView;
use App\Models\WatchEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsController extends Controller
{
    public function summary(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        if (! $this->hasAnalyticsTables()) {
            return response()->json([
                'ok' => false,
                'error' => 'ANALYTICS_TABLES_MISSING',
                'message' => 'Analytics tables are not fully available.',
            ], 500);
        }

        $appId = (int) $app->id;

        $contentViewsTotal = (int) DB::table('content_views')
            ->where('app_id', $appId)
            ->count();

        $watchEventsTotal = (int) DB::table('watch_events')
            ->where('app_id', $appId)
            ->count();

        $watchPlayCount = (int) DB::table('watch_events')
            ->where('app_id', $appId)
            ->where('event_type', 'play')
            ->count();

        $watchCompleteCount = (int) DB::table('watch_events')
            ->where('app_id', $appId)
            ->where('event_type', 'complete')
            ->count();

        $contentUniqueUsers = (int) DB::table('content_views')
            ->where('app_id', $appId)
            ->whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');

        $contentUniqueSessions = (int) DB::table('content_views')
            ->where('app_id', $appId)
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->count('session_id');

        $watchUniqueSessions = (int) DB::table('watch_events')
            ->where('app_id', $appId)
            ->whereNotNull('session_id')
            ->distinct('session_id')
            ->count('session_id');

        return response()->json([
            'ok' => true,
            'app' => $this->appPayload($app),
            'meta' => [
                'api_version' => 'v1.1',
                'screen' => 'analytics_summary',
            ],
            'stats' => [
                'content_views_total' => $contentViewsTotal,
                'watch_events_total' => $watchEventsTotal,
                'watch_play_count' => $watchPlayCount,
                'watch_complete_count' => $watchCompleteCount,
                'content_unique_users' => $contentUniqueUsers,
                'content_unique_sessions' => $contentUniqueSessions,
                'watch_unique_sessions' => $watchUniqueSessions,
            ],
        ]);
    }

    public function topPosts(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        if (! Schema::hasTable('content_views')) {
            return response()->json([
                'ok' => false,
                'error' => 'CONTENT_VIEWS_TABLE_MISSING',
                'message' => 'content_views table not found.',
            ], 500);
        }

        $limit = min(50, max(1, (int) $request->query('limit', 10)));

        $rows = DB::table('content_views')
            ->leftJoin('content_posts', 'content_posts.id', '=', 'content_views.content_post_id')
            ->where('content_views.app_id', (int) $app->id)
            ->groupBy(
                'content_views.content_post_id',
                'content_posts.title',
                'content_posts.slug',
                'content_posts.bucket',
                'content_posts.cover_image_url'
            )
            ->selectRaw('
                content_views.content_post_id as post_id,
                content_posts.title as title,
                content_posts.slug as slug,
                content_posts.bucket as bucket,
                content_posts.cover_image_url as image_url,
                COUNT(*) as views_count
            ')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();

        $items = $rows->map(function ($row) use ($appSlug) {
            $slug = $row->slug ? (string) $row->slug : null;
            $postId = (int) ($row->post_id ?? 0);
            $idOrSlug = $slug ?: (string) $postId;

            return [
                'post_id' => $postId,
                'title' => (string) ($row->title ?? 'Untitled'),
                'slug' => $slug,
                'bucket' => $row->bucket ? (string) $row->bucket : null,
                'image_url' => $row->image_url ? (string) $row->image_url : null,
                'views_count' => (int) ($row->views_count ?? 0),
                'route' => '/content/' . $idOrSlug,
                'api_url' => "/api/v1/apps/{$appSlug}/content/{$idOrSlug}",
            ];
        })->values()->all();

        return response()->json([
            'ok' => true,
            'app' => $this->appPayload($app),
            'meta' => [
                'api_version' => 'v1.1',
                'screen' => 'analytics_top_posts',
                'limit' => $limit,
                'count' => count($items),
            ],
            'items' => $items,
        ]);
    }

    public function recentWatchActivity(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        if (! Schema::hasTable('watch_events')) {
            return response()->json([
                'ok' => false,
                'error' => 'WATCH_EVENTS_TABLE_MISSING',
                'message' => 'watch_events table not found.',
            ], 500);
        }

        $limit = min(100, max(1, (int) $request->query('limit', 12)));

        $rows = DB::table('watch_events')
            ->leftJoin('watch_links', 'watch_links.id', '=', 'watch_events.watch_link_id')
            ->leftJoin('content_posts', 'content_posts.id', '=', 'watch_events.content_post_id')
            ->leftJoin('users', 'users.id', '=', 'watch_events.user_id')
            ->where('watch_events.app_id', (int) $app->id)
            ->orderByDesc('watch_events.occurred_at')
            ->orderByDesc('watch_events.id')
            ->limit($limit)
            ->select([
                'watch_events.id',
                'watch_events.watch_link_id',
                'watch_events.content_post_id',
                'watch_events.user_id',
                'watch_events.event_type',
                'watch_events.session_id',
                'watch_events.source',
                'watch_events.platform',
                'watch_events.device_type',
                'watch_events.app_version',
                'watch_events.os_version',
                'watch_events.position_seconds',
                'watch_events.duration_seconds',
                'watch_events.progress_percent',
                'watch_events.occurred_at',
                'watch_links.title as watch_link_title',
                'content_posts.title as content_post_title',
                'content_posts.slug as content_post_slug',
                'users.name as user_name',
            ])
            ->get();

        $items = $rows->map(function ($row) use ($appSlug) {
            $targetTitle = $row->watch_link_title ?: $row->content_post_title ?: 'Unknown target';
            $contentPostSlug = $row->content_post_slug ? (string) $row->content_post_slug : null;
            $contentPostId = is_numeric($row->content_post_id) ? (int) $row->content_post_id : null;
            $contentIdOrSlug = $contentPostSlug ?: ($contentPostId ? (string) $contentPostId : null);

            return [
                'id' => (int) ($row->id ?? 0),
                'event_type' => (string) ($row->event_type ?? 'unknown'),
                'target' => [
                    'watch_link_id' => is_numeric($row->watch_link_id) ? (int) $row->watch_link_id : null,
                    'content_post_id' => $contentPostId,
                    'title' => (string) $targetTitle,
                    'content_route' => $contentIdOrSlug ? '/content/' . $contentIdOrSlug : null,
                    'content_api_url' => $contentIdOrSlug ? "/api/v1/apps/{$appSlug}/content/{$contentIdOrSlug}" : null,
                ],
                'user' => [
                    'id' => is_numeric($row->user_id) ? (int) $row->user_id : null,
                    'name' => $row->user_name ? (string) $row->user_name : null,
                ],
                'session_id' => $row->session_id ? (string) $row->session_id : null,
                'source' => $row->source ? (string) $row->source : null,
                'platform' => $row->platform ? (string) $row->platform : null,
                'device_type' => $row->device_type ? (string) $row->device_type : null,
                'app_version' => $row->app_version ? (string) $row->app_version : null,
                'os_version' => $row->os_version ? (string) $row->os_version : null,
                'position_seconds' => is_numeric($row->position_seconds) ? (int) $row->position_seconds : null,
                'duration_seconds' => is_numeric($row->duration_seconds) ? (int) $row->duration_seconds : null,
                'progress_percent' => is_numeric($row->progress_percent) ? (int) $row->progress_percent : null,
                'occurred_at' => $this->isoString($row->occurred_at),
            ];
        })->values()->all();

        return response()->json([
            'ok' => true,
            'app' => $this->appPayload($app),
            'meta' => [
                'api_version' => 'v1.1',
                'screen' => 'analytics_recent_watch_activity',
                'limit' => $limit,
                'count' => count($items),
            ],
            'items' => $items,
        ]);
    }

    public function trackContentView(Request $request, string $appSlug, string $idOrSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        if (! Schema::hasTable('content_views')) {
            return response()->json([
                'ok' => false,
                'error' => 'CONTENT_VIEWS_TABLE_MISSING',
                'message' => 'content_views table not found.',
            ], 500);
        }

        $post = $this->resolvePost((int) $app->id, $idOrSlug);
        if (! $post) {
            return response()->json([
                'ok' => false,
                'error' => 'POST_NOT_FOUND',
                'message' => 'Content post not found.',
            ], 404);
        }

        $data = $request->validate([
            'session_id' => ['nullable', 'string', 'max:120'],
            'source' => ['nullable', 'string', 'max:60'],
            'platform' => ['nullable', 'string', 'max:30'],
            'device_type' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'os_version' => ['nullable', 'string', 'max:40'],
            'viewed_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        $row = ContentView::query()->create([
            'app_id' => (int) $app->id,
            'content_post_id' => (int) $post->id,
            'user_id' => $user ? (int) $user->id : null,
            'session_id' => $data['session_id'] ?? null,
            'source' => $data['source'] ?? 'flutter',
            'platform' => $data['platform'] ?? null,
            'device_type' => $data['device_type'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'os_version' => $data['os_version'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta_json' => $data['meta'] ?? null,
            'viewed_at' => $data['viewed_at'] ?? now(),
        ]);

        $viewsCount = (int) ContentView::query()
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id)
            ->count();

        return response()->json([
            'ok' => true,
            'app' => $this->appPayload($app),
            'post_id' => (int) $post->id,
            'views_count' => $viewsCount,
            'item' => [
                'id' => (int) $row->id,
                'viewed_at' => optional($row->viewed_at)->toISOString(),
            ],
        ], 201);
    }

    public function trackWatchEvent(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        if (! Schema::hasTable('watch_events')) {
            return response()->json([
                'ok' => false,
                'error' => 'WATCH_EVENTS_TABLE_MISSING',
                'message' => 'watch_events table not found.',
            ], 500);
        }

        $data = $request->validate([
            'watch_link_id' => ['nullable', 'integer'],
            'content_post_id' => ['nullable', 'integer'],
            'session_id' => ['nullable', 'string', 'max:120'],
            'event_type' => ['required', 'string', 'in:open,play,pause,progress,heartbeat,complete,exit'],
            'source' => ['nullable', 'string', 'max:60'],
            'platform' => ['nullable', 'string', 'max:30'],
            'position_seconds' => ['nullable', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'progress_percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'device_type' => ['nullable', 'string', 'max:30'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'os_version' => ['nullable', 'string', 'max:40'],
            'occurred_at' => ['nullable', 'date'],
            'meta' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        $row = WatchEvent::query()->create([
            'app_id' => (int) $app->id,
            'watch_link_id' => $data['watch_link_id'] ?? null,
            'content_post_id' => $data['content_post_id'] ?? null,
            'user_id' => $user ? (int) $user->id : null,
            'session_id' => $data['session_id'] ?? null,
            'event_type' => $data['event_type'],
            'source' => $data['source'] ?? 'flutter',
            'platform' => $data['platform'] ?? null,
            'position_seconds' => $data['position_seconds'] ?? null,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'progress_percent' => $data['progress_percent'] ?? null,
            'device_type' => $data['device_type'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'os_version' => $data['os_version'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'meta_json' => $data['meta'] ?? null,
            'occurred_at' => $data['occurred_at'] ?? now(),
        ]);

        return response()->json([
            'ok' => true,
            'app' => $this->appPayload($app),
            'item' => [
                'id' => (int) $row->id,
                'event_type' => (string) $row->event_type,
                'occurred_at' => optional($row->occurred_at)->toISOString(),
            ],
        ], 201);
    }

    private function resolveApp(Request $request, string $appSlug): ?App
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            return $currentApp;
        }

        $legacyApp = $request->attributes->get('app');
        if ($legacyApp instanceof App) {
            return $legacyApp;
        }

        return App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->first();
    }

    private function resolvePost(int $appId, string $idOrSlug): ?ContentPost
    {
        return ContentPost::query()
            ->where('app_id', $appId)
            ->where(function ($q) use ($idOrSlug) {
                if (ctype_digit($idOrSlug)) {
                    $q->where('id', (int) $idOrSlug)
                        ->orWhere('slug', $idOrSlug);
                } else {
                    $q->where('slug', $idOrSlug);
                }
            })
            ->first();
    }

    private function hasAnalyticsTables(): bool
    {
        return Schema::hasTable('content_views') && Schema::hasTable('watch_events');
    }

    private function appPayload(App $app): array
    {
        return [
            'id' => (int) $app->id,
            'name' => (string) $app->name,
            'slug' => (string) $app->slug,
        ];
    }

    private function isoString($value): ?string
    {
        if ($value instanceof \Carbon\CarbonInterface) {
            return $value->toISOString();
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return now()->parse($value)->toISOString();
            } catch (\Throwable $e) {
                return trim($value);
            }
        }

        return null;
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedReaction;
use App\Models\FeedReport;
use App\Models\FeedSave;
use App\Models\FeedSetting;
use App\Models\FeedShare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FeedController extends Controller
{
    public function index(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        if (! Schema::hasTable('feed_posts')) {
            return response()->json([
                'ok' => false,
                'error' => 'FEED_TABLE_MISSING',
                'message' => 'Feed Engine tables are not installed.',
            ], 500);
        }

        $settings = $this->settingsFor((int) $app->id);
        if (! ($settings['is_enabled'] ?? true)) {
            return response()->json([
                'ok' => true,
                'app' => $this->shapeApp($app),
                'settings' => $settings,
                'buckets' => $this->bucketOptions(),
                'items' => [],
                'data' => [],
                'meta' => [
                    'screen' => 'community_feed',
                    'feed_enabled' => false,
                ],
            ]);
        }

        $bucket = $this->cleanString($request->query('bucket'));
        $filter = $this->cleanString($request->query('filter', 'all')) ?: 'all';
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));
        $page = max(1, (int) $request->query('page', 1));
        $viewer = $request->user();

        $qb = FeedPost::query()
            ->where('app_id', (int) $app->id)
            ->where('status', FeedPost::STATUS_PUBLISHED)
            ->where('approval_status', FeedPost::APPROVAL_APPROVED)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });

        if ($bucket && $bucket !== 'all') {
            $qb->where('bucket', $bucket);
        }

        if ($filter === 'live') {
            $qb->whereIn('post_type', ['live', 'live_broadcast']);
        } elseif ($filter === 'trending') {
            $qb->withCount([
                'reactions as likes_count' => fn ($q) => $q->where('type', 'like'),
                'comments as comments_count' => fn ($q) => $q->where('status', FeedComment::STATUS_PUBLISHED),
                'shares as shares_count',
                'saves as saves_count',
            ])->orderByRaw('(likes_count + comments_count * 2 + shares_count * 2 + saves_count) desc');
        }

        $qb->orderByDesc('is_pinned')
            ->orderByDesc('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('id');

        $total = (clone $qb)->count();
        $rows = $qb->forPage($page, $perPage)->get();

        $items = $rows->map(fn (FeedPost $post): array => $this->shapePost($post, $viewer))->values()->all();

        return response()->json([
            'ok' => true,
            'app' => $this->shapeApp($app),
            'settings' => $settings,
            'scope' => [
                'bucket' => $bucket ?: 'all',
                'filter' => $filter,
            ],
            'buckets' => $this->bucketOptions(),
            'filters' => $this->filterOptions(),
            'meta' => [
                'api_version' => 'v1.feed.1',
                'screen' => 'community_feed',
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil(max(1, $total) / $perPage),
            ],
            'items' => $items,
            'data' => $items,
        ]);
    }

    public function show(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        $post = FeedPost::query()
            ->where('app_id', (int) $app->id)
            ->whereKey($feedPost)
            ->first();

        if (! $post || ($post->status !== FeedPost::STATUS_PUBLISHED && ! $request->user())) {
            return response()->json([
                'ok' => false,
                'error' => 'FEED_POST_NOT_FOUND',
                'message' => 'Feed post not found.',
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'app' => $this->shapeApp($app),
            'item' => $this->shapePost($post, $request->user()),
            'data' => $this->shapePost($post, $request->user()),
        ]);
    }

    public function comments(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        $post = $this->resolvePost((int) $app->id, $feedPost);
        if (! $post) {
            return $this->postNotFound();
        }

        $items = FeedComment::query()
            ->with(['user:id,name,email'])
            ->where('app_id', (int) $app->id)
            ->where('feed_post_id', (int) $post->id)
            ->where('status', FeedComment::STATUS_PUBLISHED)
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(fn (FeedComment $comment): array => $this->shapeComment($comment))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'post_id' => (int) $post->id,
            'items' => $items,
            'data' => $items,
        ]);
    }

    public function storeComment(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }

        $post = $this->resolvePost((int) $app->id, $feedPost);
        if (! $post) {
            return $this->postNotFound();
        }

        $settings = $this->settingsFor((int) $app->id);
        if (! ($settings['comments_enabled'] ?? true)) {
            return response()->json([
                'ok' => false,
                'error' => 'COMMENTS_DISABLED',
                'message' => 'Comments are disabled for this feed.',
            ], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:3000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $status = ($settings['comments_require_approval'] ?? false)
            ? FeedComment::STATUS_PENDING
            : FeedComment::STATUS_PUBLISHED;

        $comment = FeedComment::query()->create([
            'app_id' => (int) $app->id,
            'feed_post_id' => (int) $post->id,
            'user_id' => (int) $user->id,
            'parent_id' => $data['parent_id'] ?? null,
            'body' => trim((string) $data['body']),
            'status' => $status,
        ]);

        $comment->load(['user:id,name,email']);

        return response()->json([
            'ok' => true,
            'requires_approval' => $status === FeedComment::STATUS_PENDING,
            'item' => $this->shapeComment($comment),
            'counts' => $this->countsFor($post),
        ], 201);
    }

    public function like(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }
        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }
        $post = $this->resolvePost((int) $app->id, $feedPost);
        if (! $post) {
            return $this->postNotFound();
        }

        FeedReaction::query()->firstOrCreate([
            'app_id' => (int) $app->id,
            'feed_post_id' => (int) $post->id,
            'user_id' => (int) $user->id,
            'type' => 'like',
        ]);

        return response()->json([
            'ok' => true,
            'liked_by_me' => true,
            'counts' => $this->countsFor($post),
        ]);
    }

    public function unlike(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }
        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }
        $post = $this->resolvePost((int) $app->id, $feedPost);
        if (! $post) {
            return $this->postNotFound();
        }

        FeedReaction::query()
            ->where('app_id', (int) $app->id)
            ->where('feed_post_id', (int) $post->id)
            ->where('user_id', (int) $user->id)
            ->where('type', 'like')
            ->delete();

        return response()->json([
            'ok' => true,
            'liked_by_me' => false,
            'counts' => $this->countsFor($post),
        ]);
    }

    public function save(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }
        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }
        $post = $this->resolvePost((int) $app->id, $feedPost);
        if (! $post) {
            return $this->postNotFound();
        }

        FeedSave::query()->firstOrCreate([
            'app_id' => (int) $app->id,
            'feed_post_id' => (int) $post->id,
            'user_id' => (int) $user->id,
        ]);

        return response()->json([
            'ok' => true,
            'saved_by_me' => true,
            'counts' => $this->countsFor($post),
        ]);
    }

    public function unsave(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }
        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }
        $post = $this->resolvePost((int) $app->id, $feedPost);
        if (! $post) {
            return $this->postNotFound();
        }

        FeedSave::query()
            ->where('app_id', (int) $app->id)
            ->where('feed_post_id', (int) $post->id)
            ->where('user_id', (int) $user->id)
            ->delete();

        return response()->json([
            'ok' => true,
            'saved_by_me' => false,
            'counts' => $this->countsFor($post),
        ]);
    }

    public function share(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }
        $post = $this->resolvePost((int) $app->id, $feedPost);
        if (! $post) {
            return $this->postNotFound();
        }

        FeedShare::query()->create([
            'app_id' => (int) $app->id,
            'feed_post_id' => (int) $post->id,
            'user_id' => $request->user()?->id,
            'channel' => $this->cleanString($request->input('channel', 'app')) ?: 'app',
            'meta_json' => [
                'source' => 'api',
            ],
        ]);

        return response()->json([
            'ok' => true,
            'counts' => $this->countsFor($post),
        ]);
    }

    public function report(Request $request, string $appSlug, int $feedPost)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }
        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }
        $post = $this->resolvePost((int) $app->id, $feedPost);
        if (! $post) {
            return $this->postNotFound();
        }

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:120'],
            'details' => ['nullable', 'string', 'max:2000'],
            'comment_id' => ['nullable', 'integer'],
        ]);

        FeedReport::query()->create([
            'app_id' => (int) $app->id,
            'feed_post_id' => (int) $post->id,
            'feed_comment_id' => $data['comment_id'] ?? null,
            'user_id' => (int) $user->id,
            'reason' => $data['reason'] ?? 'reported',
            'details' => $data['details'] ?? null,
            'status' => FeedReport::STATUS_OPEN,
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Report submitted.',
        ], 201);
    }

    private function resolveApp(Request $request, string $appSlug): ?App
    {
        $currentApp = $request->attributes->get('current_app');
        if ($currentApp instanceof App) {
            return $currentApp;
        }

        return App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->first();
    }

    private function resolvePost(int $appId, int $postId): ?FeedPost
    {
        return FeedPost::query()
            ->where('app_id', $appId)
            ->where('status', FeedPost::STATUS_PUBLISHED)
            ->where('approval_status', FeedPost::APPROVAL_APPROVED)
            ->where(function ($q) {
                $q->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->whereKey($postId)
            ->first();
    }

    private function settingsFor(int $appId): array
    {
        if (! Schema::hasTable('feed_settings')) {
            return $this->defaultSettings();
        }

        $settings = FeedSetting::query()->firstOrCreate(
            ['app_id' => $appId],
            $this->defaultSettings()
        );

        return array_merge($this->defaultSettings(), $settings->toArray());
    }

    private function defaultSettings(): array
    {
        return [
            'is_enabled' => true,
            'auto_approve_system_posts' => true,
            'auto_approve_tool_posts' => false,
            'auto_approve_text_posts' => false,
            'comments_enabled' => true,
            'comments_require_approval' => false,
            'user_text_posts_enabled' => false,
            'external_media_uploads_enabled' => false,
        ];
    }

    private function shapePost(FeedPost $post, $viewer = null): array
    {
        $counts = $this->countsFor($post);
        $viewerId = $viewer ? (int) $viewer->id : 0;

        $liked = false;
        $saved = false;
        if ($viewerId > 0) {
            $liked = FeedReaction::query()
                ->where('feed_post_id', (int) $post->id)
                ->where('user_id', $viewerId)
                ->where('type', 'like')
                ->exists();
            $saved = FeedSave::query()
                ->where('feed_post_id', (int) $post->id)
                ->where('user_id', $viewerId)
                ->exists();
        }

        return [
            'id' => (int) $post->id,
            'app_id' => (int) $post->app_id,
            'bucket' => (string) $post->bucket,
            'post_type' => (string) $post->post_type,
            'source_engine' => $post->source_engine,
            'source_id' => $post->source_id,
            'title' => $post->title,
            'body' => $post->body,
            'excerpt' => $post->excerpt ?: Str::limit(strip_tags((string) $post->body), 160),
            'thumbnail_url' => $post->thumbnail_url,
            'media_url' => $post->media_url,
            'deep_link' => $post->deep_link,
            'cta_label' => $post->cta_label ?: $this->defaultCta((string) $post->post_type),
            'status' => $post->status,
            'approval_status' => $post->approval_status,
            'visibility' => $post->visibility,
            'is_pinned' => (bool) $post->is_pinned,
            'is_featured' => (bool) $post->is_featured,
            'published_at' => optional($post->published_at ?: $post->created_at)->toIso8601String(),
            'created_by_type' => $post->created_by_type,
            'created_by_id' => $post->created_by_id,
            'meta' => $post->meta_json ?: [],
            'counts' => $counts,
            'likes_count' => $counts['likes'],
            'comments_count' => $counts['comments'],
            'shares_count' => $counts['shares'],
            'saves_count' => $counts['saves'],
            'liked_by_me' => $liked,
            'saved_by_me' => $saved,
        ];
    }

    private function shapeComment(FeedComment $comment): array
    {
        return [
            'id' => (int) $comment->id,
            'feed_post_id' => (int) $comment->feed_post_id,
            'parent_id' => $comment->parent_id ? (int) $comment->parent_id : null,
            'body' => $comment->body,
            'status' => $comment->status,
            'created_at' => optional($comment->created_at)->toIso8601String(),
            'user' => $comment->user ? [
                'id' => (int) $comment->user->id,
                'name' => $comment->user->name,
            ] : null,
        ];
    }

    private function countsFor(FeedPost $post): array
    {
        return [
            'likes' => FeedReaction::query()->where('feed_post_id', (int) $post->id)->where('type', 'like')->count(),
            'comments' => FeedComment::query()->where('feed_post_id', (int) $post->id)->where('status', FeedComment::STATUS_PUBLISHED)->count(),
            'shares' => FeedShare::query()->where('feed_post_id', (int) $post->id)->count(),
            'saves' => FeedSave::query()->where('feed_post_id', (int) $post->id)->count(),
            'reports' => FeedReport::query()->where('feed_post_id', (int) $post->id)->count(),
        ];
    }

    private function bucketOptions(): array
    {
        return [
            ['key' => 'all', 'label' => 'All'],
            ['key' => 'announcements', 'label' => 'Announcements'],
            ['key' => 'articles', 'label' => 'Articles'],
            ['key' => 'quotes', 'label' => 'Quotes'],
            ['key' => 'short_videos', 'label' => 'Short Videos'],
            ['key' => 'videos', 'label' => 'Videos'],
            ['key' => 'books', 'label' => 'Books'],
            ['key' => 'daily_scripture', 'label' => 'Daily Scripture'],
            ['key' => 'bible_quiz', 'label' => 'Bible Quiz'],
            ['key' => 'game_achievements', 'label' => 'Achievements'],
            ['key' => 'live_broadcast', 'label' => 'Live'],
            ['key' => 'events', 'label' => 'Events'],
            ['key' => 'testimonies', 'label' => 'Testimonies'],
            ['key' => 'devotional', 'label' => 'Devotional'],
            ['key' => 'user_activity', 'label' => 'User Activity'],
            ['key' => 'admin_posts', 'label' => 'Admin Posts'],
        ];
    }

    private function filterOptions(): array
    {
        return [
            ['key' => 'all', 'label' => 'All'],
            ['key' => 'latest', 'label' => 'Latest'],
            ['key' => 'trending', 'label' => 'Trending'],
            ['key' => 'live', 'label' => 'Live'],
        ];
    }

    private function defaultCta(string $postType): string
    {
        return match ($postType) {
            'article' => 'Read Article',
            'short_video' => 'Watch Short',
            'video' => 'Watch Video',
            'quote', 'daily_scripture' => 'Open',
            'book' => 'Read Book',
            'live', 'live_broadcast' => 'Watch Now',
            'quiz_result', 'bible_quiz' => 'Open Quiz',
            'game_achievement' => 'Play',
            default => 'Open',
        };
    }

    private function cleanString($value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = Str::of((string) $value)->trim()->lower()->replace(' ', '_')->toString();
        return $value !== '' ? $value : null;
    }

    private function shapeApp(App $app): array
    {
        return [
            'id' => (int) $app->id,
            'name' => (string) $app->name,
            'slug' => (string) $app->slug,
        ];
    }

    private function appNotFound()
    {
        return response()->json([
            'ok' => false,
            'error' => 'APP_NOT_FOUND',
            'message' => 'App not found or inactive.',
        ], 404);
    }

    private function postNotFound()
    {
        return response()->json([
            'ok' => false,
            'error' => 'FEED_POST_NOT_FOUND',
            'message' => 'Feed post not found.',
        ], 404);
    }

    private function unauthenticated()
    {
        return response()->json([
            'ok' => false,
            'error' => 'UNAUTHENTICATED',
            'message' => 'Authentication required.',
        ], 401);
    }
}

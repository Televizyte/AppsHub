<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ContentPost;
use App\Models\ContentPostComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModerationController extends Controller
{
    public function summary(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }

        $postIds = $this->moderatablePostIds((int) $app->id, (int) $user->id);

        $base = ContentPostComment::query()
            ->where('app_id', (int) $app->id)
            ->whereIn('content_post_id', $postIds);

        $stats = [
            'total_comments' => (clone $base)->count(),
            'published_comments' => (clone $base)->where('status', ContentPostComment::STATUS_PUBLISHED)->count(),
            'pending_comments' => (clone $base)->where('status', ContentPostComment::STATUS_PENDING)->count(),
            'hidden_comments' => (clone $base)->where('status', ContentPostComment::STATUS_HIDDEN)->count(),
            'distinct_posts_with_comments' => (clone $base)->distinct('content_post_id')->count('content_post_id'),
        ];

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'meta' => [
                'api_version' => 'v1.1',
                'screen' => 'moderation_summary',
            ],
            'stats' => $stats,
        ]);
    }

    public function comments(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }

        $status = $this->cleanStatus($request->query('status'));
        $search = $this->cleanString($request->query('search'));
        $postId = $request->query('post_id');
        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));
        $page = max(1, (int) $request->query('page', 1));

        $postIds = $this->moderatablePostIds((int) $app->id, (int) $user->id);

        $qb = ContentPostComment::query()
            ->with([
                'user:id,name,email',
                'post:id,title,slug,bucket,author_user_id',
            ])
            ->where('app_id', (int) $app->id)
            ->whereIn('content_post_id', $postIds);

        if ($status) {
            $qb->where('status', $status);
        }

        if (is_numeric($postId)) {
            $qb->where('content_post_id', (int) $postId);
        }

        if ($search) {
            $qb->where(function ($q) use ($search) {
                $q->where('body', 'like', '%' . $search . '%')
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', '%' . $search . '%')
                            ->orWhere('email', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('post', function ($pq) use ($search) {
                        $pq->where('title', 'like', '%' . $search . '%')
                            ->orWhere('slug', 'like', '%' . $search . '%');
                    });
            });
        }

        $total = (clone $qb)->count();

        $rows = $qb
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get();

        $items = $rows->map(function (ContentPostComment $comment) {
            return $this->shapeModerationComment($comment);
        })->values()->all();

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'meta' => [
                'api_version' => 'v1.1',
                'screen' => 'moderation_comments',
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => (int) ceil(max(1, $total) / $perPage),
                'status' => $status,
                'search' => $search,
            ],
            'items' => $items,
        ]);
    }

    public function activity(Request $request, string $appSlug)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }

        $limit = min(50, max(1, (int) $request->query('limit', 12)));
        $postIds = $this->moderatablePostIds((int) $app->id, (int) $user->id);

        $rows = ContentPostComment::query()
            ->with([
                'user:id,name,email',
                'post:id,title,slug,bucket,author_user_id',
            ])
            ->where('app_id', (int) $app->id)
            ->whereIn('content_post_id', $postIds)
            ->where(function ($q) {
                $q->where('status', ContentPostComment::STATUS_HIDDEN)
                    ->orWhere('status', ContentPostComment::STATUS_PENDING);
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        $items = $rows->map(function (ContentPostComment $comment) {
            return [
                'id' => (int) $comment->id,
                'status' => (string) $comment->status,
                'body' => (string) $comment->body,
                'user' => [
                    'id' => (int) ($comment->user?->id ?? 0),
                    'name' => (string) ($comment->user?->name ?? 'Unknown'),
                ],
                'post' => [
                    'id' => (int) ($comment->post?->id ?? 0),
                    'title' => (string) ($comment->post?->title ?? 'Untitled'),
                    'slug' => $comment->post?->slug ? (string) $comment->post->slug : null,
                    'bucket' => $comment->post?->bucket ? (string) $comment->post->bucket : null,
                ],
                'created_at' => $comment->created_at?->toISOString(),
                'updated_at' => $comment->updated_at?->toISOString(),
            ];
        })->values()->all();

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'meta' => [
                'api_version' => 'v1.1',
                'screen' => 'moderation_activity',
                'limit' => $limit,
                'count' => count($items),
            ],
            'items' => $items,
        ]);
    }

    public function updateStatus(Request $request, string $appSlug, int $commentId)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', ContentPostComment::allowedStatuses())],
        ]);

        $comment = ContentPostComment::query()
            ->with([
                'user:id,name,email',
                'post:id,title,slug,bucket,author_user_id',
            ])
            ->where('app_id', (int) $app->id)
            ->where('id', $commentId)
            ->first();

        if (! $comment) {
            return response()->json([
                'ok' => false,
                'error' => 'COMMENT_NOT_FOUND',
                'message' => 'Comment not found.',
            ], 404);
        }

        if (! $comment->post || (int) $comment->post->author_user_id !== (int) $user->id) {
            return response()->json([
                'ok' => false,
                'error' => 'FORBIDDEN',
                'message' => 'You are not allowed to moderate this comment.',
            ], 403);
        }

        $comment->status = (string) $data['status'];
        $comment->save();

        return response()->json([
            'ok' => true,
            'item' => $this->shapeModerationComment($comment->fresh(['user:id,name,email', 'post:id,title,slug,bucket,author_user_id'])),
        ]);
    }

    public function destroy(Request $request, string $appSlug, int $commentId)
    {
        $app = $this->resolveApp($request, $appSlug);
        if (! $app) {
            return $this->appNotFound();
        }

        $user = $request->user();
        if (! $user) {
            return $this->unauthenticated();
        }

        $comment = ContentPostComment::query()
            ->with([
                'user:id,name,email',
                'post:id,title,slug,bucket,author_user_id',
            ])
            ->where('app_id', (int) $app->id)
            ->where('id', $commentId)
            ->first();

        if (! $comment) {
            return response()->json([
                'ok' => false,
                'error' => 'COMMENT_NOT_FOUND',
                'message' => 'Comment not found.',
            ], 404);
        }

        $isCommentOwner = (int) $comment->user_id === (int) $user->id;
        $isPostOwner = $comment->post && (int) $comment->post->author_user_id === (int) $user->id;

        if (! $isCommentOwner && ! $isPostOwner) {
            return response()->json([
                'ok' => false,
                'error' => 'FORBIDDEN',
                'message' => 'You are not allowed to delete this comment.',
            ], 403);
        }

        $deletedId = (int) $comment->id;
        $comment->delete();

        return response()->json([
            'ok' => true,
            'deleted' => true,
            'comment_id' => $deletedId,
        ]);
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

    private function moderatablePostIds(int $appId, int $userId): array
    {
        return ContentPost::query()
            ->where('app_id', $appId)
            ->where('author_user_id', $userId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function shapeModerationComment(ContentPostComment $comment): array
    {
        return [
            'id' => (int) $comment->id,
            'body' => (string) $comment->body,
            'status' => (string) $comment->status,
            'user' => [
                'id' => (int) ($comment->user?->id ?? 0),
                'name' => (string) ($comment->user?->name ?? 'Unknown'),
                'email' => $comment->user?->email ? (string) $comment->user->email : null,
            ],
            'post' => [
                'id' => (int) ($comment->post?->id ?? 0),
                'title' => (string) ($comment->post?->title ?? 'Untitled'),
                'slug' => $comment->post?->slug ? (string) $comment->post->slug : null,
                'bucket' => $comment->post?->bucket ? (string) $comment->post->bucket : null,
                'route' => $comment->post?->slug ? '/content/' . $comment->post->slug : null,
            ],
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
        ];
    }

    private function cleanStatus($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = strtolower(trim($value));

        return in_array($value, ContentPostComment::allowedStatuses(), true) ? $value : null;
    }

    private function cleanString($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function appNotFound()
    {
        return response()->json([
            'ok' => false,
            'error' => 'APP_NOT_FOUND',
            'message' => 'App not found or inactive.',
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

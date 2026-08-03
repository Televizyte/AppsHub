<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ContentPost;
use App\Models\ContentPostComment;
use Illuminate\Http\Request;

class PostCommentController extends Controller
{
    public function index(Request $request, string $appSlug, string $idOrSlug)
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            $app = $currentApp;
        } else {
            $app = App::query()
                ->where('slug', $appSlug)
                ->where('is_active', true)
                ->first();
        }

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        $viewer = $request->user();

        $post = $this->resolvePost((int) $app->id, $idOrSlug);
        if (! $post) {
            return response()->json([
                'ok' => false,
                'error' => 'POST_NOT_FOUND',
                'message' => 'Content post not found.',
            ], 404);
        }

        $includeAll = $this->truthy($request->query('include_all'));
        $canModeratePost = $this->canModeratePost($viewer, $post);

        $qb = ContentPostComment::query()
            ->with(['user:id,name,email'])
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id);

        if (! $includeAll || ! $canModeratePost) {
            $qb->where('status', ContentPostComment::STATUS_PUBLISHED);
        }

        $comments = $qb
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function (ContentPostComment $comment) use ($viewer, $post) {
                return $this->shapeComment($comment, $viewer, $post);
            })
            ->values();

        return response()->json([
            'ok' => true,
            'post_id' => (int) $post->id,
            'comments_count' => $comments->count(),
            'can_moderate' => $canModeratePost,
            'items' => $comments,
        ]);
    }

    public function store(Request $request, string $appSlug, string $idOrSlug)
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            $app = $currentApp;
        } else {
            $app = App::query()
                ->where('slug', $appSlug)
                ->where('is_active', true)
                ->first();
        }

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
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
            'body' => ['required', 'string', 'min:1', 'max:3000'],
        ]);

        $comment = ContentPostComment::query()->create([
            'app_id' => (int) $app->id,
            'content_post_id' => (int) $post->id,
            'user_id' => (int) $user->id,
            'body' => trim($data['body']),
            'status' => ContentPostComment::STATUS_PUBLISHED,
        ]);

        $comment->load(['user:id,name,email']);

        $publishedCount = ContentPostComment::query()
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id)
            ->where('status', ContentPostComment::STATUS_PUBLISHED)
            ->count();

        return response()->json([
            'ok' => true,
            'post_id' => (int) $post->id,
            'comments_count' => $publishedCount,
            'item' => $this->shapeComment($comment, $user, $post),
        ], 201);
    }

    public function updateStatus(Request $request, string $appSlug, string $idOrSlug, int $commentId)
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            $app = $currentApp;
        } else {
            $app = App::query()
                ->where('slug', $appSlug)
                ->where('is_active', true)
                ->first();
        }

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
        }

        $post = $this->resolvePost((int) $app->id, $idOrSlug);
        if (! $post) {
            return response()->json([
                'ok' => false,
                'error' => 'POST_NOT_FOUND',
                'message' => 'Content post not found.',
            ], 404);
        }

        if (! $this->canModeratePost($user, $post)) {
            return response()->json([
                'ok' => false,
                'error' => 'FORBIDDEN',
                'message' => 'You are not allowed to moderate comments for this post.',
            ], 403);
        }

        $comment = ContentPostComment::query()
            ->with(['user:id,name,email'])
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id)
            ->where('id', $commentId)
            ->first();

        if (! $comment) {
            return response()->json([
                'ok' => false,
                'error' => 'COMMENT_NOT_FOUND',
                'message' => 'Comment not found.',
            ], 404);
        }

        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', ContentPostComment::allowedStatuses())],
        ]);

        $comment->status = (string) $data['status'];
        $comment->save();

        $publishedCount = ContentPostComment::query()
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id)
            ->where('status', ContentPostComment::STATUS_PUBLISHED)
            ->count();

        return response()->json([
            'ok' => true,
            'post_id' => (int) $post->id,
            'comments_count' => $publishedCount,
            'item' => $this->shapeComment($comment->fresh(['user:id,name,email']), $user, $post),
        ]);
    }

    public function destroy(Request $request, string $appSlug, string $idOrSlug, int $commentId)
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            $app = $currentApp;
        } else {
            $app = App::query()
                ->where('slug', $appSlug)
                ->where('is_active', true)
                ->first();
        }

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
        }

        $post = $this->resolvePost((int) $app->id, $idOrSlug);
        if (! $post) {
            return response()->json([
                'ok' => false,
                'error' => 'POST_NOT_FOUND',
                'message' => 'Content post not found.',
            ], 404);
        }

        $comment = ContentPostComment::query()
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id)
            ->where('id', $commentId)
            ->first();

        if (! $comment) {
            return response()->json([
                'ok' => false,
                'error' => 'COMMENT_NOT_FOUND',
                'message' => 'Comment not found.',
            ], 404);
        }

        $canDelete = $this->canDeleteComment($user, $comment, $post);
        if (! $canDelete) {
            return response()->json([
                'ok' => false,
                'error' => 'FORBIDDEN',
                'message' => 'You are not allowed to delete this comment.',
            ], 403);
        }

        $comment->delete();

        $remainingCount = ContentPostComment::query()
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id)
            ->where('status', ContentPostComment::STATUS_PUBLISHED)
            ->count();

        return response()->json([
            'ok' => true,
            'deleted' => true,
            'post_id' => (int) $post->id,
            'comment_id' => $commentId,
            'comments_count' => $remainingCount,
        ]);
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

    private function canModeratePost($user, ContentPost $post): bool
    {
        if (! $user) {
            return false;
        }

        return (int) $user->id === (int) ($post->author_user_id ?? 0);
    }

    private function canDeleteComment($user, ContentPostComment $comment, ContentPost $post): bool
    {
        if (! $user) {
            return false;
        }

        if ((int) $user->id === (int) $comment->user_id) {
            return true;
        }

        return $this->canModeratePost($user, $post);
    }

    private function shapeComment(ContentPostComment $comment, $viewer, ContentPost $post): array
    {
        $isMine = $viewer ? ((int) $viewer->id === (int) $comment->user_id) : false;
        $canModerate = $this->canModeratePost($viewer, $post);
        $canDelete = $isMine || $canModerate;

        return [
            'id' => (int) $comment->id,
            'body' => (string) $comment->body,
            'status' => (string) $comment->status,
            'is_mine' => $isMine,
            'can_delete' => $canDelete,
            'can_moderate' => $canModerate,
            'user' => [
                'id' => (int) ($comment->user?->id ?? 0),
                'name' => (string) ($comment->user?->name ?? 'Unknown'),
            ],
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
        ];
    }

    private function truthy($v): bool
    {
        if (is_bool($v)) {
            return $v;
        }

        if (is_numeric($v)) {
            return (int) $v === 1;
        }

        if (! is_string($v)) {
            return false;
        }

        return in_array(strtolower(trim($v)), ['1', 'true', 'yes', 'on'], true);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ContentPost;
use App\Models\ContentPostLike;
use Illuminate\Http\Request;

class PostLikeController extends Controller
{
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

        ContentPostLike::query()->firstOrCreate([
            'app_id' => (int) $app->id,
            'content_post_id' => (int) $post->id,
            'user_id' => (int) $user->id,
        ]);

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'post_id' => (int) $post->id,
            'liked' => true,
            'likes_count' => $this->likesCount((int) $app->id, (int) $post->id),
        ]);
    }

    public function destroy(Request $request, string $appSlug, string $idOrSlug)
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

        ContentPostLike::query()
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id)
            ->where('user_id', (int) $user->id)
            ->delete();

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'post_id' => (int) $post->id,
            'liked' => false,
            'likes_count' => $this->likesCount((int) $app->id, (int) $post->id),
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

    private function likesCount(int $appId, int $postId): int
    {
        return (int) ContentPostLike::query()
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->count();
    }
}

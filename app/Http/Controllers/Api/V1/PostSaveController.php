<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ContentPost;
use App\Models\ContentPostSave;
use Illuminate\Http\Request;

class PostSaveController extends Controller
{
    public function store(Request $request, string $appSlug, string $idOrSlug)
    {
        [$app, $user, $post, $error] = $this->context($request, $appSlug, $idOrSlug);
        if ($error) {
            return $error;
        }

        ContentPostSave::query()->firstOrCreate([
            'app_id' => (int) $app->id,
            'content_post_id' => (int) $post->id,
            'user_id' => (int) $user->id,
        ]);

        return response()->json([
            'ok' => true,
            'post_id' => (int) $post->id,
            'saved' => true,
            'saved_by_me' => true,
            'saves_count' => $this->count((int) $app->id, (int) $post->id),
        ]);
    }

    public function destroy(Request $request, string $appSlug, string $idOrSlug)
    {
        [$app, $user, $post, $error] = $this->context($request, $appSlug, $idOrSlug);
        if ($error) {
            return $error;
        }

        ContentPostSave::query()
            ->where('app_id', (int) $app->id)
            ->where('content_post_id', (int) $post->id)
            ->where('user_id', (int) $user->id)
            ->delete();

        return response()->json([
            'ok' => true,
            'post_id' => (int) $post->id,
            'saved' => false,
            'saved_by_me' => false,
            'saves_count' => $this->count((int) $app->id, (int) $post->id),
        ]);
    }

    private function context(Request $request, string $appSlug, string $idOrSlug): array
    {
        $app = $request->attributes->get('current_app');

        if (! $app instanceof App) {
            $app = App::query()
                ->where('slug', $appSlug)
                ->where('is_active', true)
                ->first();
        }

        if (! $app) {
            return [null, null, null, response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404)];
        }

        $user = $request->user();
        if (! $user) {
            return [$app, null, null, response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401)];
        }

        $post = ContentPost::query()
            ->where('app_id', (int) $app->id)
            ->where(function ($query) use ($idOrSlug) {
                if (ctype_digit($idOrSlug)) {
                    $query->where('id', (int) $idOrSlug)
                        ->orWhere('slug', $idOrSlug);
                } else {
                    $query->where('slug', $idOrSlug);
                }
            })
            ->first();

        if (! $post) {
            return [$app, $user, null, response()->json([
                'ok' => false,
                'error' => 'POST_NOT_FOUND',
                'message' => 'Content post not found.',
            ], 404)];
        }

        return [$app, $user, $post, null];
    }

    private function count(int $appId, int $postId): int
    {
        return (int) ContentPostSave::query()
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->count();
    }
}

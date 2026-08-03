<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function store(Request $request, string $appSlug, int $userId)
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

        $authUser = $request->user();
        if (! $authUser) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
        }

        if ((int) $authUser->id === (int) $userId) {
            return response()->json([
                'ok' => false,
                'error' => 'INVALID_TARGET',
                'message' => 'You cannot follow yourself.',
            ], 422);
        }

        $targetUser = User::query()->find($userId);
        if (! $targetUser) {
            return response()->json([
                'ok' => false,
                'error' => 'USER_NOT_FOUND',
                'message' => 'User not found.',
            ], 404);
        }

        UserFollow::query()->firstOrCreate([
            'app_id' => (int) $app->id,
            'follower_user_id' => (int) $authUser->id,
            'following_user_id' => (int) $targetUser->id,
        ]);

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'target_user_id' => (int) $targetUser->id,
            'following' => true,
            'followers_count' => $this->followersCount((int) $app->id, (int) $targetUser->id),
        ]);
    }

    public function destroy(Request $request, string $appSlug, int $userId)
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

        $authUser = $request->user();
        if (! $authUser) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
        }

        $targetUser = User::query()->find($userId);
        if (! $targetUser) {
            return response()->json([
                'ok' => false,
                'error' => 'USER_NOT_FOUND',
                'message' => 'User not found.',
            ], 404);
        }

        UserFollow::query()
            ->where('app_id', (int) $app->id)
            ->where('follower_user_id', (int) $authUser->id)
            ->where('following_user_id', (int) $targetUser->id)
            ->delete();

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'target_user_id' => (int) $targetUser->id,
            'following' => false,
            'followers_count' => $this->followersCount((int) $app->id, (int) $targetUser->id),
        ]);
    }

    private function followersCount(int $appId, int $userId): int
    {
        return (int) UserFollow::query()
            ->where('app_id', $appId)
            ->where('following_user_id', $userId)
            ->count();
    }
}

<?php

namespace App\Services\Accounts;

use App\Models\App;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DeleteAppUserAccount
{
    /**
     * Permanently remove a user's app-scoped data for the selected app.
     *
     * The shared user record is deleted only when it is not an admin account and
     * no user-owned data or app token remains for another AppsHub application.
     */
    public function handle(User $user, App $app): array
    {
        return DB::transaction(function () use ($user, $app): array {
            $userId = (int) $user->id;
            $appId = (int) $app->id;
            $deleted = [];

            $simpleTables = [
                'content_post_comments',
                'content_post_likes',
                'content_views',
                'device_tokens',
                'feed_comments',
                'feed_reactions',
                'feed_reports',
                'feed_saves',
                'feed_shares',
                'quiz_attempts',
                'quiz_user_progress',
                'watch_events',
            ];

            foreach ($simpleTables as $table) {
                if (! Schema::hasTable($table)
                    || ! Schema::hasColumn($table, 'app_id')
                    || ! Schema::hasColumn($table, 'user_id')) {
                    continue;
                }

                $deleted[$table] = DB::table($table)
                    ->where('app_id', $appId)
                    ->where('user_id', $userId)
                    ->delete();
            }

            if (Schema::hasTable('user_follows')) {
                $deleted['user_follows'] = DB::table('user_follows')
                    ->where('app_id', $appId)
                    ->where(function ($query) use ($userId) {
                        $query->where('follower_user_id', $userId)
                            ->orWhere('following_user_id', $userId);
                    })
                    ->delete();
            }

            // Remove only access tokens issued for this app.
            $user->tokens()
                ->where(function ($query) use ($app) {
                    $query->where('name', 'app:' . $app->slug)
                        ->orWhereJsonContains('abilities', 'app:' . (int) $app->id)
                        ->orWhereJsonContains('abilities', 'app_slug:' . $app->slug);
                })
                ->delete();

            // Current-app tokens were removed above. Any remaining personal
            // access token belongs to another AppsHub application or service
            // and must keep the shared user record alive.
            $hasOtherAppToken = $user->tokens()->exists();
            $hasOtherAppData = $hasOtherAppToken
                || $this->hasOtherAppData($userId, $appId);

            $isAdmin = method_exists($user, 'adminRoleKey')
                ? $user->adminRoleKey() !== 'unassigned'
                : ! empty($user->admin_role);

            $userDeleted = false;
            if (! $hasOtherAppData && ! $isAdmin) {
                $user->tokens()->delete();
                $user->delete();
                $userDeleted = true;
            } elseif ((int) ($user->active_app_id ?? 0) === $appId) {
                $user->forceFill(['active_app_id' => null])->save();
            }

            return [
                'user_deleted' => $userDeleted,
                'app_data_deleted' => true,
                'deleted_records' => $deleted,
            ];
        });
    }

    private function hasOtherAppData(int $userId, int $excludedAppId): bool
    {
        $tables = [
            'content_post_comments',
            'content_post_likes',
            'content_views',
            'device_tokens',
            'feed_comments',
            'feed_reactions',
            'feed_reports',
            'feed_saves',
            'feed_shares',
            'quiz_attempts',
            'quiz_user_progress',
            'watch_events',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'app_id')
                || ! Schema::hasColumn($table, 'user_id')) {
                continue;
            }

            if (DB::table($table)
                ->where('user_id', $userId)
                ->where('app_id', '<>', $excludedAppId)
                ->exists()) {
                return true;
            }
        }

        if (Schema::hasTable('user_follows')) {
            if (DB::table('user_follows')
                ->where('app_id', '<>', $excludedAppId)
                ->where(function ($query) use ($userId) {
                    $query->where('follower_user_id', $userId)
                        ->orWhere('following_user_id', $userId);
                })
                ->exists()) {
                return true;
            }
        }

        return false;
    }
}

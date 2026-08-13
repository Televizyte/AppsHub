<?php

namespace App\Support;

use App\Models\App;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

final class AdminAccess
{
    public static function user(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    public static function super(): bool
    {
        return (bool) self::user()?->isSuperAdmin();
    }

    public static function active(): bool
    {
        $user = self::user();
        return $user !== null && $user->isAdminActive();
    }

    public static function has(string|array $permissions): bool
    {
        $user = self::user();
        if (! $user || ! $user->isAdminActive()) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return true;
        }
        foreach ((array) $permissions as $permission) {
            if ($user->hasAdminPermission((string) $permission)) {
                return true;
            }
        }
        return false;
    }

    public static function anyWorkspaceAccess(): bool
    {
        return self::super() || self::has([
            'app_settings.view', 'content.view', 'shorts.view', 'books.view', 'quiz.view',
            'media.view', 'notifications.view', 'users.view', 'analytics.view',
            'comments.moderate', 'monetization.view',
        ]);
    }

    public static function canUseApp(?int $appId): bool
    {
        $user = self::user();
        if (! $user || ! $user->isAdminActive() || ! $appId) {
            return false;
        }
        if ($user->isSuperAdmin()) {
            return App::query()->whereKey($appId)->where('is_active', 1)->exists();
        }
        return in_array((int) $appId, $user->assignedAppIds(), true)
            && App::query()->whereKey($appId)->where('is_active', 1)->exists();
    }

    public static function allowedAppIds(): array
    {
        $user = self::user();
        if (! $user || ! $user->isAdminActive()) {
            return [];
        }
        if ($user->isSuperAdmin()) {
            return App::query()->where('is_active', 1)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }
        return App::query()
            ->whereIn('id', $user->assignedAppIds())
            ->where('is_active', 1)
            ->orderBy('id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public static function firstAllowedAppId(): ?int
    {
        $ids = self::allowedAppIds();
        return $ids[0] ?? null;
    }

    public static function canSeeTop(string $target): bool
    {
        return match ($target) {
            'control_center' => self::super() || self::has('system.view'),
            'app_workspace' => self::anyWorkspaceAccess() && self::firstAllowedAppId() !== null,
            'all_apps' => self::super() || self::has('apps.view'),
            'new_app' => self::super() || self::has('apps.create'),
            default => false,
        };
    }

    public static function page(string $key): bool
    {
        if (! self::active()) {
            return false;
        }
        return match ($key) {
            'control_center', 'engine_room', 'template_library' => self::super() || self::has('system.view'),
            'roles_permissions' => self::super() || self::has(['roles.view', 'roles.manage']),
            'app_workspace', 'destination_builder' => self::anyWorkspaceAccess() && self::firstAllowedAppId() !== null,
            'app_settings', 'app_capabilities' => self::has('app_settings.view'),
            'content_channels', 'quote_engine' => self::has('content.view'),
            'short_video_engine' => self::has('shorts.view'),
            'bible_engine', 'books' => self::has(['books.view', 'books.manage']),
            'quiz_center' => self::has('quiz.view'),
            'media_library', 'icon_library' => self::has('media.view'),
            'push_notifications' => self::has('notifications.view'),
            'app_users' => self::has('users.view'),
            'moderation' => self::has('comments.moderate'),
            'analytics' => self::has('analytics.view'),
            'ads_monetization' => self::has('monetization.view'),
            'watch_builder', 'video_engine' => self::has(['content.view', 'watch.manage']),
            'design_engine' => self::super() || self::has('design.view'),
            default => self::super(),
        };
    }
}

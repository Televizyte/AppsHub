<?php

namespace App\Support;

use App\Models\App;
use Illuminate\Support\Facades\Auth;

final class ActiveApp
{
    public const SESSION_KEY = 'active_app_id';

    /**
     * Return first active app id or null.
     */
    private static function firstActiveId(): ?int
    {
        if (Auth::check()) {
            $allowed = AdminAccess::allowedAppIds();

            if ($allowed !== []) {
                return (int) $allowed[0];
            }

            return null;
        }

        $first = App::query()
            ->where('is_active', 1)
            ->orderBy('id')
            ->value('id');

        return $first ? (int) $first : null;
    }

    /**
     * Check if app id exists AND is active.
     */
    private static function isValidId(?int $id): bool
    {
        if (!$id) {
            return false;
        }

        if (Auth::check()) {
            return AdminAccess::canUseApp((int) $id);
        }

        return App::query()
            ->whereKey($id)
            ->where('is_active', 1)
            ->exists();
    }

    /**
     * Normalize any candidate id into a valid active app id (or null).
     */
    private static function normalize(?int $candidate): ?int
    {
        if (self::isValidId($candidate)) {
            return (int) $candidate;
        }

        return self::firstActiveId();
    }

    /**
     * Set active app into session and optionally persist into the logged-in user.
     */
    public static function set(int $appId, bool $persistToUser = true): void
    {
        $id = self::normalize($appId);

        if ($id === null) {
            return;
        }

        session()->put(self::SESSION_KEY, $id);

        if ($persistToUser && Auth::check()) {
            $user = Auth::user();

            if ((int) $user->active_app_id !== (int) $id) {
                $user->active_app_id = $id;
                $user->save();
            }
        }
    }

    /**
     * Get active app id from session (validated), fallback to user preference,
     * then first active app.
     */
    public static function get(): ?int
    {
        $fromSession = session()->get(self::SESSION_KEY);
        $sessionId = is_numeric($fromSession) ? (int) $fromSession : null;

        if ($sessionId !== null) {
            return self::normalize($sessionId);
        }

        if (Auth::check() && is_numeric(Auth::user()->active_app_id)) {
            $userId = (int) Auth::user()->active_app_id;
            $normalizedUser = self::normalize($userId);

            if ($normalizedUser !== null) {
                return $normalizedUser;
            }
        }

        return self::firstActiveId();
    }

    /**
     * Ensure session has a VALID active app id.
     * Returns the resolved id.
     */
    public static function ensureId(): ?int
    {
        $id = self::get();

        if ($id === null) {
            return null;
        }

        session()->put(self::SESSION_KEY, $id);

        if (Auth::check()) {
            $user = Auth::user();
            $current = is_numeric($user->active_app_id) ? (int) $user->active_app_id : null;

            if (!self::isValidId($current) || (int) $current !== (int) $id) {
                $user->active_app_id = $id;
                $user->save();
            }
        }

        return $id;
    }

    /**
     * Backwards-compatible alias.
     */
    public static function ensure(): ?int
    {
        return self::ensureId();
    }
}

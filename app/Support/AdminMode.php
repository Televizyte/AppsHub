<?php

namespace App\Support;

final class AdminMode
{
    public const SESSION_KEY = 'admin_mode';
    public const BEGINNER = 'beginner';
    public const ADVANCED = 'advanced';

    /**
     * Get the current admin mode from session.
     */
    public static function get(): string
    {
        $mode = session(self::SESSION_KEY);

        if (! is_string($mode) || ! in_array($mode, [self::BEGINNER, self::ADVANCED], true)) {
            return self::BEGINNER;
        }

        return $mode;
    }

    /**
     * Persist a valid mode into session.
     */
    public static function set(string $mode): void
    {
        if (! in_array($mode, [self::BEGINNER, self::ADVANCED], true)) {
            return;
        }

        session()->put(self::SESSION_KEY, $mode);
    }

    public static function isBeginner(): bool
    {
        return self::get() === self::BEGINNER;
    }

    public static function isAdvanced(): bool
    {
        return self::get() === self::ADVANCED;
    }
}

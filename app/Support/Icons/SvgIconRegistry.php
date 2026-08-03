<?php

namespace App\Support\Icons;

final class SvgIconRegistry
{
    /**
     * Normalize icon keys (safe for DB / meta_json usage).
     */
    public static function norm(?string $key): ?string
    {
        if (!is_string($key)) return null;

        $k = trim($key);
        if ($k === '') return null;

        // keep simple: lowercase, underscores
        $k = strtolower($k);
        $k = str_replace([' ', '-'], '_', $k);
        $k = preg_replace('/_+/', '_', $k) ?: $k;

        return $k ?: null;
    }

    /**
     * Shortcut alias (used by some controllers)
     */
    public static function icon(?string $key): ?array
    {
        return self::get($key);
    }

    /**
     * Return icon payload:
     *  [
     *    'key' => 'home',
     *    'svg' => '<svg .../>',
     *  ]
     */
    public static function get(?string $key): ?array
    {
        $key = self::norm($key);
        if (!$key) return null;

        $icons = self::all();

        if (!array_key_exists($key, $icons)) {
            return null;
        }

        return [
            'key' => $key,
            'svg' => $icons[$key],
        ];
    }

    /** Convenience for Filament Select options */
    public static function options(): array
    {
        $out = [];
        foreach (array_keys(self::all()) as $k) {
            $out[$k] = self::label($k);
        }
        return $out;
    }

    /** Return raw map [key => svg] */
    public static function allMap(): array
    {
        return self::all();
    }

    /** Used in UI labels */
    public static function label(string $key): string
    {
        $key = str_replace(['-', '_'], ' ', strtolower(trim($key)));
        $key = preg_replace('/\s+/', ' ', $key) ?: $key;
        return ucwords($key);
    }

    /** All SVG icons (add more anytime) */
    private static function all(): array
    {
        return [
            // Tabs
            'home' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'watch' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M21 12s-3.5 7-9 7-9-7-9-7 3.5-7 9-7 9 7 9 7Z" stroke="currentColor" stroke-width="1.8"/><path d="M10.5 9.5 15 12l-4.5 2.5v-5Z" fill="currentColor"/></svg>',
            'inspire' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 2c3.3 0 6 2.7 6 6 0 2.2-1.2 4.1-3 5.2V16a1 1 0 0 1-1 1h-4a1 1 0 0 1-1-1v-2.8c-1.8-1.1-3-3-3-5.2 0-3.3 2.7-6 6-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9.5 21h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'explore' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V6l-8-4-8 4v6c0 6 8 10 8 10Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9.5 12.5 11 14l3.5-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'more' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 12h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path d="M19 12h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path d="M5 12h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>',

            // Content buckets
            'motivation' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 2l1.6 5.1L19 9l-5.4 1.9L12 16l-1.6-5.1L5 9l5.4-1.9L12 2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            'wordification' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M6 4h12v16H6V4Z" stroke="currentColor" stroke-width="1.8"/><path d="M9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'articles' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M7 4h10v16H7V4Z" stroke="currentColor" stroke-width="1.8"/><path d="M9 8h6M9 12h6M9 16h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'highlights' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M4 20h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7 16l10-10 2 2-10 10H7v-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            'inside_dunamis' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 3l9 6-9 6-9-6 9-6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M3 9v6l9 6 9-6V9" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
            'sod' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 2c4.4 0 8 3.6 8 8 0 4.8-3.2 8.9-8 12-4.8-3.1-8-7.2-8-12 0-4.4 3.6-8 8-8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',

            // Utility / tools
            'quote' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M7 11h4v7H5v-5c0-3 1-5 2-6l2 2c-1 .8-2 1.8-2 2Z" fill="currentColor"/><path d="M17 11h4v7h-6v-5c0-3 1-5 2-6l2 2c-1 .8-2 1.8-2 2Z" fill="currentColor"/></svg>',
            'notes' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M6 3h9l3 3v15H6V3Z" stroke="currentColor" stroke-width="1.8"/><path d="M9 11h6M9 15h6M9 19h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'bible' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M7 4h10a2 2 0 0 1 2 2v14H9a2 2 0 0 0-2 2V4Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M7 20h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'user' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="currentColor" stroke-width="1.8"/><path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'settings' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M12 15.5a3.5 3.5 0 1 0-3.5-3.5 3.5 3.5 0 0 0 3.5 3.5Z" stroke="currentColor" stroke-width="1.8"/><path d="M19.4 15a8.2 8.2 0 0 0 .1-1 8.2 8.2 0 0 0-.1-1l2-1.5-2-3.5-2.4 1a7.6 7.6 0 0 0-1.7-1l-.4-2.6H10l-.4 2.6a7.6 7.6 0 0 0-1.7 1l-2.4-1-2 3.5 2 1.5a8.2 8.2 0 0 0-.1 1 8.2 8.2 0 0 0 .1 1l-2 1.5 2 3.5 2.4-1a7.6 7.6 0 0 0 1.7 1l.4 2.6h4.2l.4-2.6a7.6 7.6 0 0 0 1.7-1l2.4 1 2-3.5-2-1.5Z" stroke="currentColor" stroke-width="1.2" stroke-linejoin="round"/></svg>',
            'play' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"><path d="M9 7v10l8-5-8-5Z" fill="currentColor"/><path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        ];
    }
}

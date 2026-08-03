<?php

namespace App\Support\Api\V1;

class ContentBadge
{
    /**
     * Returns a consistent badge object for a content bucket/type.
     * Output shape:
     *   ['key' => 'wordification', 'text' => 'Wordification']
     */
    public static function forBucket(?string $bucket): ?array
    {
        $bucket = is_string($bucket) ? trim($bucket) : null;
        if (!$bucket) return null;

        $map = [
            'wordification'   => 'Wordification',
            'motivation'      => 'Motivation',
            'sod'             => 'SOD',
            'highlight'       => 'Highlights',
            'highlights'      => 'Highlights',
            'inside_dunamis'  => 'Inside Dunamis',
            'inside-dunamis'  => 'Inside Dunamis',
            'articles'        => 'Articles',
            'article'         => 'Articles',
        ];

        $text = $map[$bucket] ?? self::titleize($bucket);

        return [
            'key' => $bucket,
            'text' => $text,
        ];
    }

    public static function titleize(string $s): string
    {
        $s = str_replace(['-', '_'], ' ', trim($s));
        $s = preg_replace('/\s+/', ' ', $s) ?: $s;
        return ucwords(strtolower($s));
    }
}

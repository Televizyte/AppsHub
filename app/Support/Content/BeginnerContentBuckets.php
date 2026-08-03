<?php

namespace App\Support\Content;

final class BeginnerContentBuckets
{
    public const SHORT_VIDEOS = 'short_videos';

    public static function all(): array
    {
        return [
            'motivation' => 'Motivation',
            'wordification' => 'Wordification',
            'highlights' => 'Message Highlights',
            'inside_dunamis' => 'Inside Dunamis / Articles',
            'sod' => 'Seed of Destiny',
            'sod_quotes' => 'SOD Quotes',
            self::SHORT_VIDEOS => 'Short Videos',
        ];
    }

    public static function keys(): array
    {
        return array_keys(self::all());
    }

    public static function normalize(string $bucket): string
    {
        return match (strtolower(trim($bucket))) {
            'highlight', 'message_highlights', 'message-highlight', 'message-highlights' => 'highlights',
            'inside', 'inside-dunamis', 'inside_dunamis_articles', 'articles', 'article' => 'inside_dunamis',
            'sod-quote', 'sod-quotes', 'quotes' => 'sod_quotes',
            'short', 'shorts', 'short-video', 'short-videos', 'short_video', 'short_videos', 'reels', 'reel' => self::SHORT_VIDEOS,
            default => strtolower(trim($bucket)),
        };
    }

    public static function label(string $bucket): string
    {
        $bucket = self::normalize($bucket);

        return self::all()[$bucket] ?? ucfirst(str_replace('_', ' ', $bucket));
    }

    public static function isAllowed(string $bucket): bool
    {
        return array_key_exists(self::normalize($bucket), self::all());
    }

    public static function isShortVideo(string $bucket): bool
    {
        return self::normalize($bucket) === self::SHORT_VIDEOS;
    }
}

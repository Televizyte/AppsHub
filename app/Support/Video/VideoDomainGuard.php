<?php

namespace App\Support\Video;

final class VideoDomainGuard
{
    public static function sameApp(mixed ...$appIds): bool
    {
        if (count($appIds) < 2) {
            return false;
        }

        $normalized = [];

        foreach ($appIds as $appId) {
            if (! is_numeric($appId) || (int) $appId <= 0) {
                return false;
            }

            $normalized[] = (int) $appId;
        }

        return count(array_unique($normalized)) === 1;
    }
}

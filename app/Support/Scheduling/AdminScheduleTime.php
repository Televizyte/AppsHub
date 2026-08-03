<?php

namespace App\Support\Scheduling;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class AdminScheduleTime
{
    public static function timezone(?string $timezone = null): string
    {
        $timezone = trim((string) $timezone);

        return $timezone !== ''
            ? $timezone
            : (string) config('app.admin_timezone', 'Africa/Lagos');
    }

    /**
     * Interpret an admin/browser datetime-local wall-clock value in the selected
     * timezone and normalize it to UTC for database storage.
     */
    public static function toUtc(mixed $value, ?string $timezone = null): ?Carbon
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value)->copy()->utc();
        }

        return Carbon::parse((string) $value, self::timezone($timezone))->utc();
    }

    /**
     * Format a UTC database value for a datetime-local field in the selected
     * admin/app timezone.
     */
    public static function toLocalInput(mixed $value, ?string $timezone = null): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        $date = $value instanceof CarbonInterface
            ? Carbon::instance($value)->copy()
            : Carbon::parse((string) $value, 'UTC');

        return $date
            ->utc()
            ->setTimezone(self::timezone($timezone))
            ->format('Y-m-d\TH:i');
    }

    /**
     * Format a UTC database value for normal human-readable admin display.
     */
    public static function toLocalDisplay(
        mixed $value,
        ?string $timezone = null,
        string $format = 'M j, Y g:i A'
    ): string {
        if ($value === null || trim((string) $value) === '') {
            return '';
        }

        $date = $value instanceof CarbonInterface
            ? Carbon::instance($value)->copy()
            : Carbon::parse((string) $value, 'UTC');

        return $date
            ->utc()
            ->setTimezone(self::timezone($timezone))
            ->format($format);
    }
}

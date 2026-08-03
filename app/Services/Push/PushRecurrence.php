<?php

namespace App\Services\Push;

use App\Models\PushNotification;
use Carbon\CarbonImmutable;

class PushRecurrence
{
    /**
     * Returns next scheduled_for (UTC datetime) or null if should stop.
     */
    public static function nextRun(PushNotification $n): ?\DateTimeInterface
    {
        $type = (string) ($n->recurrence_type ?: 'none');
        if ($type === 'none') {
            return null;
        }

        // Stop rules
        if (!empty($n->max_runs) && (int)$n->runs_count >= (int)$n->max_runs) {
            return null;
        }

        $tz = (string) ($n->timezone ?: config('push.default_timezone') ?: config('app.timezone') ?: 'UTC');

        $nowLocal = CarbonImmutable::now($tz);

        $hour = is_null($n->recurrence_hour) ? (int) $nowLocal->hour : (int) $n->recurrence_hour;
        $minute = is_null($n->recurrence_minute) ? (int) $nowLocal->minute : (int) $n->recurrence_minute;

        $endsAt = $n->ends_at ? CarbonImmutable::parse($n->ends_at)->setTimezone($tz) : null;

        $candidateLocal = match ($type) {
            'daily'   => self::nextDaily($nowLocal, $hour, $minute),
            'weekly'  => self::nextWeekly($nowLocal, $hour, $minute, (array) ($n->recurrence_weekdays ?: [])),
            'monthly' => self::nextMonthly($nowLocal, $hour, $minute, (int) ($n->recurrence_month_day ?: 1)),
            default   => null,
        };

        if (!$candidateLocal) {
            return null;
        }

        if ($endsAt && $candidateLocal->greaterThan($endsAt)) {
            return null;
        }

        // Store as UTC datetime in DB
        return $candidateLocal->setTimezone('UTC')->toDateTime();
    }

    private static function nextDaily(CarbonImmutable $nowLocal, int $hour, int $minute): CarbonImmutable
    {
        $todayAt = $nowLocal->setTime($hour, $minute, 0);
        if ($todayAt->greaterThan($nowLocal)) {
            return $todayAt;
        }
        return $todayAt->addDay();
    }

    /**
     * Weekdays: 1=Mon ... 7=Sun
     */
    private static function nextWeekly(CarbonImmutable $nowLocal, int $hour, int $minute, array $weekdays): ?CarbonImmutable
    {
        $weekdays = array_values(array_filter(array_map('intval', $weekdays), fn ($d) => $d >= 1 && $d <= 7));
        if (empty($weekdays)) {
            // If none selected, default to today
            $weekdays = [(int) $nowLocal->isoWeekday()];
        }

        $start = $nowLocal->setTime($hour, $minute, 0);

        // If today's slot still ahead and today is included -> pick today
        $todayDow = (int) $nowLocal->isoWeekday();
        if (in_array($todayDow, $weekdays, true) && $start->greaterThan($nowLocal)) {
            return $start;
        }

        // Search next 14 days to be safe
        for ($i = 1; $i <= 14; $i++) {
            $d = $nowLocal->addDays($i);
            if (in_array((int) $d->isoWeekday(), $weekdays, true)) {
                return $d->setTime($hour, $minute, 0);
            }
        }

        return null;
    }

    private static function nextMonthly(CarbonImmutable $nowLocal, int $hour, int $minute, int $dayOfMonth): CarbonImmutable
    {
        $dayOfMonth = max(1, min(31, $dayOfMonth));

        $thisMonth = self::safeDayOfMonth($nowLocal, $dayOfMonth)->setTime($hour, $minute, 0);
        if ($thisMonth->greaterThan($nowLocal)) {
            return $thisMonth;
        }

        $nextMonth = $nowLocal->addMonthNoOverflow();
        return self::safeDayOfMonth($nextMonth, $dayOfMonth)->setTime($hour, $minute, 0);
    }

    private static function safeDayOfMonth(CarbonImmutable $base, int $dayOfMonth): CarbonImmutable
    {
        $last = (int) $base->endOfMonth()->day;
        $day = min($dayOfMonth, $last);
        return $base->setDay($day);
    }
}

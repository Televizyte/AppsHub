<?php

namespace App\Services\Push;

use App\Models\PushNotification;
use App\Services\Push\Senders\FcmLegacySender;
use App\Services\Push\Senders\FcmV1Sender;
use App\Services\Push\Senders\LogSender;
use Carbon\Carbon;

class PushManager
{
    public static function send(PushNotification $notification): array
    {
        $driver = (string) config('push.driver', 'log');

        return match ($driver) {
            'fcm_v1', 'firebase_v1' => (new FcmV1Sender())->send($notification),
            'fcm_legacy' => (new FcmLegacySender())->send($notification),
            default => (new LogSender())->send($notification),
        };
    }

    public static function computeNextRun(PushNotification $n, ?Carbon $from = null): ?Carbon
    {
        $type = (string) ($n->recurrence_type ?? 'none');

        if ($type === 'none') {
            return null;
        }

        $tz = (string) ($n->timezone ?: (config('push.default_timezone') ?: config('app.timezone')));
        $base = ($from ?: now())->copy()->timezone($tz);

        if ($n->ends_at) {
            $ends = Carbon::parse($n->ends_at)->timezone($tz);

            if ($base->greaterThanOrEqualTo($ends)) {
                return null;
            }
        }

        if (! is_null($n->max_runs) && (int) $n->runs_count >= (int) $n->max_runs) {
            return null;
        }

        $hour = is_numeric($n->recurrence_hour) ? (int) $n->recurrence_hour : (int) $base->hour;
        $min = is_numeric($n->recurrence_minute) ? (int) $n->recurrence_minute : (int) $base->minute;

        if ($type === 'daily') {
            $next = $base->copy()->setTime($hour, $min, 0);

            if ($next->lessThanOrEqualTo($base)) {
                $next->addDay();
            }

            return $next;
        }

        if ($type === 'weekly') {
            $days = is_array($n->recurrence_weekdays) ? $n->recurrence_weekdays : [];
            $days = array_values(array_filter(array_map('intval', $days), fn ($d) => $d >= 1 && $d <= 7));

            if (empty($days)) {
                return $base->copy()->addWeek()->setTime($hour, $min, 0);
            }

            for ($i = 0; $i < 14; $i++) {
                $candidate = $base->copy()->addDays($i)->setTime($hour, $min, 0);

                if ($candidate->lessThanOrEqualTo($base)) {
                    continue;
                }

                if (in_array($candidate->isoWeekday(), $days, true)) {
                    return $candidate;
                }
            }

            return null;
        }

        if ($type === 'monthly') {
            $day = is_numeric($n->recurrence_month_day) ? (int) $n->recurrence_month_day : (int) $base->day;
            $day = max(1, min(31, $day));

            $candidate = $base->copy()->setDay(min($day, $base->daysInMonth))->setTime($hour, $min, 0);

            if ($candidate->lessThanOrEqualTo($base)) {
                $nextMonth = $base->copy()->addMonthNoOverflow();
                $candidate = $nextMonth->setDay(min($day, $nextMonth->daysInMonth))->setTime($hour, $min, 0);
            }

            return $candidate;
        }

        if ($type === 'yearly') {
            $candidate = $base->copy()->setTime($hour, $min, 0);

            if ($candidate->lessThanOrEqualTo($base)) {
                $candidate->addYear();
            }

            return $candidate;
        }

        return null;
    }
}

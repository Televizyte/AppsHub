<?php

namespace App\Services\Push\Senders;

use App\Models\PushNotification;
use Illuminate\Support\Facades\Log;

class LogSender
{
    public function send(PushNotification $notification): array
    {
        $app = $notification->app;

        Log::info('[PUSH:LOG] Notification simulated (no real provider configured)', [
            'push_id' => $notification->id,
            'app_id' => $notification->app_id,
            'app_slug' => $app?->slug,
            'title' => $notification->title,
            'body' => $notification->body,
            'image_url' => $notification->image_url,
            'deep_link_url' => $notification->deep_link_url,
            'click_action' => $notification->click_action,
            'target_type' => $notification->target_type,
            'target_value' => $notification->target_value,
            'timezone' => $notification->timezone,
            'scheduled_for' => optional($notification->scheduled_for)->toISOString(),
            'recurrence_type' => $notification->recurrence_type,
            'recurrence_weekdays' => $notification->recurrence_weekdays,
            'recurrence_month_day' => $notification->recurrence_month_day,
            'recurrence_hour' => $notification->recurrence_hour,
            'recurrence_minute' => $notification->recurrence_minute,
            'ends_at' => optional($notification->ends_at)->toISOString(),
            'max_runs' => $notification->max_runs,
            'runs_count' => $notification->runs_count,
            'meta_json' => $notification->meta_json,
        ]);

        return [
            'ok' => true,
            'provider' => 'log',
            'message' => 'Logged only (PUSH_DRIVER=log).',
        ];
    }
}

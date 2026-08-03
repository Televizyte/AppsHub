<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Services\Push\PushManager;
use App\Services\Push\PushRecurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendPushNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $notificationId;

    public function __construct(int $notificationId)
    {
        $this->notificationId = $notificationId;
        $this->onQueue('push');
    }

    public function handle(): void
    {
        $n = PushNotification::query()->with('app')->find($this->notificationId);

        if (!$n) {
            return;
        }

        // Cancelled should never send.
        if (($n->status ?? '') === 'cancelled') {
            return;
        }

        $status = (string) ($n->status ?? '');
        $isDue = is_null($n->scheduled_for) || $n->scheduled_for->lte(now());

        // Queue duplication guard: if an old duplicate job runs after the first
        // job has already moved the notification to sent/scheduled/completed,
        // skip it instead of sending the same push again.
        $canSend = in_array($status, ['queued', 'sending'], true)
            || ($isDue && in_array($status, ['draft', 'scheduled', 'failed'], true));

        if (! $canSend) {
            return;
        }

        // Prevent double sends for one-time notifications.
        if (($n->recurrence_type ?? 'none') === 'none' && $status === 'sent') {
            return;
        }

        $n->status = 'sending';
        $n->save();

        try {
            $result = PushManager::send($n);

            if (($result['ok'] ?? false) === true) {
                // Increase runs count
                $n->runs_count = (int) ($n->runs_count ?? 0) + 1;

                $n->meta_json = array_merge((array) ($n->meta_json ?? []), [
                    'provider' => $result['provider'] ?? null,
                    'provider_result' => $result,
                    'last_sent_at' => now()->toISOString(),
                ]);

                $isRecurring = ((string) ($n->recurrence_type ?? 'none')) !== 'none';

                if ($isRecurring) {
                    $sentCopy = $n->replicate();
                    $sentCopy->status = 'sent';
                    $sentCopy->scheduled_for = null;
                    $sentCopy->recurrence_type = 'none';
                    $sentCopy->recurrence_weekdays = null;
                    $sentCopy->recurrence_hour = null;
                    $sentCopy->recurrence_minute = null;
                    $sentCopy->recurrence_month_day = null;
                    $sentCopy->max_runs = null;
                    $sentCopy->runs_count = 1;
                    $sentCopy->meta_json = array_merge((array) ($n->meta_json ?? []), [
                        'created_from_recurring_push_id' => $n->id,
                        'archived_occurrence_at' => now()->toISOString(),
                        'payload_version' => '6h',
                    ]);
                    $sentCopy->save();
                }

                // Recurrence: compute next scheduled time after archiving this occurrence.
                $next = PushRecurrence::nextRun($n);

                if ($next) {
                    $n->scheduled_for = $next;
                    $n->status = 'scheduled';
                } else {
                    $n->status = $isRecurring ? 'completed' : 'sent';
                }

                $n->save();
                return;
            }

            $n->status = 'failed';
            $n->meta_json = array_merge((array) ($n->meta_json ?? []), [
                'provider' => $result['provider'] ?? null,
                'provider_result' => $result,
                'failed_at' => now()->toISOString(),
            ]);
            $n->save();
        } catch (\Throwable $e) {
            $n->status = 'failed';
            $n->meta_json = array_merge((array) ($n->meta_json ?? []), [
                'provider' => 'exception',
                'error' => $e->getMessage(),
                'failed_at' => now()->toISOString(),
            ]);
            $n->save();
        }
    }
}

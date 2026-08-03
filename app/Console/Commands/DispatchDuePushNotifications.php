<?php

namespace App\Console\Commands;

use App\Jobs\SendPushNotificationJob;
use App\Models\PushNotification;
use Illuminate\Console\Command;

class DispatchDuePushNotifications extends Command
{
    protected $signature = 'push:dispatch-due {--limit=50}';
    protected $description = 'Dispatch due/scheduled push notifications into the queue.';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        if ($limit <= 0) $limit = 50;

        $nowUtc = now(); // scheduled_for stored in UTC

        $due = PushNotification::query()
            // Only dispatch fresh due notifications. Do not redispatch rows that
            // are already queued/sending, otherwise a stopped worker can create
            // thousands of duplicate jobs every minute.
            ->whereIn('status', ['draft', 'scheduled'])
            ->where(function ($q) use ($nowUtc) {
                $q->whereNull('scheduled_for')->orWhere('scheduled_for', '<=', $nowUtc);
            })
            ->orderByRaw('scheduled_for IS NULL DESC') // null first (immediate)
            ->orderBy('scheduled_for')
            ->limit($limit)
            ->get();

        if ($due->isEmpty()) {
            $this->info('No due notifications.');
            return self::SUCCESS;
        }

        $count = 0;

        foreach ($due as $n) {
            if (($n->status ?? '') === 'cancelled') {
                continue;
            }

            // Mark queued (so UI reflects it)
            $n->status = 'queued';
            $n->save();

            SendPushNotificationJob::dispatch((int) $n->id);
            $count++;
        }

        $this->info("Dispatched {$count} notifications.");
        return self::SUCCESS;
    }
}

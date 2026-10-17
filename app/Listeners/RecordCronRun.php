<?php

namespace App\Listeners;

use App\Models\CronRun;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;

/** Protokolliert jede vom Scheduler ausgeführte Aufgabe, damit in den Einstellungen sichtbar ist, ob der Cron regelmäßig läuft. */
class RecordCronRun
{
    public function handle(ScheduledTaskFinished|ScheduledTaskFailed $event): void
    {
        $failed = $event instanceof ScheduledTaskFailed;
        $task   = $event->task;

        CronRun::create([
            'task'        => $task->description ?: $task->getSummaryForDisplay(),
            'source'      => app()->bound('cron.source') ? app('cron.source') : 'cron',
            'status'      => $failed ? 'failed' : 'ok',
            'duration_ms' => $failed ? null : (int) round($event->runtime * 1000),
            'message'     => $failed ? mb_substr($event->exception->getMessage(), 0, 500) : null,
            'ran_at'      => now(),
        ]);

        // Aufräumen ohne eigenen Job: gelegentlich alte Einträge entfernen.
        if (random_int(1, 100) === 1) {
            CronRun::where('ran_at', '<', now()->subDays(3))->delete();
        }
    }
}

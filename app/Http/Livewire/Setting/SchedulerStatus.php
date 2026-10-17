<?php

namespace App\Http\Livewire\Setting;

use App\Models\CronRun;
use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Livewire\Component;
use Symfony\Component\Process\PhpExecutableFinder;

/** Einstellungen → Planer (Cron): Status des Schedulers, der einzurichtende Cron-Befehl und die Cron-Trigger-URL. */
class SchedulerStatus extends Component
{
    private function authorizeView(): void
    {
        abort_unless(auth()->user()?->hasPermission('system.scheduler'), 403);
    }

    public function mount(): void
    {
        $this->authorizeView();
    }

    public function toggleUrl(): void
    {
        $this->authorizeView();
        abort_unless(\App\Http\Controllers\Setting\CronController::available(), 404);
        $enabled = Setting::get('cron_url_enabled') === '1';
        Setting::set('cron_url_enabled', $enabled ? '0' : '1');
        if (! $enabled && ! Setting::get('cron_url_token')) {
            Setting::set('cron_url_token', Str::random(40));
        }
    }

    public function regenerateToken(): void
    {
        $this->authorizeView();
        abort_unless(\App\Http\Controllers\Setting\CronController::available(), 404);
        Setting::set('cron_url_token', Str::random(40));
    }

    /** Führt den Scheduler jetzt einmal aus (wie ein Cron-Aufruf), Quelle "manuell". */
    public function runNow(): void
    {
        $this->authorizeView();
        app()->instance('cron.source', 'manual');
        Artisan::call('schedule:run');
    }

    public function render()
    {
        $this->authorizeView();

        $last      = CronRun::orderByDesc('ran_at')->first();
        $ageSec    = $last ? (int) $last->ran_at->diffInSeconds(now(), true) : null;
        $lastHour  = CronRun::where('task', $last?->task ?? '')->where('ran_at', '>=', now()->subHour())->orderBy('ran_at')->get();
        $gaps      = $lastHour->pluck('ran_at')->sliding(2)->map(fn ($pair) => (int) $pair->first()->diffInSeconds($pair->last(), true))->values();

        $state = match (true) {
            $last === null    => 'none',
            $ageSec <= 150    => 'ok',
            $ageSec <= 900    => 'delayed',
            default           => 'down',
        };

        $token = (string) Setting::get('cron_url_token', '');
        $phpBinary = (new PhpExecutableFinder())->find() ?: 'php';

        return view('livewire.setting.scheduler-status', [
            'state'      => $state,
            'last'       => $last,
            'ageSec'     => $ageSec,
            'runsLastHour' => $lastHour->count(),
            'maxGap'     => $gaps->max(),
            'recent'     => CronRun::orderByDesc('ran_at')->limit(12)->get(),
            'cronLine'   => '* * * * * cd ' . base_path() . ' && ' . $phpBinary . ' artisan schedule:run >> /dev/null 2>&1',
            'urlAvailable' => \App\Http\Controllers\Setting\CronController::available(),
            'urlEnabled' => Setting::get('cron_url_enabled') === '1',
            'url'        => $token !== '' ? route('cron.run', $token) : null,
            'schedule'   => app(\Illuminate\Console\Scheduling\Schedule::class)->events(),
        ]);
    }
}

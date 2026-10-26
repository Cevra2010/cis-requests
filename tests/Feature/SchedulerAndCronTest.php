<?php

namespace Tests\Feature;

use App\Http\Livewire\Setting\SchedulerStatus;
use App\Models\CronRun;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class SchedulerAndCronTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_the_schedule_contains_the_workflow_event_task(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('workflow:process-events')->assertSuccessful();
    }

    public function test_every_scheduled_task_run_is_recorded_for_the_status_display(): void
    {
        $task = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'workflow:process-events'));

        event(new \Illuminate\Console\Events\ScheduledTaskFinished($task, 0.25));
        event(new \Illuminate\Console\Events\ScheduledTaskFailed($task, new \RuntimeException('SMTP down')));

        $ok     = CronRun::where('status', 'ok')->first();
        $failed = CronRun::where('status', 'failed')->first();
        $this->assertSame('Workflow-Ereignisse prüfen', $ok->task);
        $this->assertSame('cron', $ok->source);
        $this->assertSame(250, $ok->duration_ms);
        $this->assertSame('SMTP down', $failed->message);
    }

    public function test_status_page_requires_the_scheduler_permission_and_reports_the_state(): void
    {
        $this->actingAs($this->makeUser());
        Livewire::test(SchedulerStatus::class)->assertForbidden();

        $this->actingAs($this->makeAdmin());
        Livewire::test(SchedulerStatus::class)->assertSee('Noch nie gelaufen');

        CronRun::create(['task' => 'Workflow-Ereignisse prüfen', 'source' => 'cron', 'status' => 'ok', 'ran_at' => now()]);
        Livewire::test(SchedulerStatus::class)->assertSee('Läuft regelmäßig');

        CronRun::query()->update(['ran_at' => now()->subHour()]);
        Livewire::test(SchedulerStatus::class)->assertSee('Läuft nicht');
    }

    public function test_cron_url_is_404_unless_enabled_with_the_right_token_and_only_in_development(): void
    {
        $this->makeUser(); // ohne Benutzer leitet die App auf die Einrichtung um
        $this->get('/Cron/irgendwas')->assertNotFound();

        // Kein echter Unterprozess gegen die (leere) Test-Datenbank: der Aufruf von schedule:run wird nur geprüft.
        \Illuminate\Support\Facades\Artisan::shouldReceive('call')->once()->with('schedule:run')->andReturn(0);
        \Illuminate\Support\Facades\Artisan::shouldReceive('output')->andReturn('Running scheduled tasks');

        Setting::set('cron_url_enabled', '1');
        Setting::set('cron_url_token', 'geheim');
        $this->get('/Cron/falsch')->assertNotFound();
        $this->get('/Cron/geheim')->assertOk()->assertJsonPath('status', 'ok');

        $this->app['env'] = 'production';
        $this->get('/Cron/geheim')->assertNotFound();
    }
}

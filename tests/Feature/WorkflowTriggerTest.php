<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\WorkflowAction;
use App\Models\WorkflowTrigger;
use App\Models\WorkflowTriggerRun;
use App\Services\Workflow\Events\WorkflowEvents;
use App\Services\Workflow\WorkflowRegistry;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class WorkflowTriggerTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function trigger(string $stepCode, string $event, array $actions = [['goto_step', ['target' => 'next']]], bool $active = true): WorkflowTrigger
    {
        $step    = $this->step($stepCode);
        $trigger = WorkflowTrigger::create(['cis_row_id_workflow_status' => $step->cis_row_id, 'event' => $event, 'is_active' => true, 'sort_order' => 0]);
        foreach ($actions as $i => [$type, $config]) {
            WorkflowAction::create([
                'cis_row_id_workflow_status'  => $step->cis_row_id,
                'cis_row_id_workflow_trigger' => $trigger->cis_row_id,
                'trigger'                     => 'event',
                'type'                        => $type,
                'sort_order'                  => $i,
                'is_active'                   => $active,
                'config'                      => $config,
            ]);
        }

        return $trigger;
    }

    public function test_tender_period_is_entered_and_shown_in_local_time_but_stored_in_utc(): void
    {
        $project = Project::create(['name' => 'Zeitzone', 'type' => 'product', 'tender_starts_at' => '2026-10-18 10:00']);

        $this->assertSame('2026-10-18 08:00:00', $project->getRawOriginal('tender_starts_at') ?? $project->getAttributes()['tender_starts_at']);
        $this->assertSame('10:00', $project->fresh()->tender_starts_at->format('H:i'));
        $this->assertSame('Europe/Berlin', $project->fresh()->tender_starts_at->timezoneName);
    }

    public function test_start_trigger_does_nothing_before_the_start_and_jumps_exactly_once_after_it(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-18 09:00', 'Europe/Berlin'));
        $this->trigger('ready_for_tender', 'tender.started');
        $project = $this->makeProject('ready_for_tender', 'product', ['tender_starts_at' => '2026-10-18 10:00']);

        WorkflowEvents::processProject($project);
        $this->assertSame('ready_for_tender', $project->fresh()->status_code, 'Beginn liegt noch in der Zukunft (Ortszeit)');

        Carbon::setTestNow(Carbon::parse('2026-10-18 10:01', 'Europe/Berlin'));
        WorkflowEvents::processProject($project->fresh());
        $this->assertSame('tender', $project->fresh()->status_code);

        WorkflowEvents::processProject($project->fresh());
        $this->assertSame('tender', $project->fresh()->status_code, 'das Ereignis feuert nur einmal');
        $this->assertSame(1, $project->statusTransitions()->where('direction', 'auto')->count());
        $this->assertSame(1, WorkflowTriggerRun::count());
    }

    public function test_end_trigger_fixes_via_the_normal_transition_and_runs_enter_actions_of_the_target(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-20 12:00', 'Europe/Berlin'));
        $project = $this->makeProject('tender', 'product', ['tender_ends_at' => '2026-10-20 11:00']);

        WorkflowEvents::processProject($project);

        $this->assertSame('ready_for_evaluation', $project->fresh()->status_code);
    }

    public function test_a_trigger_without_active_actions_is_not_consumed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-18 11:00', 'Europe/Berlin'));
        $trigger = $this->trigger('ready_for_tender', 'tender.started', [['goto_step', ['target' => 'next']]], active: false);
        $project = $this->makeProject('ready_for_tender', 'product', ['tender_starts_at' => '2026-10-18 10:00']);

        WorkflowEvents::processProject($project);
        $this->assertSame('ready_for_tender', $project->fresh()->status_code);
        $this->assertSame(0, WorkflowTriggerRun::count(), 'ohne aktive Maßnahme bleibt das Ereignis offen');

        WorkflowAction::where('cis_row_id_workflow_trigger', $trigger->cis_row_id)->update(['is_active' => true]);
        WorkflowEvents::processProject($project->fresh());
        $this->assertSame('tender', $project->fresh()->status_code, 'nach dem Aktivieren greift das noch offene Ereignis');
    }

    public function test_inactive_triggers_never_fire(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-18 11:00', 'Europe/Berlin'));
        $trigger = $this->trigger('ready_for_tender', 'tender.started');
        $trigger->update(['is_active' => false]);
        $project = $this->makeProject('ready_for_tender', 'product', ['tender_starts_at' => '2026-10-18 10:00']);

        WorkflowEvents::processProject($project);

        $this->assertSame('ready_for_tender', $project->fresh()->status_code);
    }

    public function test_projects_on_other_steps_are_not_affected_by_a_trigger(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-18 11:00', 'Europe/Berlin'));
        $this->trigger('ready_for_tender', 'tender.started');
        $other = $this->makeProject('draft', 'product', ['tender_starts_at' => '2026-10-18 10:00']);

        WorkflowEvents::processDue();

        $this->assertSame('draft', $other->fresh()->status_code);
    }

    public function test_mutual_jump_loops_are_stopped(): void
    {
        // Schritt A springt auf B und B (beim Erreichen) wieder auf A: darf nicht endlos laufen.
        $this->addEnterAction($this->step('tender'), 'goto_step', ['target' => 'ready_for_tender']);
        $this->addEnterAction($this->step('ready_for_tender'), 'goto_step', ['target' => 'tender']);
        WorkflowRegistry::flush();
        $project = $this->makeProject('draft');

        $project->jumpToStep($this->step('ready_for_tender'));

        $this->assertLessThan(30, $project->statusTransitions()->count(), 'die Schleife wird begrenzt');
        $this->assertTrue(\App\Models\WorkflowActionRun::where('status', 'failed')->where('summary', 'like', '%Schleife%')->exists());
    }

    public function test_scheduled_command_runs_due_triggers(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-18 11:00', 'Europe/Berlin'));
        $this->trigger('ready_for_tender', 'tender.started');
        $project = $this->makeProject('ready_for_tender', 'product', ['tender_starts_at' => '2026-10-18 10:00']);

        $this->artisan('workflow:process-events')->assertSuccessful();

        $this->assertSame('tender', $project->fresh()->status_code);
    }
}

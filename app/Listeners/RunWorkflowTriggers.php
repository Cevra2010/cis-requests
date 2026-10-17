<?php

namespace App\Listeners;

use App\Events\WorkflowEventOccurred;
use App\Models\WorkflowTrigger;
use App\Models\WorkflowTriggerRun;
use App\Services\Workflow\WorkflowActionRunner;

/** Führt die Maßnahmen aller aktiven Trigger auf dem aktuellen Schritt des Projekts aus, die auf das Ereignis lauten – je Ereigniszeitpunkt nur einmal. */
class RunWorkflowTriggers
{
    public function handle(WorkflowEventOccurred $event): void
    {
        $step = $event->project->currentStep();
        if (! $step) {
            return;
        }

        $triggers = WorkflowTrigger::where('cis_row_id_workflow_status', $step->cis_row_id)
            ->where('event', $event->eventKey)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($triggers as $trigger) {
            if (! WorkflowTriggerRun::claim($event->project, $trigger, $event->occurredAt)) {
                continue;
            }

            app(WorkflowActionRunner::class)->runActions(
                $trigger->actions()->where('is_active', true)->get(),
                $event->project,
                null
            );
        }
    }
}

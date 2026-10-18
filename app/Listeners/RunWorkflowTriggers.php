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
            $actions = $trigger->actions()->where('is_active', true)->get();

            // Ohne aktive Maßnahme gibt es nichts auszuführen – das Ereignis dann nicht als erledigt vermerken,
            // damit ein später aktivierte Maßnahme noch greift, solange das Projekt auf dem Schritt steht.
            if ($actions->isEmpty() || ! WorkflowTriggerRun::claim($event->project, $trigger, $event->occurredAt)) {
                continue;
            }

            app(WorkflowActionRunner::class)->runActions($actions, $event->project, null);
        }
    }
}

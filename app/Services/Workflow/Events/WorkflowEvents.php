<?php

namespace App\Services\Workflow\Events;

use App\Events\WorkflowEventOccurred;
use App\Models\Project;
use App\Models\WorkflowTrigger;
use Carbon\CarbonInterface;

/**
 * Event-Manager des Workflows: feuert Ereignisse (dispatch()) und prüft zeitgesteuerte
 * Ereignisse auf Fälligkeit (processDue()/processProject()). Reagiert wird per Laravel-Event
 * WorkflowEventOccurred (siehe App\Listeners\RunWorkflowTriggers).
 */
class WorkflowEvents
{
    /** Wie oft ein Projekt innerhalb eines Durchlaufs nacheinander weiterspringen darf (Schutz vor Endlosschleifen). */
    private const MAX_PASSES = 6;

    public static function dispatch(string $eventKey, Project $project, CarbonInterface $occurredAt): void
    {
        event(new WorkflowEventOccurred($eventKey, $project, $occurredAt));
    }

    /** Prüft alle Projekte, deren aktueller Schritt zeitgesteuerte Trigger hat. @return int Anzahl geprüfter Projekte */
    public static function processDue(): int
    {
        $scheduledKeys = array_keys(WorkflowEventTypes::scheduled());
        if (empty($scheduledKeys)) {
            return 0;
        }

        $triggers = WorkflowTrigger::with('status')
            ->where('is_active', true)
            ->whereIn('event', $scheduledKeys)
            ->get()
            ->filter(fn (WorkflowTrigger $t) => $t->status !== null);

        $count = 0;
        foreach ($triggers->groupBy(fn (WorkflowTrigger $t) => $t->status->project_type . '|' . $t->status->code) as $group) {
            $status = $group->first()->status;
            Project::where('type', $status->project_type)->where('status_code', $status->code)->get()
                ->each(function (Project $project) use (&$count) {
                    self::processProject($project);
                    $count++;
                });
        }

        return $count;
    }

    /** Feuert alle fälligen zeitgesteuerten Ereignisse für ein Projekt (mehrere Durchgänge, falls es dabei weiterspringt). */
    public static function processProject(Project $project): void
    {
        for ($pass = 0; $pass < self::MAX_PASSES; $pass++) {
            $step = $project->currentStep();
            if (! $step) {
                return;
            }

            $startCode = $project->status_code;
            $events    = WorkflowTrigger::where('cis_row_id_workflow_status', $step->cis_row_id)
                ->where('is_active', true)
                ->pluck('event')
                ->unique();

            foreach ($events as $key) {
                $type = WorkflowEventTypes::find($key);
                if (! $type || ! $type->scheduled()) {
                    continue;
                }
                $at = $type->occurredAt($project);
                if ($at && $at->lessThanOrEqualTo(now())) {
                    self::dispatch($key, $project, $at);
                }
                if ($project->status_code !== $startCode) {
                    break;
                }
            }

            if ($project->status_code === $startCode) {
                return;
            }
        }
    }
}

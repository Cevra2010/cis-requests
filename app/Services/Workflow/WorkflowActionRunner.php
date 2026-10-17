<?php

namespace App\Services\Workflow;

use App\Models\Project;
use App\Models\ProjectStatusTransition;
use App\Models\WorkflowAction;
use App\Models\WorkflowActionRun;
use App\Models\WorkflowStatus;
use App\Services\Workflow\Actions\WorkflowActionTypes;

/**
 * Führt alle aktiven Maßnahmen eines Workflow-Schritts aus, sobald ein Projekt
 * ihn per Vorrücken erreicht (siehe Project::transitionTo()). Der Status wird
 * dabei immer VOR dem Aufruf hier bereits gespeichert – ein Maßnahmen-Fehler
 * (z.B. SMTP down) blockiert daher nie die eigentliche Statusänderung.
 */
class WorkflowActionRunner
{
    public function run(Project $project, WorkflowStatus $target, ?ProjectStatusTransition $transition): void
    {
        $this->runActions(
            WorkflowAction::where('cis_row_id_workflow_status', $target->cis_row_id)
                ->where('trigger', 'enter')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(),
            $project,
            $transition
        );
    }

    /** Führt die übergebenen Maßnahmen der Reihe nach aus und protokolliert jede (Fehler einer Maßnahme stoppen die übrigen nicht). */
    public function runActions(\Illuminate\Support\Collection $actions, Project $project, ?ProjectStatusTransition $transition): void
    {
        foreach ($actions as $action) {
            $type = WorkflowActionTypes::find($action->type);

            if (! $type) {
                $result = ['status' => 'skipped', 'summary' => 'Unbekannte Maßnahmenart.', 'details' => null];
            } else {
                try {
                    $result = $type->execute($project, $action, $transition);
                } catch (\Throwable $e) {
                    report($e);
                    $result = ['status' => 'failed', 'summary' => 'Unerwarteter Fehler.', 'details' => ['error' => $e->getMessage()]];
                }
            }

            WorkflowActionRun::create([
                'cis_row_id_project'         => $project->cis_row_id,
                'cis_row_id_workflow_action' => $action->cis_row_id,
                'cis_row_id_transition'      => $transition?->cis_row_id,
                'status'                     => $result['status'],
                'summary'                    => $result['summary'] ?? null,
                'details'                    => $result['details'] ?? null,
            ]);
        }
    }
}

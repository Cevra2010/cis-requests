<?php

namespace App\Services\Workflow;

use App\Services\Workflow\Actions\FixationAction;
use App\Models\WorkflowAction;
use App\Models\WorkflowApprover;
use App\Models\WorkflowStatus;
use Illuminate\Database\QueryException;

/**
 * Lädt/cached den Workflow je Projekttyp für die Dauer des Requests. Wird von
 * WorkflowStatus/WorkflowApprover (saved/deleted) und vom Workflow-Editor per
 * flush() invalidiert.
 */
class WorkflowRegistry
{
    /** @var array<string, WorkflowDefinition> */
    protected static array $cache = [];

    public static function for(string $projectType): WorkflowDefinition
    {
        if (isset(self::$cache[$projectType])) {
            return self::$cache[$projectType];
        }

        try {
            $steps = WorkflowStatus::where('project_type', $projectType)
                ->orderBy('sort_order')
                ->with('approvers')
                ->get();

            if ($steps->isEmpty()) {
                WorkflowDefaults::ensureSeeded($projectType);
                $steps = WorkflowStatus::where('project_type', $projectType)
                    ->orderBy('sort_order')
                    ->with('approvers')
                    ->get();
            }

            $lockOverride = WorkflowApprover::where('project_type', $projectType)
                ->where('scope', 'lock_override')
                ->get();

            $fixations = self::fixationsFor($steps);
        } catch (QueryException $e) {
            // Tabellen existieren (ausnahmsweise) noch nicht, z.B. während einer laufenden
            // Migration – fail-closed statt hart zu crashen.
            return self::$cache[$projectType] = WorkflowDefaults::inMemoryFallback($projectType);
        }

        return self::$cache[$projectType] = new WorkflowDefinition($steps, $lockOverride, $fixations);
    }

    /**
     * Fixierungsbereich => Code des Schritts, der die aktive Fixierungs-Maßnahme trägt (der früheste, falls – unzulässig –
     * mehrere vorkommen; der Workflow-Editor verhindert das).
     *
     * @return array<string, string>
     */
    protected static function fixationsFor($steps): array
    {
        $types   = array_map([FixationAction::class, 'typeKey'], array_keys(FixationAction::SCOPES));
        $byStep  = $steps->keyBy('cis_row_id');
        $actions = WorkflowAction::whereIn('type', $types)
            ->where('trigger', 'enter')
            ->where('is_active', true)
            ->whereIn('cis_row_id_workflow_status', $byStep->keys())
            ->get();

        $map = [];
        foreach ($steps as $step) {
            foreach ($actions->where('cis_row_id_workflow_status', $step->cis_row_id) as $action) {
                $scope = FixationAction::scopeOf($action->type);
                if ($scope !== null && ! isset($map[$scope])) {
                    $map[$scope] = $step->code;
                }
            }
        }

        return $map;
    }

    public static function flush(): void
    {
        self::$cache = [];
    }

    /** Alle bekannten Status-Codes -> Label, über beide Typen hinweg (für den Status-Filter in ProjectTable). */
    public static function statusOptions(): array
    {
        $rows = WorkflowStatus::orderBy('label')->get(['code', 'label']);

        $byCode = [];
        foreach ($rows as $row) {
            $byCode[$row->code][$row->label] = true;
        }

        return collect($byCode)->map(fn ($labels) => implode(' / ', array_keys($labels)))->all();
    }
}

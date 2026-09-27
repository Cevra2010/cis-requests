<?php

namespace App\Services\Workflow;

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
        } catch (QueryException $e) {
            // Tabellen existieren (ausnahmsweise) noch nicht, z.B. während einer laufenden
            // Migration – fail-closed statt hart zu crashen.
            return self::$cache[$projectType] = WorkflowDefaults::inMemoryFallback($projectType);
        }

        return self::$cache[$projectType] = new WorkflowDefinition($steps, $lockOverride);
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

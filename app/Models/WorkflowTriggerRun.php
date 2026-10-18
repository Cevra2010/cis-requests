<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;

/** Merkt sich, dass ein Trigger für ein Projekt zu einem bestimmten Ereigniszeitpunkt bereits gefeuert hat. */
class WorkflowTriggerRun extends Model
{
    use CisUuid;

    protected $fillable = ['cis_row_id_project', 'cis_row_id_workflow_trigger', 'occurred_at'];

    protected $casts = ['occurred_at' => 'datetime'];

    /** Reserviert den Lauf; false, wenn er (parallel oder früher) bereits reserviert wurde. */
    public static function claim(Project $project, WorkflowTrigger $trigger, \DateTimeInterface $occurredAt): bool
    {
        $at = \Carbon\Carbon::instance($occurredAt)->utc()->format('Y-m-d H:i:s');

        if (static::where('cis_row_id_project', $project->cis_row_id)
            ->where('cis_row_id_workflow_trigger', $trigger->cis_row_id)
            ->where('occurred_at', $at)->exists()) {
            return false;
        }

        try {
            static::create([
                'cis_row_id_project'          => $project->cis_row_id,
                'cis_row_id_workflow_trigger' => $trigger->cis_row_id,
                'occurred_at'                 => $at,
            ]);
        } catch (QueryException) {
            return false;
        }

        return true;
    }
}

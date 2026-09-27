<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/** Protokoll einer einzelnen Maßnahmen-Ausführung (z.B. eine E-Mail-Versandaktion). */
class WorkflowActionRun extends Model
{
    use CisUuid;

    protected $fillable = [
        'cis_row_id_project', 'cis_row_id_workflow_action', 'cis_row_id_transition',
        'status', 'summary', 'details',
    ];

    protected $casts = ['details' => 'array'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'cis_row_id_project', 'cis_row_id');
    }

    public function action()
    {
        return $this->belongsTo(WorkflowAction::class, 'cis_row_id_workflow_action', 'cis_row_id');
    }

    public function statusIcon(): string
    {
        return match ($this->status) {
            'sent'    => 'fa-circle-check text-emerald-500',
            'partial' => 'fa-triangle-exclamation text-amber-500',
            'failed'  => 'fa-circle-xmark text-red-500',
            default   => 'fa-circle-minus text-gray-300',
        };
    }
}

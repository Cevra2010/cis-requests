<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Eine "Workflow Maßnahme" (aktuell nur: E-Mail senden), die feuert, sobald ein
 * Projekt den zugehörigen WorkflowStatus per Vorrücken erreicht (trigger='enter').
 * type ist ein Schlüssel in App\Services\Workflow\Actions\WorkflowActionTypes,
 * config ist der typspezifische JSON-Inhalt (siehe dort).
 */
class WorkflowAction extends Model
{
    use CisUuid;

    protected $fillable = ['cis_row_id_workflow_status', 'cis_row_id_workflow_trigger', 'trigger', 'type', 'sort_order', 'is_active', 'config'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
        'config'     => 'array',
    ];

    public function status()
    {
        return $this->belongsTo(WorkflowStatus::class, 'cis_row_id_workflow_status', 'cis_row_id');
    }
}

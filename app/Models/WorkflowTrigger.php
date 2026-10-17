<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/**
 * Ein Trigger an einem Workflow-Schritt: reagiert auf ein Ereignis (event = Schlüssel in
 * App\Services\Workflow\Events\WorkflowEventTypes), solange ein Projekt auf diesem Schritt
 * steht, und löst seine Maßnahmen aus.
 */
class WorkflowTrigger extends Model
{
    use CisUuid;

    protected $fillable = ['cis_row_id_workflow_status', 'event', 'is_active', 'sort_order', 'config'];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
        'config'     => 'array',
    ];

    public function status()
    {
        return $this->belongsTo(WorkflowStatus::class, 'cis_row_id_workflow_status', 'cis_row_id');
    }

    public function actions()
    {
        return $this->hasMany(WorkflowAction::class, 'cis_row_id_workflow_trigger', 'cis_row_id')
            ->orderBy('sort_order');
    }
}

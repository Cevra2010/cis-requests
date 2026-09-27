<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use App\Services\Workflow\WorkflowRegistry;
use Illuminate\Database\Eloquent\Model;

/**
 * Wer einen Workflow-Schritt freigeben darf (scope='transition', an einen
 * WorkflowStatus gebunden) oder Fixierungen aufheben darf (scope='lock_override',
 * projekttyp-weit). Bewusst unabhängig vom klassischen Permission/Role-Grant-
 * System – principal_type 'role' referenziert eine Rolle nur als Personenkreis,
 * nicht über deren Permission-Zuordnungen.
 */
class WorkflowApprover extends Model
{
    use CisUuid;

    protected $table = 'workflow_approvers';

    protected $fillable = ['project_type', 'scope', 'cis_row_id_workflow_status', 'principal_type', 'principal_id'];

    protected static function booted(): void
    {
        static::saved(fn () => WorkflowRegistry::flush());
        static::deleted(fn () => WorkflowRegistry::flush());
    }

    public function status()
    {
        return $this->belongsTo(WorkflowStatus::class, 'cis_row_id_workflow_status', 'cis_row_id');
    }

    public function principal(): User|Group|Role|null
    {
        return match ($this->principal_type) {
            'user'  => User::find($this->principal_id),
            'group' => Group::find($this->principal_id),
            'role'  => Role::find($this->principal_id),
            default => null,
        };
    }

    public function label(): string
    {
        $p = $this->principal();

        if (! $p) {
            return match ($this->principal_type) {
                'user'  => 'Gelöschter Benutzer',
                'group' => 'Gelöschte Gruppe',
                'role'  => 'Gelöschte Rolle',
                default => 'Unbekannt',
            };
        }

        return match ($this->principal_type) {
            'user'  => $p->name(),
            'group' => 'Gruppe „' . $p->name . '“',
            'role'  => 'Rolle „' . $p->name . '“',
            default => '?',
        };
    }

    public function isDangling(): bool
    {
        return $this->principal() === null;
    }
}

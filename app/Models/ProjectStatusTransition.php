<?php

namespace App\Models;

use App\Models\Traits\CisUuid;
use Illuminate\Database\Eloquent\Model;

/** Verlaufs-Historie eines Projekt-Statuswechsels – füllt den "Verlauf" im Workflow-Wizard. */
class ProjectStatusTransition extends Model
{
    use CisUuid;

    protected $fillable = [
        'cis_row_id_project', 'from_code', 'to_code', 'from_label', 'to_label',
        'direction', 'cis_row_id_user',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'cis_row_id_user', 'cis_row_id');
    }

    public function directionIcon(): string
    {
        return match ($this->direction) {
            'advance' => 'fa-arrow-right',
            'revert'  => 'fa-rotate-left',
            'auto'    => 'fa-clock',
            default   => 'fa-flag',
        };
    }

    public function actorLabel(): string
    {
        if ($this->direction === 'auto') {
            return 'System (automatisch)';
        }

        return $this->user?->name() ?? 'Unbekannt';
    }
}

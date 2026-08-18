<?php

namespace App\Http\Livewire\Concerns;

use App\Models\Project;

/**
 * Für Livewire-Komponenten, die Produkte oder Ausschreibungstext eines Projekts
 * bearbeiten. Nach dem Fixieren einer Ausschreibung (Project::lock()) sind
 * diese Inhalte nur noch mit der Berechtigung project.tender.override_lock
 * veränderbar. Mutierende Methoden müssen mit assertEditable() beginnen.
 */
trait RespectsProjectLock
{
    protected function assertEditable(string $projectId): bool
    {
        $project = Project::where('cis_row_id', $projectId)->first();
        return $project ? $project->isEditableBy(auth()->user()) : false;
    }
}

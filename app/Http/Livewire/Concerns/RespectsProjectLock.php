<?php

namespace App\Http\Livewire\Concerns;

use App\Models\Project;

/**
 * Für Livewire-Komponenten, die Produkte oder Ausschreibungstext eines Projekts
 * bearbeiten. Nach dem Fixieren einer Ausschreibung sind diese Inhalte nur noch
 * bearbeitbar, wenn der Benutzer im Workflow-Bildschirm als Fixierungs-Override
 * hinterlegt ist (siehe Project::canOverrideLock()). Mutierende Methoden müssen
 * mit assertEditable() beginnen.
 */
trait RespectsProjectLock
{
    protected function assertEditable(string $projectId): bool
    {
        $project = Project::where('cis_row_id', $projectId)->first();
        return $project ? $project->isEditableBy(auth()->user()) : false;
    }
}

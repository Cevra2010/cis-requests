<?php

namespace App\Http\Livewire\Concerns;

use App\Models\Project;

/**
 * Für Livewire-Komponenten, die Produkte, Ausschreibungstext, Angebote oder die
 * Bestellzuordnung eines Projekts bearbeiten. Nach dem Fixieren (Fixierungs-Maßnahmen im
 * Workflow) ist der jeweilige Bereich für alle gesperrt (siehe Project::isEditable());
 * erst das Zurücksetzen des Status vor den fixierenden Schritt macht ihn wieder bearbeitbar. Mutierende Methoden müssen
 * mit assertEditable() beginnen.
 */
trait RespectsProjectLock
{
    /** @param string $scope Fixierungsbereich: products (Standard), tender, offers oder awarding – siehe FixationAction::SCOPES */
    protected function assertEditable(string $projectId, string $scope = 'products'): bool
    {
        $project = Project::where('cis_row_id', $projectId)->first();
        return $project ? $project->isEditable($scope) : false;
    }
}

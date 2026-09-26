<?php

namespace Modules\Lager\Http\Livewire;

use App\Models\Project;
use Livewire\Component;
use Modules\Lager\Models\Lagerort;

/**
 * Projekt-Tab "Lager": diesem Projekt zugewiesene Lagerorte (Zuweisen/
 * Entfernen) sowie der aktuell für dieses Projekt reservierte Bestand.
 */
class ProjectLagerManager extends Component
{
    public string $projectId;

    public string $newLagerortId = '';

    public function mount(string $projectId): void
    {
        $this->projectId = $projectId;
    }

    public function assignLagerort(): void
    {
        if ($this->newLagerortId === '') {
            return;
        }

        // Zuteilung erfolgt rekursiv: nur die untersten Elemente werden gespeichert.
        $leafIds = Lagerort::leafIdsByNode()[$this->newLagerortId] ?? [];

        $project = Project::where('cis_row_id', $this->projectId)->firstOrFail();
        $project->lagerorte()->syncWithoutDetaching($leafIds);
        $this->newLagerortId = '';
    }

    public function removeLagerort(string $lagerortId): void
    {
        $project = Project::where('cis_row_id', $this->projectId)->firstOrFail();
        $project->lagerorte()->detach($lagerortId);
    }

    public function render()
    {
        $project           = Project::where('cis_row_id', $this->projectId)->firstOrFail();
        $assignedLagerorte = $project->lagerorte()->get();
        $assignedIds       = $assignedLagerorte->pluck('cis_row_id')->all();

        $leafMap = Lagerort::leafIdsByNode();
        $availableLagerorte = Lagerort::flatTree()->reject(
            fn (Lagerort $l) => array_diff($leafMap[$l->cis_row_id] ?? [$l->cis_row_id], $assignedIds) === []
        );

        return view('lager::livewire.project-lager-manager', compact('assignedLagerorte', 'availableLagerorte'));
    }
}

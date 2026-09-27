<?php

namespace App\Http\Livewire\Project;

use App\Http\Livewire\Concerns\HasFilterableTable;
use App\Models\Project;
use App\Models\WorkflowStatus;
use App\Services\Workflow\WorkflowRegistry;
use CisFoundation\CisCategoryManager\CisCategoryManager;
use Livewire\Component;

class ProjectTable extends Component
{
    use HasFilterableTable;

    /** Springt Projekte auf Schritten mit Auto-Vorrücken automatisch weiter, sobald die Fälligkeit überschritten ist. */
    public function mount(): void
    {
        $autoAdvanceCodes = WorkflowStatus::where('auto_advance_on_due', true)->pluck('code')->unique();

        Project::whereIn('status_code', $autoAdvanceCodes)
            ->where('due_date', '<', now())
            ->get()
            ->each->syncAutoStatus();
    }

    public function tableKey(): string
    {
        return 'projects';
    }

    public function baseQuery()
    {
        return Project::query();
    }

    public function columns(): array
    {
        return [
            [
                'key' => 'name', 'label' => 'Projektname',
                'sortable' => true, 'filterable' => true,
                'column' => 'name', 'value' => fn ($p) => $p->name,
            ],
            [
                'key' => 'status', 'label' => 'Status',
                'sortable' => true, 'filterable' => true,
                'column' => 'status_code', 'value' => fn ($p) => $p->status_code,
                'sortValue' => fn ($p) => $p->statusLabel(),
                'optionsSource' => fn () => WorkflowRegistry::statusOptions(),
            ],
            [
                'key' => 'locked', 'label' => 'Ausschreibung',
                'filterable' => true,
                'value' => fn ($p) => $p->isLocked() ? 'locked' : 'unlocked',
                'optionsSource' => fn () => ['locked' => 'Fixiert', 'unlocked' => 'In Bearbeitung'],
            ],
            [
                'key' => 'category', 'label' => 'Kategorie',
                'sortable' => true, 'filterable' => true,
                'column' => 'category_id', 'value' => fn ($p) => $p->category_id,
                'sortValue' => fn ($p) => $p->categoryLabel(),
                'optionsSource' => fn () => CisCategoryManager::optionsForType('project.category'),
            ],
            [
                'key' => 'assignee', 'label' => 'Verantwortlich',
                'sortable' => true, 'filterable' => true,
                'value' => fn ($p) => $p->assigneeLabel() !== '–' ? $p->assigneeLabel() : null,
            ],
            [
                'key' => 'client', 'label' => 'Auftraggeber',
                'sortable' => true, 'filterable' => true,
                'column' => 'client', 'value' => fn ($p) => $p->client,
            ],
            [
                'key' => 'due_date', 'label' => 'Fällig',
                'sortable' => true, 'filterable' => true,
                'value' => fn ($p) => optional($p->due_date)->format('d.m.Y'),
                'sortValue' => fn ($p) => $p->due_date,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.project.project-table', [
            'rows' => $this->paginatedRows(),
        ]);
    }
}

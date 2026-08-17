<?php

namespace App\Http\Livewire\Project;

use App\Models\Group;
use App\Models\User;
use Livewire\Component;

class AssigneeSearch extends Component
{
    public string $search = '';

    public string $selectedId   = '';
    public string $selectedType = '';
    public string $selectedName = '';

    public bool $open = false;

    public array $results = [];

    public ?string $initialType = null;
    public ?string $initialId   = null;

    public function mount(?string $assigneeType = null, ?string $assigneeId = null): void
    {
        if ($assigneeType && $assigneeId) {
            $this->selectedType = $assigneeType;
            $this->selectedId   = $assigneeId;

            if ($assigneeType === 'user') {
                $u = User::find($assigneeId);
                $this->selectedName = $u ? $u->name() : '';
            } elseif ($assigneeType === 'group') {
                $g = Group::find($assigneeId);
                $this->selectedName = $g ? ($g->name ?? '') : '';
            }

            $this->search = $this->selectedName;
        }
    }

    public function updatedSearch(): void
    {
        $q = trim($this->search);

        if (strlen($q) < 1) {
            $this->results = [];
            $this->open    = false;
            return;
        }

        $results = [];

        foreach (
            User::where(function ($query) use ($q) {
                $query->where('firstname', 'like', "%{$q}%")
                      ->orWhere('lastname',  'like', "%{$q}%")
                      ->orWhere('email',     'like', "%{$q}%");
            })->take(8)->get() as $u
        ) {
            $results[] = [
                'id'    => (string) $u->cis_row_id,
                'type'  => 'user',
                'label' => $u->name(),
                'sub'   => 'Benutzer',
            ];
        }

        foreach (
            Group::where('name', 'like', "%{$q}%")->take(8)->get() as $g
        ) {
            $results[] = [
                'id'    => (string) $g->cis_row_id,
                'type'  => 'group',
                'label' => (string) $g->name,
                'sub'   => 'Gruppe',
            ];
        }

        $this->results = $results;
        $this->open    = count($results) > 0;
    }

    public function select(string $id, string $type, string $label): void
    {
        $this->selectedId   = $id;
        $this->selectedType = $type;
        $this->selectedName = $label;
        $this->search       = $label;
        $this->open         = false;
        $this->results      = [];
    }

    public function clear(): void
    {
        $this->selectedId   = '';
        $this->selectedType = '';
        $this->selectedName = '';
        $this->search       = '';
        $this->open         = false;
        $this->results      = [];
    }

    public function render()
    {
        return view('livewire.project.assignee-search');
    }
}

<?php

namespace App\Http\Livewire\Workflow;

use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Livewire\Component;

/**
 * Sucht Benutzer/Gruppen/Rollen (optional: freie E-Mail-Adresse) und meldet die
 * Auswahl per "principal-picked"-Event an die Elternkomponente. Verallgemeinerung
 * von App\Http\Livewire\Project\AssigneeSearch um Rollen (Workflow-Freigeber)
 * und E-Mail (künftige Maßnahmen-Empfänger).
 */
class PrincipalPicker extends Component
{
    public string $context;
    public bool $allowEmail = false;

    public string $search = '';
    public array $results = [];
    public bool $open = false;

    public function mount(string $context, bool $allowEmail = false): void
    {
        $this->context    = $context;
        $this->allowEmail = $allowEmail;
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

        foreach (User::where(function ($query) use ($q) {
            $query->where('firstname', 'like', "%{$q}%")
                ->orWhere('lastname', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%");
        })->take(6)->get() as $u) {
            $results[] = ['type' => 'user', 'id' => (string) $u->cis_row_id, 'label' => $u->name(), 'sub' => 'Benutzer'];
        }

        foreach (Group::where('name', 'like', "%{$q}%")->take(6)->get() as $g) {
            $results[] = ['type' => 'group', 'id' => (string) $g->cis_row_id, 'label' => (string) $g->name, 'sub' => 'Gruppe'];
        }

        foreach (Role::where('name', 'like', "%{$q}%")->take(6)->get() as $r) {
            $results[] = ['type' => 'role', 'id' => (string) $r->cis_row_id, 'label' => (string) $r->name, 'sub' => 'Rolle'];
        }

        if ($this->allowEmail && filter_var($q, FILTER_VALIDATE_EMAIL)) {
            $results[] = ['type' => 'email', 'id' => $q, 'label' => $q, 'sub' => 'Externe E-Mail'];
        }

        $this->results = $results;
        $this->open    = count($results) > 0;
    }

    public function select(string $type, string $id): void
    {
        $this->dispatch('principal-picked', context: $this->context, type: $type, id: $id);
        $this->search  = '';
        $this->results = [];
        $this->open    = false;
    }

    public function render()
    {
        return view('livewire.workflow.principal-picker');
    }
}

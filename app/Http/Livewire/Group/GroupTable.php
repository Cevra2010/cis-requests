<?php

namespace App\Http\Livewire\Group;

use App\Http\Livewire\Concerns\HasFilterableTable;
use App\Models\Group;
use Livewire\Component;

class GroupTable extends Component
{
    use HasFilterableTable;

    public function tableKey(): string
    {
        return 'groups';
    }

    public function baseQuery()
    {
        return Group::query()->withCount('users');
    }

    public function columns(): array
    {
        return [
            [
                'key' => 'name', 'label' => 'Gruppe',
                'sortable' => true, 'filterable' => true,
                'column' => 'name', 'value' => fn ($g) => $g->name,
            ],
            [
                'key' => 'description', 'label' => 'Beschreibung',
                'sortable' => true, 'filterable' => true,
                'column' => 'description', 'value' => fn ($g) => $g->description,
            ],
            [
                'key' => 'users_count', 'label' => 'Mitglieder',
                'sortable' => true, 'filterable' => true,
                'value' => fn ($g) => $g->users_count,
            ],
        ];
    }

    public function render()
    {
        return view('livewire.group.group-table', [
            'rows' => $this->paginatedRows(),
        ]);
    }
}

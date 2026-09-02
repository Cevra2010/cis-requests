<?php

namespace App\Http\Livewire\User;

use App\Http\Livewire\Concerns\HasFilterableTable;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use Livewire\Component;

class UserTable extends Component
{
    use HasFilterableTable;

    public function tableKey(): string
    {
        return 'users';
    }

    public function baseQuery()
    {
        return User::query()->with(['groups', 'roles']);
    }

    public function columns(): array
    {
        return [
            [
                'key' => 'name', 'label' => 'Name',
                'sortable' => true, 'filterable' => true,
                'value' => fn ($u) => $u->name(),
                'sortValue' => fn ($u) => $u->lastname . ' ' . $u->firstname,
            ],
            [
                'key' => 'email', 'label' => 'E-Mail',
                'sortable' => true, 'filterable' => true,
                'column' => 'email', 'value' => fn ($u) => $u->email,
            ],
            [
                'key' => 'groups', 'label' => 'Gruppen',
                'sortable' => false, 'filterable' => true,
                'value' => fn ($u) => $u->groups->pluck('cis_row_id')->all(),
                'optionsSource' => fn () => Group::orderBy('name')->pluck('name', 'cis_row_id')->all(),
            ],
            [
                'key' => 'roles', 'label' => 'Rollen',
                'sortable' => false, 'filterable' => true,
                'value' => fn ($u) => $u->roles->pluck('cis_row_id')->all(),
                'optionsSource' => fn () => Role::orderBy('name')->pluck('name', 'cis_row_id')->all(),
            ],
        ];
    }

    public function render()
    {
        return view('livewire.user.user-table', [
            'rows' => $this->paginatedRows(),
        ]);
    }
}

<?php

namespace App\Http\Livewire\User;

use App\Models\Role;
use App\Models\User;
use Livewire\Component;

class RoleSelector extends Component
{

    public $user;
    public $roles;

    public function mount(User $user) {
        $this->user = $user;
        $this->roles = Role::all();
    }

    public function render()
    {
        return view('livewire.user.role-selector');
    }

    public function changeRole($roleUuid) {
        $role = Role::findOrFail($roleUuid);
        if($this->user->roles()->find($role)) {
            $this->user->roles()->detach($role);
        }
        else {
            $this->user->roles()->attach($role);
        }
    }
}

<?php

namespace App\Http\Livewire\Role;

use App\Models\Role;
use Livewire\Component;

class RoleTable extends Component
{
    public $searchString;
    public $orderBy = 'name';
    public $orderDirection = 'ASC';

    public function render()
    {
        $roles = Role::where('name','like','%'.$this->searchString.'%')->orderBy($this->orderBy,$this->orderDirection)->paginate();
        return view('livewire.role.role-table',[
            'roles' => $roles,
        ]);
    }

    public function order($orderName) {
        if($orderName == $this->orderBy) {
            if($this->orderDirection == "ASC") {
                $this->orderDirection = "DESC";
            }
            else {
                $this->orderDirection = "ASC";
            }
        }
        else {
            $this->orderDirection = "ASC";
            $this->orderBy = $orderName;
        }
    }
}

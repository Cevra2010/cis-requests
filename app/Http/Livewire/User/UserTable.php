<?php

namespace App\Http\Livewire\User;

use App\Models\User;
use Livewire\Component;

class UserTable extends Component
{

    public $searchString;
    public $orderBy = 'lastname';
    public $orderDirection = 'ASC';

    public function render()
    {
        $users = User::where('firstname','like','%'.$this->searchString.'%')->orWhere('lastname','like','%'.$this->searchString.'%')->orderBy($this->orderBy,$this->orderDirection)->paginate();
        return view('livewire.user.user-table',[
            'users' => $users,
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

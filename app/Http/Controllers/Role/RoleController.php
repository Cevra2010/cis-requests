<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Http\Logic\CisAccess\Facades\Access;
use App\Http\Requests\Role\CreateRoleRequest;
use App\Http\Requests\Role\DeleteRoleRequest;
use App\Models\Area;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index() {
        return view("role.index");
    }

    public function edit(Role $role)
    {
        return view("role.edit",[
            'role' => $role,
            'areas' => Area::where('parent_slug',null)->orderBy('slug')->get(),
        ]);
    }

    public function create() {
        return view("role.create");
    }

    public function delete(Role $role) {
        return view("role.delete",[
            'role' => $role,
            'users' => $role->users()->get(),
        ]);
    }

    public function store(CreateRoleRequest $request ) {
        $role = new Role();
        $role->name = $request->get('name');
        $role->save();

        if(Access::hasAccess("role.edit")) {
            return redirect()->route("user.role.edit",$role);
        }
        else {
            session()->flash('success','Die Rolle wurde erstellt.');
            return redirect()->route("user.role.index");
        }
    }

    public function deleteStore(DeleteRoleRequest $request, Role $role) {
        if($request->get('delete_key') != "DEL-".$role->name){
            return redirect()->back()->withErrors('Der Sicherheitsabfrage ist nicht korrekt.')->withInput();
        }
        $role->delete();
        session()->flash('success','Die Rolle wurde erfolgreich gelöscht.');
        return redirect()->route("user.role.index");
    }
}

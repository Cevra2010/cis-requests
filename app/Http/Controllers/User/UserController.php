<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Logic\CisAccess\Facades\Access;
use App\Http\Requests\User\DeleteRequest;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateSecurityRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index() {
        return view("user.index");
    }

    public function create() {
        return view("user.create");
    }

    public function delete(User $user) {
        return view("user.delete",[
            'user' => $user,
        ]);
    }

    public function deleteStore(DeleteRequest $request, User $user) {
        if($request->get('delete_key') != "DEL-".$user->firstname."/".$user->lastname){
            return redirect()->back()->withErrors('Der Sicherheitsabfrage ist nicht korrekt.')->withInput();
        }
        $user->delete();
        session()->flash('success','Das Konto wurde erfolgreich gelöscht.');
        return redirect()->route("user.index");
    }

    public function store(StoreUserRequest $request) {
        $user = new User();
        $user->fill($request->all());
        $user->password = Hash::make($request->get("password"));
        $user->save();

        if(Access::hasAccess("user.edit.roles") || Access::hasAccess("user.create.roles")) {
            session()->flash('success','Das Konto wurde erfolgreich angelegt. Bitte ordnen Sie die gewünschten Rollen zu.');
            return redirect()->route("user.edit.roles",$user);
        }
        else {
            session()->flash('success','Das Konto wurde erfolgreich angelegt.');
            return redirect()->route("user.index");
        }
    }

    public function edit(User $user) {
        return view("user.edit",[
            'user' => $user,
        ]);
    }

    public function roles(User $user) {
        return view("user.roles",[
            'user' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request,User $user) {
        $user->fill($request->all());
        $user->save();
        session()->flash('success','Benutzerkonto wurde erfolgreich geändert.');
        return redirect()->route("user.edit",$user);
    }

    public function security(User $user) {
        return view("user.edit-security",[
            'user' => $user,
        ]);
    }

    public function securityUpdate(UpdateSecurityRequest $request, User $user) {
        $user->password = Hash::make($request->get('password'));
        $user->save();
        session()->flash('success','Benutzerkonto wurde erfolgreich geändert.');
        return redirect()->route("user.edit",$user);
    }
}

<?php

namespace App\Http\Controllers\Role;

use App\Http\Controllers\Controller;
use App\Models\Role;
use CisFoundation\CisPermissionManager\CisPermissionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();
        return view('role.index', compact('roles'));
    }

    public function create()
    {
        return view('role.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|size:7',
        ]);

        $role = Role::create($data);

        session()->flash('success', 'Rolle „' . $role->name . '" wurde erstellt.');
        return redirect()->route('role.permissions', $role);
    }

    public function edit(Role $role)
    {
        return view('role.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|size:7',
        ]);

        $role->update($data);
        session()->flash('success', 'Rolle wurde aktualisiert.');
        return redirect()->route('role.edit', $role);
    }

    public function permissions(Role $role)
    {
        $permissions = CisPermissionManager::grouped();
        $granted     = DB::table('role_permissions')
            ->where('role_id', $role->cis_row_id)
            ->whereNull('project_id')
            ->pluck('granted', 'permission_slug');

        return view('role.permissions', compact('role', 'permissions', 'granted'));
    }

    public function permissionsUpdate(Request $request, Role $role)
    {
        $selected = $request->input('permissions', []);

        DB::table('role_permissions')
            ->where('role_id', $role->cis_row_id)
            ->whereNull('project_id')
            ->delete();

        foreach ($selected as $slug) {
            DB::table('role_permissions')->insert([
                'role_id'         => $role->cis_row_id,
                'permission_slug' => $slug,
                'project_id'      => null,
                'granted'         => true,
            ]);
        }

        session()->flash('success', 'Berechtigungen wurden gespeichert.');
        return redirect()->route('role.permissions', $role);
    }

    public function delete(Role $role)
    {
        return view('role.delete', ['role' => $role, 'users' => $role->users()->get()]);
    }

    public function destroy(Request $request, Role $role)
    {
        $request->validate(['delete_key' => 'required|string']);

        if ($request->delete_key !== 'DEL-' . $role->name) {
            return back()->withErrors(['delete_key' => 'Sicherheitsabfrage ist nicht korrekt.'])->withInput();
        }

        DB::table('role_permissions')->where('role_id', $role->cis_row_id)->delete();
        DB::table('user_roles')->where('role_id', $role->cis_row_id)->delete();
        $role->delete();

        session()->flash('success', 'Rolle wurde gelöscht.');
        return redirect()->route('role.index');
    }
}

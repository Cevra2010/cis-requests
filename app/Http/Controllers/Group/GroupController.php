<?php

namespace App\Http\Controllers\Group;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Permission;
use CisFoundation\CisPermissionManager\CisPermissionManager;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::withCount('users')->orderBy('name')->get();
        return view('group.index', compact('groups'));
    }

    public function create()
    {
        return view('group.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|size:7',
        ]);

        $group = Group::create($data);

        session()->flash('success', 'Gruppe „' . $group->name . '" wurde erstellt.');
        return redirect()->route('group.permissions', $group);
    }

    public function edit(Group $group)
    {
        return view('group.edit', compact('group'));
    }

    public function update(Request $request, Group $group)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'color'       => 'nullable|string|size:7',
        ]);

        $group->update($data);
        session()->flash('success', 'Gruppe wurde aktualisiert.');
        return redirect()->route('group.edit', $group);
    }

    public function permissions(Group $group)
    {
        $permissions = CisPermissionManager::grouped();
        $granted     = \DB::table('group_permissions')
            ->where('group_id', $group->cis_row_id)
            ->whereNull('project_id')
            ->pluck('granted', 'permission_slug');

        return view('group.permissions', compact('group', 'permissions', 'granted'));
    }

    public function permissionsUpdate(Request $request, Group $group)
    {
        $selected = $request->input('permissions', []);

        // Alle globalen Berechtigungen für diese Gruppe löschen und neu setzen
        \DB::table('group_permissions')
            ->where('group_id', $group->cis_row_id)
            ->whereNull('project_id')
            ->delete();

        foreach ($selected as $slug) {
            \DB::table('group_permissions')->insert([
                'group_id'        => $group->cis_row_id,
                'permission_slug' => $slug,
                'project_id'      => null,
                'granted'         => true,
            ]);
        }

        session()->flash('success', 'Berechtigungen wurden gespeichert.');
        return redirect()->route('group.permissions', $group);
    }

    public function delete(Group $group)
    {
        return view('group.delete', compact('group'));
    }

    public function destroy(Request $request, Group $group)
    {
        $request->validate([
            'delete_key' => 'required|string',
        ]);

        if ($request->delete_key !== 'DEL-' . $group->name) {
            return back()->withErrors(['delete_key' => 'Sicherheitsabfrage ist nicht korrekt.'])->withInput();
        }

        \DB::table('group_permissions')->where('group_id', $group->cis_row_id)->delete();
        \DB::table('user_groups')->where('group_id', $group->cis_row_id)->delete();
        $group->delete();

        session()->flash('success', 'Gruppe wurde gelöscht.');
        return redirect()->route('group.index');
    }
}

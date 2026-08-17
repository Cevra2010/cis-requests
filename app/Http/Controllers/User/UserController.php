<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Role;
use App\Models\User;
use CisFoundation\CisPermissionManager\CisPermissionManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with(['groups', 'roles'])->orderBy('lastname')->orderBy('firstname')->get();
        return view('user.index', compact('users'));
    }

    public function create()
    {
        return view('user.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'firstname' => $data['firstname'],
            'lastname'  => $data['lastname'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
        ]);

        session()->flash('success', 'Konto wurde erstellt. Bitte Gruppen und Rollen zuweisen.');
        return redirect()->route('user.edit.membership', $user);
    }

    public function edit(User $user)
    {
        return view('user.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->cis_row_id . ',cis_row_id',
        ]);

        $user->update($data);
        session()->flash('success', 'Benutzerkonto wurde aktualisiert.');
        return redirect()->route('user.edit', $user);
    }

    public function security(User $user)
    {
        return view('user.security', compact('user'));
    }

    public function securityUpdate(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        session()->flash('success', 'Passwort wurde geändert.');
        return redirect()->route('user.edit', $user);
    }

    // ── Gruppen & Rollen ─────────────────────────────────────────────────────

    public function membership(User $user)
    {
        $groups = Group::orderBy('name')->get();
        $roles  = Role::orderBy('name')->get();

        $userGroupIds = $user->groups()->pluck('groups.cis_row_id')->toArray();
        $userRoleIds  = $user->roles()->pluck('roles.cis_row_id')->toArray();

        return view('user.membership', compact('user', 'groups', 'roles', 'userGroupIds', 'userRoleIds'));
    }

    public function membershipUpdate(Request $request, User $user)
    {
        $groupIds = $request->input('groups', []);
        $roleIds  = $request->input('roles', []);

        DB::table('user_groups')->where('user_id', $user->cis_row_id)->delete();
        foreach ($groupIds as $groupId) {
            DB::table('user_groups')->insert(['user_id' => $user->cis_row_id, 'group_id' => $groupId]);
        }

        DB::table('user_roles')->where('user_id', $user->cis_row_id)->delete();
        foreach ($roleIds as $roleId) {
            DB::table('user_roles')->insert(['user_id' => $user->cis_row_id, 'role_id' => $roleId]);
        }

        session()->flash('success', 'Gruppen und Rollen wurden gespeichert.');
        return redirect()->route('user.edit.membership', $user);
    }

    // ── Explizite User-Berechtigungen ────────────────────────────────────────

    public function permissions(User $user)
    {
        $permissions    = CisPermissionManager::grouped();
        $userPerms      = DB::table('user_permissions')
            ->where('user_id', $user->cis_row_id)
            ->whereNull('project_id')
            ->get()
            ->keyBy('permission_slug');

        return view('user.permissions', compact('user', 'permissions', 'userPerms'));
    }

    public function permissionsUpdate(Request $request, User $user)
    {
        // granted = explizit erlaubt, denied = explizit verboten, absent = vererbt (kein Override)
        $granted = $request->input('granted', []);
        $denied  = $request->input('denied', []);

        DB::table('user_permissions')
            ->where('user_id', $user->cis_row_id)
            ->whereNull('project_id')
            ->delete();

        foreach ($granted as $slug) {
            DB::table('user_permissions')->insert([
                'user_id'         => $user->cis_row_id,
                'permission_slug' => $slug,
                'project_id'      => null,
                'granted'         => true,
            ]);
        }

        foreach ($denied as $slug) {
            if (!in_array($slug, $granted)) { // granted hat Vorrang bei doppelter Eingabe
                DB::table('user_permissions')->insert([
                    'user_id'         => $user->cis_row_id,
                    'permission_slug' => $slug,
                    'project_id'      => null,
                    'granted'         => false,
                ]);
            }
        }

        session()->flash('success', 'Berechtigungen wurden gespeichert.');
        return redirect()->route('user.permissions', $user);
    }

    // ── Löschen ──────────────────────────────────────────────────────────────

    public function delete(User $user)
    {
        return view('user.delete', compact('user'));
    }

    public function destroy(Request $request, User $user)
    {
        $request->validate(['delete_key' => 'required|string']);

        if ($request->delete_key !== 'DEL-' . $user->firstname . '/' . $user->lastname) {
            return back()->withErrors(['delete_key' => 'Sicherheitsabfrage ist nicht korrekt.'])->withInput();
        }

        DB::table('user_groups')->where('user_id', $user->cis_row_id)->delete();
        DB::table('user_roles')->where('user_id', $user->cis_row_id)->delete();
        DB::table('user_permissions')->where('user_id', $user->cis_row_id)->delete();
        $user->delete();

        session()->flash('success', 'Konto wurde gelöscht.');
        return redirect()->route('user');
    }

    // ── Eigenes Profil ────────────────────────────────────────────────────────

    public function self()
    {
        $user = auth()->user()->load(['groups', 'roles']);
        return view('user.self', compact('user'));
    }

    public function selfUpdate(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $data = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname'  => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $user->cis_row_id . ',cis_row_id',
            'phone'     => 'nullable|string|max:50',
            'birthdate' => 'nullable|date|before:today',
            'bio'       => 'nullable|string|max:500',
            'avatar'    => 'nullable|image|max:2048|mimes:jpg,jpeg,png,webp',
        ]);

        if ($request->hasFile('avatar')) {
            // Altes Avatar löschen
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $user->update($data);
        session()->flash('success', 'Profil wurde gespeichert.');
        return redirect()->route('dashboard.self');
    }

    public function selfPasswordUpdate(Request $request)
    {
        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        auth()->user()->update([
            'password' => Hash::make($request->password),
        ]);

        session()->flash('success', 'Passwort wurde geändert.');
        return redirect()->route('dashboard.self');
    }
}

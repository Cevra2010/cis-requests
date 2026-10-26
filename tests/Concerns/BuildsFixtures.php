<?php

namespace Tests\Concerns;

use App\Models\Group;
use App\Models\Product;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\WorkflowAction;
use App\Models\WorkflowStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Hilfen zum Anlegen von Testdaten. Der Standard-Workflow stammt aus der Seed-Migration. */
trait BuildsFixtures
{
    protected function makeUser(string $email = null, array $roles = [], string $lastname = null): User
    {
        $user = User::create([
            'firstname' => 'Test',
            'lastname'  => $lastname ?? Str::random(5),
            'email'     => $email ?? Str::lower(Str::random(8)) . '@example.test',
            'password'  => bcrypt('secret'),
        ]);
        foreach ($roles as $role) {
            $user->roles()->attach((string) ($role instanceof Role ? $role->cis_row_id : $role));
        }

        return $user;
    }

    protected function makeRole(string $name = null): Role
    {
        return Role::create(['name' => $name ?? 'Rolle ' . Str::random(5)]);
    }

    protected function makeGroup(string $name = null): Group
    {
        return Group::create(['name' => $name ?? 'Gruppe ' . Str::random(5)]);
    }

    protected function makeProduct(string $name = null): Product
    {
        return Product::create(['name' => $name ?? 'Produkt ' . Str::random(5)]);
    }

    /** Legt ein Projekt an und setzt es – ohne Statusübergangs-Logik – direkt auf den Schritt $status. */
    protected function makeProject(string $status = 'draft', string $type = 'product', array $attrs = []): Project
    {
        $project = Project::create($attrs + ['name' => 'Projekt ' . Str::random(5), 'type' => $type]);
        if ($project->status_code !== $status) {
            DB::table('projects')->where('cis_row_id', $project->cis_row_id)->update(['status_code' => $status]);
            $project = $project->fresh();
        }

        return $project;
    }

    protected function step(string $code, string $type = 'product'): WorkflowStatus
    {
        return WorkflowStatus::where('project_type', $type)->where('code', $code)->firstOrFail();
    }

    protected function addEnterAction(WorkflowStatus $step, string $type, array $config = [], bool $active = true): WorkflowAction
    {
        return WorkflowAction::create([
            'cis_row_id_workflow_status' => $step->cis_row_id,
            'trigger'                    => 'enter',
            'type'                       => $type,
            'sort_order'                 => 99,
            'is_active'                  => $active,
            'config'                     => $config,
        ]);
    }

    /** Benutzer mit einer Rolle, die jede bekannte Berechtigung global besitzt. */
    protected function makeAdmin(): User
    {
        $role = $this->makeRole('Voll-Admin');
        foreach (\App\Models\Permission::pluck('slug') as $slug) {
            DB::table('role_permissions')->insert(['role_id' => (string) $role->cis_row_id, 'permission_slug' => $slug, 'project_id' => null, 'granted' => true]);
        }

        return $this->makeUser(null, [$role]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Legt die Berechtigung zum Bearbeiten fixierter Ausschreibungen an und
 * gewährt sie der Administrator-Rolle. Behebt außerdem eine bestehende Lücke:
 * user_roles war leer, wodurch bisher niemand – auch kein Administrator –
 * jemals eine Rolle zugewiesen hatte.
 */
return new class extends Migration
{
    public function up(): void
    {
        $slug = 'project.tender.override_lock';

        DB::table('permissions')->updateOrInsert(
            ['slug' => $slug],
            [
                'label'       => 'Fixierte Ausschreibung bearbeiten',
                'module'      => null,
                'description' => 'Erlaubt das Bearbeiten von Produkten und Ausschreibungstext, nachdem ein Projekt fixiert wurde.',
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        $adminRole = DB::table('roles')->where('name', 'Administrator')->first();

        if ($adminRole) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $adminRole->cis_row_id, 'permission_slug' => $slug, 'project_id' => null],
                ['granted' => true]
            );

            // Den ursprünglich per Seeder vorgesehenen Administrator-Account nachträglich
            // zuordnen (die Zuweisung war zuvor an einer inzwischen entfernten
            // Tabelle gescheitert, siehe DatabaseSeeder).
            $admin = DB::table('users')->where('email', 'admin@istrator.de')->first();
            if ($admin) {
                DB::table('user_roles')->updateOrInsert([
                    'user_id' => $admin->cis_row_id,
                    'role_id' => $adminRole->cis_row_id,
                ]);
            }
        }
    }

    public function down(): void
    {
        $slug = 'project.tender.override_lock';
        DB::table('role_permissions')->where('permission_slug', $slug)->delete();
        DB::table('permissions')->where('slug', $slug)->delete();
    }
};

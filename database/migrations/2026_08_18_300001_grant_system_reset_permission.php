<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Gewährt die neue Berechtigung "system.reset" der Systemadministrator-Rolle.
 */
return new class extends Migration
{
    public function up(): void
    {
        $slug = 'system.reset';

        DB::table('permissions')->updateOrInsert(
            ['slug' => $slug],
            [
                'label'       => 'System zurücksetzen',
                'module'      => null,
                'description' => 'Erlaubt destruktive Reset-Vorgänge (Preise, Produktdaten, Systemdaten, Werkseinstellung) sowie das Einspielen von Demo-Daten.',
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        $adminRole = DB::table('roles')->where('name', 'Systemadministrator')->first();

        if ($adminRole) {
            DB::table('role_permissions')->updateOrInsert(
                ['role_id' => $adminRole->cis_row_id, 'permission_slug' => $slug, 'project_id' => null],
                ['granted' => true]
            );
        }
    }

    public function down(): void
    {
        DB::table('role_permissions')->where('permission_slug', 'system.reset')->delete();
        DB::table('permissions')->where('slug', 'system.reset')->delete();
    }
};

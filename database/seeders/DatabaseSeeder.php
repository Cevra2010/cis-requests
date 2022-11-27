<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Area;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            FirstUserSeeder::class,
            FirstRoleSeeder::class,
            FirstAreaSeeder::class,
            FirstProductSeeder::class,
        ]);

        /** Connect first User with first Role */
        $user = User::where("email","admin@istrator.de")->first();
        $role_admin = Role::where('name','Administrator')->first();
        $role_default = Role::where('name','Default')->first();
        $areas = Area::all();
        $area_dashbaord = Area::where('slug','dashboard')->first();

        DB::table('user_role')->insert([
            'cis_row_id_user' => $user->cis_row_id,
            'cis_row_id_role' => $role_admin->cis_row_id,
        ]);

        DB::table('user_role')->insert([
            'cis_row_id_user' => $user->cis_row_id,
            'cis_row_id_role' => $role_default->cis_row_id,
        ]);

        foreach($areas as $area) {
            DB::table('area_role')->insert([
                'cis_row_id_area' => $area->cis_row_id,
                'cis_row_id_role' => $role_admin->cis_row_id,
            ]);
        }

        DB::table('area_role')->insert([
            'cis_row_id_area' => $area_dashbaord->cis_row_id,
            'cis_row_id_role' => $role_default->cis_row_id,
        ]);
    }
}

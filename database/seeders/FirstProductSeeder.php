<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class FirstProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('products')->insert([
        [
        'cis_row_id' => Uuid::uuid4(),
        'name' => 'Demo Produkt 1',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
        ],
        [
        'cis_row_id' => Uuid::uuid4(),
        'name' => 'Demo Produkt 2',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
        ],
        [
        'cis_row_id' => Uuid::uuid4(),
        'name' => 'Demo Produkt 3',
        'created_at' => Carbon::now(),
        'updated_at' => Carbon::now(),
        ],
    ]);
    }
}

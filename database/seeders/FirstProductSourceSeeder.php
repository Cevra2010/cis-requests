<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class FirstProductSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('product_sources')->insert([
            [
                'cis_row_id' => Uuid::uuid4(),
                'name' => 'Lieferant 1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'name' => 'Lieferant 2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'name' => 'Lieferant 3',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]
    );
    }
}

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

        $productsArray = [];
        for($i = 1; $i < 80; $i++) {
            $productsArray[] = [
                'cis_row_id' => Uuid::uuid4(),
                'name' => 'Demo Produkt '.$i,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        DB::table('products')->insert($productsArray);
    }
}

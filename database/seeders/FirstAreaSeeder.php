<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class FirstAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('areas')->insert([
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'user',
            'parent_slug' => null,
            'name' => 'Benutzerverwaltung',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'user.edit',
            'parent_slug' => 'user',
            'name' => 'Benutzer ändern',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'user.edit.base',
            'parent_slug' => 'user.edit',
            'name' => 'Benutzer ändern (Stammdaten)',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'user.edit.roles',
            'parent_slug' => 'user.edit',
            'name' => 'Benutzer ändern (Rollen)',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'user.create',
            'parent_slug' => 'user',
            'name' => 'Benutzer erstellen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'user.edit.delete',
            'parent_slug' => 'user.edit',
            'name' => 'Benutzer löschen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'user.create.roles',
            'parent_slug' => 'user.create',
            'name' => 'Benutzer erstellen (Rollenvergabe)',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'role',
            'parent_slug' => null,
            'name' => 'Rollenverwaltung',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'role.create',
            'parent_slug' => 'role',
            'name' => 'Rolle erstellen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'role.edit',
            'parent_slug' => 'role',
            'name' => 'Rolle ändern',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'role.edit.delete',
            'parent_slug' => 'role.edit',
            'name' => 'Rolle löschen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'setting',
            'parent_slug' => null,
            'name' => 'Einstellungsverwaltung',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'dashboard',
            'parent_slug' => null,
            'name' => 'Dashboard',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'product',
            'parent_slug' => null,
            'name' => 'Produktverwaltung',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'product.create',
            'parent_slug' => 'product',
            'name' => 'Produkt erstellen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'product.edit',
            'parent_slug' => 'product',
            'name' => 'Produkt ändern',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'product.edit.price',
            'parent_slug' => 'product.edit',
            'name' => 'Preis eintragen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'product.edit.description',
            'parent_slug' => 'product.edit',
            'name' => 'Produktbeschreibung ändern',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'product.edit.parameter',
            'parent_slug' => 'product.edit',
            'name' => 'Produktparameter ändern',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'product.edit.delete',
            'parent_slug' => 'product.edit',
            'name' => 'Produkt löschen',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
            [
            'cis_row_id' => Uuid::uuid4(),
            'slug' => 'product.source',
            'parent_slug' => 'product.edit',
            'name' => 'Produktquellen verwalten',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            ],
    ]);
    }
}

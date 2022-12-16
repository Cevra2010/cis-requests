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


            /**
             * USER
             */
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

            /**
             * ROLE
             */
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

            /**
             * SETTINGS
             */

            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'setting',
                'parent_slug' => null,
                'name' => 'Einstellungsverwaltung',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            /**
             * DASHBOARD
             */

            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'dashboard',
                'parent_slug' => null,
                'name' => 'Dashboard',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            /**
             * PRODUCTS
             */

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
                'slug' => 'product.edit.rename',
                'parent_slug' => 'product.edit',
                'name' => 'Produkt ändern',
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

            /**
             * SOURCES
             */

            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'source',
                'parent_slug' => null,
                'name' => 'Produktquellen verwalten',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'source.create',
                'parent_slug' => 'source',
                'name' => 'Produktquelle hinzufügen',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'source.edit',
                'parent_slug' => 'source',
                'name' => 'Produktquelle bearbeiten',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'source.edit.delete',
                'parent_slug' => 'source.edit',
                'name' => 'Produktquelle löschen',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            /**
             * PRICES
             */
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'price',
                'parent_slug' => null,
                'name' => 'Preise verwalten',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'price.create',
                'parent_slug' => 'price',
                'name' => 'Produktpreise einpflegen',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

            /**
             * PROJECTS
             */
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'project',
                'parent_slug' => null,
                'name' => 'Projekte verwalten',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'project.create',
                'parent_slug' => 'project',
                'name' => 'Projekt erstellen',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'project.delete',
                'parent_slug' => 'project',
                'name' => 'Projekt löschen',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'project.edit',
                'parent_slug' => 'project',
                'name' => 'Projekt bearbeiten',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'cis_row_id' => Uuid::uuid4(),
                'slug' => 'project.edit.products',
                'parent_slug' => 'project.edit',
                'name' => 'Produkte im Projekt verwalten',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}

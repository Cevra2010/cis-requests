<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductSource;
use App\Models\Project;
use App\Models\ProjectProduct;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Spielt ein überschaubares, realistisches Demo-Datenset ein. Rein additiv –
 * löscht oder verändert nie bestehende Daten. Kann mehrfach ausgeführt werden
 * (Namen sind eindeutig genug gehalten, um Verwechslungen unwahrscheinlich zu
 * machen; ein erneuter Lauf legt aber bewusst neue Datensätze an statt alte
 * wiederzuverwenden, damit "Demo-Daten" nicht mit echten Daten kollidieren).
 */
class DemoDataService
{
    public function run(): array
    {
        return DB::transaction(function () {
            $groups     = $this->createGroups();
            $users      = $this->createUsers($groups);
            $categories = $this->createCategories();
            $sources    = $this->createSources();
            $products   = $this->createProducts($sources);
            $projects   = $this->createProjects($categories, $products);

            return [
                'Gruppen'         => count($groups),
                'Benutzer'        => count($users),
                'Kategorien'      => count($categories),
                'Produktquellen'  => count($sources),
                'Produkte'        => count($products),
                'Projekte'        => count($projects),
            ];
        });
    }

    private function createGroups(): array
    {
        $suffix = strtoupper(Str::random(4));

        return [
            Group::create(['name' => "Einkauf ({$suffix})", 'description' => 'Demo-Gruppe: Beschaffung & Bestellwesen', 'color' => '#3b82f6']),
            Group::create(['name' => "Lager ({$suffix})", 'description' => 'Demo-Gruppe: Wareneingang & Lagerhaltung', 'color' => '#16a34a']),
        ];
    }

    private function createUsers(array $groups): array
    {
        $names = [
            ['Sabine', 'Hartmann'],
            ['Jonas', 'Weller'],
            ['Petra', 'Sander'],
            ['Tobias', 'Krämer'],
            ['Nadine', 'Vogt'],
        ];

        $users = [];
        foreach ($names as $i => [$first, $last]) {
            $email = strtolower($first . '.' . $last) . '+demo-' . Str::random(4) . '@example.test';

            $user = User::create([
                'firstname' => $first,
                'lastname'  => $last,
                'email'     => $email,
                'password'  => Hash::make('Demo1234!'),
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $groups[$i % count($groups)]->users()->attach((string) $user->cis_row_id);

            $users[] = $user;
        }

        return $users;
    }

    private function createCategories(): array
    {
        $names  = ['Sonderfahrzeuge', 'Persönliche Schutzausrüstung', 'Atemschutz', 'Kommunikationstechnik'];
        $colors = ['#ef4444', '#f59e0b', '#8b5cf6', '#06b6d4'];

        $categories = [];
        foreach ($names as $i => $name) {
            $existing = DB::table('categories')
                ->where('type', 'project.category')
                ->where('name', $name)
                ->whereNull('deleted_at')
                ->first();

            if ($existing) {
                $categories[] = $existing;
                continue;
            }

            $id = DB::table('categories')->insertGetId([
                'type'       => 'project.category',
                'name'       => $name,
                'color'      => $colors[$i % count($colors)],
                'module'     => 'Demo',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $categories[] = (object) ['id' => $id, 'name' => $name];
        }

        return $categories;
    }

    private function createSources(): array
    {
        $sources = [
            ['name' => 'Muster Feuerwehrtechnik GmbH', 'contact_name' => 'Frank Ostermann', 'contact_email' => 'vertrieb@muster-ft.example', 'contact_phone' => '02451 998877'],
            ['name' => 'Nord Sicherheitstechnik', 'contact_name' => 'Anke Bruns', 'contact_email' => 'info@nord-sicherheit.example', 'contact_phone' => '0421 445566'],
            ['name' => 'Süd Brandschutz Handel', 'contact_name' => 'Elias Roth', 'contact_email' => 'kontakt@sued-brandschutz.example', 'contact_phone' => '089 112233'],
        ];

        return array_map(fn (array $s) => ProductSource::create($s), $sources);
    }

    private function createProducts(array $sources): array
    {
        $catalog = [
            'Feuerwehrhelm HPS 6100'        => 289.00,
            'Schutzjacke HuPF Teil 3'       => 415.50,
            'Schutzhose HuPF Teil 2'        => 289.90,
            'Feuerwehrstiefel Größe 43'     => 159.00,
            'Handfunkgerät BOS digital'     => 890.00,
            'Pressluftatmer PA 90 Plus'     => 2450.00,
            'Wärmebildkamera K2'            => 3990.00,
            'Löschdecke 180x180 cm'         => 45.00,
            'Rettungswesten-Set'            => 210.00,
            'Erste-Hilfe-Koffer DIN 13157'  => 89.90,
        ];

        $products = [];
        $i = 0;
        foreach ($catalog as $name => $price) {
            $product = Product::create(['name' => $name]);
            Price::add($price, $product, $sources[$i % count($sources)]);
            $products[] = $product;
            $i++;
        }

        return $products;
    }

    private function createProjects(array $categories, array $products): array
    {
        $definitions = [
            [
                'name'        => 'Demo-Projekt: Neubeschaffung TSF-W',
                'description' => 'Beispielprojekt zur Beschaffung persönlicher Schutzausrüstung für ein TSF-W.',
                'category'    => $categories[1] ?? null,
                'items'       => 4,
            ],
            [
                'name'        => 'Demo-Projekt: Ausrüstung Atemschutz',
                'description' => 'Beispielprojekt zur Beschaffung von Atemschutztechnik.',
                'category'    => $categories[2] ?? null,
                'items'       => 3,
            ],
        ];

        $projects = [];
        foreach ($definitions as $def) {
            $project = Project::create([
                'name'          => $def['name'],
                'description'   => $def['description'],
                'status_code'   => 'draft',
                'category_id'   => $def['category']?->id,
                'client'        => 'Freiwillige Feuerwehr Musterstadt',
                'tender_year'   => (int) now()->format('Y'),
            ]);

            $selection = collect($products)->shuffle()->take($def['items']);
            foreach ($selection as $index => $product) {
                ProjectProduct::create([
                    'cis_row_id_project' => $project->cis_row_id,
                    'cis_row_id_product' => $product->cis_row_id,
                    'product_count'      => random_int(1, 6),
                    'sort_order'         => $index + 1,
                ]);
            }

            $projects[] = $project;
        }

        return $projects;
    }
}

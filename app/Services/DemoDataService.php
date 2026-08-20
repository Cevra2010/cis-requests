<?php

namespace App\Services;

use App\Models\Group;
use App\Models\Price;
use App\Models\Product;
use App\Models\ProductSource;
use App\Models\Project;
use App\Models\ProjectProduct;
use App\Models\ProjectVehicleBlock;
use App\Models\ProjectVehicleBlockItem;
use App\Models\TemplateParameter;
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
            $groups            = $this->createGroups();
            $users             = $this->createUsers($groups);
            $categories        = $this->createCategories();
            $productCategories = $this->createProductCategories();
            $sources           = $this->createSources();
            $products          = $this->createProducts($sources, $productCategories);
            $projects          = $this->createProjects($categories, $products);

            $vehicleCategories = $this->createVehicleCategories();
            $parameters        = $this->createTemplateParameters($vehicleCategories);
            $vehicleProject    = $this->createVehicleProject($parameters);

            return [
                'Gruppen'                 => count($groups),
                'Benutzer'                => count($users),
                'Kategorien'              => count($categories) + count($productCategories) + count($vehicleCategories),
                'Produktquellen'          => count($sources),
                'Produkte'                => count($products),
                'Parameter'               => count($parameters),
                'Projekte'                => count($projects) + ($vehicleProject ? 1 : 0),
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

    /** Produktkategorien (Kategorie-Typ 'product.category'), zum Filtern des Produktkatalogs. */
    private function createProductCategories(): array
    {
        $names = ['Schläuche', 'Wasserführende Armaturen', 'Persönliche Schutzausrüstung'];

        $categories = [];
        foreach ($names as $i => $name) {
            $existing = DB::table('categories')
                ->where('type', 'product.category')
                ->where('name', $name)
                ->whereNull('deleted_at')
                ->first();

            $id = $existing?->id ?? DB::table('categories')->insertGetId([
                'type'       => 'product.category',
                'name'       => $name,
                'module'     => 'Demo',
                'sort_order' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $categories[$name] = $id;
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

    private function createProducts(array $sources, array $productCategories = []): array
    {
        $catalog = [
            'Feuerwehrhelm HPS 6100'        => ['price' => 289.00,  'category' => 'Persönliche Schutzausrüstung'],
            'Schutzjacke HuPF Teil 3'       => ['price' => 415.50,  'category' => 'Persönliche Schutzausrüstung'],
            'Schutzhose HuPF Teil 2'        => ['price' => 289.90,  'category' => 'Persönliche Schutzausrüstung'],
            'Feuerwehrstiefel Größe 43'     => ['price' => 159.00,  'category' => 'Persönliche Schutzausrüstung'],
            'Handfunkgerät BOS digital'     => ['price' => 890.00,  'category' => null],
            'Pressluftatmer PA 90 Plus'     => ['price' => 2450.00, 'category' => null],
            'Wärmebildkamera K2'            => ['price' => 3990.00, 'category' => null],
            'Löschdecke 180x180 cm'         => ['price' => 45.00,   'category' => null],
            'Rettungswesten-Set'            => ['price' => 210.00,  'category' => null],
            'Erste-Hilfe-Koffer DIN 13157'  => ['price' => 89.90,   'category' => null],
            'B-Druckschlauch 20m'           => ['price' => 145.00,  'category' => 'Schläuche'],
            'C-Druckschlauch 15m'           => ['price' => 95.00,   'category' => 'Schläuche'],
            'Hohlstrahlrohr C'              => ['price' => 320.00,  'category' => 'Wasserführende Armaturen'],
            'Verteiler B-CBC'               => ['price' => 180.00,  'category' => 'Wasserführende Armaturen'],
        ];

        $products = [];
        $i = 0;
        foreach ($catalog as $name => $def) {
            $product = Product::create([
                'name'        => $name,
                'category_id' => $def['category'] ? ($productCategories[$def['category']] ?? null) : null,
            ]);
            Price::add($def['price'], $product, $sources[$i % count($sources)]);
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

    /**
     * Klassifikations-Kategorien für Fahrzeug-Parameter (Kategorie-Typ 'vehicle.spec').
     * Reine Taxonomie – enthält keine Ausschreibungsinhalte, nur Gruppierungen wie
     * "Fahrgestell > Wattiefe", unter denen mehrere gleichartige Parameter-Varianten liegen.
     */
    private function createVehicleCategories(): array
    {
        $tree = [
            'Fahrgestell' => [
                'Winkel'   => [],
                'Wattiefe' => [],
            ],
            'Aufbau' => [
                'Wasserwerfer' => [],
            ],
        ];

        $created = [];
        $this->seedCategoryTree($tree, null, $created);

        return $created;
    }

    private function seedCategoryTree(array $nodes, ?int $parentId, array &$created): void
    {
        $sortOrder = 0;
        foreach ($nodes as $name => $children) {
            $existing = DB::table('categories')
                ->where('type', 'vehicle.spec')
                ->where('name', $name)
                ->where('parent_id', $parentId)
                ->whereNull('deleted_at')
                ->first();

            $id = $existing?->id ?? DB::table('categories')->insertGetId([
                'type'       => 'vehicle.spec',
                'name'       => $name,
                'parent_id'  => $parentId,
                'module'     => 'Demo',
                'sort_order' => $sortOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created[$name] = $id;
            $sortOrder++;

            if (! empty($children)) {
                $this->seedCategoryTree($children, $id, $created);
            }
        }
    }

    /**
     * Beispiel-Parameter für die Fahrzeug-Konfiguration: "Allrad" mit Unter-Parametern
     * (Rampenwinkel, Wattiefe) sowie eine weitere, alternative Wattiefe-Variante –
     * beide unter derselben Kategorie "Fahrgestell > Wattiefe" auffindbar.
     */
    private function createTemplateParameters(array $vehicleCategoryIds): array
    {
        $allrad = TemplateParameter::firstOrCreate(
            ['name' => 'Allrad'],
            ['description' => 'Das Fahrzeug ist ein Allrad-Fahrzeug.', 'sort_order' => 0]
        );

        $rampenwinkel = TemplateParameter::firstOrCreate(
            ['name' => 'Rampenwinkel min. 13°', 'cis_row_id_parent' => $allrad->cis_row_id],
            [
                'description' => 'Es besitzt einen Rampenwinkel von mindestens 13°.',
                'category_id' => $vehicleCategoryIds['Winkel'] ?? null,
                'sort_order'  => 0,
            ]
        );

        $wattiefe30 = TemplateParameter::firstOrCreate(
            ['name' => 'Wattiefe 30 cm', 'cis_row_id_parent' => $allrad->cis_row_id],
            [
                'description' => 'Es besitzt eine Wattiefe von 30 cm.',
                'category_id' => $vehicleCategoryIds['Wattiefe'] ?? null,
                'sort_order'  => 1,
            ]
        );

        $wattiefe50 = TemplateParameter::firstOrCreate(
            ['name' => 'Wattiefe 50 cm'],
            [
                'description' => 'Es besitzt eine Wattiefe von 50 cm.',
                'category_id' => $vehicleCategoryIds['Wattiefe'] ?? null,
                'sort_order'  => 0,
            ]
        );

        $wasserwerfer = TemplateParameter::firstOrCreate(
            ['name' => 'Wasserwerfer, schwenk- und neigbar'],
            [
                'description' => 'Wasserwerfer schwenk- und neigbar, Durchfluss min. 2000 l/min.',
                'category_id' => $vehicleCategoryIds['Wasserwerfer'] ?? null,
                'sort_order'  => 0,
            ]
        );

        return [$allrad, $rampenwinkel, $wattiefe30, $wattiefe50, $wasserwerfer];
    }

    /** Demo-Fahrzeugausschreibung mit bereits befüllter Fahrzeug-Konfiguration. */
    private function createVehicleProject(array $parameters): ?Project
    {
        $exists = Project::where('name', 'Demo-Projekt: Neubeschaffung Löschfahrzeug')->exists();
        if ($exists) {
            return null;
        }

        [$allrad, , , , $wasserwerfer] = $parameters;

        $project = Project::create([
            'name'        => 'Demo-Projekt: Neubeschaffung Löschfahrzeug',
            'description' => 'Beispielprojekt für eine Fahrzeugausschreibung mit Fahrzeug-Konfiguration.',
            'status_code' => 'draft',
            'type'        => 'vehicle',
            'client'      => 'Freiwillige Feuerwehr Musterstadt',
            'tender_year' => (int) now()->format('Y'),
        ]);

        $product = Product::firstOrCreate(['name' => 'Fahrzeug-Gesamtkonfiguration']);
        ProjectProduct::firstOrCreate(
            ['cis_row_id_project' => $project->cis_row_id, 'cis_row_id_product' => $product->cis_row_id],
            ['product_count' => 1, 'sort_order' => 0]
        );

        $fahrgestellBlock = ProjectVehicleBlock::create([
            'cis_row_id_project' => $project->cis_row_id,
            'title'              => 'Fahrgestell',
            'sort_order'         => 1,
        ]);
        $this->insertParameterIntoBlock($fahrgestellBlock, $allrad);

        $aufbauBlock = ProjectVehicleBlock::create([
            'cis_row_id_project' => $project->cis_row_id,
            'title'              => 'Aufbau',
            'sort_order'         => 2,
        ]);
        $this->insertParameterIntoBlock($aufbauBlock, $wasserwerfer);

        return $project;
    }

    private function insertParameterIntoBlock(ProjectVehicleBlock $block, TemplateParameter $parameter): void
    {
        $order = 0;
        foreach ($parameter->selfAndDescendantsFlat() as $entry) {
            $p      = $entry['parameter'];
            $prefix = str_repeat('　↳ ', $entry['depth']);
            $text   = $p->description ? "{$p->name}: {$p->description}" : $p->name;

            ProjectVehicleBlockItem::create([
                'cis_row_id_block'     => $block->cis_row_id,
                'type'                 => 'parameter',
                'text'                 => $prefix . $text,
                'cis_row_id_parameter' => $p->cis_row_id,
                'source_label'         => $p->name,
                'sort_order'           => $order++,
            ]);
        }
    }
}

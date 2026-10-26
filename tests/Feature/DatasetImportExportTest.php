<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Price;
use App\Models\ProductSource;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Services\Datasets\Dataset;
use Modules\Export\Services\Datasets\DefaultTemplates;
use Modules\Export\Services\Datasets\Datasets;
use Modules\Export\Services\ExportFileBuilder;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class DatasetImportExportTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        DefaultTemplates::ensure();
    }

    private function template(string $dataset): ExportTemplate
    {
        return ExportTemplate::with('columns')->where('dataset', $dataset)->firstOrFail();
    }

    /** Exportiert und ändert einzelne Zellen: $edits = [Zeilenindex => [Feldschlüssel => Wert]]; $new = zusätzliche Zeilen [Feldschlüssel => Wert]. */
    private function plan(string $dataset, array $edits = [], array $new = [], bool $allowCreate = true): array
    {
        $t    = $this->template($dataset);
        $ds   = Datasets::find($dataset);
        $rows = $ds->build($t)['rows'];
        $idx  = $t->columns->pluck('field_key')->flip()->all();

        foreach ($edits as $r => $fields) {
            foreach ($fields as $field => $value) {
                $rows[$r][$idx[$field]] = $value;
            }
        }
        foreach ($new as $fields) {
            $row = array_fill(0, $t->columns->count() + 1, '');
            foreach ($fields as $field => $value) {
                $row[$idx[$field]] = $value;
            }
            $rows[] = $row;
        }

        return [$ds, $t, $ds->plan($t, $rows, $allowCreate)];
    }

    private function actions(array $plan): array
    {
        return array_map(fn ($e) => $e['action'], $plan);
    }

    public function test_default_templates_exist_for_every_available_dataset(): void
    {
        foreach (array_keys(Datasets::available()) as $key) {
            $this->assertTrue(ExportTemplate::where('dataset', $key)->exists(), "Standardvorlage für {$key}");
        }
    }

    public function test_export_followed_by_import_check_changes_nothing_for_every_dataset(): void
    {
        $role = $this->makeRole('Einkäufer');
        $this->makeUser('rundlauf@example.test', [$role]);
        $this->makeGroup('Einkauf');
        Category::create(['type' => 'project.category', 'name' => 'Atemschutz']);
        $this->makeProject('draft', 'product', ['name' => 'Rundlauf-Projekt', 'tender_starts_at' => '2026-10-20 08:00', 'tender_ends_at' => '2026-10-27 18:30', 'due_date' => '2026-11-01']);
        $this->makeProduct('Schlauch');
        $halle = \Modules\Lager\Models\Lagerort::create(['name' => 'Halle']);
        \Modules\Lager\Models\Lagerort::create(['name' => 'Regal', 'cis_row_id_parent' => (string) $halle->cis_row_id]);

        foreach (Datasets::available() as $key => $dataset) {
            if (! $dataset->supportsImport()) {
                continue;
            }
            [, , $plan] = $this->plan($key);
            $this->assertNotEmpty($plan, "{$key} hat Zeilen");
            $this->assertSame([], array_values(array_diff($this->actions($plan), ['unchanged'])), "{$key}: Rundlauf muss unverändert sein");
        }
    }

    public function test_user_import_updates_fields_and_role_membership_but_never_creates_users(): void
    {
        $role = $this->makeRole('Sachbearbeiter');
        $user = $this->makeUser('alt@example.test');

        [$ds, $t, $plan] = $this->plan('users', [0 => ['us_phone' => '0611 123', 'us_roles' => 'Sachbearbeiter']], [['us_firstname' => 'Neu', 'us_email' => 'neu@example.test']]);
        $this->assertSame(['update', 'skipped'], array_slice($this->actions($plan), -2), 'neue Benutzer werden nicht angelegt');

        $ds->apply($plan);
        $user = $user->fresh();
        $this->assertSame('0611 123', $user->phone);
        $this->assertSame(['Sachbearbeiter'], $user->roles->pluck('name')->all());
        $this->assertNull(User::where('email', 'neu@example.test')->first());
    }

    public function test_user_import_rejects_unknown_roles_and_duplicate_emails_per_row(): void
    {
        $this->makeUser('a@example.test', [], 'Aaa');
        $this->makeUser('b@example.test', [], 'Bbb');

        [, , $plan] = $this->plan('users', [0 => ['us_roles' => 'GibtsNicht'], 1 => ['us_email' => 'a@example.test']]);

        $errors = array_values(array_filter($plan, fn ($e) => $e['action'] === 'error'));
        $this->assertCount(2, $errors);
        $this->assertStringContainsString('existiert nicht', $errors[0]['error']);
        $this->assertStringContainsString('bereits', $errors[1]['error']);
    }

    public function test_role_import_replaces_global_permissions_and_creates_roles(): void
    {
        Permission::updateOrCreate(['slug' => 'test.read'], ['label' => 'Lesen', 'module' => 'test']);
        Permission::updateOrCreate(['slug' => 'test.write'], ['label' => 'Schreiben', 'module' => 'test']);
        $this->makeRole('Alt');
        $member = $this->makeUser('mitglied@example.test');

        [$ds, , $plan] = $this->plan('roles', [0 => ['ro_permissions' => 'test.read; test.write']], [['ro_name' => 'Neu', 'ro_permissions' => 'test.read', 'ro_users' => 'mitglied@example.test']]);
        $ds->apply($plan);

        $alt = Role::where('name', 'Alt')->first();
        $neu = Role::where('name', 'Neu')->first();
        $this->assertSame(['test.read', 'test.write'], \DB::table('role_permissions')->where('role_id', (string) $alt->cis_row_id)->orderBy('permission_slug')->pluck('permission_slug')->all());
        $this->assertSame(['test.read'], \DB::table('role_permissions')->where('role_id', (string) $neu->cis_row_id)->pluck('permission_slug')->all());
        $this->assertSame([(string) $member->cis_row_id], $neu->users->pluck('cis_row_id')->map(fn ($i) => (string) $i)->all());
    }

    public function test_role_import_rejects_unknown_permissions(): void
    {
        $this->makeRole('Alt');

        [, , $plan] = $this->plan('roles', [0 => ['ro_permissions' => 'gibt.es.nicht']]);

        $this->assertSame('error', $plan[0]['action']);
        $this->assertStringContainsString('Unbekannte Berechtigung', $plan[0]['error']);
    }

    public function test_project_import_creates_projects_in_the_first_workflow_step_and_never_sets_the_status(): void
    {
        $this->assertArrayNotHasKey('pj_status', array_filter(Datasets::find('projects')->fields(), fn ($f) => ! empty($f['import'])));

        [$ds, , $plan] = $this->plan('projects', [], [['pj_name' => 'Importiert', 'pj_type' => 'Fahrzeugausschreibung', 'pj_tender_start' => '20.10.2026 08:00', 'pj_tender_end' => '27.10.2026 18:00']]);
        $this->assertSame('create', $plan[0]['action']);
        $ds->apply($plan);

        $project = Project::where('name', 'Importiert')->firstOrFail();
        $this->assertSame('vehicle', $project->type);
        $this->assertSame('draft', $project->status_code);
        $this->assertSame('2026-10-20 08:00', $project->tender_starts_at->format('Y-m-d H:i'));
    }

    public function test_project_import_validates_the_tender_period_and_dates(): void
    {
        $this->makeProject('draft', 'product', ['name' => 'P']);

        [, , $plan] = $this->plan('projects', [0 => ['pj_tender_start' => '20.10.2026 08:00', 'pj_tender_end' => '19.10.2026 08:00']]);
        $this->assertStringContainsString('nach dem Ausschreibungsbeginn', $plan[0]['error']);

        [, , $plan] = $this->plan('projects', [0 => ['pj_due_date' => 'morgen']]);
        $this->assertStringContainsString('kein gültiges Datum', $plan[0]['error']);
    }

    public function test_project_type_cannot_change_for_existing_projects(): void
    {
        $this->makeProject('draft', 'product', ['name' => 'P']);

        [, , $plan] = $this->plan('projects', [0 => ['pj_type' => 'Fahrzeugausschreibung']]);

        $this->assertSame('error', $plan[0]['action']);
    }

    public function test_product_price_import_creates_a_new_price_entry_and_needs_a_source(): void
    {
        $product = $this->makeProduct('Schlauch');
        $source  = ProductSource::create(['name' => 'Händler']);
        Price::add('10,00', $product, $source);
        \DB::table('prices')->update(['created_at' => now()->subDay()]); // der neue Preis muss eindeutig der jüngere sein

        [, , $plan] = $this->plan('products', [0 => ['pd_price' => '12,50', 'pd_price_source' => '']]);
        $this->assertSame('update', $plan[0]['action'], 'Quelle des bisherigen Preises wird übernommen');

        [$ds, , $plan] = $this->plan('products', [0 => ['pd_price' => '12,50']]);
        $ds->apply($plan);
        $this->assertSame(2, $product->prices()->count(), 'die Preis-Historie bleibt erhalten');
        $this->assertEquals(12.5, $product->fresh()->price()->amount);

        [, , $plan] = $this->plan('products', [0 => ['pd_price' => 'abc']]);
        $this->assertSame('error', $plan[0]['action']);
    }

    public function test_rows_without_import_key_are_skipped_unless_creation_is_enabled(): void
    {
        [, , $plan] = $this->plan('roles', [], [['ro_name' => 'Neu']], allowCreate: false);

        $this->assertSame('skipped', end($plan)['action']);
    }

    public function test_csv_export_neutralises_formula_injection(): void
    {
        $csv = (new ExportFileBuilder())->build(['Name'], [['=HYPERLINK("http://böse")'], ['@SUM(A1)'], ['Normal']], 'csv');

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'@SUM", $csv);
        $this->assertStringNotContainsString(';=HYPERLINK', $csv);
    }
}

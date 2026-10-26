<?php

namespace Tests\Feature;

use App\Models\Offer;
use App\Models\ProductSource;
use App\Models\ProjectProduct;
use App\Models\WorkflowStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

/** Fängt Render-/Routing-Fehler ab: jede Seite ohne Parameter und die Projektseite in JEDEM Workflow-Schritt. */
class PageSmokeTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_every_parameterless_page_renders_for_an_administrator(): void
    {
        $this->actingAs($this->makeAdmin());

        $skip = ['Debug', 'Logout', 'logout', 'Login', 'login', 'Setup', 'Cron', 'Download', 'PDF', 'Export/Table', 'Export/Transfer/'];
        $checked = 0;
        $failures = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();
            if (! in_array('GET', $route->methods(), true) || str_contains($uri, '{') || str_starts_with($uri, 'livewire') || str_starts_with($uri, '_') || str_starts_with($uri, 'api/') || str_starts_with($uri, 'sanctum')) {
                continue;
            }
            foreach ($skip as $needle) {
                if (str_contains($uri, $needle)) {
                    continue 2;
                }
            }

            $status = $this->get('/' . ltrim($uri, '/'))->getStatusCode();
            $checked++;
            if ($status >= 400 || $status === 500) {
                $failures[] = "{$uri} → {$status}";
            }
        }

        $this->assertGreaterThan(15, $checked, 'es wurden zu wenige Seiten geprüft');
        $this->assertSame([], $failures, "Seiten mit Fehlern:\n" . implode("\n", $failures));
    }

    public function test_project_page_renders_on_every_workflow_step_of_both_project_types(): void
    {
        $this->actingAs($this->makeAdmin());
        $source  = ProductSource::create(['name' => 'Händler']);
        $product = $this->makeProduct('Teil');
        $failures = [];

        foreach (['product', 'vehicle'] as $type) {
            foreach (WorkflowStatus::where('project_type', $type)->orderBy('sort_order')->get() as $step) {
                $project = $this->makeProject($step->code, $type);
                ProjectProduct::create(['cis_row_id_project' => $project->cis_row_id, 'cis_row_id_product' => $product->cis_row_id, 'product_count' => 1, 'sort_order' => 0]);
                Offer::create(['cis_row_id_project' => $project->cis_row_id, 'cis_row_id_source' => $source->cis_row_id, 'active' => true]);

                foreach ([route('project.show', $project->cis_row_id), route('project.edit', $project->cis_row_id)] as $url) {
                    $status = $this->get($url)->getStatusCode();
                    if ($status !== 200) {
                        $failures[] = "{$type}/{$step->code} {$url} → {$status}";
                    }
                }
            }
        }

        $this->assertSame([], $failures, implode("\n", $failures));
    }

    public function test_project_pages_show_only_the_tabs_of_the_current_step(): void
    {
        $this->actingAs($this->makeAdmin());
        $draft = $this->makeProject('draft');

        $html = $this->get(route('project.show', $draft->cis_row_id))->assertOk()->getContent();

        $this->assertStringNotContainsString("tab = 'angebote'", $html, 'Angebote sind im Entwurf noch nicht sichtbar');
        $this->assertStringContainsString("tab = 'beladung'", $html);
    }

    public function test_status_buttons_are_protected_by_the_approvers_of_the_workflow(): void
    {
        $project = $this->makeProject('draft');
        $this->actingAs($this->makeUser()); // keine Rolle → kein Freigeber für "Bereit zur Ausschreibung"/"Ausschreibung"

        $this->post(route('project.status.advance', $project->cis_row_id))->assertForbidden();
        $this->assertSame('draft', $project->fresh()->status_code);

        $admin = $this->makeAdmin();
        \App\Services\Workflow\WorkflowDefaults::grantRoleEverywhere((string) $admin->roles->first()->cis_row_id); // wie der Einrichtungs-Assistent
        \App\Services\Workflow\WorkflowRegistry::flush();
        $this->actingAs($admin);
        $this->post(route('project.status.advance', $project->cis_row_id))->assertRedirect();
        $this->assertNotSame('draft', $project->fresh()->status_code);
    }
}

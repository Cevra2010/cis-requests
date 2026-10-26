<?php

namespace Tests\Feature;

use App\Http\Livewire\Workflow\WorkflowEditor;
use App\Models\WorkflowAction;
use App\Models\WorkflowStatus;
use App\Services\Workflow\WorkflowRegistry;
use App\Services\Workflow\WorkflowTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class WorkflowEditorTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    private function editor(string $type = 'product')
    {
        $this->actingAs($this->makeAdmin());

        return Livewire::test(WorkflowEditor::class)->call('setType', $type);
    }

    private function id(string $code, string $type = 'product'): string
    {
        return (string) $this->step($code, $type)->cis_row_id;
    }

    public function test_only_users_with_the_workflow_right_may_open_the_editor(): void
    {
        $this->actingAs($this->makeUser());

        Livewire::test(WorkflowEditor::class)->assertForbidden();
    }

    public function test_a_custom_step_is_inserted_after_the_selected_step_and_can_never_land_behind_completed(): void
    {
        $editor = $this->editor();

        $editor->call('selectStep', $this->id('ready_for_tender'))->call('addStep', 'custom');
        $codes = WorkflowStatus::where('project_type', 'product')->orderBy('sort_order')->pluck('code')->all();
        $this->assertStringStartsWith('custom_', $codes[array_search('ready_for_tender', $codes) + 1]);

        $editor->set('selectedStepId', null)->call('addStep', 'custom');
        $codes = WorkflowStatus::where('project_type', 'product')->orderBy('sort_order')->pluck('code')->all();
        $this->assertSame('completed', end($codes));
        $this->assertNull($editor->get('error'));
    }

    public function test_reordering_against_the_logical_order_is_rejected(): void
    {
        $editor = $this->editor();
        $ids    = WorkflowStatus::where('project_type', 'product')->orderBy('sort_order')->pluck('cis_row_id')->map(fn ($i) => (string) $i)->all();
        $bad    = $ids;
        [$bad[2], $bad[4]] = [$bad[4], $bad[2]]; // z.B. "Ausgewertet" vor "Ausschreibung"

        $editor->call('reorderSteps', $bad)->assertSet('error', fn ($e) => $e !== null);

        $this->assertSame($ids, WorkflowStatus::where('project_type', 'product')->orderBy('sort_order')->pluck('cis_row_id')->map(fn ($i) => (string) $i)->all());
    }

    public function test_fixation_actions_are_validated_when_dropped_onto_steps(): void
    {
        $editor = $this->editor();

        // Angebote können erst ab dem Schritt fixiert werden, der Angebote freischaltet.
        $editor->call('addActionAt', $this->id('draft'), 'fix_offers', 0, null)->assertSet('error', fn ($e) => $e && str_contains($e, 'erst ab'));
        $this->assertSame(0, WorkflowAction::where('type', 'fix_offers')->count());

        // Produkte sind schon beim Schritt "Ausschreibung" fixiert → eine zweite Fixierung gleicher Art ist unzulässig.
        $editor->call('addActionAt', $this->id('evaluated'), 'fix_products', 0, null)->assertSet('error', fn ($e) => $e && str_contains($e, 'nur einmal'));

        // An einem Trigger ergeben Fixierungen keinen Sinn.
        $editor->call('addActionAt', $this->id('evaluated'), 'fix_offers', 0, 'irgendein-trigger')->assertSet('error', fn ($e) => $e && str_contains($e, 'Trigger'));

        // Gültig: Angebote ab "Bereit zur Auswertung".
        $editor->call('addActionAt', $this->id('ready_for_evaluation'), 'fix_offers', 0, null)->assertSet('error', null);
        WorkflowRegistry::flush();
        $this->assertTrue($this->makeProject('ready_for_evaluation')->isFixed('offers'));
    }

    public function test_goto_actions_are_active_immediately_while_email_actions_wait_for_configuration(): void
    {
        $editor = $this->editor();
        $editor->call('addActionAt', $this->id('draft'), 'goto_step', 0, null)
               ->call('addActionAt', $this->id('draft'), 'email', 1, null);

        $this->assertTrue((bool) WorkflowAction::where('type', 'goto_step')->latest('created_at')->first()->is_active);
        $this->assertFalse((bool) WorkflowAction::where('type', 'email')->latest('created_at')->first()->is_active);
    }

    public function test_tabs_can_be_hidden_only_when_logically_available_and_one_tab_always_stays(): void
    {
        $editor = $this->editor();
        $draft  = $this->id('draft');

        $editor->call('toggleHiddenTab', $draft, 'angebote'); // noch nicht verfügbar → wirkungslos
        $this->assertSame([], $this->step('draft')->hiddenTabs());

        foreach (['lager', 'ausschreibung'] as $tab) {
            $editor->call('toggleHiddenTab', $draft, $tab);
        }
        $editor->call('toggleHiddenTab', $draft, 'beladung')->assertSet('error', fn ($e) => $e && str_contains($e, 'Mindestens ein Tab'));
        $this->assertEqualsCanonicalizing(['lager', 'ausschreibung'], $this->step('draft')->hiddenTabs());

        $project = $this->makeProject('draft');
        $this->assertSame(['beladung'], array_keys(array_filter(WorkflowTabs::visible($project))));

        $editor->call('toggleHiddenTab', $draft, 'lager');
        $this->assertSame(['beladung', 'lager'], array_keys(array_filter(WorkflowTabs::visible($this->makeProject('draft')))));
    }

    public function test_unavailable_tabs_report_their_reason(): void
    {
        $availability = WorkflowTabs::availability(WorkflowRegistry::for('vehicle'), 'draft', 'vehicle');

        $this->assertFalse($availability['ausschreibung']['available']);
        $this->assertFalse($availability['angebote']['available']);
        $this->assertStringContainsString('Erst ab dem Schritt', $availability['angebote']['reason']);
        $this->assertTrue($availability['beladung']['available']);
    }

    public function test_deleting_a_step_removes_its_actions_and_triggers(): void
    {
        $editor = $this->editor();
        $editor->call('addStep', 'custom');
        $custom = WorkflowStatus::where('kind', 'custom')->firstOrFail();
        $this->addEnterAction($custom, 'goto_step', ['target' => 'next']);

        $editor->call('deleteStep', (string) $custom->cis_row_id);

        $this->assertSame(0, WorkflowAction::where('cis_row_id_workflow_status', (string) $custom->cis_row_id)->count());
    }

    public function test_required_steps_cannot_be_deleted_and_used_steps_neither(): void
    {
        $editor = $this->editor();
        $editor->call('deleteStep', $this->id('tender'))->assertSet('error', fn ($e) => $e && str_contains($e, 'Pflicht'));

        $editor->call('addStep', 'custom');
        $custom = WorkflowStatus::where('kind', 'custom')->firstOrFail();
        $this->makeProject($custom->code);
        $editor->call('deleteStep', (string) $custom->cis_row_id)->assertSet('error', fn ($e) => $e && str_contains($e, 'verwendet'));
    }
}

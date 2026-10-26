<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Services\Workflow\WorkflowRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class WorkflowFixationTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_default_workflow_fixes_products_and_tender_text_from_the_tender_step(): void
    {
        $workflow = WorkflowRegistry::for('product');

        $this->assertSame([], $workflow->fixedScopesAt('ready_for_tender'));
        $this->assertSame(['products', 'tender'], $workflow->fixedScopesAt('tender'));
        $this->assertSame(['products', 'tender'], $workflow->fixedScopesAt('completed'));
    }

    public function test_vehicle_workflow_has_no_tender_text_fixation(): void
    {
        $this->assertSame(['products'], WorkflowRegistry::for('vehicle')->fixedScopesAt('tender'));
    }

    public function test_project_editability_follows_the_current_step(): void
    {
        $open  = $this->makeProject('draft');
        $fixed = $this->makeProject('tender');

        $this->assertTrue($open->isEditable('products'));
        $this->assertTrue($open->isEditable('tender'));
        $this->assertFalse($fixed->isEditable('products'));
        $this->assertFalse($fixed->isEditable('tender'));
        $this->assertFalse($fixed->isEditableBy($this->makeUser()), 'auch kein Benutzer darf ein fixiertes Projekt bearbeiten');
    }

    public function test_fixation_actions_added_to_a_step_fix_offers_and_awarding_from_there(): void
    {
        $this->addEnterAction($this->step('ready_for_evaluation'), 'fix_offers');
        $this->addEnterAction($this->step('ordered'), 'fix_awarding');
        WorkflowRegistry::flush();

        $this->assertFalse($this->makeProject('tender')->isFixed('offers'));
        $this->assertTrue($this->makeProject('ready_for_evaluation')->isFixed('offers'));
        $this->assertFalse($this->makeProject('evaluated')->isFixed('awarding'));
        $this->assertTrue($this->makeProject('ordered')->isFixed('awarding'));
        $this->assertSame(['products', 'tender', 'offers', 'awarding'], $this->makeProject('completed')->fixedScopes());
    }

    public function test_inactive_fixation_actions_have_no_effect(): void
    {
        $this->addEnterAction($this->step('evaluated'), 'fix_offers', active: false);
        WorkflowRegistry::flush();

        $this->assertFalse($this->makeProject('completed')->isFixed('offers'));
    }

    public function test_reverting_past_a_fixation_opens_it_again_and_requires_the_override_right(): void
    {
        $this->addEnterAction($this->step('ready_for_evaluation'), 'fix_offers');
        WorkflowRegistry::flush();

        $project = $this->makeProject('ready_for_evaluation');
        $change  = $project->workflow()->fixationChange('ready_for_evaluation', 'tender');
        $this->assertSame(['offers'], $change['opened']);

        $nobody = $this->makeUser();
        $this->assertFalse($project->canRevertStatus($nobody), 'ohne Freigabe-/Aufheben-Recht kein Zurücksetzen');
    }

    public function test_price_snapshots_follow_the_products_fixation(): void
    {
        $product = $this->makeProduct();
        $source  = \App\Models\ProductSource::create(['name' => 'Händler']);
        \App\Models\Price::add('12,50', $product, $source);

        $project = $this->makeProject('ready_for_tender');
        \App\Models\ProjectProduct::create(['cis_row_id_project' => $project->cis_row_id, 'cis_row_id_product' => $product->cis_row_id, 'product_count' => 2, 'sort_order' => 0]);

        $project->advanceStatus();
        $this->assertSame('tender', $project->fresh()->status_code);
        $this->assertSame(1, $project->priceSnapshots()->count(), 'beim Fixieren werden die Preise eingefroren');
        $this->assertNotNull($project->fresh()->tender_locked_at);

        $project->fresh()->revertStatus();
        $this->assertSame(0, $project->priceSnapshots()->count(), 'beim Aufheben der Fixierung werden die Preise wieder freigegeben');
        $this->assertNull($project->fresh()->tender_locked_at);
    }
}

<?php

namespace Tests\Feature;

use App\Http\Livewire\Project\AwardManager;
use App\Http\Livewire\Project\OfferComparison;
use App\Http\Livewire\Project\ProjectProductManager;
use App\Http\Livewire\Project\TenderEditor;
use App\Models\Offer;
use App\Models\ProductSource;
use App\Models\ProjectProduct;
use App\Models\ProjectTenderBlock;
use App\Services\Workflow\WorkflowRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class ProjectLockEnforcementTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->makeUser());
    }

    public function test_products_can_be_added_while_the_project_is_open_but_not_when_fixed(): void
    {
        $product = $this->makeProduct();
        $open    = $this->makeProject('draft');
        $fixed   = $this->makeProject('tender');

        Livewire::test(ProjectProductManager::class, ['projectId' => $open->cis_row_id])->call('add', $product->cis_row_id);
        Livewire::test(ProjectProductManager::class, ['projectId' => $fixed->cis_row_id])->call('add', $product->cis_row_id);

        $this->assertSame(1, ProjectProduct::where('cis_row_id_project', $open->cis_row_id)->count());
        $this->assertSame(0, ProjectProduct::where('cis_row_id_project', $fixed->cis_row_id)->count());
    }

    public function test_tender_text_is_locked_by_the_tender_fixation_not_by_the_product_fixation(): void
    {
        $open  = $this->makeProject('draft');
        $fixed = $this->makeProject('tender');

        Livewire::test(TenderEditor::class, ['projectId' => $open->cis_row_id])->call('addBlock', 'heading');
        Livewire::test(TenderEditor::class, ['projectId' => $fixed->cis_row_id])->call('addBlock', 'heading');

        $this->assertGreaterThan(0, ProjectTenderBlock::where('cis_row_id_project', $open->cis_row_id)->count());
        $before = ProjectTenderBlock::where('cis_row_id_project', $fixed->cis_row_id)->count();
        Livewire::test(TenderEditor::class, ['projectId' => $fixed->cis_row_id])->call('addBlock', 'text');
        $this->assertSame($before, ProjectTenderBlock::where('cis_row_id_project', $fixed->cis_row_id)->count());
    }

    public function test_offers_are_locked_only_once_the_offers_fixation_is_reached(): void
    {
        $this->addEnterAction($this->step('ready_for_evaluation'), 'fix_offers');
        WorkflowRegistry::flush();

        $source  = ProductSource::create(['name' => 'Händler A']);
        $before  = $this->makeProject('tender');
        $locked  = $this->makeProject('ready_for_evaluation');

        Livewire::test(OfferComparison::class, ['projectId' => $before->cis_row_id])
            ->set('newSourceId', $source->cis_row_id)->call('createOffer');
        Livewire::test(OfferComparison::class, ['projectId' => $locked->cis_row_id])
            ->set('newSourceId', $source->cis_row_id)->call('createOffer');

        $this->assertSame(1, Offer::where('cis_row_id_project', $before->cis_row_id)->count());
        $this->assertSame(0, Offer::where('cis_row_id_project', $locked->cis_row_id)->count());
    }

    public function test_award_decisions_are_locked_with_the_awarding_fixation(): void
    {
        $this->addEnterAction($this->step('ordered'), 'fix_awarding');
        WorkflowRegistry::flush();

        $source  = ProductSource::create(['name' => 'Händler B']);
        $project = $this->makeProject('ordered');
        $offer   = Offer::create(['cis_row_id_project' => $project->cis_row_id, 'cis_row_id_source' => $source->cis_row_id, 'active' => true]);

        Livewire::test(AwardManager::class, ['projectId' => $project->cis_row_id])->call('excludeOffer', $offer->cis_row_id);

        $this->assertTrue((bool) $offer->fresh()->active, 'ein fixierter Bestellbereich lässt keine Zuordnungsänderung mehr zu');
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WorkflowActionRun;
use App\Models\WorkflowStatus;
use App\Models\WorkflowTriggerRun;
use App\Services\SystemResetService;
use App\Services\Workflow\WorkflowRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Export\Models\ExportTemplate;
use Modules\Export\Services\Datasets\DefaultTemplates;
use Tests\Concerns\BuildsFixtures;
use Tests\TestCase;

class SystemResetTest extends TestCase
{
    use RefreshDatabase, BuildsFixtures;

    public function test_factory_reset_empties_everything_and_the_workflow_is_seeded_again_on_demand(): void
    {
        $project = $this->makeProject('tender');
        WorkflowActionRun::create(['cis_row_id_project' => (string) $project->cis_row_id, 'cis_row_id_workflow_action' => 'x', 'status' => 'done']);
        WorkflowTriggerRun::create(['cis_row_id_project' => (string) $project->cis_row_id, 'cis_row_id_workflow_trigger' => 'x', 'occurred_at' => now()]);
        $this->makeUser();

        (new SystemResetService())->factoryReset();
        WorkflowRegistry::flush();

        $this->assertSame(0, User::count(), 'danach greift der Einrichtungs-Assistent');
        $this->assertSame(0, WorkflowActionRun::count());
        $this->assertSame(0, WorkflowTriggerRun::count());
        $this->assertSame(0, WorkflowStatus::count());

        // Erster Zugriff seedet den Standard-Workflow samt Fixierungen und Trigger neu.
        $this->assertSame(['products', 'tender'], WorkflowRegistry::for('product')->fixedScopesAt('tender'));
        $this->assertSame(['products'], WorkflowRegistry::for('vehicle')->fixedScopesAt('tender'));
        $this->assertSame(1, \App\Models\WorkflowTrigger::whereIn('cis_row_id_workflow_status', WorkflowStatus::where('project_type', 'product')->pluck('cis_row_id'))->count());
    }

    public function test_default_export_templates_are_not_duplicated_after_the_settings_were_reset(): void
    {
        DefaultTemplates::ensure();
        $before = ExportTemplate::count();

        \DB::table('settings')->delete(); // z.B. durch den Reset der Einstellungen
        DefaultTemplates::ensure();

        $this->assertSame($before, ExportTemplate::count());
    }

    public function test_a_deliberately_deleted_default_template_does_not_come_back(): void
    {
        DefaultTemplates::ensure();
        ExportTemplate::where('dataset', 'roles')->get()->each->delete();

        DefaultTemplates::ensure();

        $this->assertFalse(ExportTemplate::where('dataset', 'roles')->exists());
    }
}

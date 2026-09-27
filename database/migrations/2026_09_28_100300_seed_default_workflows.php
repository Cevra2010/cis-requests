<?php

use App\Services\Workflow\WorkflowDefaults;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        WorkflowDefaults::ensureSeeded('product');
        WorkflowDefaults::ensureSeeded('vehicle');
    }

    public function down(): void
    {
        DB::table('workflow_statuses')->whereIn('project_type', ['product', 'vehicle'])->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Je Workflow-Schritt: Projekt-Tabs, die auf diesem Schritt verborgen werden (siehe App\Services\Workflow\WorkflowTabs). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workflow_statuses', function (Blueprint $table) {
            $table->json('hidden_tabs')->nullable()->after('default_tab');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_statuses', function (Blueprint $table) {
            $table->dropColumn('hidden_tabs');
        });
    }
};

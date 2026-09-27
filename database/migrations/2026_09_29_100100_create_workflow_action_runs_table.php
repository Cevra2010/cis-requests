<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_action_runs', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project')->index();
            $table->string('cis_row_id_workflow_action')->index();
            $table->string('cis_row_id_transition')->nullable()->index();
            $table->string('status', 20); // 'sent' | 'partial' | 'failed' | 'skipped'
            $table->string('summary')->nullable();
            $table->json('details')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_action_runs');
    }
};

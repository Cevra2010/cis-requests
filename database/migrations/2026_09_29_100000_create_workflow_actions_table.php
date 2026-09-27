<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_actions', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_workflow_status')->index();
            $table->string('trigger', 20)->default('enter'); // 'enter' – reserviert für später z.B. 'revert'
            $table->string('type', 40); // WorkflowActionTypes-Key, z.B. 'email'
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(false);
            $table->json('config')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_actions');
    }
};

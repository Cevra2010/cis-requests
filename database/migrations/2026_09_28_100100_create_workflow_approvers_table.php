<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_approvers', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('project_type', 20)->index();
            $table->string('scope', 20); // 'transition' | 'lock_override'
            $table->string('cis_row_id_workflow_status')->nullable()->index();
            $table->string('principal_type', 10); // 'user' | 'group' | 'role'
            $table->string('principal_id')->index();
            $table->timestamps();

            $table->index(['project_type', 'scope']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_approvers');
    }
};

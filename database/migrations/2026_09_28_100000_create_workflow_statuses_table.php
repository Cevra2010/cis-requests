<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_statuses', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('project_type', 20)->index();
            $table->string('code', 40);
            $table->string('kind', 40);
            $table->string('label', 100);
            $table->text('description')->nullable();
            $table->string('color', 20)->default('gray');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('approval_mode', 20)->default('anyone');
            $table->boolean('auto_advance_on_due')->default(false);
            $table->string('default_tab', 30)->nullable();
            $table->timestamps();

            $table->unique(['project_type', 'code']);
            $table->index(['project_type', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_statuses');
    }
};

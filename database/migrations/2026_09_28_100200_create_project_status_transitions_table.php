<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_status_transitions', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project')->index();
            $table->string('from_code', 40)->nullable();
            $table->string('to_code', 40);
            $table->string('from_label', 100)->nullable();
            $table->string('to_label', 100);
            $table->string('direction', 10); // 'initial' | 'advance' | 'revert' | 'auto'
            $table->string('cis_row_id_user')->nullable();
            $table->timestamps();

            $table->index(['cis_row_id_project', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_status_transitions');
    }
};

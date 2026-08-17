<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('cis_row_id_source');
            $table->string('reference')->nullable();
            $table->date('submitted_at')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('min_value_ignored')->default(false);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['cis_row_id_project', 'cis_row_id_source'], 'offers_project_source_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};

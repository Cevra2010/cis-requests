<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_price_snapshots', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('cis_row_id_product');
            $table->decimal('amount', 10, 2);
            $table->string('source_name')->nullable();
            $table->timestamp('frozen_at');
            $table->timestamps();

            $table->unique(['cis_row_id_project', 'cis_row_id_product']);
            $table->index('cis_row_id_project');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_price_snapshots');
    }
};

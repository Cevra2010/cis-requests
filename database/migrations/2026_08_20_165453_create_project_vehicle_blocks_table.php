<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_vehicle_blocks', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('title');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('cis_row_id_project');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_vehicle_blocks');
    }
};

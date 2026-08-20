<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_vehicle_block_items', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_block');
            $table->string('type', 20)->default('text'); // text | parameter
            $table->text('text')->nullable();
            $table->string('cis_row_id_parameter')->nullable();
            $table->string('source_label')->nullable(); // Name des Parameters zum Zeitpunkt der Übernahme
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('cis_row_id_block');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_vehicle_block_items');
    }
};

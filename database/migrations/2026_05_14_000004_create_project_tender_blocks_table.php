<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_tender_blocks', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('type'); // heading | properties | products
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('config')->nullable(); // {"text": "Fahrgestell"} for heading type
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_tender_blocks');
    }
};

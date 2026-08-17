<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('position_awards', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('cis_row_id_project_product')->unique();
            $table->string('cis_row_id_offer')->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('position_awards');
    }
};

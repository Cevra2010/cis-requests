<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_template_columns', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_template');
            $table->string('label');
            $table->string('field_key');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('cis_row_id_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_template_columns');
    }
};

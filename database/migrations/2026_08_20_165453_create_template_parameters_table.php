<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_parameters', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('cis_row_id_parent')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->index('cis_row_id_parent');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_parameters');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tender_template_blocks', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_template');
            $table->string('type'); // heading | text | space | products
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->index('cis_row_id_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tender_template_blocks');
    }
};

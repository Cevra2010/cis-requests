<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_items', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_offer');
            $table->string('cis_row_id_project_product');
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('not_offered')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['cis_row_id_offer', 'cis_row_id_project_product'], 'offer_items_offer_position_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_items');
    }
};

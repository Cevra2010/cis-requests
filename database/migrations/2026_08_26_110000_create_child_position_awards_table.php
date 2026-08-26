<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zuordnung eines Unterprodukts (projektweit aggregiert, siehe OfferChildItem/
 * ChildProductAggregator) zu genau einem Angebot/Anbieter – unabhängig davon,
 * bei welchem Anbieter die zugehörigen Elternprodukte bestellt werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('child_position_awards', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('cis_row_id_product');
            $table->string('cis_row_id_offer')->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();

            $table->unique(['cis_row_id_project', 'cis_row_id_product'], 'child_position_awards_project_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('child_position_awards');
    }
};

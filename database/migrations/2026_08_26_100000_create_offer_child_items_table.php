<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vergleichspositionen für Unterprodukte im Angebotsvergleich. Ein Unterprodukt
 * kann mehreren Elternprodukten im selben Projekt zugeordnet sein (z.B.
 * "Übergangsstück" bei "Strahlrohr" UND "Verteiler") – hier gibt es je Angebot
 * nur EINE Zeile pro Unterprodukt (nicht je Eltern-Position), da die Mengen
 * bereits über alle Positionen aggregiert verglichen werden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_child_items', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_offer');
            $table->string('cis_row_id_product');
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('not_offered')->default(false);
            $table->timestamps();

            $table->unique(['cis_row_id_offer', 'cis_row_id_product'], 'offer_child_items_offer_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_child_items');
    }
};

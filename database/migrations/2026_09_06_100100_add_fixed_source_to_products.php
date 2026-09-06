<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Feste Quelle": ein Produkt kann fest einer ProductSource zugeordnet werden
 * (Beispiel: "Digitalfunkgerät" → immer "Funkwerkstatt"). Ist diese Quelle
 * nicht ausschreibungsrelevant (siehe product_sources.tender_relevant), gilt
 * das für das Produkt automatisch in jedem Projekt – siehe Product::isTenderRelevant().
 *
 * include_in_estimate: nur relevant für Produkte mit einer nicht-
 * ausschreibungsrelevanten Quelle – erlaubt, ein einzelnes Produkt trotzdem
 * aus der groben Kostenschätzung auszuschließen (Standard: eingeschlossen).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('cis_row_id_source')->nullable()->after('category_id');
            $table->boolean('include_in_estimate')->default(true)->after('cis_row_id_source');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['cis_row_id_source', 'include_in_estimate']);
        });
    }
};

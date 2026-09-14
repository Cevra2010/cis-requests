<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Markiert eine einzelne Projektposition als "aus Lagerbestand bezogen" statt
 * ausgeschrieben (Modul "Lager", Phase 2: ProjectProductManager zeigt bei
 * vorhandenem, nicht zugeordnetem Lagerbestand die Wahl "Ausschreiben" oder
 * "Aus Lager beziehen" an). Bewusst pro Position statt pro Produkt (anders als
 * Product::isTenderRelevant()/die feste Quelle) – dasselbe Produkt kann im
 * einen Projekt ausgeschrieben und im anderen aus Lager bezogen werden, je
 * nachdem, ob zum Zeitpunkt der Zuordnung gerade Bestand verfügbar war.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_product', function (Blueprint $table) {
            $table->boolean('sourced_from_stock')->default(false)->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('project_product', function (Blueprint $table) {
            $table->dropColumn('sourced_from_stock');
        });
    }
};

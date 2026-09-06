<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Ausschreibungsrelevant": Produkte, deren feste Quelle (siehe
 * products.cis_row_id_source) hier auf false steht, erscheinen nicht auf der
 * Ausschreibung/im Angebotsvergleich/in der Bestellzuordnung – lösen damit
 * das bisherige, manuell pro Projekt zu setzende "Hausintern"-Merkmal ab.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sources', function (Blueprint $table) {
            $table->boolean('tender_relevant')->default(true)->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('product_sources', function (Blueprint $table) {
            $table->dropColumn('tender_relevant');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Setprodukte dienen der internen Bündelung mehrerer Produkte (z.B. ein PSA-Set).
 * Ein Set hat keinen eigenen Preis/Beschreibungstext (Preis = Summe der
 * Mitgliedsprodukte, siehe Product::getGroupPrice()) und erscheint auf der
 * Ausschreibung nicht als eigene Position – nur seine Mitgliedsprodukte, siehe
 * TenderEditor/TenderExporter/OfferComparison/AwardManager.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_set')->default(false)->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_set');
        });
    }
};

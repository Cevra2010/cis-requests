<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lagerort: freier Text je Kommissionierungs-Position, wo die Ware nach der
 * Kontrolle abgelegt wird. Status-Kategorie: Folgestatus nach kontrolliertem
 * Wareneingang (z.B. "Eingelagert", "Versendet an Fahrzeughersteller"), über
 * den neuen Kategorie-Typ "wareneingang.item_status" verwaltet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->string('lagerort')->nullable()->after('note');
            $table->unsignedBigInteger('status_category_id')->nullable()->after('lagerort');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn(['lagerort', 'status_category_id']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Append-only Ereignisprotokoll: "X Stück der Wareneingangsposition Y wurden
 * in Lagerort Z eingebucht". Dient ausschließlich der Berechnung "wie viel
 * dieser Wareneingangsposition ist schon eingelagert?" (Summe je
 * cis_row_id_goods_receipt_item) – spätere Verschiebungen zwischen Lagerorten
 * ändern nur lager_stock, nicht dieses Protokoll (die Herkunft bleibt so
 * nachvollziehbar, ohne ein volles Bewegungsjournal führen zu müssen).
 * Lose Referenz auf Modules\Wareneingang\Models\GoodsReceiptItem (keine FK,
 * gleiche Konvention wie überall in diesem Projekt) – funktioniert daher auch
 * unabhängig davon, ob das Wareneingang-Modul aktuell aktiv ist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lager_placements', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_goods_receipt_item');
            $table->string('cis_row_id_lagerort');
            $table->integer('quantity');
            $table->timestamps();

            $table->index('cis_row_id_goods_receipt_item');
            $table->index('cis_row_id_lagerort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lager_placements');
    }
};

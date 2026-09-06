<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wareneingang für nicht-ausschreibungsrelevante Positionen (feste, interne
 * Quelle statt eines Angebots – siehe products.cis_row_id_source). Bewusst
 * ohne Schema-Änderung an "cis_row_id_offer" (das würde doctrine/dbal
 * voraussetzen, das hier nicht installiert ist): bei einem internen
 * Wareneingang bleibt cis_row_id_offer eine leere Zeichenkette, maßgeblich
 * ist dann ausschließlich cis_row_id_source.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->string('cis_row_id_source')->nullable()->after('cis_row_id_offer');
            $table->index('cis_row_id_source');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropColumn('cis_row_id_source');
        });
    }
};

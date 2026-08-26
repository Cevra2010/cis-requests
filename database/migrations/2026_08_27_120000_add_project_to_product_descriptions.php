<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ausschreibungstexte waren bisher ausschließlich global pro Produkt gespeichert
 * – Bearbeitung in einem Projekt überschrieb den Text überall (andere Projekte
 * und die Produktstammdaten). Ab jetzt: cis_row_id_project = NULL bleibt der
 * globale Standardtext (Fallback), ein gesetzter Wert ist eine projektspezifische
 * Abweichung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_descriptions', function (Blueprint $table) {
            $table->string('cis_row_id_project')->nullable()->after('cis_row_id_product');
            $table->index(['cis_row_id_product', 'cis_row_id_project']);
        });
    }

    public function down(): void
    {
        Schema::table('product_descriptions', function (Blueprint $table) {
            $table->dropColumn('cis_row_id_project');
        });
    }
};

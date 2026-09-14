<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aktueller Bestand je Produkt/Lagerort/Projekt (mutable "Ist-Zustand", kein
 * Bewegungsprotokoll – siehe lager_placements dafür). cis_row_id_project ist
 * bewusst nullable: NULL = nicht einem Projekt zugeordnet, also freier
 * Bestand (relevant für "schon auf Lager, bevor neu ausgeschrieben wird").
 * Bewusst kein DB-Unique-Constraint über die Kombination (NULL-Verhalten bei
 * Unique-Indizes ist je nach DB tückisch) – Eindeutigkeit wird stattdessen
 * ausschließlich über Modules\Lager\Services\LagerStockService sichergestellt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lager_stock', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_product');
            $table->string('cis_row_id_lagerort');
            $table->string('cis_row_id_project')->nullable();
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->index('cis_row_id_product');
            $table->index('cis_row_id_lagerort');
            $table->index('cis_row_id_project');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lager_stock');
    }
};

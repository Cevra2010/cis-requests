<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frei tiefer Lagerort-Baum (z.B. Lager → Raum → Regal → Fach), analog zum
 * generischen Kategorie-Baum (App\Models\Category), aber als eigenes Modell,
 * da ein Lagerort eigene, fachfremde Beziehungen braucht (Projekte, Bestand)
 * statt die für alle Module geteilte "categories"-Tabelle zu belasten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lagerorte', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_parent')->nullable();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('cis_row_id_parent');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lagerorte');
    }
};

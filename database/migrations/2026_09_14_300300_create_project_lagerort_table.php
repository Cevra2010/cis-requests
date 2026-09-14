<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pivot für Project::lagerorte()/Lagerort::projects() (Modul "Lager"), analog
 * zu project_product. Bewusst im Kern statt im Lager-Modul, da Project ein
 * Kern-Modell ist – die Tabelle bleibt einfach ungenutzt, wenn das Lager-Modul
 * nicht installiert/aktiv ist (rein additiv, keine Fremdschlüssel-Constraints).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_lagerort', function (Blueprint $table) {
            $table->string('cis_row_id_project');
            $table->string('cis_row_id_lagerort');
            $table->timestamps();

            $table->primary(['cis_row_id_project', 'cis_row_id_lagerort']);
            $table->index('cis_row_id_lagerort');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_lagerort');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Datengrundlage einer Vorlage: 'project_positions' (bisheriges Verhalten – Positionen
 * eines Projekts, siehe TenderExporter) oder ein Systemdatensatz wie 'products'
 * (siehe Services/Datasets), der im Import-/Export-Werkzeug genutzt wird.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->string('dataset')->default('project_positions')->after('phase');
        });
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn('dataset');
        });
    }
};

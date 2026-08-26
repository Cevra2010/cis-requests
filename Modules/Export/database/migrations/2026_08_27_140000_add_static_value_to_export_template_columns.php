<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Freifeld"-Spalte: nicht an ein echtes Datenfeld gebunden, sondern entweder
 * ein fester, beim Anlegen der Spalte eingegebener Text (in jeder Zeile
 * identisch) oder – bleibt static_value leer – eine leere Spalte zum
 * manuellen Ausfüllen nach dem Export.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_template_columns', function (Blueprint $table) {
            $table->string('static_value')->nullable()->after('field_key');
        });
    }

    public function down(): void
    {
        Schema::table('export_template_columns', function (Blueprint $table) {
            $table->dropColumn('static_value');
        });
    }
};

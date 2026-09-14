<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Teilt Export-Vorlagen in zwei Phasen ein: "vor der Ausschreibung" (ausgehende
 * Tabelle an Anbieter, Default) und "nach der Ausschreibung/Auswertung"
 * (Ergebnis-Export). Zusätzlich eine optionale Sortierung je Vorlage
 * (Feld + Richtung) – ersetzt zusammen mit den neuen Export-Template-Filtern
 * (siehe folgende Migration) die bisherige, starre "include_non_tender_relevant"-
 * Checkbox.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->string('phase')->default('pre_tender')->after('is_default');
            $table->string('sort_field')->nullable()->after('include_non_tender_relevant');
            $table->string('sort_direction')->default('asc')->after('sort_field');
        });
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn(['phase', 'sort_field', 'sort_direction']);
        });
    }
};

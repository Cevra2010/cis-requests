<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Standardmäßig blenden Export-Vorlagen nicht-ausschreibungsrelevante
 * Positionen aus (deckt sich mit dem Verhalten der Ausschreibungs-PDF) – für
 * interne Materiallisten (z.B. eine Vorlage speziell für die Funkwerkstatt)
 * kann eine Vorlage das hier gezielt einschließen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->boolean('include_non_tender_relevant')->default(false)->after('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn('include_non_tender_relevant');
        });
    }
};

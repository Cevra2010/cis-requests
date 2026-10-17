<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ob eine Vorlage den technischen Import-Schlüssel (Spalte "_ImportKey") mit in
 * die Tabelle schreibt, wird jetzt je Vorlage festgelegt statt implizit über
 * das Vorhandensein von Händler-Preisfeldern. Bestehende Vorlagen mit
 * Händler-Preisfeld behalten ihr Verhalten (Schlüssel an). Die frühere
 * "Standard"-Markierung hatte keine fachliche Wirkung mehr und entfällt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->boolean('include_import_key')->default(false)->after('phase');
        });

        $templateIds = DB::table('export_template_columns')
            ->whereIn('field_key', ['vendor_unit_price', 'vendor_total_price'])
            ->pluck('cis_row_id_template')
            ->unique()
            ->all();

        if (! empty($templateIds)) {
            DB::table('export_templates')->whereIn('cis_row_id', $templateIds)->update(['include_import_key' => true]);
        }

        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn('is_default');
        });
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->boolean('is_default')->default(false);
        });

        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn('include_import_key');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Frei kombinierbare (UND-verknüpfte) Filter je Export-Vorlage – siehe
 * Modules\Export\Services\ExportFilterRegistry für die verfügbaren Feldtypen
 * und Modules\Export\Services\ExportTemplateFilterMatcher für die Auswertung.
 * "value" ist bewusst generisch (JSON-kodiertes Array), damit sich künftige
 * Filtertypen ohne Schemaänderung ergänzen lassen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_template_filters', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_template');
            $table->string('field_key');
            $table->text('value');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('cis_row_id_template');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_template_filters');
    }
};

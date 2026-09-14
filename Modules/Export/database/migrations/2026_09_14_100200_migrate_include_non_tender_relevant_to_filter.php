<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Löst die bisherige, starre Checkbox "Auch nicht-ausschreibungsrelevante
 * Positionen einschließen" (export_templates.include_non_tender_relevant)
 * durch das neue, generische Filtersystem ab (siehe
 * Modules\Export\Services\ExportFilterRegistry). Migriert zunächst bestehende
 * Vorlagen verlustfrei, damit sich am Exportergebnis nichts ändert: eine
 * Vorlage, die bisher nicht-ausschreibungsrelevante Positionen ausgeblendet
 * hat (include_non_tender_relevant = false, der bisherige Standard), bekommt
 * dafür einen expliziten Filter "Ausschreibungsrelevant = Ja". War die
 * Checkbox gesetzt (= keine Einschränkung), wird kein Filter angelegt – eine
 * Vorlage ganz ohne Filter schließt im neuen System ebenfalls nichts aus.
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('export_templates')
            ->where('include_non_tender_relevant', false)
            ->get(['cis_row_id'])
            ->each(function ($template) use ($now) {
                DB::table('export_template_filters')->insert([
                    'cis_row_id'          => (string) Str::uuid(),
                    'cis_row_id_template' => $template->cis_row_id,
                    'field_key'           => 'tender_relevant',
                    'value'               => json_encode(['yes']),
                    'sort_order'          => 0,
                    'created_at'          => $now,
                    'updated_at'          => $now,
                ]);
            });

        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn('include_non_tender_relevant');
        });
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->boolean('include_non_tender_relevant')->default(false);
        });

        // Absichtlich keine Rückmigration der Filterdaten in die Checkbox –
        // das neue Filtersystem kann Kombinationen abbilden, die sich nicht
        // verlustfrei auf ein einzelnes Boolean zurückführen lassen.
    }
};

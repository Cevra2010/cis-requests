<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Die Projektfixierung hängt nicht mehr an der Art des Schritts "Ausschreibung", sondern an Maßnahmen
 * (siehe App\Services\Workflow\Actions\FixationAction). Damit sich bestehende Workflows nicht ändern, bekommt der Schritt
 * "Ausschreibung" jedes Projekttyps die Maßnahmen "Produkte fixiert" und "Ausschreibung fixiert".
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        DB::table('workflow_statuses')->where('kind', 'tender')->get(['cis_row_id', 'project_type'])->each(function ($status) use ($now) {
            $has = DB::table('workflow_actions')
                ->where('cis_row_id_workflow_status', $status->cis_row_id)
                ->where('trigger', 'enter')
                ->whereIn('type', ['fix_products', 'fix_tender'])
                ->pluck('type')
                ->all();

            $next = (int) DB::table('workflow_actions')->where('cis_row_id_workflow_status', $status->cis_row_id)->where('trigger', 'enter')->max('sort_order') + 1;

            // Fahrzeugausschreibungen haben keinen Ausschreibungstext.
            foreach (($status->project_type === 'vehicle' ? ['fix_products'] : ['fix_products', 'fix_tender']) as $type) {
                if (in_array($type, $has, true)) {
                    continue;
                }
                DB::table('workflow_actions')->insert([
                    'cis_row_id'                 => (string) Str::uuid(),
                    'cis_row_id_workflow_status' => $status->cis_row_id,
                    'trigger'                    => 'enter',
                    'type'                       => $type,
                    'sort_order'                 => $next++,
                    'is_active'                  => true,
                    'config'                     => json_encode([]),
                    'created_at'                 => $now,
                    'updated_at'                 => $now,
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('workflow_actions')->whereIn('type', ['fix_products', 'fix_tender', 'fix_offers', 'fix_awarding'])->delete();
    }
};

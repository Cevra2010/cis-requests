<?php

use App\Models\Product;
use App\Models\ProductSource;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Löst das bisherige, manuell pro Projekt zu setzende "Hausintern"-Merkmal
 * (products.default_is_internal, project_product.is_internal) durch die neue,
 * quellenbasierte Regel ab (siehe products.cis_row_id_source +
 * product_sources.tender_relevant). Migriert zunächst bestehende Daten
 * verlustfrei, bevor die alten Spalten entfernt werden: jedes Produkt, das
 * bisher default_is_internal=true war oder in mindestens einem Projekt als
 * is_internal=true markiert wurde, bekommt automatisch eine (neu angelegte
 * oder bereits vorhandene) ProductSource "Hausintern" mit tender_relevant=false
 * als feste Quelle zugewiesen.
 */
return new class extends Migration
{
    public function up(): void
    {
        $productIds = DB::table('products')
            ->where('default_is_internal', true)
            ->whereNull('cis_row_id_source')
            ->pluck('cis_row_id')
            ->merge(
                DB::table('project_product')
                    ->where('is_internal', true)
                    ->pluck('cis_row_id_product')
            )
            ->unique()
            ->values();

        if ($productIds->isNotEmpty()) {
            $hausintern = ProductSource::withTrashed()->firstOrCreate(
                ['name' => 'Hausintern'],
                ['tender_relevant' => false]
            );
            if ($hausintern->tender_relevant) {
                $hausintern->update(['tender_relevant' => false]);
            }
            if ($hausintern->trashed()) {
                $hausintern->restore();
            }

            Product::whereIn('cis_row_id', $productIds)
                ->whereNull('cis_row_id_source')
                ->update(['cis_row_id_source' => $hausintern->cis_row_id]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('default_is_internal');
        });

        Schema::table('project_product', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('default_is_internal')->default(false);
        });

        Schema::table('project_product', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false);
        });
    }
};

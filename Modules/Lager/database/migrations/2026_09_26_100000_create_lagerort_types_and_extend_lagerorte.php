<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lagerort_types', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('name');
            $table->string('abbreviation', 20);
            $table->timestamps();
        });

        Schema::table('lagerorte', function (Blueprint $table) {
            $table->string('cis_row_id_type')->nullable()->after('cis_row_id_parent')->index();
            $table->unsignedInteger('number')->default(0)->after('name');
        });

        // Standardtyp ("Standard" => "S"): muss immer existieren.
        $typeId = (string) Str::uuid();
        DB::table('lagerort_types')->insert([
            'cis_row_id' => $typeId, 'name' => 'Standard', 'abbreviation' => 'S',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Bestehende Lagerorte: Standardtyp + laufende Nummer je Elternelement.
        $counters = [];
        foreach (DB::table('lagerorte')->orderBy('sort_order')->orderBy('name')->get() as $row) {
            $key = (string) $row->cis_row_id_parent;
            $counters[$key] = ($counters[$key] ?? 0) + 1;
            DB::table('lagerorte')->where('cis_row_id', $row->cis_row_id)
                ->update(['cis_row_id_type' => $typeId, 'number' => $counters[$key]]);
        }
    }

    public function down(): void
    {
        Schema::table('lagerorte', function (Blueprint $table) {
            $table->dropColumn(['cis_row_id_type', 'number']);
        });
        Schema::dropIfExists('lagerort_types');
    }
};

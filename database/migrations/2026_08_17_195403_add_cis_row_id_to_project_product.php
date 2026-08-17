<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_product', function (Blueprint $table) {
            $table->string('cis_row_id')->nullable()->after('cis_row_id_product');
        });

        foreach (DB::table('project_product')->whereNull('cis_row_id')->get() as $row) {
            DB::table('project_product')
                ->where('cis_row_id_project', $row->cis_row_id_project)
                ->where('cis_row_id_product', $row->cis_row_id_product)
                ->update(['cis_row_id' => (string) Uuid::uuid4()]);
        }

        Schema::table('project_product', function (Blueprint $table) {
            $table->string('cis_row_id')->nullable(false)->change();
            $table->unique('cis_row_id');
            $table->unique(['cis_row_id_project', 'cis_row_id_product'], 'project_product_project_product_unique');
        });
    }

    public function down(): void
    {
        Schema::table('project_product', function (Blueprint $table) {
            $table->dropUnique('project_product_project_product_unique');
            $table->dropUnique(['cis_row_id']);
            $table->dropColumn('cis_row_id');
        });
    }
};

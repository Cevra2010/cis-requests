<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_participants', function (Blueprint $table) {
            $table->string('cis_row_id_user')->nullable()->after('cis_row_id_goods_receipt');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_participants', function (Blueprint $table) {
            $table->dropColumn('cis_row_id_user');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropUnique('goods_receipts_access_token_unique');
            $table->dropColumn(['access_token', 'checked_by_name']);
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->string('access_token', 64)->nullable()->unique();
            $table->string('checked_by_name')->nullable();
        });
    }
};

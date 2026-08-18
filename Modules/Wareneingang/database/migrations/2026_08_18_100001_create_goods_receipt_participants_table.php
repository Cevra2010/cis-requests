<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_participants', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_goods_receipt');
            $table->string('access_token', 64)->unique();
            $table->string('name')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('cis_row_id_goods_receipt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_participants');
    }
};

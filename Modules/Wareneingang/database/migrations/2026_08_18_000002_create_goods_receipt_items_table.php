<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_goods_receipt');
            $table->string('cis_row_id_project_product');
            $table->unsignedInteger('expected_count');
            $table->unsignedInteger('received_count')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->index('cis_row_id_goods_receipt');
            $table->unique(['cis_row_id_goods_receipt', 'cis_row_id_project_product'], 'goods_receipt_items_unique_position');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('cis_row_id_offer');
            $table->string('access_token', 64)->unique();
            $table->string('checked_by_name')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('cis_row_id_project');
            $table->index('cis_row_id_offer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipts');
    }
};

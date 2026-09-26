<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            // Noch nicht gebuchte Erfassung ("Jetzt buchen" übernimmt sie in received_count).
            $table->unsignedInteger('draft_count')->nullable()->after('received_count');
            $table->string('draft_lagerort_id')->nullable()->after('draft_count');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipt_items', function (Blueprint $table) {
            $table->dropColumn(['draft_count', 'draft_lagerort_id']);
        });
    }
};

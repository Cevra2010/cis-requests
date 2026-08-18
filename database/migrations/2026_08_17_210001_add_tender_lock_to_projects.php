<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->timestamp('tender_locked_at')->nullable()->after('min_order_value');
            $table->string('tender_locked_by')->nullable()->after('tender_locked_at');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['tender_locked_at', 'tender_locked_by']);
        });
    }
};

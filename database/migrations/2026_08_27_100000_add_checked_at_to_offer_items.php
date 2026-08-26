<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offer_items', function (Blueprint $table) {
            $table->timestamp('checked_at')->nullable()->after('not_offered');
        });

        Schema::table('offer_child_items', function (Blueprint $table) {
            $table->timestamp('checked_at')->nullable()->after('not_offered');
        });
    }

    public function down(): void
    {
        Schema::table('offer_items', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });

        Schema::table('offer_child_items', function (Blueprint $table) {
            $table->dropColumn('checked_at');
        });
    }
};

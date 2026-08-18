<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sources', function (Blueprint $table) {
            $table->string('url', 500)->nullable()->after('name');
            $table->string('contact_name')->nullable()->after('url');
            $table->string('contact_email')->nullable()->after('contact_name');
            $table->string('contact_phone', 50)->nullable()->after('contact_email');
            $table->text('notes')->nullable()->after('contact_phone');
        });
    }

    public function down(): void
    {
        Schema::table('product_sources', function (Blueprint $table) {
            $table->dropColumn(['url', 'contact_name', 'contact_email', 'contact_phone', 'notes']);
        });
    }
};

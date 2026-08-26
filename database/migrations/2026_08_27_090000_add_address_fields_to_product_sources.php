<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_sources', function (Blueprint $table) {
            $table->string('address_street')->nullable()->after('url');
            $table->string('address_postal_code')->nullable()->after('address_street');
            $table->string('address_city')->nullable()->after('address_postal_code');
            $table->string('address_country')->nullable()->after('address_city');
        });
    }

    public function down(): void
    {
        Schema::table('product_sources', function (Blueprint $table) {
            $table->dropColumn(['address_street', 'address_postal_code', 'address_city', 'address_country']);
        });
    }
};

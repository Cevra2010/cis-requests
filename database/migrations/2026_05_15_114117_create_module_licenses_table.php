<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('module_licenses', function (Blueprint $table) {
            $table->uuid('cis_row_id')->primary();
            $table->string('module_name')->unique();
            $table->text('license_key');
            $table->string('licensee');
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_licenses');
    }
};

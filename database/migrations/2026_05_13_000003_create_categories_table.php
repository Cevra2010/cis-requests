<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('type');          // z.B. 'project.category'
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 7)->nullable();   // HEX
            $table->integer('sort_order')->default(0);
            $table->string('module')->nullable();     // welches Modul hat diesen Typ registriert
            $table->softDeletes();
            $table->timestamps();

            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};

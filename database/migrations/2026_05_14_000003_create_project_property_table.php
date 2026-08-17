<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_property', function (Blueprint $table) {
            $table->string('cis_row_id_project');
            $table->string('cis_row_id_property');
            $table->text('custom_description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->primary(['cis_row_id_project', 'cis_row_id_property']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_property');
    }
};

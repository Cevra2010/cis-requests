<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumentenmanager je Projekt: frei hochgeladene Dateien oder mit einem
 * Arbeitsschritt verknüpft (z.B. die vom Händler zurückgesandte
 * Angebots-Excel-Datei, siehe linked_type/linked_id – bewusst als einfache
 * String-Spalten statt polymorpher Eloquent-Relation, analog zu
 * ChildPositionAward/PositionAward in diesem Projekt).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_documents', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->string('linked_type')->nullable(); // z.B. 'offer_import'
            $table->string('linked_id')->nullable();   // z.B. cis_row_id des Angebots
            $table->string('cis_row_id_uploaded_by')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['cis_row_id_project']);
            $table->index(['linked_type', 'linked_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_documents');
    }
};

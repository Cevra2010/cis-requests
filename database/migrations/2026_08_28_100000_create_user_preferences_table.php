<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Generischer Key-Value-Speicher für pro Benutzer gespeicherte Einstellungen –
 * bewusst nicht auf eine einzelne Funktion (z.B. Tabellen-Seitengröße)
 * beschränkt, damit künftige Einstellungen (nicht nur Tabellen) denselben
 * Mechanismus nutzen können. Wird aktuell u.a. für die Filter/Sortierung/
 * Seitengröße der Datentabellen genutzt (Keys wie "table.products.filters").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->uuid('cis_row_id')->primary();
            $table->uuid('cis_row_id_user');
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->unique(['cis_row_id_user', 'key'], 'user_preferences_user_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};

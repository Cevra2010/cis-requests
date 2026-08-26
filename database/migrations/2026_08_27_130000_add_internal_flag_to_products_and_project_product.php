<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Hausinterne Produkte" – Produkte, die nicht ausgeschrieben werden, weil sie
 * bereits im Haus vorhanden sind (Beispiel: Funkgeräte aus der Funkwerkstatt).
 * products.default_is_internal ist die Vorbelegung beim Hinzufügen zu einem
 * Projekt; project_product.is_internal ist das tatsächlich wirksame Flag je
 * Projekt-Position (danach frei umschaltbar, unabhängig vom Produkt-Standard).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('default_is_internal')->default(false)->after('is_set');
        });

        Schema::table('project_product', function (Blueprint $table) {
            $table->boolean('is_internal')->default(false)->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('default_is_internal');
        });

        Schema::table('project_product', function (Blueprint $table) {
            $table->dropColumn('is_internal');
        });
    }
};

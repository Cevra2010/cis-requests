<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bereiche, in denen eine Export-Vorlage angeboten wird (siehe ExportTemplate::USAGES).
 * Bestehende Vorlagen erscheinen weiterhin im Dokumentenmanager der Projekte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->json('usages')->nullable()->after('include_import_key');
        });

        DB::table('export_templates')->update(['usages' => json_encode(['project_documents'])]);
    }

    public function down(): void
    {
        Schema::table('export_templates', function (Blueprint $table) {
            $table->dropColumn('usages');
        });
    }
};

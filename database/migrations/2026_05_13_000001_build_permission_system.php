<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Baut das vollständige Gruppen/Rollen/Rechte-System auf.
 * Ersetzt die alten area_role, areas und user_role Tabellen.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Alte Tabellen bereinigen ─────────────────────────────────────────
        Schema::dropIfExists('area_role');
        Schema::dropIfExists('areas');
        Schema::dropIfExists('user_role');

        // ── Rollen: description und color ergänzen ───────────────────────────
        Schema::table('roles', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->string('color', 7)->nullable()->after('description'); // HEX
        });

        // ── Gruppen ──────────────────────────────────────────────────────────
        Schema::create('groups', function (Blueprint $table) {
            $table->uuid('cis_row_id')->primary();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('color', 7)->nullable(); // HEX, z.B. #3B82F6
            $table->softDeletes();
            $table->timestamps();
        });

        // ── Benutzer ↔ Gruppen ───────────────────────────────────────────────
        Schema::create('user_groups', function (Blueprint $table) {
            $table->string('user_id');
            $table->string('group_id');
            $table->primary(['user_id', 'group_id']);
        });

        // ── Benutzer ↔ Rollen ────────────────────────────────────────────────
        Schema::create('user_roles', function (Blueprint $table) {
            $table->string('user_id');
            $table->string('role_id');
            $table->primary(['user_id', 'role_id']);
        });

        // ── Berechtigungs-Registry ───────────────────────────────────────────
        // Wird von Modulen per CisPermissionManager::register() befüllt.
        Schema::create('permissions', function (Blueprint $table) {
            $table->string('slug')->primary();           // z.B. 'project.export'
            $table->string('label');                     // 'Projekt exportieren'
            $table->string('module')->nullable();        // 'ExportModule' | null = Core
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // ── Gruppen-Berechtigungen ───────────────────────────────────────────
        Schema::create('group_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('group_id');
            $table->string('permission_slug');
            $table->string('project_id')->nullable(); // null = global
            $table->boolean('granted')->default(true);
            $table->unique(['group_id', 'permission_slug', 'project_id'], 'gp_unique');
        });

        // ── Rollen-Berechtigungen ────────────────────────────────────────────
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role_id');
            $table->string('permission_slug');
            $table->string('project_id')->nullable();
            $table->boolean('granted')->default(true);
            $table->unique(['role_id', 'permission_slug', 'project_id'], 'rp_unique');
        });

        // ── Benutzer-Berechtigungen (explizite Overrides) ────────────────────
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('permission_slug');
            $table->string('project_id')->nullable();
            $table->boolean('granted'); // false = explizites Verbot
            $table->unique(['user_id', 'permission_slug', 'project_id'], 'up_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('group_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_groups');
        Schema::dropIfExists('groups');

        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['description', 'color']);
        });

        // Alte Tabellen wiederherstellen
        Schema::create('areas', function (Blueprint $table) {
            $table->uuid('cis_row_id')->primary();
            $table->string('slug')->unique()->key();
            $table->string('parent_slug')->nullable()->key();
            $table->string('name');
            $table->string('description')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('area_role', function (Blueprint $table) {
            $table->string('cis_row_id_area')->key();
            $table->string('cis_row_id_role')->key();
        });
        Schema::create('user_role', function (Blueprint $table) {
            $table->string('cis_row_id_user')->key();
            $table->string('cis_row_id_role')->key();
        });
    }
};

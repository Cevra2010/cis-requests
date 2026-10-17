<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Ausschreibungszeitraum je Projekt (Beginn/Ende mit Uhrzeit) und Workflow-Trigger.
 *
 * Ein Trigger gehört zu einem Workflow-Schritt und reagiert auf ein Ereignis (siehe
 * App\Services\Workflow\Events), solange ein Projekt auf diesem Schritt steht; er löst
 * Maßnahmen aus (workflow_actions.cis_row_id_workflow_trigger). workflow_trigger_runs
 * verhindert, dass dasselbe Ereignis für dasselbe Projekt zweimal feuert.
 *
 * Das bisherige Flag "Automatisch vorrücken, sobald die Fälligkeit überschritten ist"
 * entfällt: wo es gesetzt war, entsteht stattdessen ein Trigger "Ausschreibungszeitraum
 * ist geendet" mit der Maßnahme "Springen zu: nächster Schritt"; als Ende wird das bisherige
 * Fälligkeitsdatum (23:59 Uhr) übernommen, damit sich laufende Projekte unverändert verhalten.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dateTime('tender_starts_at')->nullable()->after('due_date');
            $table->dateTime('tender_ends_at')->nullable()->after('tender_starts_at');
        });

        DB::table('projects')->whereNotNull('due_date')->get(['cis_row_id', 'due_date'])->each(function ($p) {
            DB::table('projects')->where('cis_row_id', $p->cis_row_id)->update([
                'tender_ends_at' => Carbon::parse($p->due_date)->setTime(23, 59, 0)->format('Y-m-d H:i:s'),
            ]);
        });

        Schema::create('workflow_triggers', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_workflow_status')->index();
            $table->string('event', 60);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('workflow_trigger_runs', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('cis_row_id_project');
            $table->string('cis_row_id_workflow_trigger');
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->unique(['cis_row_id_project', 'cis_row_id_workflow_trigger', 'occurred_at'], 'workflow_trigger_runs_unique');
        });

        Schema::table('workflow_actions', function (Blueprint $table) {
            $table->string('cis_row_id_workflow_trigger')->nullable()->index()->after('cis_row_id_workflow_status');
        });

        $now = now();
        DB::table('workflow_statuses')->where('auto_advance_on_due', true)->get(['cis_row_id'])->each(function ($status) use ($now) {
            $triggerId = (string) Str::uuid();
            DB::table('workflow_triggers')->insert([
                'cis_row_id'                 => $triggerId,
                'cis_row_id_workflow_status' => $status->cis_row_id,
                'event'                      => 'tender.ended',
                'is_active'                  => true,
                'sort_order'                 => 0,
                'created_at'                 => $now,
                'updated_at'                 => $now,
            ]);
            DB::table('workflow_actions')->insert([
                'cis_row_id'                  => (string) Str::uuid(),
                'cis_row_id_workflow_status'  => $status->cis_row_id,
                'cis_row_id_workflow_trigger' => $triggerId,
                'trigger'                     => 'event',
                'type'                        => 'goto_step',
                'sort_order'                  => 0,
                'is_active'                   => true,
                'config'                      => json_encode(['target' => 'next']),
                'created_at'                  => $now,
                'updated_at'                  => $now,
            ]);
        });

        Schema::table('workflow_statuses', function (Blueprint $table) {
            $table->dropColumn('auto_advance_on_due');
        });
    }

    public function down(): void
    {
        Schema::table('workflow_statuses', function (Blueprint $table) {
            $table->boolean('auto_advance_on_due')->default(false);
        });
        Schema::table('workflow_actions', function (Blueprint $table) {
            $table->dropColumn('cis_row_id_workflow_trigger');
        });
        Schema::dropIfExists('workflow_trigger_runs');
        Schema::dropIfExists('workflow_triggers');
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['tender_starts_at', 'tender_ends_at']);
        });
    }
};

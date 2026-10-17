<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Protokoll der geplanten Aufgaben (Laravel-Scheduler) – Grundlage der Cron-Statusanzeige in den Einstellungen. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cron_runs', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('task');
            $table->string('source', 20)->default('cron'); // cron | url | manual
            $table->string('status', 10)->default('ok');   // ok | failed
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('ran_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cron_runs');
    }
};

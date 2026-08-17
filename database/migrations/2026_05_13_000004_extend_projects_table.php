<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Status als lesbarer String
            $table->string('status_code', 30)->default('draft')->after('status');

            // Kategorie (FK zu categories.id)
            $table->unsignedBigInteger('category_id')->nullable()->after('status_code');

            // Verantwortlicher (polymorphisch: User oder Group)
            $table->string('assignee_type')->nullable()->after('category_id');
            $table->string('assignee_id')->nullable()->after('assignee_type');

            // Auftraggeber (Freitext – Gemeinde, Behörde)
            $table->string('client')->nullable()->after('assignee_id');

            // Zeitplanung
            $table->year('tender_year')->nullable()->after('client');
            $table->date('due_date')->nullable()->after('tender_year');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn([
                'status_code', 'category_id',
                'assignee_type', 'assignee_id',
                'client', 'tender_year', 'due_date',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {

        /**
         * Projektstatus
         * 1 = neu/eröffnet
         * 2 = vorgeplant
         * 3 = in Bearbeitung
         * 4 = Projektmappe fertiggestellt
         * 5 = ausgeschrieben
         * 6 = durchsicht
         * 7 = fertiggestellt
         */

        Schema::create('projects', function (Blueprint $table) {
            $table->string('cis_row_id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->integer('status')->default(1);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('projects');
    }
};

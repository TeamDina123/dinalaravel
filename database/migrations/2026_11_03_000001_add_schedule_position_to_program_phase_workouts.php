<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('program_phase_workouts', function (Blueprint $table) {
            $table->unsignedInteger('week_number')->nullable();
            $table->unsignedTinyInteger('day_number')->nullable();
        });
    }

    public function down()
    {
        Schema::table('program_phase_workouts', function (Blueprint $table) {
            $table->dropColumn(['week_number', 'day_number']);
        });
    }
};

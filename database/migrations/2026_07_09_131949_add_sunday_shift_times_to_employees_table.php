<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Optional Sunday-specific shift override. Some night-shift staff work a
        // different (usually shorter/earlier) shift on their weekly-off day, which
        // otherwise gets flagged as a stray/implausible punch against their weekday
        // schedule. Null means "same as time_in/time_out".
        Schema::table('employees', function (Blueprint $table) {
            $table->time('sunday_time_in')->nullable()->after('time_in');
            $table->time('sunday_time_out')->nullable()->after('time_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['sunday_time_in', 'sunday_time_out']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Employees who don't use the biometric punch machine (e.g. CEO, HR head). They are never
 * listed as "Absent" on the dashboard just because they have no punches.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->boolean('attendance_exempt')->default(false)->after('working_mode');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('attendance_exempt');
        });
    }
};

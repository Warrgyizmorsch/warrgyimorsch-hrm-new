<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present','half_day','absent','missing_punch','leave','wfh','half_day_leave','early_leave','unpaid_leave','unauthorised','overtime','late','activity') NOT NULL DEFAULT 'absent'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present','half_day','absent','missing_punch','leave','wfh','half_day_leave','early_leave','unpaid_leave','unauthorised','overtime') NOT NULL DEFAULT 'absent'");
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permanently deleting a former employee removes all their data except payroll (kept
        // for wage/PF/tax records). payrolls.employee_id cascades on delete, so the employee row
        // stays as a stripped, soft-deleted shell that only payroll history still refers to.
        Schema::table('employees', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};

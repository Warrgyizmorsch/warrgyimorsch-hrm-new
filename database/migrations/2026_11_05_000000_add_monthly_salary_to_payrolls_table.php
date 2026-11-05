<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The column was added to the local database by hand, so this only adds it where it is missing.
 * The payroll import writes to it, so other servers need it before the import is used there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('payrolls', 'monthly_salary')) {
            Schema::table('payrolls', function (Blueprint $table) {
                $table->decimal('monthly_salary', 12, 2)->default(0)->after('net_salary');
            });
        }
    }

    public function down(): void
    {
        // Left in place: this migration may not have been what created the column.
    }
};

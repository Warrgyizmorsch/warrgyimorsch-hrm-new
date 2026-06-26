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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('payable_days', 6, 2)->default(0)->change();

            if (!Schema::hasColumn('payrolls', 'unpaid_days')) {
                $table->decimal('unpaid_days', 6, 2)->default(0)->after('payable_days');
            }

            if (!Schema::hasColumn('payrolls', 'paid_leave_days')) {
                $table->decimal('paid_leave_days', 6, 2)->default(0)->after('unpaid_days');
            }

            if (!Schema::hasColumn('payrolls', 'unpaid_leave_days')) {
                $table->decimal('unpaid_leave_days', 6, 2)->default(0)->after('paid_leave_days');
            }

            if (!Schema::hasColumn('payrolls', 'leave_balance_before_payroll')) {
                $table->decimal('leave_balance_before_payroll', 6, 2)->default(0)->after('unpaid_leave_days');
            }

            if (!Schema::hasColumn('payrolls', 'overtime_hours')) {
                $table->decimal('overtime_hours', 8, 2)->default(0)->after('leave_balance_before_payroll');
            }

            if (!Schema::hasColumn('payrolls', 'overtime_days')) {
                $table->decimal('overtime_days', 6, 2)->default(0)->after('overtime_hours');
            }

            if (!Schema::hasColumn('payrolls', 'salary_loss')) {
                $table->decimal('salary_loss', 12, 2)->default(0)->after('net_salary');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->integer('payable_days')->default(0)->change();

            $table->dropColumn([
                'unpaid_days',
                'paid_leave_days',
                'unpaid_leave_days',
                'leave_balance_before_payroll',
                'overtime_hours',
                'overtime_days',
                'salary_loss',
            ]);
        });
    }
};

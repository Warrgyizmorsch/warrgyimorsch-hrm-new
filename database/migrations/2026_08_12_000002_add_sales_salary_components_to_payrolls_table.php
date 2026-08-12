<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('dearness_allowance', 12, 2)->default(0)->after('basic_salary');
            $table->decimal('variable_earning', 12, 2)->default(0)->after('other_allowance');
            $table->decimal('epf_deduction', 12, 2)->default(0)->after('esi_deduction');
            $table->decimal('professional_tax_deduction', 12, 2)->default(0)->after('epf_deduction');
            $table->decimal('loan_recovery_deduction', 12, 2)->default(0)->after('professional_tax_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'dearness_allowance',
                'variable_earning',
                'epf_deduction',
                'professional_tax_deduction',
                'loan_recovery_deduction',
            ]);
        });
    }
};

<?php

namespace App\Services;

/**
 * Splits a monthly gross salary into Basic / HRA / Other allowance (see config/salary_structure.php)
 * and works out the employee's PF / ESIC. The same rules apply to every department.
 */
class SalaryStructureService
{
    /**
     * Gross -> salary components. What is left after Basic + HRA is split into fixed Medical and
     * Travel (conveyance) amounts, capped by what is available, with the rest as Other allowance.
     * DA is no longer used and is returned as 0 so saving the result clears any old value.
     * The parts always add up exactly to $gross.
     *
     * $departmentId is unused (kept so existing callers keep working).
     */
    public static function breakdown(float $gross, ?int $departmentId = null): array
    {
        $gross = max(0, round($gross, 2));
        $basic = round($gross * (float) config('salary_structure.basic_of_gross', 0.5));
        $hra = round($basic * (float) config('salary_structure.hra_of_basic', 0.4));
        $remaining = max(0, round($gross - $basic - $hra, 2));
        $medical = min((float) config('salary_structure.medical_allowance', 1250), $remaining);
        $conveyance = min((float) config('salary_structure.conveyance_allowance', 1600), $remaining - $medical);

        return [
            'basic_salary' => $basic,
            'dearness_allowance' => 0.0,
            'hra' => $hra,
            'conveyance_allowance' => round($conveyance, 2),
            'medical_allowance' => round($medical, 2),
            'other_allowance' => round($remaining - $medical - $conveyance, 2),
        ];
    }

    /** Employee PF on the (earned) basic; 0 when the employee is not in PF. */
    public static function employeePf(float $basic, bool $eligible): float
    {
        return $eligible ? round($basic * (float) config('salary_structure.employee_pf_rate', 0.12)) : 0.0;
    }

    /**
     * Employee ESIC on the (earned) gross; 0 when the employee is not in ESI or their full monthly
     * gross is above the ESIC wage limit.
     */
    public static function employeeEsic(float $earnedGross, float $monthlyGross, bool $eligible): float
    {
        if (!$eligible || $monthlyGross > (float) config('salary_structure.esic_gross_limit', 21000)) {
            return 0.0;
        }

        return round($earnedGross * (float) config('salary_structure.employee_esic_rate', 0.0075));
    }

    /** Values the employee forms need for their live preview. */
    public static function jsConfig(): array
    {
        return [
            'basic_of_gross' => (float) config('salary_structure.basic_of_gross', 0.5),
            'hra_of_basic' => (float) config('salary_structure.hra_of_basic', 0.4),
            'medical_allowance' => (float) config('salary_structure.medical_allowance', 1250),
            'conveyance_allowance' => (float) config('salary_structure.conveyance_allowance', 1600),
            'employee_pf_rate' => (float) config('salary_structure.employee_pf_rate', 0.12),
            'employee_esic_rate' => (float) config('salary_structure.employee_esic_rate', 0.0075),
            'esic_gross_limit' => (float) config('salary_structure.esic_gross_limit', 21000),
        ];
    }
}

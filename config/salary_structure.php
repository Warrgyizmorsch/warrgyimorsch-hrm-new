<?php

/*
 * Salary bifurcation for every department (from CTC_Salary_PF_ESIC_Calculator.xlsx).
 *
 *   Basic           = ROUND(Gross x basic_of_gross)
 *   HRA             = ROUND(Basic x hra_of_basic)
 *   Remaining       = Gross - Basic - HRA, then split as:
 *     Medical allowance    = MIN(medical_allowance, Remaining)
 *     Travel (conveyance)  = MIN(conveyance_allowance, Remaining - Medical)
 *     Other allowance      = whatever is left
 *
 *   Employee PF     = ROUND(Basic earned x employee_pf_rate)        only when the employee has PF
 *   Employee ESIC   = ROUND(Gross earned x employee_esic_rate)      only when the employee has ESI
 *                                                                   and monthly gross <= esic_gross_limit
 */
return [

    'basic_of_gross' => 0.50,
    'hra_of_basic' => 0.40,

    // Fixed monthly amounts carved out of the remaining (post Basic + HRA) amount.
    'medical_allowance' => 1250,
    'conveyance_allowance' => 1600,

    'employee_pf_rate' => 0.12,
    'employee_esic_rate' => 0.0075,
    'esic_gross_limit' => 21000,

];

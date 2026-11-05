<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;

/**
 * Reads the payroll import template as raw rows (Excel::toArray). It saves nothing:
 * validation and saving live in PayrollImportService, so a file is always previewed first.
 */
class PayrollImport implements WithCalculatedFormulas
{
}

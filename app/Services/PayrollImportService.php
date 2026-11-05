<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Bulk payroll import from the fixed template: parse -> review (preview) -> commit.
 *
 * Rows are matched on employee_code only; employee_name is a cross-check against typos.
 * The sheet is the source of truth for amounts: a net that doesn't add up is only a warning.
 * A row that can't be tied to one employee, or would touch approved/paid payroll, blocks the file.
 */
class PayrollImportService
{
    public const HEADERS = [
        'employee_code',
        'employee_name',
        'working_days',
        'payable_days',
        'leave_balance',
        'monthly_salary',
        'gross_paid',
        'additional',
        'pf',
        'esic',
        'late_deduction',
        'net_salary',
        'remarks',
    ];

    private const REQUIRED_HEADERS = [
        'employee_code', 'employee_name', 'working_days', 'payable_days',
        'monthly_salary', 'gross_paid', 'net_salary',
    ];

    private const REQUIRED_NUMBERS = ['working_days', 'payable_days', 'monthly_salary', 'gross_paid', 'net_salary'];

    private const OPTIONAL_NUMBERS = ['leave_balance', 'additional', 'pf', 'esic', 'late_deduction'];

    /** Rupees of difference between the sheet's net and the recomputed net before warning. */
    private const NET_TOLERANCE = 1.00;

    /**
     * Turn the first sheet's raw rows into [line, values] rows keyed by header.
     *
     * @param array<int, array<int, mixed>> $sheet
     * @return array<int, array{line: int, values: array<string, mixed>}>
     *
     * @throws InvalidArgumentException when the header row isn't the template's
     */
    public function parse(array $sheet): array
    {
        $headerRow = array_map(fn ($cell) => strtolower(trim((string) $cell)), $sheet[0] ?? []);

        $missing = array_diff(self::REQUIRED_HEADERS, $headerRow);
        if ($missing) {
            throw new InvalidArgumentException(
                'This file is not in the payroll import template. Row 1 must hold the column names; missing: '
                . implode(', ', $missing) . '. Download the template and copy your data into it.'
            );
        }

        $columns = [];
        foreach ($headerRow as $index => $header) {
            if (!in_array($header, self::HEADERS, true)) {
                continue;
            }
            if (isset($columns[$header])) {
                throw new InvalidArgumentException("The column \"{$header}\" appears twice in row 1.");
            }
            $columns[$header] = $index;
        }

        $rows = [];
        foreach (array_slice($sheet, 1) as $offset => $cells) {
            $values = [];
            foreach (self::HEADERS as $header) {
                $cell = isset($columns[$header]) ? ($cells[$columns[$header]] ?? null) : null;
                $values[$header] = is_string($cell) ? trim($cell) : $cell;
            }
            $rows[] = ['line' => $offset + 2, 'values' => $values];
        }

        return $rows;
    }

    /**
     * Validate parsed rows against current data. Writes nothing.
     *
     * @param array<int, array{line: int, values: array<string, mixed>}> $rows
     * @return array{rows: array<int, array<string, mixed>>, error_count: int, warning_count: int, save_count: int, replace_count: int, gross_total: float, net_total: float}
     */
    public function review(array $rows, string $month): array
    {
        $daysInMonth = Carbon::createFromFormat('Y-m', $month)->daysInMonth;

        // Matched in PHP, not SQL: MySQL would coerce a code like "35abc" to the number 35.
        $employees = Employee::all()->keyBy(fn ($e) => $this->normalizeCode($e->employee_code));
        $activeIds = Employee::active()->pluck('id')->flip();
        $existing = Payroll::where('month', $month)->get()->keyBy('employee_id');

        $reviewed = [];
        $seen = [];

        foreach ($rows as $row) {
            $result = $this->reviewRow($row, $employees, $activeIds, $existing, $seen, $daysInMonth);
            if ($result !== null) {
                $reviewed[] = $result;
            }
        }

        $saving = array_filter($reviewed, fn ($r) => $r['action'] !== 'skip' && !$r['errors']);

        return [
            'rows' => $reviewed,
            'error_count' => count(array_filter($reviewed, fn ($r) => $r['errors'])),
            'warning_count' => count(array_filter($reviewed, fn ($r) => $r['warnings'])),
            'save_count' => count($saving),
            'replace_count' => count(array_filter($saving, fn ($r) => $r['action'] === 'replace')),
            'gross_total' => round(array_sum(array_map(fn ($r) => $r['attributes']['gross_salary'] ?? 0, $saving)), 2),
            'net_total' => round(array_sum(array_map(fn ($r) => $r['attributes']['net_salary'] ?? 0, $saving)), 2),
        ];
    }

    /**
     * Save a clean review in one transaction: all rows or none.
     *
     * @param array{rows: array<int, array<string, mixed>>, error_count: int} $review
     */
    public function commit(array $review, string $month, string $fileName): int
    {
        if ($review['error_count'] > 0) {
            throw new InvalidArgumentException('This file has errors, so nothing was saved.');
        }

        return DB::transaction(function () use ($review, $month, $fileName) {
            $saved = 0;
            foreach ($review['rows'] as $row) {
                if ($row['action'] === 'skip') {
                    continue;
                }

                $attributes = $row['attributes'];
                $note = trim((string) $row['values']['remarks']);
                $attributes['remarks'] = "Imported from {$fileName}." . ($note !== '' ? " {$note}" : '');

                Payroll::updateOrCreate(['employee_id' => $row['employee_id'], 'month' => $month], $attributes);
                $saved++;
            }

            return $saved;
        });
    }

    /**
     * @return array<string, mixed>|null null for a row with nothing in it
     */
    private function reviewRow(array $row, $employees, $activeIds, $existing, array &$seen, int $daysInMonth): ?array
    {
        $values = $row['values'];
        $code = $this->normalizeCode($values['employee_code']);
        $name = trim((string) $values['employee_name']);
        $amountKeys = array_merge(self::REQUIRED_NUMBERS, self::OPTIONAL_NUMBERS);
        $amountsBlank = !array_filter($amountKeys, fn ($key) => ($values[$key] ?? null) !== null && $values[$key] !== '');
        $amountsEmpty = !array_filter($amountKeys, fn ($key) => !in_array($values[$key] ?? null, [null, '', 0, 0.0, '0'], true));

        // Trailing spacer rows, including ones holding leftover zero formulas.
        if ($code === '' && $name === '' && $amountsEmpty) {
            return null;
        }

        $result = [
            'line' => $row['line'],
            'values' => $values,
            'employee_id' => null,
            'employee_name' => null,
            'action' => 'new',
            'existing_net' => null,
            'errors' => [],
            'warnings' => [],
            'attributes' => [],
        ];

        // A row from the pre-filled template that nobody filled in.
        if ($amountsBlank && $code !== '') {
            $result['action'] = 'skip';
            $result['employee_name'] = $employees[$code]->name ?? null;
            $result['warnings'][] = 'No amounts entered, so this row will be skipped.';

            return $result;
        }

        $employee = null;
        if ($code === '') {
            $result['errors'][] = 'Employee code is missing.';
        } else {
            $employee = $employees[$code] ?? null;
            if (!$employee) {
                $result['errors'][] = "No employee has code {$code}.";
            }
        }

        if ($employee) {
            $result['employee_id'] = $employee->id;
            $result['employee_name'] = $employee->name;

            if (isset($seen[$employee->id])) {
                $result['errors'][] = "Duplicate: code {$code} is already on line {$seen[$employee->id]}.";
            }
            $seen[$employee->id] ??= $row['line'];

            if ($name === '') {
                $result['errors'][] = 'Employee name is missing.';
            } elseif (!$this->namesMatch($name, $employee->name)) {
                $result['errors'][] = "Code {$code} belongs to {$employee->name}, but this row says \"{$name}\".";
            }

            if (!isset($activeIds[$employee->id])) {
                $result['warnings'][] = 'This employee\'s account is not active.';
            }

            $current = $existing[$employee->id] ?? null;
            if ($current) {
                if ($current->status !== 'pending') {
                    $result['errors'][] = "Payroll for this month is already {$current->status} and can't be overwritten.";
                } else {
                    $result['action'] = 'replace';
                    $result['existing_net'] = (float) $current->net_salary;
                }
            }
        }

        $numbers = [];
        foreach (self::REQUIRED_NUMBERS as $key) {
            $numbers[$key] = $this->number($values[$key], $key, true, $result['errors']);
        }
        foreach (self::OPTIONAL_NUMBERS as $key) {
            $numbers[$key] = $this->number($values[$key], $key, false, $result['errors']);
        }

        // A bad number already produced an error, so there is nothing further to check.
        if (in_array(null, $numbers, true)) {
            return $result;
        }

        $expectedNet = round(
            $numbers['gross_paid'] + $numbers['additional'] - $numbers['pf'] - $numbers['esic'] - $numbers['late_deduction'],
            2
        );
        if (abs($expectedNet - $numbers['net_salary']) > self::NET_TOLERANCE) {
            $result['warnings'][] = 'Net ' . number_format($numbers['net_salary'], 2)
                . ' will be saved as in the sheet, but gross + additional - PF - ESIC - late = ' . number_format($expectedNet, 2) . '.';
        }

        if ($numbers['payable_days'] > $daysInMonth) {
            $result['warnings'][] = "Payable days ({$numbers['payable_days']}) are more than the {$daysInMonth} days in this month.";
        }

        if ($employee) {
            $onRecord = round((float) $employee->basic_salary + (float) $employee->dearness_allowance + (float) $employee->hra
                + (float) $employee->conveyance_allowance + (float) $employee->medical_allowance + (float) $employee->other_allowance, 2);
            if (abs($onRecord - $numbers['monthly_salary']) > self::NET_TOLERANCE) {
                $result['warnings'][] = 'Monthly salary differs from the employee record (' . number_format($onRecord, 2) . ').';
            }
        }

        $deductions = round($numbers['pf'] + $numbers['esic'] + $numbers['late_deduction'], 2);

        $result['attributes'] = [
            'payable_days' => round($numbers['payable_days'], 2),
            'unpaid_days' => max(0, round($daysInMonth - $numbers['payable_days'], 2)),
            'paid_leave_days' => max(0, round($numbers['payable_days'] - $numbers['working_days'], 2)),
            'leave_balance_before_payroll' => round($numbers['leave_balance'], 2),
            'monthly_salary' => round($numbers['monthly_salary'], 2),
            'basic_salary' => round($numbers['monthly_salary'], 2),
            'gross_salary' => round($numbers['gross_paid'] + $numbers['additional'], 2),
            'variable_earning' => round($numbers['additional'], 2),
            'dearness_allowance' => 0,
            'hra' => 0,
            'conveyance_allowance' => 0,
            'medical_allowance' => 0,
            'other_allowance' => 0,
            'pf_deduction' => round($numbers['pf'], 2),
            'esi_deduction' => round($numbers['esic'], 2),
            'other_deduction' => round($numbers['late_deduction'], 2),
            'epf_deduction' => 0,
            'professional_tax_deduction' => 0,
            'loan_recovery_deduction' => 0,
            'deductions' => $deductions,
            'salary_loss' => max(0, round($numbers['monthly_salary'] - $numbers['gross_paid'], 2)),
            'net_salary' => round($numbers['net_salary'], 2),
            'status' => 'pending',
        ];

        return $result;
    }

    /** @return float|null null when invalid; a blank optional value counts as 0 */
    private function number(mixed $value, string $key, bool $required, array &$errors): ?float
    {
        if ($value === null || $value === '') {
            if ($required) {
                $errors[] = "{$key} is required.";

                return null;
            }

            return 0.0;
        }

        if (!is_numeric($value)) {
            $errors[] = "{$key} must be a plain number, not \"{$value}\".";

            return null;
        }

        if ((float) $value < 0) {
            $errors[] = "{$key} can't be negative.";

            return null;
        }

        return (float) $value;
    }

    /** Excel returns numeric cells as int or float, so 35.0 and "35" must both become "35". */
    private function normalizeCode(mixed $code): string
    {
        if (is_numeric($code) && (float) $code == (int) $code) {
            return (string) (int) $code;
        }

        return trim((string) $code);
    }

    /**
     * True when any word of the sheet name fits a word of the employee's name, allowing short
     * forms ("Moh." / "Mohammad") and small misspellings ("Ujjawal" / "ujjwal").
     */
    private function namesMatch(string $sheetName, string $employeeName): bool
    {
        $tokens = fn (string $name) => array_filter(
            explode(' ', preg_replace('/[^a-z]+/', ' ', strtolower($name))),
            fn ($token) => strlen($token) >= 2
        );

        foreach ($tokens($sheetName) as $sheetToken) {
            foreach ($tokens($employeeName) as $employeeToken) {
                $shorter = min(strlen($sheetToken), strlen($employeeToken));
                if ($sheetToken === $employeeToken
                    || ($shorter >= 3 && (str_starts_with($employeeToken, $sheetToken) || str_starts_with($sheetToken, $employeeToken)))
                    || ($shorter >= 5 && levenshtein($sheetToken, $employeeToken) <= 2)) {
                    return true;
                }
            }
        }

        return false;
    }
}

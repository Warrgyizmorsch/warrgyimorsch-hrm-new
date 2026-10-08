<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class PayrollImport implements ToCollection
{
    /** @var array<int, string> Names of rows that had no matching employee. */
    public array $skipped = [];

    /** @var array<int, string> Names of rows successfully saved. */
    public array $imported = [];

    private string $month;

    private int $daysInMonth;

    /** @var Collection<int, Employee>|null */
    private ?Collection $employees = null;

    public function __construct(string $month)
    {
        $this->month = $month;
        $this->daysInMonth = Carbon::createFromFormat('Y-m', $month)->daysInMonth;
    }

    public function collection(Collection $rows)
    {
        $columns = $this->detectFinalSalaryColumns($rows);

        if ($columns) {
            $this->importFinalSalarySheet($rows, $columns);
            return;
        }

        $this->importLegacySheet($rows);
    }

    /**
     * Legacy layout: Name | Code | ? | Pay | Salary | ? | Paid | ? | ? | Add.
     */
    private function importLegacySheet(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row[0] ?? ''));
            $employeeCode = trim((string) ($row[1] ?? ''));
            $payableDays = $row[3] ?? null;   // "Pay" (days present)
            $basicSalary = $row[4] ?? null;   // "Salary" (monthly base)
            $grossSalary = $row[6] ?? null;   // "Paid" (prorated gross for the month)
            $addNote = trim((string) ($row[9] ?? ''));

            // Skip header rows and blank trailing rows.
            if ($name === '' || $name === 'Name' || !is_numeric($payableDays) || !is_numeric($grossSalary)) {
                continue;
            }

            $employee = $this->findEmployee($employeeCode);

            if (!$employee) {
                $this->skipped[] = $name . ($employeeCode !== '' ? " (code {$employeeCode})" : '');
                continue;
            }

            $payableDays = round((float) $payableDays, 2);
            $basicSalary = round((float) ($basicSalary ?? 0), 2);
            $grossSalary = round((float) $grossSalary, 2);

            $esiDeduction = ($employee->esi && $grossSalary <= 21000)
                ? round($grossSalary * 0.0075, 2)
                : 0.0;

            $netSalary = round($grossSalary - $esiDeduction, 2);
            $unpaidDays = max(0, round($this->daysInMonth - $payableDays, 2));
            $salaryLoss = round($basicSalary - $grossSalary, 2);

            Payroll::updateOrCreate(
                ['employee_id' => $employee->id, 'month' => $this->month],
                [
                    'payable_days' => $payableDays,
                    'unpaid_days' => $unpaidDays,
                    'basic_salary' => $basicSalary,
                    'gross_salary' => $grossSalary,
                    'hra' => 0,
                    'conveyance_allowance' => 0,
                    'medical_allowance' => 0,
                    'other_allowance' => 0,
                    'pf_deduction' => 0,
                    'esi_deduction' => $esiDeduction,
                    'other_deduction' => 0,
                    'deductions' => $esiDeduction,
                    'salary_loss' => $salaryLoss,
                    'net_salary' => $netSalary,
                    'status' => 'pending',
                    'remarks' => $addNote !== '' ? "Imported from Excel. Add.: {$addNote}" : 'Imported from Excel.',
                ]
            );

            $this->imported[] = $employee->name;
        }
    }

    /**
     * "Final salary" layout (two header rows, no employee code):
     * Name | Working | Old | Month | New Balance | After Adjustment | Pay | Salary | per day | per hr
     *      | Paid | ESIC 0.75% | Total | Late hr/mins | after 30 min deduct | Late Arrivals | Final
     *
     * Columns are located by header text so inserted/reordered columns don't break the import.
     *
     * @return array<string, int>|null
     */
    private function detectFinalSalaryColumns(Collection $rows): ?array
    {
        // Merge the header text of the first few rows per column ("Late Arrivals" + "deduction amt.").
        $headers = [];
        foreach ($rows->take(5) as $row) {
            // Header rows hold only text; the first row with numbers is data.
            if (collect($row)->contains(fn ($cell) => is_int($cell) || is_float($cell))) {
                break;
            }
            foreach ($row as $index => $cell) {
                if (is_string($cell) && trim($cell) !== '') {
                    $headers[$index] = trim(($headers[$index] ?? '') . ' ' . strtolower(trim($cell)));
                }
            }
        }

        $find = function (callable $match) use ($headers): ?int {
            foreach ($headers as $index => $text) {
                if ($match($text)) {
                    return $index;
                }
            }
            return null;
        };

        $columns = [
            'name' => $find(fn ($h) => $h === 'name'),
            'code' => $find(fn ($h) => str_contains($h, 'code')),
            'working' => $find(fn ($h) => $h === 'working'),
            'new_balance' => $find(fn ($h) => str_contains($h, 'new balance')),
            'after_adjustment' => $find(fn ($h) => str_contains($h, 'after adjustment')),
            'pay' => $find(fn ($h) => $h === 'pay'),
            'salary' => $find(fn ($h) => $h === 'salary'),
            'paid' => $find(fn ($h) => $h === 'paid'),
            'esic' => $find(fn ($h) => str_contains($h, 'esic') || str_contains($h, 'esi ')),
            'late_hours' => $find(fn ($h) => str_starts_with($h, 'late hr')),
            'late_deduction' => $find(fn ($h) => str_contains($h, 'late arrival')),
            'final' => $find(fn ($h) => str_starts_with($h, 'final')),
        ];

        foreach (['name', 'pay', 'salary', 'paid', 'final'] as $required) {
            if ($columns[$required] === null) {
                return null;
            }
        }

        return $columns;
    }

    /**
     * @param array<string, int|null> $columns
     */
    private function importFinalSalarySheet(Collection $rows, array $columns): void
    {
        $value = fn ($row, string $key) => $columns[$key] === null ? null : ($row[$columns[$key]] ?? null);
        $number = fn ($row, string $key) => is_numeric($value($row, $key)) ? (float) $value($row, $key) : 0.0;

        // Pass 1: collect data rows and score each against every employee.
        $entries = [];
        foreach ($rows as $row) {
            $name = trim((string) $value($row, 'name'));

            if ($name === '' || strtolower($name) === 'name' || !is_numeric($value($row, 'pay')) || !is_numeric($value($row, 'paid'))) {
                continue;
            }

            $code = trim((string) $value($row, 'code'));
            $byCode = $code !== '' ? $this->findEmployee($code) : null;

            $entries[] = [
                'row' => $row,
                'name' => $name,
                'employee' => $byCode,
                'candidates' => $byCode ? [] : $this->rankByName($name),
            ];
        }

        // Pass 2: lock in confident matches, then let ambiguous rows ("Kapil") pick from
        // the employees not already claimed by a more specific row ("Kapil Menaria").
        $claimed = [];
        foreach ($entries as $i => $entry) {
            if (!$entry['employee']) {
                $entries[$i]['employee'] = $this->pickBest($entry['candidates'], []);
            }
            if ($entries[$i]['employee']) {
                $claimed[$entries[$i]['employee']->id] = true;
            }
        }
        foreach ($entries as $i => $entry) {
            if (!$entry['employee'] && $entry['candidates']) {
                $entries[$i]['employee'] = $this->pickBest($entry['candidates'], $claimed);
                if ($entries[$i]['employee']) {
                    $claimed[$entries[$i]['employee']->id] = true;
                }
            }
        }

        $saved = [];
        foreach ($entries as $entry) {
            $row = $entry['row'];
            $employee = $entry['employee'];

            if (!$employee) {
                $tied = collect($entry['candidates'])
                    ->filter(fn ($c) => $c['score'] === ($entry['candidates'][0]['score'] ?? -1))
                    ->pluck('employee.name');
                $this->skipped[] = $entry['name'] . ($tied->count() > 1 ? ' (matches ' . $tied->implode(' / ') . ' — use full name)' : ' (no matching employee)');
                continue;
            }

            if (isset($saved[$employee->id])) {
                $this->skipped[] = "{$entry['name']} (duplicate row for {$employee->name})";
                continue;
            }

            $payableDays = round($number($row, 'pay'), 2);
            $basicSalary = round($number($row, 'salary'), 2);
            $grossSalary = round($number($row, 'paid'), 2);
            $esiDeduction = round($number($row, 'esic'), 2);
            $lateDeduction = round($number($row, 'late_deduction'), 2);
            $deductions = round($esiDeduction + $lateDeduction, 2);
            $netSalary = round($number($row, 'final'), 2);

            $working = $number($row, 'working');
            $paidLeaveDays = $columns['working'] !== null ? max(0, round($payableDays - $working, 2)) : 0;

            $remarks = "Imported from Excel ({$entry['name']}).";
            $lateHours = $value($row, 'late_hours');
            if (is_numeric($lateHours) && (float) $lateHours > 0) {
                $remarks .= " Late hr/mins: {$lateHours}.";
            }
            if ($lateDeduction > 0) {
                $remarks .= " Late deduction: {$lateDeduction}.";
            }

            Payroll::updateOrCreate(
                ['employee_id' => $employee->id, 'month' => $this->month],
                [
                    'payable_days' => $payableDays,
                    'unpaid_days' => max(0, round($this->daysInMonth - $payableDays, 2)),
                    'paid_leave_days' => $paidLeaveDays,
                    'leave_balance_before_payroll' => round($number($row, 'new_balance'), 2),
                    'basic_salary' => $basicSalary,
                    'gross_salary' => $grossSalary,
                    'hra' => 0,
                    'conveyance_allowance' => 0,
                    'medical_allowance' => 0,
                    'other_allowance' => 0,
                    'pf_deduction' => 0,
                    'esi_deduction' => $esiDeduction,
                    'other_deduction' => $lateDeduction,
                    'deductions' => $deductions,
                    'salary_loss' => max(0, round($basicSalary - $grossSalary, 2)),
                    'net_salary' => $netSalary,
                    'status' => 'pending',
                    'remarks' => $remarks,
                ]
            );

            $saved[$employee->id] = true;
            $this->imported[] = $employee->name;
        }
    }

    /**
     * Score every active employee against a sheet name. Sheet names are often partial or
     * misspelled ("Moh. Kaif", "khushboo vaishnav", "Kheta Ram(Anshul)"), so each sheet word
     * scores 3 for an exact word match, 2 for a prefix match and 1 for a near-spelling match.
     *
     * @return array<int, array{employee: Employee, score: int}> best first
     */
    private function rankByName(string $name): array
    {
        $sheetTokens = $this->tokens($name);
        if (!$sheetTokens) {
            return [];
        }

        $ranked = [];
        foreach ($this->employees() as $employee) {
            $employeeTokens = $this->tokens($employee->name);

            if ($employeeTokens === $sheetTokens) {
                $ranked[] = ['employee' => $employee, 'score' => 1000];
                continue;
            }

            $score = 0;
            foreach ($sheetTokens as $token) {
                $best = 0;
                foreach ($employeeTokens as $candidate) {
                    if ($token === $candidate) {
                        $best = 3;
                        break;
                    }
                    $shorter = min(strlen($token), strlen($candidate));
                    if ($shorter >= 3 && (str_starts_with($candidate, $token) || str_starts_with($token, $candidate))) {
                        $best = max($best, 2);
                    } elseif ($shorter >= 5 && levenshtein($token, $candidate) <= ($shorter >= 7 ? 2 : 1)) {
                        $best = max($best, 1);
                    }
                }
                $score += $best;
            }

            if ($score > 0) {
                $ranked[] = ['employee' => $employee, 'score' => $score];
            }
        }

        usort($ranked, fn ($a, $b) => $b['score'] <=> $a['score']);

        return $ranked;
    }

    /**
     * Highest-scoring candidate not already claimed, or null when the top score is tied.
     *
     * @param array<int, array{employee: Employee, score: int}> $candidates
     * @param array<int, bool> $claimed
     */
    private function pickBest(array $candidates, array $claimed): ?Employee
    {
        $open = array_values(array_filter($candidates, fn ($c) => !isset($claimed[$c['employee']->id])));

        if (!$open || (isset($open[1]) && $open[1]['score'] === $open[0]['score'])) {
            return null;
        }

        return $open[0]['employee'];
    }

    /** @return array<int, string> */
    private function tokens(string $name): array
    {
        $clean = preg_replace('/[^a-z]+/', ' ', strtolower($name));

        return array_values(array_filter(explode(' ', $clean), fn ($t) => strlen($t) >= 2));
    }

    private function employees(): Collection
    {
        return $this->employees ??= Employee::get(['id', 'name', 'employee_code', 'esi']);
    }

    private function findEmployee(string $employeeCode): ?Employee
    {
        if ($employeeCode === '' || !is_numeric($employeeCode)) {
            return null;
        }

        return Employee::where('employee_code', $employeeCode)->first();
    }
}

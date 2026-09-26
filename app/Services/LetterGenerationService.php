<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class LetterGenerationService
{
    public static function placeholders(Employee $employee): array
    {
        $gross = $employee->basic_salary
            + $employee->dearness_allowance
            + $employee->hra
            + $employee->conveyance_allowance
            + $employee->medical_allowance
            + $employee->other_allowance;

        return [
            '{{employee_name}}' => $employee->name,
            '{{employee_code}}' => $employee->employee_code ?? ('EC-' . str_pad($employee->id, 5, '0', STR_PAD_LEFT)),
            '{{designation}}' => $employee->designation ?? '—',
            '{{department}}' => $employee->departmentRef->name ?? '—',
            '{{date_of_joining}}' => $employee->date_of_joining ? Carbon::parse($employee->date_of_joining)->format('d M Y') : '—',
            '{{gross_salary}}' => '₹' . number_format($gross, 2),
            '{{gross_salary_words}}' => self::amountInWords($gross) . ' only',
            '{{today}}' => Carbon::now()->format('d M Y'),
            '{{company_name}}' => 'Warrgyizmorsch Pvt. Ltd.',
        ];
    }

    public static function render(string $content, Employee $employee): string
    {
        return str_replace(
            array_keys(self::placeholders($employee)),
            array_values(self::placeholders($employee)),
            $content
        );
    }

    // Indian numbering system (lakh/crore), integer rupees only.
    public static function amountInWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        if ($rupees === 0) {
            return 'Zero Rupees';
        }

        $units = ['', 'Thousand', 'Lakh', 'Crore'];
        $words = [];
        $groups = [];

        $groups[] = $rupees % 1000;
        $rupees = intdiv($rupees, 1000);
        while ($rupees > 0) {
            $groups[] = $rupees % 100;
            $rupees = intdiv($rupees, 100);
        }

        foreach (array_reverse($groups, true) as $index => $group) {
            if ($group === 0) {
                continue;
            }
            $words[] = trim(self::numberToWords($group) . ' ' . $units[$index]);
        }

        return trim(implode(' ', $words)) . ' Rupees';
    }

    private static function numberToWords(int $number): string
    {
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten',
            'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $words = '';
        if ($number >= 100) {
            $words .= $ones[intdiv($number, 100)] . ' Hundred ';
            $number %= 100;
        }
        if ($number >= 20) {
            $words .= $tens[intdiv($number, 10)] . ' ';
            $number %= 10;
        }
        if ($number > 0) {
            $words .= $ones[$number] . ' ';
        }

        return trim($words);
    }
}

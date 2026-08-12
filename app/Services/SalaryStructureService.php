<?php

namespace App\Services;

use Illuminate\Support\Str;

class SalaryStructureService
{
    /**
     * Split a gross salary into components using the configured percentages
     * for the given department (e.g. "Business Development"). Returns null
     * if no breakdown is configured for that department. Rounding drift is
     * absorbed into the last component so the parts always sum exactly to
     * the input gross.
     */
    public static function breakdown(float $gross, string $department): ?array
    {
        $ratios = config('salary_structure.' . Str::slug($department, '_'));

        if (empty($ratios)) {
            return null;
        }

        $components = [];
        foreach ($ratios as $field => $ratio) {
            $components[$field] = round($gross * $ratio, 2);
        }

        $sum = array_sum($components);
        $lastField = array_key_last($components);
        $components[$lastField] = round($components[$lastField] + ($gross - $sum), 2);

        return $components;
    }

    public static function isConfigured(string $department): bool
    {
        return !empty(config('salary_structure.' . Str::slug($department, '_')));
    }
}

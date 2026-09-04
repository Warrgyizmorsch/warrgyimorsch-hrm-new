<?php

namespace App\Services;

use App\Models\Department;
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
    public static function breakdown(float $gross, ?int $departmentId): ?array
    {
        $name = $departmentId ? Department::find($departmentId)?->name : null;
        $ratios = $name ? config('salary_structure.' . Str::slug($name, '_')) : null;

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

    public static function isConfigured(?int $departmentId): bool
    {
        $name = $departmentId ? Department::find($departmentId)?->name : null;

        return $name && !empty(config('salary_structure.' . Str::slug($name, '_')));
    }
}

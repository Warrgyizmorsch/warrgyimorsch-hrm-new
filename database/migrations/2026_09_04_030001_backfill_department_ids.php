<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $departments = DB::table('departments')->get(['id', 'name']);
        $lookup = [];
        foreach ($departments as $dept) {
            $lookup[$this->normalize($dept->name)] = $dept->id;
        }

        $unmatched = [];

        // 1. employees.department -> employees.department_id
        $employees = DB::table('employees')->get(['id', 'department', 'additional_led_departments']);
        foreach ($employees as $employee) {
            if ($employee->department) {
                $deptId = $lookup[$this->normalize($employee->department)] ?? null;
                if ($deptId) {
                    DB::table('employees')->where('id', $employee->id)->update(['department_id' => $deptId]);
                } else {
                    $unmatched[] = "employee #{$employee->id} department='{$employee->department}'";
                }
            }

            // 2. additional_led_departments (JSON array) -> department_employee_led pivot
            $led = json_decode($employee->additional_led_departments ?? '[]', true);
            if (is_array($led)) {
                foreach ($led as $name) {
                    if (!$name) {
                        continue;
                    }
                    $deptId = $lookup[$this->normalize($name)] ?? null;
                    if ($deptId) {
                        DB::table('department_employee_led')->updateOrInsert(
                            ['employee_id' => $employee->id, 'department_id' => $deptId],
                            ['created_at' => now(), 'updated_at' => now()]
                        );
                    } else {
                        $unmatched[] = "employee #{$employee->id} additional_led_departments entry='{$name}'";
                    }
                }
            }
        }

        // 3. projects.department (JSON array) -> department_project pivot
        $projects = DB::table('projects')->get(['id', 'department']);
        foreach ($projects as $project) {
            $tags = json_decode($project->department ?? '[]', true);
            if (!is_array($tags)) {
                $tags = $project->department ? [$project->department] : [];
            }
            foreach ($tags as $name) {
                if (!$name) {
                    continue;
                }
                $deptId = $lookup[$this->normalize($name)] ?? null;
                if ($deptId) {
                    DB::table('department_project')->updateOrInsert(
                        ['project_id' => $project->id, 'department_id' => $deptId],
                        ['created_at' => now(), 'updated_at' => now()]
                    );
                } else {
                    $unmatched[] = "project #{$project->id} department entry='{$name}'";
                }
            }
        }

        if (!empty($unmatched)) {
            fwrite(STDOUT, "\n[backfill_department_ids] " . count($unmatched) . " unmatched string(s) — review manually:\n");
            foreach ($unmatched as $line) {
                fwrite(STDOUT, "  - {$line}\n");
            }
        } else {
            fwrite(STDOUT, "\n[backfill_department_ids] all department strings matched cleanly.\n");
        }
    }

    public function down(): void
    {
        DB::table('employees')->update(['department_id' => null]);
        DB::table('department_employee_led')->truncate();
        DB::table('department_project')->truncate();
    }

    private function normalize(string $value): string
    {
        return strtolower(str_replace('_', ' ', trim($value)));
    }
};

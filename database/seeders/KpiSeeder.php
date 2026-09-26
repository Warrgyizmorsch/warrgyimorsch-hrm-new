<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Kpi;
use Illuminate\Database\Seeder;

class KpiSeeder extends Seeder
{
    public function run(): void
    {
        $byName = fn (string $name) => Department::where('name', $name)->value('id');

        $kpis = [
            'Web/App Development' => [
                ['title' => 'Tasks Completed On Time', 'unit' => '%', 'target_value' => 90, 'weightage' => 50],
                ['title' => 'Bugs Resolved', 'unit' => 'count', 'target_value' => 20, 'weightage' => 30],
                ['title' => 'Code Review Participation', 'unit' => 'count', 'target_value' => 10, 'weightage' => 20],
            ],
            'Mobile App Development' => [
                ['title' => 'Sprint Task Completion', 'unit' => '%', 'target_value' => 90, 'weightage' => 50],
                ['title' => 'Crash-free Sessions', 'unit' => '%', 'target_value' => 95, 'weightage' => 30],
                ['title' => 'Release Milestones Hit', 'unit' => 'count', 'target_value' => 2, 'weightage' => 20],
            ],
            'Digital Marketing' => [
                ['title' => 'Organic Traffic Growth', 'unit' => '%', 'target_value' => 10, 'weightage' => 30],
                ['title' => 'Keywords in Top 10', 'unit' => 'count', 'target_value' => 15, 'weightage' => 40],
                ['title' => 'Leads Generated', 'unit' => 'count', 'target_value' => 30, 'weightage' => 30],
            ],
            'Social Media Marketing' => [
                ['title' => 'Follower Growth', 'unit' => '%', 'target_value' => 5, 'weightage' => 30],
                ['title' => 'Posts Published', 'unit' => 'count', 'target_value' => 20, 'weightage' => 30],
                ['title' => 'Engagement Rate', 'unit' => '%', 'target_value' => 3, 'weightage' => 40],
            ],
            'Web & Graphics Design' => [
                ['title' => 'Design Tasks Delivered On Time', 'unit' => '%', 'target_value' => 90, 'weightage' => 50],
                ['title' => 'Assets Delivered', 'unit' => 'count', 'target_value' => 25, 'weightage' => 50],
            ],
            'Business Development' => [
                ['title' => 'Leads Converted', 'unit' => 'count', 'target_value' => 15, 'weightage' => 50],
                ['title' => 'Revenue Target Achieved', 'unit' => '%', 'target_value' => 100, 'weightage' => 50],
            ],
            'HR Department' => [
                ['title' => 'Positions Closed On Time', 'unit' => '%', 'target_value' => 90, 'weightage' => 50],
                ['title' => 'Onboarding Completed On Time', 'unit' => '%', 'target_value' => 100, 'weightage' => 50],
            ],
            'Administration' => [
                ['title' => 'Admin Tasks Completed On Time', 'unit' => '%', 'target_value' => 90, 'weightage' => 50],
                ['title' => 'Compliance Checklist Completion', 'unit' => '%', 'target_value' => 100, 'weightage' => 50],
            ],
        ];

        foreach ($kpis as $deptName => $rows) {
            $departmentId = $byName($deptName);
            if (!$departmentId) {
                continue;
            }

            foreach ($rows as $sort => $row) {
                Kpi::firstOrCreate(
                    ['department_id' => $departmentId, 'title' => $row['title']],
                    $row + ['sort_order' => $sort, 'status' => true]
                );
            }
        }
    }
}

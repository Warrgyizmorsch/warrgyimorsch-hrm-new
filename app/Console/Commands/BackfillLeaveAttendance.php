<?php

namespace App\Console\Commands;

use App\Http\Controllers\LeaveApplicationController;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillLeaveAttendance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-leave-attendance {--dry-run : Only report what would change, write nothing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-apply approved leave applications to Attendance so every day in range reflects the leave, fixing rows missed by past bugs';

    public function handle(LeaveApplicationController $controller)
    {
        $dryRun = (bool) $this->option('dry-run');
        $leaves = LeaveApplication::where('status', 'approved')->get();

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Scanning {$leaves->count()} approved leave applications...");

        $before = Attendance::all()->keyBy(fn ($a) => $a->employee_id . '|' . $a->attendance_date->format('Y-m-d'))
            ->map(fn ($a) => $a->toArray());

        $changed = [];

        DB::beginTransaction();
        try {
            foreach ($leaves as $leave) {
                $controller->applyApprovedLeaveToAttendance($leave);
            }

            $after = Attendance::all()->keyBy(fn ($a) => $a->employee_id . '|' . $a->attendance_date->format('Y-m-d'));

            foreach ($after as $key => $row) {
                $prev = $before->get($key);
                if ($prev === null || $prev['status'] !== $row->status || $prev['total_hours'] != $row->total_hours) {
                    $changed[] = $prev ?? [
                        'employee_id' => $row->employee_id,
                        'attendance_date' => $row->attendance_date->format('Y-m-d'),
                        '_was_missing' => true,
                    ];
                }
            }
        } finally {
            $dryRun ? DB::rollBack() : DB::commit();
        }

        if (count($changed) > 0) {
            $path = storage_path('app/leave-attendance-backfill-backup-' . now()->format('Ymd_His') . '.json');
            file_put_contents($path, json_encode($changed, JSON_PRETTY_PRINT));
            $this->info('Backed up ' . count($changed) . " pre-change attendance row(s) to: {$path}");
        }

        $this->info(($dryRun ? '[DRY RUN] Would touch' : 'Touched') . ' ' . count($changed) . " attendance row(s) across {$leaves->count()} approved leave application(s).");

        return self::SUCCESS;
    }
}

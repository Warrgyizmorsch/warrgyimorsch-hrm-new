<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Services\AttendanceStatusService;
use Illuminate\Console\Command;

class BackfillSaturdayCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-saturday-credit {--dry-run : Only report what would change, write nothing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recompute stored status on historical Saturday attendance rows to apply the 1hr recurring-activity credit, matching the rule now baked into the biometric sync';

    public function handle()
    {
        $dryRun = (bool) $this->option('dry-run');

        $rows = Attendance::whereNotNull('check_in')
            ->whereNotNull('check_out')
            ->where('is_manual', false)
            ->get()
            ->filter(fn (Attendance $a) => $a->attendance_date->isSaturday());

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Scanning {$rows->count()} Saturday attendance row(s) with real punches...");

        $backup = [];

        foreach ($rows as $a) {
            if (AttendanceStatusService::isLeaveDerivedStatus($a->status ?? '')) {
                continue;
            }

            $hours = (float) ($a->total_hours ?? 0);
            $fullDayHours = AttendanceStatusService::isNightShiftRecord($a)
                ? 8.0
                : AttendanceStatusService::FULL_DAY_HOURS;
            $credited = $hours + AttendanceStatusService::SATURDAY_CREDIT_HOURS;

            $newStatus = match (true) {
                $credited >= $fullDayHours => 'present',
                $credited >= AttendanceStatusService::HALF_DAY_MIN_HOURS => 'half_day',
                default => 'absent',
            };

            if (strtolower($a->status ?? '') === $newStatus) {
                continue;
            }

            $backup[] = $a->toArray();

            if (!$dryRun) {
                $a->update(['status' => $newStatus]);
            }
        }

        if (count($backup) > 0) {
            $path = storage_path('app/saturday-credit-backfill-backup-' . now()->format('Ymd_His') . '.json');
            file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT));
            $this->info('Backed up ' . count($backup) . " pre-change attendance row(s) to: {$path}");
        }

        $this->info(($dryRun ? '[DRY RUN] Would touch' : 'Touched') . ' ' . count($backup) . ' Saturday attendance row(s).');

        return self::SUCCESS;
    }
}

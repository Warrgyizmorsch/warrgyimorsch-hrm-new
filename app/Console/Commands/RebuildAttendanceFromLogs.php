<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;

class RebuildAttendanceFromLogs extends Command
{
    protected $signature = 'attendance:rebuild-from-logs {employee_code? : Optional biometric employee code, e.g. 29}';

    protected $description = 'Rebuild attendance records from stored biometric device logs';

    public function handle(AttendanceService $attendanceService): int
    {
        $employeeCode = $this->argument('employee_code');

        $this->info(
            $employeeCode
                ? "Rebuilding attendance for employee code {$employeeCode}..."
                : 'Rebuilding attendance for all stored biometric logs...'
        );

        $processedGroups = $attendanceService->rebuildFromDeviceLogs(
            $employeeCode ? (string) $employeeCode : null
        );

        $this->info("Processed {$processedGroups} attendance day group(s).");

        return Command::SUCCESS;
    }
}

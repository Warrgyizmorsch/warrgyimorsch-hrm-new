<?php

namespace App\Console\Commands;

use App\Http\Controllers\ZKTController;
use App\Models\Employee;
use App\Services\BiometricSyncService;
use App\Services\PyAttendanceService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SyncBiometricAttendance extends Command
{
    /**
     * php artisan attendance:sync                          all employees, BIOMETRIC_SYNC_DAYS
     * php artisan attendance:sync --days=7                 all employees, last 7 days
     * php artisan attendance:sync --from=2026-10-01 --to=2026-10-05
     * php artisan attendance:sync --employee=WG-0231 --days=30   (employee code or ID)
     */
    protected $signature = 'attendance:sync
        {--days= : Last N days, counting today}
        {--from= : Start date (Y-m-d)}
        {--to= : End date (Y-m-d), defaults to today}
        {--employee= : Only this employee (employee code or ID)}';

    protected $description = 'Sync biometric attendance (all employees for BIOMETRIC_SYNC_DAYS unless options are given)';

    public function handle(
        ZKTController $controller,
        PyAttendanceService $service,
        BiometricSyncService $biometricSync
    ) {
        try {
            Log::info('Attendance sync started at ' . now());

            $params = array_filter([
                'days' => $this->option('days'),
                'from_date' => $this->option('from'),
                'to_date' => $this->option('to'),
            ], fn ($v) => $v !== null && $v !== '');

            if ($emp = $this->option('employee')) {
                $employee = Employee::where('employee_code', $emp)->first()
                    ?? (ctype_digit((string) $emp) ? Employee::find((int) $emp) : null);
                if (!$employee) {
                    $this->error("No employee found with code or ID \"{$emp}\".");

                    return Command::FAILURE;
                }
                $params['employee_id'] = $employee->id;
            }

            $response = $controller->syncAttendance(new Request($params), $service, $biometricSync);
            $payload = $response->getData(true);

            if (($payload['success'] ?? false) !== true) {
                $this->error($payload['message'] ?? 'Attendance sync failed.');

                return Command::FAILURE;
            }

            $this->info($payload['message'] ?? 'Attendance synced successfully');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Attendance sync command failed', ['error' => $e->getMessage()]);

            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}

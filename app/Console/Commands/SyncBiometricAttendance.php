<?php

namespace App\Console\Commands;

use App\Http\Controllers\ZKTController;
use App\Services\BiometricSyncService;
use App\Services\PyAttendanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncBiometricAttendance extends Command
{
    protected $signature = 'attendance:sync';

    protected $description = 'Sync biometric attendance';

    public function handle(
        ZKTController $controller,
        PyAttendanceService $service,
        BiometricSyncService $biometricSync
    ) {
        try {
            Log::info('Attendance sync started at ' . now());

            $response = $controller->syncAttendance($service, $biometricSync);
            $payload = $response->getData(true);

            if (($payload['success'] ?? false) !== true) {
                $this->error($payload['message'] ?? 'Attendance sync failed.');

                return Command::FAILURE;
            }

            $this->info('Attendance synced successfully');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Attendance sync command failed', ['error' => $e->getMessage()]);

            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
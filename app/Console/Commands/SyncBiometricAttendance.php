<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ZKTController;
use App\Services\PyAttendanceService;
use Illuminate\Support\Facades\Log;

class SyncBiometricAttendance extends Command
{
    protected $signature = 'attendance:sync';

    protected $description = 'Sync biometric attendance';

    public function handle()
    {
        try {

            $controller = new ZKTController();
            Log::info('Attendance sync started at '.now());
            $service = app(PyAttendanceService::class);

            $response = $controller->syncAttendance($service);

            $this->info('Attendance synced successfully');

            return Command::SUCCESS;

        } catch (\Exception $e) {

            $this->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}
<?php

namespace App\Http\Controllers;

use App\Services\PyAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZKTController extends Controller
{
    public function syncAttendance(PyAttendanceService $service): JsonResponse
    {
        $python = trim((string) config('biometric.python_path'));
        $pythonScript = trim((string) config('biometric.script_path'));

        if ($python === '' || $pythonScript === '') {
            return response()->json([
                'success' => false,
                'message' => 'Biometric sync is not configured. Set BIOMETRIC_PYTHON_PATH and BIOMETRIC_SCRIPT_PATH in .env',
            ], 422);
        }

        if (!is_file($pythonScript)) {
            return response()->json([
                'success' => false,
                'message' => 'Biometric script not found at: ' . $pythonScript,
            ], 422);
        }

        $command = escapeshellarg($python) . ' ' . escapeshellarg($pythonScript);

        Log::info('Biometric attendance sync started', [
            'command' => $command,
        ]);

        $output = shell_exec($command);

        if ($output === null) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to run biometric sync script. Check Python path and server permissions.',
            ], 500);
        }

        $records = json_decode(trim($output), true);

        if (!is_array($records)) {
            Log::warning('Biometric sync returned invalid JSON', [
                'raw' => $output,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No valid data received from biometric device',
                'raw' => $output,
            ], 422);
        }

        if (isset($records['error'])) {
            return response()->json([
                'success' => false,
                'message' => $records['error'],
            ], 422);
        }

        if ($records === []) {
            return response()->json([
                'success' => true,
                'total_records' => 0,
                'message' => 'No new punches found on the biometric device',
            ]);
        }

        foreach ($records as $att) {
            DB::table('attendance_logs')->updateOrInsert(
                [
                    'device_uid' => $att['uid'],
                    'timestamp'  => $att['timestamp'],
                ],
                [
                    'user_id'    => $att['user_id'],
                    'status'     => $att['status'],
                    'punch'      => $att['punch'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $formatted = collect($records)->map(function ($att) {
            return [
                'employee_code' => $att['user_id'],
                'timestamp'     => $att['timestamp'],
            ];
        })->toArray();

        $service->processPunches($formatted);

        Log::info('Biometric attendance sync completed', [
            'total_records' => count($records),
        ]);

        return response()->json([
            'success' => true,
            'total_records' => count($records),
            'message' => 'Latest punches imported successfully',
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\BiometricSyncService;
use App\Services\PyAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZKTController extends Controller
{
    public function syncAttendance(
        PyAttendanceService $service,
        BiometricSyncService $biometricSync
    ): JsonResponse {
        $result = $biometricSync->fetchAttendance();

        if (!$result['success']) {
            Log::warning('Biometric attendance sync failed to start', [
                'message' => $result['message'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to fetch biometric attendance.',
            ], 500);
        }

        $records = $result['records'];

        if ($records === []) {
            return response()->json([
                'success' => true,
                'total_records' => 0,
                'message' => 'No new punches found on the biometric device',
            ]);
        }

        $rs9nMap = config('biometric.rs9n_employee_map', []);
        $affectedCodes = [];

        foreach ($records as $att) {
            $rawUserId = trim((string) ($att['user_id'] ?? ''));

            if (($att['machine'] ?? null) === 'rs9n') {
                // The 'rs9n' device's own enrollment IDs (1-28) do not match
                // employees.employee_code — they were verified and mapped by mobile
                // number (config/biometric.php). Anything not in the map is skipped
                // rather than guessed, since a wrong guess silently attributes a
                // punch to the wrong employee.
                $deviceId = ctype_digit($rawUserId) ? (int) ltrim($rawUserId, '0') ?: 0 : null;
                $userId = $deviceId !== null ? ($rs9nMap[$deviceId] ?? null) : null;

                if ($userId === null) {
                    Log::warning('rs9n punch skipped: no employee mapping', [
                        'rs9n_user_id' => $rawUserId,
                        'timestamp'    => $att['timestamp'] ?? null,
                    ]);

                    continue;
                }

                $userId = (string) $userId;
            } else {
                // 'zk' machine codes already equal employee_code directly.
                $userId = $rawUserId;
            }

            $affectedCodes[$userId] = true;

            // Dedupe on (user_id, timestamp) — that's the true identity of a punch,
            // stable across both the 'zk' and 'rs9n' machines since employee codes
            // are unique company-wide. Neither machine gives a stable per-punch ID
            // (pyzk's old `uid` was just a positional index), so keying on anything
            // device-local risks re-inserting the same real punch as a new row.
            DB::table('attendance_logs')->updateOrInsert(
                [
                    'user_id'   => $userId,
                    'timestamp' => $att['timestamp'],
                ],
                [
                    'device_uid' => $att['device'] ?? $att['machine'] ?? 'unknown',
                    // punch is a NOT NULL int column (legacy ZKTeco convention: 0 = check-in, 1 = check-out).
                    'punch'      => ($att['direction'] ?? null) === 'OUT' ? 1 : 0,
                    'status'     => $att['direction'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $affectedCodes = collect(array_keys($affectedCodes));

        foreach ($affectedCodes as $employeeCode) {
            $service->rebuildFromDeviceLogs((string) $employeeCode);
        }

        Log::info('Biometric attendance sync completed', [
            'total_records' => count($records),
            'employees_updated' => $affectedCodes->count(),
        ]);

        return response()->json([
            'success' => true,
            'total_records' => count($records),
            'employees_updated' => $affectedCodes->count(),
            'message' => 'Latest punches imported successfully',
        ]);
    }

    public function rebuildFromLogs(PyAttendanceService $service): JsonResponse
    {
        $processedGroups = $service->rebuildFromDeviceLogs();

        return response()->json([
            'success' => true,
            'processed_groups' => $processedGroups,
            'message' => 'Attendance recalculated from stored biometric logs.',
        ]);
    }
}

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

        foreach ($records as $att) {
            // Dedupe on (user_id, timestamp) — that's the true identity of a punch.
            // pyzk's `uid` is only the record's position within the device's current
            // result set, not a stable per-punch ID, so it can point at a different
            // timestamp on every sync. Keying on it let the same real punch be
            // re-inserted as a brand new row instead of being matched and updated.
            DB::table('attendance_logs')->updateOrInsert(
                [
                    'user_id'   => $att['user_id'],
                    'timestamp' => $att['timestamp'],
                ],
                [
                    'device_uid' => $att['uid'],
                    'status'     => $att['status'],
                    'punch'      => $att['punch'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $affectedCodes = collect($records)->pluck('user_id')->unique()->filter()->values();

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

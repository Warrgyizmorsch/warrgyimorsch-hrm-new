<?php

namespace App\Http\Controllers;

use App\Models\BiometricEnrollment;
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

        $codeMap = BiometricEnrollment::codeMap();
        $affectedCodes = [];
        $unmapped = 0;

        foreach ($records as $att) {
            $rawUserId = trim((string) ($att['user_id'] ?? ''));
            $machine = (string) ($att['machine'] ?? 'zk');

            // Every machine numbers its own users (zk user 17 ≠ rs9n user 17), so punches are
            // matched only through that machine's enrollment (employee form → Biometric IDs).
            // Unknown IDs are kept aside, never guessed: a guess silently gives the punch to an
            // unrelated employee. They're replayed automatically once the ID is mapped.
            $userId = $rawUserId !== '' ? BiometricEnrollment::resolveCode($codeMap, $machine, $rawUserId) : null;

            if ($userId === null) {
                if ($rawUserId !== '' && !empty($att['timestamp'])) {
                    BiometricEnrollment::recordUnmapped($machine, $rawUserId, (string) $att['timestamp'], $att['direction'] ?? null, 'sync');
                }
                $unmapped++;

                continue;
            }

            $affectedCodes[$userId] = true;

            // Dedupe on (user_id, timestamp) — that's the true identity of a punch,
            // stable across every machine since employee codes are unique company-wide.
            // Neither machine gives a stable per-punch ID
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
            'unmapped_punches' => $unmapped,
        ]);

        return response()->json([
            'success' => true,
            'total_records' => count($records),
            'employees_updated' => $affectedCodes->count(),
            'unmapped_punches' => $unmapped,
            'message' => 'Latest punches imported successfully'
                . ($unmapped ? " ({$unmapped} punch(es) from device IDs not yet linked to an employee — link them under the employee's Biometric IDs)" : ''),
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

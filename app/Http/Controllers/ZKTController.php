<?php

namespace App\Http\Controllers;

use App\Models\BiometricEnrollment;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\BiometricSyncService;
use App\Services\PyAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ZKTController extends Controller
{
    /**
     * Pull punches from the biometric API and rebuild attendance.
     *
     * Optional filters (all may be omitted — then it syncs every employee for BIOMETRIC_SYNC_DAYS):
     *   employee_id        only this employee (uses their Biometric IDs per machine)
     *   days               last N days, counting today
     *   from_date/to_date  a date range; the API only supports "last N days", so we fetch back to
     *                      from_date and keep just the punches inside the range
     */
    public function syncAttendance(
        Request $request,
        PyAttendanceService $service,
        BiometricSyncService $biometricSync
    ): JsonResponse {
        $maxDays = max(1, (int) config('biometric.max_sync_days', 90));
        $today = Carbon::today();

        $validator = validator($request->all(), [
            'employee_id' => 'nullable|integer|exists:employees,id',
            'days' => "nullable|integer|min:1|max:{$maxDays}",
            'from_date' => 'nullable|date|before_or_equal:today',
            'to_date' => 'nullable|date|before_or_equal:today|after_or_equal:from_date',
        ], [
            'days.max' => "You can sync at most {$maxDays} days at a time.",
            'from_date.before_or_equal' => 'The start date cannot be in the future.',
            'to_date.before_or_equal' => 'The end date cannot be in the future.',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        // ---- Date range → number of days to ask the API for
        $from = $request->filled('from_date') ? Carbon::parse($request->input('from_date'))->startOfDay() : null;
        $to = $request->filled('to_date') ? Carbon::parse($request->input('to_date'))->endOfDay() : null;
        if ($to && !$from) {
            $from = $to->copy()->startOfDay();
        }

        if ($from) {
            $days = (int) $from->diffInDays($today) + 1;
            $to = $to ?? $today->copy()->endOfDay();
        } elseif ($request->filled('days')) {
            $days = (int) $request->input('days');
        } else {
            $days = (int) config('biometric.sync_days', 2); // automatic, as configured in .env
        }

        if ($days > $maxDays) {
            return response()->json([
                'success' => false,
                'message' => "That range needs {$days} days of punches; the limit is {$maxDays}. Pick a later start date or raise BIOMETRIC_MAX_SYNC_DAYS.",
            ], 422);
        }

        // ---- Optional single employee → their device user ID on each machine
        $employee = null;
        $machineUserIds = null;
        if ($request->filled('employee_id')) {
            $employee = Employee::with('biometricEnrollments')->find($request->input('employee_id'));
            $configured = config('biometric.machines', []);
            $machineUserIds = $employee->biometricEnrollments
                ->filter(fn ($e) => in_array($e->machine, $configured, true))
                ->mapWithKeys(fn ($e) => [$e->machine => (string) $e->device_user_id])
                ->all();

            if ($machineUserIds === []) {
                return response()->json([
                    'success' => false,
                    'message' => "{$employee->name} has no Biometric IDs linked. Add them on the employee form (Biometric IDs) first.",
                ], 422);
            }
        }

        $scopeText = ($employee ? "{$employee->name}, " : 'all employees, ')
            . ($from
                ? $from->format('d M Y') . ($to && !$to->isSameDay($from) ? ' – ' . $to->format('d M Y') : '')
                : "last {$days} day" . ($days === 1 ? '' : 's'));

        $result = $biometricSync->fetchAttendance($days, $machineUserIds);

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

        // Keep only punches inside the requested range (the API returns everything since from_date).
        if ($from) {
            $records = array_values(array_filter($records, function ($att) use ($from, $to) {
                try {
                    $at = Carbon::parse((string) ($att['timestamp'] ?? ''));
                } catch (\Throwable $e) {
                    return false;
                }

                return $at->betweenIncluded($from, $to);
            }));
        }

        if ($records === []) {
            return response()->json([
                'success' => true,
                'total_records' => 0,
                'scope' => $scopeText,
                'message' => "No punches found on the biometric device for {$scopeText}.",
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
            'scope' => $scopeText,
            'total_records' => count($records),
            'employees_updated' => $affectedCodes->count(),
            'unmapped_punches' => $unmapped,
        ]);

        return response()->json([
            'success' => true,
            'total_records' => count($records),
            'employees_updated' => $affectedCodes->count(),
            'unmapped_punches' => $unmapped,
            'scope' => $scopeText,
            'message' => count($records) . " punch(es) imported for {$scopeText}"
                . ($unmapped ? " ({$unmapped} punch(es) from device IDs not yet linked to an employee — link them under the employee's Biometric IDs)" : '')
                . (!empty($result['message']) ? ' — ' . $result['message'] : ''),
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

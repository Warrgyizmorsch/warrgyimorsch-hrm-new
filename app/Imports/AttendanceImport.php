<?php

namespace App\Imports;

use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AttendanceImport implements ToCollection
{
    /** Number of rows skipped because their rs9n device ID had no employee mapping. */
    public int $skippedUnmapped = 0;

    public function __construct(private string $machine = 'zk')
    {
    }

    public function collection(Collection $rows)
    {
        $records = [];
        // Same mapping ZKTController applies to live rs9n punches: that device's own
        // internal enrollment IDs don't match employees.employee_code, so an Excel
        // export straight off the rs9n device carries device IDs, not real codes.
        $rs9nMap = Employee::whereNotNull('rs9n_device_id')->pluck('employee_code', 'rs9n_device_id');

        foreach ($rows as $index => $row) {
            if ($index == 0) {
                continue;
            }

            $rawEmployeeCode = trim($row[0] ?? '');
            $dateTimeRaw = $row[1] ?? null;

            if (!$rawEmployeeCode || !$dateTimeRaw) {
                continue;
            }

            if ($this->machine === 'rs9n') {
                // rs9n assigns its own sequential internal enrollment ID to every person it
                // enrolls, unrelated to employee_code — not limited to the original 28-person
                // group. rs9n_employee_map is the only source of truth; anything not in it is
                // skipped rather than guessed, since a wrong guess silently attributes a punch
                // to a different, unrelated employee.
                $deviceId = ctype_digit($rawEmployeeCode) ? (int) ltrim($rawEmployeeCode, '0') ?: 0 : null;
                $employeeCode = $deviceId !== null ? ($rs9nMap[$deviceId] ?? null) : null;

                if ($employeeCode === null) {
                    \Log::warning('rs9n excel import row skipped: no employee mapping', [
                        'rs9n_user_id' => $rawEmployeeCode,
                        'timestamp' => $dateTimeRaw,
                    ]);

                    $this->skippedUnmapped++;

                    continue;
                }

                $employeeCode = (string) $employeeCode;
            } else {
                $employeeCode = $rawEmployeeCode;
            }

            try {
                if (is_numeric($dateTimeRaw)) {
                    $dateTime = Carbon::instance(Date::excelToDateTimeObject($dateTimeRaw));
                } else {
                    $dateTime = Carbon::parse($dateTimeRaw);
                }
            } catch (\Exception $e) {
                \Log::error('Date parse failed during attendance import', ['value' => $dateTimeRaw]);
                continue;
            }

            $records[] = [
                'employee_code' => $employeeCode,
                'timestamp' => $dateTime->toDateTimeString(),
            ];
        }

        if ($records === []) {
            return;
        }

        // Persist into attendance_logs (same dedup-upsert the biometric sync uses) instead of
        // recomputing attendance from just this file's rows. Otherwise a later import covering
        // only part of a day (e.g. re-run to bring in one new employee) would overwrite the
        // whole day using only the punches present in that one file, discarding earlier ones.
        foreach ($records as $record) {
            DB::table('attendance_logs')->updateOrInsert(
                [
                    'user_id' => $record['employee_code'],
                    'timestamp' => $record['timestamp'],
                ],
                [
                    'device_uid' => 'excel-import',
                    'punch' => null,
                    'status' => null,
                    'updated_at' => now(),
                ],
                ['created_at' => now()]
            );
        }

        $affectedCodes = collect($records)->pluck('employee_code')->unique()->filter()->values();

        $service = app(AttendanceService::class);
        foreach ($affectedCodes as $employeeCode) {
            $service->rebuildFromDeviceLogs((string) $employeeCode);
        }
    }
}

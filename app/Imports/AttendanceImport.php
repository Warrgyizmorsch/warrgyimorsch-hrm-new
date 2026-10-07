<?php

namespace App\Imports;

use App\Models\BiometricEnrollment;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AttendanceImport implements ToCollection
{
    /** Sheet whose first column already holds employee codes (not a raw machine export). */
    public const SOURCE_CODES = 'codes';

    /** Number of rows skipped because their device ID / code matched no employee. */
    public int $skippedUnmapped = 0;

    /**
     * @param  string  $machine  a machine key (raw export off that device — first column is
     *                           the device's own user ID) or SOURCE_CODES (manual sheet)
     */
    public function __construct(private string $machine = self::SOURCE_CODES)
    {
    }

    public function collection(Collection $rows)
    {
        $records = [];
        // Same matching ZKTController applies to live punches: a raw machine export carries
        // that machine's own user IDs, which only mean something via its enrollments.
        $codeMap = BiometricEnrollment::codeMap();
        $knownCodes = $this->machine === self::SOURCE_CODES
            ? Employee::whereNotNull('employee_code')->pluck('employee_code')->map(fn ($c) => (string) $c)->flip()
            : collect();

        foreach ($rows as $index => $row) {
            if ($index == 0) {
                continue;
            }

            $rawEmployeeCode = trim($row[0] ?? '');
            $dateTimeRaw = $row[1] ?? null;

            if (!$rawEmployeeCode || !$dateTimeRaw) {
                continue;
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

            if ($this->machine === self::SOURCE_CODES) {
                $employeeCode = isset($knownCodes[$rawEmployeeCode]) ? $rawEmployeeCode : null;
            } else {
                // Never guess: an unknown device ID is kept aside and replayed once HR links it.
                $employeeCode = BiometricEnrollment::resolveCode($codeMap, $this->machine, $rawEmployeeCode);
                if ($employeeCode === null) {
                    BiometricEnrollment::recordUnmapped($this->machine, $rawEmployeeCode, $dateTime->toDateTimeString(), null, 'excel-import');
                }
            }

            if ($employeeCode === null) {
                $this->skippedUnmapped++;

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

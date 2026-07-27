<?php

namespace App\Imports;

use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class AttendanceImport implements ToCollection
{
    public function collection(Collection $rows)
    {
        $records = [];

        foreach ($rows as $index => $row) {
            if ($index == 0) {
                continue;
            }

            $employeeCode = trim($row[0] ?? '');
            $dateTimeRaw = $row[1] ?? null;

            if (!$employeeCode || !$dateTimeRaw) {
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

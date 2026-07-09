<?php

namespace App\Imports;

use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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

        app(AttendanceService::class)->processPunchRecords($records);
    }
}

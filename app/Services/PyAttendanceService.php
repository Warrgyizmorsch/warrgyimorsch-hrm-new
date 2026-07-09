<?php

namespace App\Services;

class PyAttendanceService
{
    public function __construct(
        private AttendanceService $attendanceService
    ) {
    }

    public function processPunches($records): int
    {
        return $this->attendanceService->processPunchRecords($records);
    }

    public function rebuildFromDeviceLogs(?string $employeeCode = null): int
    {
        return $this->attendanceService->rebuildFromDeviceLogs($employeeCode);
    }
}

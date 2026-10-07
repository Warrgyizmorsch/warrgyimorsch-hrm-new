<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeLetter;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Permanently deletes a former (inactive) employee's data. Payroll is the one exception —
 * wage/PF/tax rules expect salary records to be kept — so payroll rows stay, and the employee
 * row is reduced to a soft-deleted shell holding only what payslips print (name, code, job,
 * PAN, bank, PF/ESI). Tasks they assigned to other people are kept and reassigned.
 */
class EmployeePurgeService
{
    /** Tables keyed by employees.id that are deleted outright. Label => table. */
    private const EMPLOYEE_TABLES = [
        'Attendance days' => 'attendances',
        'Leave applications' => 'leave_applications',
        'Leave allotments' => 'leave_allotments',
        'Leave balances' => 'employee_leave_balances',
        'Leave transactions' => 'leave_transactions',
        'Daily tasks (with progress)' => 'daily_tasks',
        'Letters' => 'employee_letters',
        'Documents' => 'employee_documents',
        'KPI assignments' => 'kpi_assignments',
        'KRA assignments' => 'kra_assignments',
        'Employee reviews' => 'employee_reviews',
        'Technical reviews' => 'technical_reviews',
        'Profile requests' => 'employee_profile_requests',
        'Biometric IDs' => 'biometric_enrollments',
        'Team-leader departments' => 'department_employee_led',
        'Login history' => 'login_activities',
    ];

    /** Tables keyed by users.id (their login) that are deleted outright. */
    private const USER_TABLES = [
        'Notes' => 'notes',
        'Support tickets' => 'tickets',
        'Asset requests' => 'asset_requests',
        'Suggestions' => 'suggestions',
        'Broadcast read receipts' => 'broadcast_user',
        'SOP acknowledgements' => 'sop_acknowledgements',
        'Login sessions' => 'sessions',
    ];

    /** Shell columns cleared on delete — everything personal that payslips don't print. */
    private const CLEARED_COLUMNS = ['email', 'mobile_number', 'password', 'aadhaar_number', 'address', 'date_of_birth', 'photo', 'profile_status'];

    public function canPurge(Employee $employee): bool
    {
        return $this->login($employee)?->account_status === 'inactive';
    }

    /**
     * What a delete would remove, for the confirmation dialog.
     *
     * @return array{delete: array<string, int>, keep: array<string, int>}
     */
    public function preview(Employee $employee): array
    {
        $user = $this->login($employee);
        $delete = [];

        foreach ($this->existing(self::EMPLOYEE_TABLES) as $label => $table) {
            $delete[$label] = DB::table($table)->where('employee_id', $employee->id)->count();
        }
        $delete['Biometric punch logs'] = $this->punchLogs($employee)->count();
        if ($user) {
            $delete['Login account'] = 1;
            foreach ($this->existing(self::USER_TABLES) as $label => $table) {
                $delete[$label] = DB::table($table)->where('user_id', $user->id)->count();
            }
        }

        return [
            'delete' => array_filter($delete),
            'keep' => array_filter([
                'Payroll records' => DB::table('payrolls')->where('employee_id', $employee->id)->count(),
                'Tasks they assigned to others (reassigned to you)' => $user ? $this->tasksForOthers($employee, $user)->count() : 0,
            ]),
        ];
    }

    /** @return array<string, int> rows deleted per label */
    public function purge(Employee $employee, User $admin): array
    {
        $user = $this->login($employee);
        $filesToDelete = $this->files($employee);
        $deleted = $this->preview($employee)['delete'];

        DB::transaction(function () use ($employee, $user, $admin) {
            if ($user) {
                // Other people's tasks would cascade away with this login — keep them.
                $this->tasksForOthers($employee, $user)->update(['assigned_by' => $admin->id]);

                foreach ($this->existing(self::USER_TABLES) as $table) {
                    DB::table($table)->where('user_id', $user->id)->delete();
                }
            }

            foreach ($this->existing(self::EMPLOYEE_TABLES) as $table) {
                DB::table($table)->where('employee_id', $employee->id)->delete();
            }
            $this->punchLogs($employee)->delete();

            if ($user) {
                $user->delete();
            }

            $employee->forceFill(array_fill_keys(self::CLEARED_COLUMNS, null))->save();
            $employee->delete(); // soft delete: the shell stays for payroll history only
        });

        // Files only after the database commit succeeded.
        foreach ($filesToDelete as [$disk, $path]) {
            Storage::disk($disk)->delete($path);
        }
        Storage::disk(EmployeeDocument::DISK)->deleteDirectory("employee-documents/{$employee->id}");

        return $deleted;
    }

    /** Skip tables that don't exist on this install (older servers may lack newer modules). */
    private function existing(array $tables): array
    {
        return array_filter($tables, fn ($table) => Schema::hasTable($table));
    }

    private function login(Employee $employee): ?User
    {
        return User::where('employee_id', $employee->id)->first();
    }

    private function punchLogs(Employee $employee)
    {
        $code = trim((string) $employee->employee_code);

        // attendance_logs has no employee FK — rows are keyed by employee_code.
        return DB::table('attendance_logs')->where('user_id', $code === '' ? '__none__' : $code);
    }

    private function tasksForOthers(Employee $employee, User $user)
    {
        return DB::table('daily_tasks')->where('assigned_by', $user->id)->where('employee_id', '!=', $employee->id);
    }

    /** @return array<int, array{0: string, 1: string}> [disk, path] */
    private function files(Employee $employee): array
    {
        $files = DB::table('employee_letters')->where('employee_id', $employee->id)->whereNotNull('file_path')
            ->pluck('file_path')->map(fn ($p) => [EmployeeLetter::DISK, $p])->all();

        DB::table('employee_documents')->where('employee_id', $employee->id)->whereNotNull('file_path')
            ->pluck('file_path')->each(function ($p) use (&$files) { $files[] = [EmployeeDocument::DISK, $p]; });

        if ($employee->photo) {
            $files[] = ['public', $employee->photo];
        }

        return $files;
    }
}

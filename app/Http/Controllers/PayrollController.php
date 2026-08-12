<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\LeaveAllotment;
use App\Models\Employee;
use App\Models\Holiday;
use App\Services\AttendanceHistoryService;
use App\Services\AttendanceStatusService;
use App\Services\LeaveBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
// use App\Imports\AttendanceImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
// use Barryvdh\DomPDF\Facade\Pdf;
// use Illuminate\Support\Facades\Log;

class PayrollController extends Controller
{
    public function __construct(private LeaveBalanceService $leaveBalanceService)
    {
    }

    /**
     * Display Attendance List
     */
    public function attendance(Request $request)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);

        $query = Attendance::query()->visible();

        $query->join('employees', 'attendances.employee_id', '=', 'employees.id');
        $query->whereDate('attendances.attendance_date', '<=', now()->toDateString());

        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $query->where('employees.department', $department);
            }
        }

        // ✅ Apply date filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('attendance_date', '>=', $request->start_date)
                ->whereDate('attendance_date', '<=', $request->end_date);
        }

        // ✅ Apply employee filter (Employee Wise)
        if ($request->filled('employee_id')) {
            $query->where('attendances.employee_id', $request->employee_id);
        }

        // ✅ Apply search filter
        if ($request->filled('search')) {
            $search = trim($request->search);
            $normalizedSearch = str_replace('/', '-', $search);
            $query->where(function($q) use ($search, $normalizedSearch) {
                $q->where('attendances.status', 'like', "%{$search}%")
                    ->orWhere('employees.name', 'like', "%{$search}%");

                try {
                    if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $normalizedSearch)) {
                        $formattedDate = Carbon::createFromFormat('d-m-Y', $normalizedSearch)->format('Y-m-d');
                        $q->orWhere('attendances.attendance_date', $formattedDate);
                    } elseif (preg_match('/^\d{1,2}-\d{1,2}$/', $normalizedSearch)) {
                        $parts = explode('-', $normalizedSearch);
                        $q->orWhere(function($sub) use ($parts) {
                            $sub->whereMonth('attendances.attendance_date', $parts[1])
                                ->whereDay('attendances.attendance_date', $parts[0]);
                        });
                    } else {
                        $q->orWhere('attendances.attendance_date', 'like', "%{$search}%");
                    }
                } catch (\Exception $e) {
                    $q->orWhere('attendances.attendance_date', 'like', "%{$search}%");
                }
            });
        }
        // ✅ GROUPING (required for your blade)
        $select = array_merge(
            ['attendances.attendance_date'],
            $this->attendanceAggregateSelectColumns()
        );

        $groupBy = ['attendances.attendance_date'];

        if ($request->filled('employee_id')) {
            $select[] = 'attendances.status';
            $select[] = 'attendances.check_in';
            $select[] = 'attendances.check_out';
            $select[] = 'attendances.total_hours';
            $groupBy[] = 'attendances.status';
            $groupBy[] = 'attendances.check_in';
            $groupBy[] = 'attendances.check_out';
            $groupBy[] = 'attendances.total_hours';
        }

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $attendance = $query->select($select)
            ->groupBy($groupBy)
            ->orderBy('attendances.attendance_date', 'desc')
            ->paginate($perPage);

        $empQuery = Employee::active()->orderBy('name', 'asc');
        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $empQuery->where('department', $department);
            }
        }
        $employees = $empQuery->get();

        return view('payroll.attendance', compact('attendance', 'employees', 'perPage'));
    }

    /**
     * Get attendance details for a specific date (AJAX)
     */
    public function getAttendanceDetails(Request $request)
    {
        try {
            $user = auth()->user();
            $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
            $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
            $isTeamLeader = in_array($role, ['team_leader']);

            $date = $request->date;
            $query = Attendance::with('employee')->visible()->where('attendance_date', $date);

            if ($isTeamLeader) {
                $department = $user->employee->department ?? null;
                if ($department) {
                    $query->whereHas('employee', function ($q) use ($department) {
                        $q->where('department', $department);
                    });
                }
            } elseif (!$isAdmin) {
                $query->where('employee_id', $user->employee_id);
            }

            $details = $query->get();
            $holiday = Holiday::whereDate('date', $date)->first();
            $dateKey = $date ? Carbon::parse($date)->format('Y-m-d') : null;
            $activityDays = $dateKey ? Attendance::computeActivityDays([$dateKey]) : [];
            $isActivityDay = (bool) ($activityDays[$dateKey] ?? false);

            $details->each(function ($attendance) use ($holiday, $isActivityDay) {
                if ($holiday) {
                    $attendance->is_holiday = true;
                    $attendance->holiday_title = $holiday->title;
                }

                $resolved = AttendanceStatusService::resolveDisplayStatus(
                    $attendance,
                    (bool) $holiday,
                    $isActivityDay
                );

                $attendance->display_status = $resolved['label'];
                $attendance->display_status_key = $resolved['key'];
                $attendance->display_status_class = $resolved['class'];
                $attendance->is_activity = $resolved['is_activity'];
            });

            return response()->json([
                'success' => true,
                'date' => $date ? Carbon::parse($date)->format('d M Y') : '',
                'data' => $details,
                'is_activity' => $isActivityDay
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get attendance records with filtering
     */
    public function getAttendance(Request $request)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);

        $query = Attendance::with('employee')->visible()->join('employees', 'attendances.employee_id', '=', 'employees.id');

        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $query->where('employees.department', $department);
            }
        } elseif (!$isAdmin) {
            $query->where('attendances.employee_id', $user->employee_id);
        }

        // Filter by Date Range (Start Date to End Date)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('attendance_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('year') && $request->filled('month')) {
            $query->whereYear('attendance_date', $request->year)
                ->whereMonth('attendance_date', $request->month);
        }

        // Filter by employee
        if (($isAdmin || $isTeamLeader) && $request->filled('employee_id')) {
            $query->where('attendances.employee_id', $request->employee_id);
        }

        $attendance = $query->orderBy('attendance_date', 'desc')->paginate(10);

        return view('payroll.attendance', compact('attendance'));
    }


    /**
     * Add Attendance - Show form
     */
    public function addAttendance()
    {
        $employees = Employee::active()->get();
        return view('payroll.add-attendance', compact('employees'));
    }

    /**
     * Store Attendance
     */
    // public function storeAttendance(Request $request)
    // {
    //     // dd($request->all());
    //     try {
    //         if (!$request->attendance_date) {
    //             throw new \Exception('Attendance date is required.');
    //         }

    //         foreach ($request->employees as $employeeData) {
    //                 $checkIn = !empty($employeeData['check_in']) ? $employeeData['check_in'] : null;
    //                 $checkOut = !empty($employeeData['check_out']) ? $employeeData['check_out'] : null;

    //                 if ($checkIn || $checkOut) {
    //                 $status = $employeeData['status'] ?? 'present';


    //               $totalHours = 0;

    //                 if ($checkIn && $checkOut) {
    //                     try {
    //                         $in = Carbon::createFromFormat('H:i', $checkIn);
    //                         $out = Carbon::createFromFormat('H:i', $checkOut);

    //                         // 🔥 Get difference (can be negative)
    //                         $diffMinutes = $in->diffInMinutes($out, false);

    //                         // ✅ Handle night shift (e.g. 10 PM → 6 AM)
    //                         if ($diffMinutes < 0) {
    //                             $diffMinutes += 24 * 60;
    //                         }

    //                        $totalHours = round($diffMinutes / 60, 2);

    //                     } catch (\Exception $e) {
    //                         $totalHours = 0;
    //                     }
    //                 }

    //                 // Auto-Leave Logic: If no check-in, set status to 'leave'
    //                 if (empty($checkIn)) {
    //                     $status = 'leave';
    //                     $checkOut = null; // Ensure no check-out if no check-in
    //                 }

    //                 Attendance::updateOrCreate(
    //                     [
    //                         'employee_id' => $employeeData['employee_id'],
    //                         'attendance_date' => $request->attendance_date
    //                     ],
    //                     [
    //                         'check_in' => $checkIn,
    //                         'check_out' => $checkOut,
    //                         'status' => $status,
    //                         'total_hours' => round($totalHours, 2),
    //                     ]
    //                 );
    //             }
    //         }

    //         return redirect()->route('payroll.attendance', ['start_date' => $request->attendance_date, 'end_date' => $request->attendance_date])
    //             ->with('success', 'Attendance records have been updated successfully! ✓');
    //     } catch (\Exception $e) {
    //         return back()->with('error', 'Error: ' . $e->getMessage())
    //             ->withInput();
    //     }
    // }

    public function storeAttendance(Request $request)
    {
        foreach ($request->employees as $emp) {

            // skip if everything empty and absent
            if (
                empty($emp['check_in']) &&
                empty($emp['check_out']) &&
                ($emp['status'] ?? 'present') == 'present'
            ) {
                continue;
            }

            $checkIn = !empty($emp['check_in']) ? $emp['check_in'] : null;
            $checkOut = !empty($emp['check_out']) ? $emp['check_out'] : null;

            $totalHours = 0;
            $status = strtolower($emp['status'] ?? 'present');

            if ($checkIn && $checkOut) {
                try {
                    $in = \Carbon\Carbon::createFromFormat('H:i', $checkIn);
                    $out = \Carbon\Carbon::createFromFormat('H:i', $checkOut);

                    $diffMinutes = $in->diffInMinutes($out, false);

                    // night shift
                    if ($diffMinutes < 0) {
                        $diffMinutes += 24 * 60;
                    }

                    $totalHours = round($diffMinutes / 60, 2);
                    $status = AttendanceStatusService::resolveStoredStatus($status, $totalHours, true);
                } catch (\Exception $e) {
                    $totalHours = 0;
                }
            }

            Attendance::create([
                'employee_id' => $emp['employee_id'],
                'attendance_date' => $request->attendance_date,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'total_hours' => $totalHours,
                'status' => $status,
                // Both punches set by hand — lock this record so a later device sync/rebuild
                // can't overwrite the manual correction.
                'is_manual' => (bool) ($checkIn && $checkOut),
            ]);
        }

        return redirect()->route('payroll.attendance')->with('success', 'Attendance saved successfully');
    }

    /**
     * Display Payroll Calculation page
     */
    public function calculation()
    {
        return view('payroll.calculation');
    }

    /**
     * Calculate and display payroll
     */
    public function calculatePayroll(Request $request)
    {
        try {
            $month = $request->month; // YYYY-MM
            $employeeId = $request->employee_id;

            $employee = Employee::active()->findOrFail($employeeId);

            // 📅 Month Setup
            $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $year = $date->year;
            $monthNum = $date->month;
            $totalDays = $date->daysInMonth;

            $leaveSummary = $this->getPayrollLeaveSummary($employee, $date);
            $historyService = app(AttendanceHistoryService::class);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();
            $history = $historyService->buildMonthlyHistory((int) $employeeId, $monthStart, $monthEnd);
            $attendanceDays = $historyService->calculateAttendancePayableDays(
                (int) $employeeId,
                $monthStart,
                $monthEnd
            );

            // Paid leave already counted in attendance (e.g. half-day leave = 0.5) must not be added twice.
            $halfDayLeaveAlreadyPaid = 0.0;
            foreach ($history as $row) {
                if (($row['status_key'] ?? '') === 'half_day_leave') {
                    $halfDayLeaveAlreadyPaid += 0.5;
                }
            }
            $leaveDays = max(0, (float) ($leaveSummary['paid_leave_days'] ?? 0) - $halfDayLeaveAlreadyPaid);
            $overtimeMinutes = 0;
            $overtimeDays = 0;
            $overtimeHours = 0;
            $shiftMinutes = $this->getEmployeeShiftMinutes($employee);
            $records = Attendance::where('employee_id', $employeeId)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $monthNum)
                ->get();

            foreach ($records as $r) {
                // Overtime is still derived from raw attendance hours (plus the Saturday activity credit).
                if ($r->total_hours > 0) {
                    $workedMinutes = $r->total_hours * 60;
                    $workedMinutes += AttendanceStatusService::saturdayCreditHours($r) * 60;

                    if ($workedMinutes > $shiftMinutes) {
                        $overtimeMinutes += ($workedMinutes - $shiftMinutes);
                    }
                }
            }

            $overtimeHours = $overtimeMinutes / 60;
            $overtimeDays = $overtimeMinutes / $shiftMinutes;

            // Final
            $payableDays = min($attendanceDays + $leaveDays, $totalDays);

            // Safety
            $payableDays = min($payableDays, $totalDays);
            $overrideSalary = $request->override_salary ?? null;
            $unpaidDays = $totalDays - $payableDays;

            $isBusinessDevelopment = $employee->department === 'Business Development';

            // 💰 STEP 2: Monthly Salary (Annual → Monthly)
            $monthlySalary = (
                ($employee->basic_salary ?? 0) +
                ($isBusinessDevelopment ? ($employee->dearness_allowance ?? 0) : 0) +
                ($employee->hra ?? 0) +
                ($employee->conveyance_allowance ?? 0) +
                ($employee->medical_allowance ?? 0) +
                ($employee->other_allowance ?? 0)
            );

            // Per day salary (base components only — overtime is shown separately, not added)
            $perDaySalary = $monthlySalary / $totalDays;

            // Base gross from payable days (capped at calendar days in month)
            $baseGrossSalary = $perDaySalary * $payableDays;

            // Overtime pay: 1.5× basic hourly rate — informational only, NOT added to gross/net salary.
            $shiftHours = max($shiftMinutes / 60, 1);
            $hourlyBasicRate = ($employee->basic_salary / $totalDays) / $shiftHours;
            $overtimePay = round($hourlyBasicRate * $overtimeHours * 1.5, 2);

            $grossSalary = $baseGrossSalary;

            // Override (HR control)
            if ($overrideSalary) {
                $grossSalary = $overrideSalary;
                $overtimePay = 0;
                $baseGrossSalary = $overrideSalary;
            }

            // Variable Earning (manually entered incentive/bonus for this pay run — Business Development only)
            $variableEarning = $isBusinessDevelopment ? (float) ($request->variable_earning ?? 0) : 0;
            $grossSalary += $variableEarning;

            // 📉 STEP 3: Deductions
            $otherDeduction = (float) ($request->other_deduction ?? 0);
            $pf = 0;
            $esi = 0;
            $epfDeduction = $isBusinessDevelopment ? (float) ($request->epf_deduction ?? 0) : 0;
            $professionalTaxDeduction = $isBusinessDevelopment ? (float) ($request->professional_tax_deduction ?? 0) : 0;
            $loanRecoveryDeduction = $isBusinessDevelopment ? (float) ($request->loan_recovery_deduction ?? 0) : 0;

            // PF (12% of earned basic for payable days)
            if ($employee->pf) {
                $earnedBasic = ($employee->basic_salary / $totalDays) * $payableDays;
                $pf = $earnedBasic * 0.12;
            }

            // ESI (0.75% of gross when enabled and gross <= 21000)
            if ($employee->esi && $grossSalary <= 21000) {
                $esi = $grossSalary * 0.0075;
            }

            $totalDeductions = $pf + $esi + $otherDeduction + $epfDeduction + $professionalTaxDeduction + $loanRecoveryDeduction;

            // 💵 STEP 4: Net Salary
            $netSalary = max(0, $grossSalary - $totalDeductions);

            // 📉 Salary Loss (LOP on base salary only, not overtime)
            $fullMonthSalary = $monthlySalary;
            $salaryLoss = $fullMonthSalary - $baseGrossSalary;

            // 📦 Final Data
            $payrollData = [
                'employee_id' => $employeeId,
                'month' => $month,
                'emp_name' => $employee->name,
                'department' => $employee->department,
                'is_business_development' => $isBusinessDevelopment,
                'overtime_hours' => round($overtimeHours, 2),
                'overtime_days' => round($overtimeDays, 2),
                'overtime_pay' => $overtimePay,
                'base_gross_salary' => round($baseGrossSalary, 2),
                // Attendance
                'payable_days' => $payableDays,
                'attendance_payable_days' => round($attendanceDays, 2),
                'unpaid_days' => round($unpaidDays, 2),
                'paid_leave_days' => round($leaveSummary['paid_leave_days'], 2),
                'unpaid_leave_days' => round($leaveSummary['unpaid_leave_days'], 2),
                'leave_balance_before_payroll' => round($leaveSummary['available_balance'], 2),

                //  ORIGINAL MONTHLY SALARY (IMPORTANT)
                'basic_salary' => $employee->basic_salary,
                'dearness_allowance' => $isBusinessDevelopment ? ($employee->dearness_allowance ?? 0) : 0,
                'hra' => $employee->hra,
                'conveyance_allowance' => $employee->conveyance_allowance,
                'medical_allowance' => $employee->medical_allowance,
                'other_allowance' => $employee->other_allowance,
                'variable_earning' => round($variableEarning, 2),

                //  CALCULATED (for reference only)
                'calculated_basic' => round(($employee->basic_salary / $totalDays) * $payableDays, 2),
                'calculated_hra' => round(($employee->hra / $totalDays) * $payableDays, 2),

                // Gross
                'gross_salary' => round($grossSalary, 2),

                // Deductions
                'pf_deduction' => round($pf, 2),
                'esi_deduction' => round($esi, 2),
                'epf_deduction' => round($epfDeduction, 2),
                'professional_tax_deduction' => round($professionalTaxDeduction, 2),
                'loan_recovery_deduction' => round($loanRecoveryDeduction, 2),
                'other_deduction' => $otherDeduction,
                'deductions' => round($totalDeductions, 2),

                // Final
                'salary_loss' => round($salaryLoss, 2),
                'net_salary' => round($netSalary, 2),

                'status' => 'pending',
                'total_days' => $totalDays,
                'perdaysalary' => round($perDaySalary, 2),
                'pf_enabled' => (bool) $employee->pf,
                'esi_enabled' => (bool) $employee->esi,
            ];

            // echo "<pre>";print_r($payrollData);exit;

            return response()->json([
                'success' => true,
                'payroll' => $payrollData
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    public function calculateInRage(Request $request)
    {
        try {
            $employee = Employee::active()->findOrFail($request->employee_id);

            $start = \Carbon\Carbon::parse($request->from_date)->startOfMonth();
            $end = \Carbon\Carbon::parse($request->to_date)->endOfMonth();

            $allPayrolls = [];

            while ($start <= $end) {

                $month = $start->format('Y-m');

                // 👇 reuse your existing function logic
                $payroll = $this->calculatePayrollInternal($employee->id, $month);

                $allPayrolls[] = $payroll;

                $start->addMonth();
            }

            $total = [
                'employee_id' => $employee->id,
                'emp_name' => $employee->name,
                'from' => $request->from_date,
                'to' => $request->to_date,

                'payable_days' => 0,
                'unpaid_days' => 0,
                'overtime_hours' => 0,

                'gross_salary' => 0,
                'deductions' => 0,
                'net_salary' => 0,
                'salary_loss' => 0,
            ];

            foreach ($allPayrolls as $p) {
                $total['payable_days'] += $p->payable_days;
                $total['unpaid_days'] += $p->unpaid_days;
                $total['overtime_hours'] += $p->overtime_hours;

                $total['gross_salary'] += $p->gross_salary;
                $total['deductions'] += $p->deductions;
                $total['net_salary'] += $p->net_salary;
                $total['salary_loss'] += $p->salary_loss;
            }

            // 📧 Send Email
            \Mail::to($employee->email)->send(
                new \App\Mail\SalarySlipMail($employee, $total, $allPayrolls)
            );
            // return response()->json([
            //     'success' => true,
            //     'summary' => $total,
            //     'months' => $allPayrolls // optional (keep for breakdown)
            // ]);

            return back()->with('success', 'Salary sent to email');

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function calculatePayrollInternal($employeeId, $month)
    {
        $request = new Request([
            'employee_id' => $employeeId,
            'month' => $month
        ]);

        $response = $this->calculatePayroll($request);

        $payroll = $response->getData()->payroll;
        $payroll->employee = Employee::active()->find($employeeId);

        return $payroll;
    }

    /**
     * Store calculated payroll
     */
    public function storePayroll(Request $request)
    {
        try {
            $data = $request->all();
            $payrollModel = new Payroll();
            $payrollData = array_intersect_key($data, array_flip($payrollModel->getFillable()));

            $payroll = Payroll::updateOrCreate(
                ['employee_id' => $data['employee_id'], 'month' => $data['month']],
                $payrollData
            );

            $employee = Employee::find($data['employee_id']);
            if ($employee && !empty($data['month'])) {
                $this->adjustLeaveAttendanceAfterPayroll($employee, Carbon::createFromFormat('Y-m', $data['month'])->startOfMonth());
            }

            return response()->json([
                'success' => true,
                'message' => 'Payroll saved successfully!',
                'payroll_id' => $payroll->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function import(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
            ]);

            if ($validator->fails()) {
                return back()->with('error', $validator->errors()->first());
            }

            $file = $request->file('import_file');

            if (!$file || $file->getSize() == 0) {
                return back()->with('error', 'Uploaded file is empty.');
            }

            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\AttendanceImport, $file);

            return back()->with('success', 'Attendance imported successfully!');

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Attendance Import Controller Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Bulk import payroll (payable days, basic salary, gross/net salary) from an Excel/CSV sheet
     * for a chosen month. Employees are matched by employee_code; ESI is cut automatically when
     * the employee's ESI flag is enabled and the gross salary is <= 21000 (same rule used by the
     * single-employee calculator).
     */
    public function importPayroll(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
                'month' => 'required|date_format:Y-m',
            ]);

            if ($validator->fails()) {
                return back()->with('error', $validator->errors()->first());
            }

            $file = $request->file('import_file');

            if (!$file || $file->getSize() == 0) {
                return back()->with('error', 'Uploaded file is empty.');
            }

            $import = new \App\Imports\PayrollImport($request->month);
            \Maatwebsite\Excel\Facades\Excel::import($import, $file);

            $message = count($import->imported) . ' payroll record(s) saved for ' . $request->month . '.';
            if ($import->skipped) {
                $message .= ' Skipped (no matching employee): ' . implode(', ', $import->skipped);
            }

            return back()->with('success', $message);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Payroll Import Controller Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display payroll list
     */
    public function index(Request $request)
    {
        $roleSlug = auth()->user()->role;

        $roleId = DB::table('roles_master')
            ->where('slug', $roleSlug)
            ->value('id');

        $isAdmin = in_array($roleId, [1, 2, 3, 4]);

        $query = Payroll::with('employee');

        if (!$isAdmin) {
            $query->where('employee_id', auth()->user()->employee_id);
        }

        // Filter by month
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        // Filter by employee
        if ($isAdmin && $request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Global search across paginated pages
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('employee', function($eq) use ($search) {
                    $eq->where('name', 'like', "%{$search}%");
                })
                ->orWhere('month', 'like', "%{$search}%")
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('payable_days', 'like', "%{$search}%")
                ->orWhere('gross_salary', 'like', "%{$search}%")
                ->orWhere('net_salary', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $payrolls = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return view('payroll.index', compact('payrolls', 'perPage'));
    }

    public function payrollEmployee(Request $request)
    {
        $user_id = auth()->user()->employee_id;
        $query = Payroll::with('employee')->where('employee_id', $user_id);

        // Filter by month
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $payrolls = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return view('payroll.emppayroll', compact('payrolls', 'perPage'));
    }


    /**
     * Get payroll records with filtering
     */
    public function getPayroll(Request $request)
    {
        $query = Payroll::with('employee');

        // Filter by month
        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        // Filter by employee
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payrolls = $query->orderBy('month', 'desc')->paginate(10);

        return view('payroll.index', compact('payrolls'));
    }

    /**
     * Show payroll record
     */
    public function show($id)
    {
        $payroll = Payroll::with('employee')->findOrFail($id);
        return view('payroll.show', compact('payroll'));
    }

    /**
     * Export payroll as PDF or CSV
     */
    public function export(Request $request)
    {
        $query = Payroll::with('employee');

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        $payrolls = $query->get();

        if ($payrolls->count() == 1) {
            $payroll = $payrolls->first();
            $filename = 'payslip_' . $payroll->employee->name . '_' . $payroll->month . '.csv';
        } else {
            $filename = 'payroll_report_' . date('Y-m-d') . '.csv';
        }

        // NEW: Default to PDF format for a professional workflow
        $format = $request->query('format', 'pdf');

        if ($format === 'pdf') {
            if ($payrolls->count() == 1) {
                return $this->downloadPdf($payrolls->first()->id);
            } else {
                return $this->bulkDownloadPdf($request);
            }
        }

        // Excel format
        if ($format === 'excel') {

            $filename = 'payroll_report_' . date('Y-m-d') . '.xlsx';

            return Excel::download(
                new class ($payrolls) implements
                \Maatwebsite\Excel\Concerns\FromArray,
                \Maatwebsite\Excel\Concerns\WithHeadings {

                private $payrolls;

                public function __construct($payrolls)
                {
                    $this->payrolls = $payrolls;
                }

                public function headings(): array
                {
                    return [
                    'Name',
                    'ID',
                    'Department',
                    'Shift Time',
                    'Month Day',
                    'Basic Working Days',
                    'Leave',
                    'Net Payable',
                    'Additional'
                    ];
                }

                public function array(): array
                {
                    $data = [];

                    foreach ($this->payrolls as $payroll) {

                        $shiftTime = ($payroll->employee->time_in && $payroll->employee->time_out)
                        ? $payroll->employee->time_in . ' - ' . $payroll->employee->time_out
                        : '-';

                        $monthDays = Carbon::parse($payroll->month)->daysInMonth;

                        $leave = $monthDays - ($payroll->payable_days ?? 0);

                        $data[] = [
                            $payroll->employee->name,
                            $payroll->employee->id,
                            $payroll->employee->department ?? '-',
                        $shiftTime,
                        $monthDays,
                            $payroll->payable_days,
                        $leave,
                            $payroll->net_payable ?? $payroll->net_salary ?? 0,
                            $payroll->other_allowance,
                        ];
                    }

                    return $data;
                }

                },
                $filename
            );
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($payrolls) {
            $file = fopen('php://output', 'w');

            // Report Header
            fputcsv($file, ['Company Name', 'PAYROLL SLIP/REPORT']);
            fputcsv($file, ['Generated On', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, ['']);

            // CSV Headers
            fputcsv($file, [
                'SR.NO',
                'EMPLOYEE NAME',
                'MONTH',
                'PAYABLE DAYS',
                'BASIC SALARY',
                'HRA',
                'CONVEYANCE',
                'MEDICAL',
                'OTHER ALLOWANCE',
                'PF DEDUCTION',
                'ESI DEDUCTION',
                'OTHER DEDUCTION',
                'GROSS SALARY',
                'TOTAL DEDUCTIONS',
                'NET SALARY',
                'STATUS'
            ]);

            // CSV Data
            $counter = 1;
            foreach ($payrolls as $payroll) {
                fputcsv($file, [
                    $counter++,
                    $payroll->employee->name,
                    $payroll->month,
                    $payroll->payable_days,
                    $payroll->basic_salary,
                    $payroll->hra,
                    $payroll->conveyance_allowance,
                    $payroll->medical_allowance,
                    $payroll->other_allowance,
                    $payroll->pf_deduction,
                    $payroll->esi_deduction,
                    $payroll->other_deduction,
                    $payroll->gross_salary,
                    $payroll->deductions,
                    $payroll->net_salary,
                    $payroll->status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export attendance records (Professional Monthly Excel Grid)
     */
    public function exportAttendance(Request $request)
    {
        $filename = 'attendance_export.xlsx';

        return Excel::download(
            new \App\Exports\AttendanceExport($request),
            $filename
        );
    }
    /**
     * Update payroll status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $payroll = Payroll::findOrFail($id);
            $payroll->update([
                'status' => $request->status,
                'payment_date' => $request->status === 'paid' ? now() : null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payroll status updated!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        $attendance = Attendance::with('employee')->findOrFail($id);
        // Employee object mein purani attendance daal dete hain taaki blade page par array loop chal sake
        $employee = $attendance->employee;
        $employee->old_check_in = $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : null;
        $employee->old_check_out = $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : null;
        $employee->old_status = $attendance->status;
        $employee->old_duration = $attendance->total_hours ? intval($attendance->total_hours) . 'h ' . round(($attendance->total_hours - intval($attendance->total_hours)) * 60) . 'm' : '--';

        return view('payroll.add-attendance', [
            'employees' => [$employee], // Array mein sirf ek employee jayega
            'edit_date' => \Carbon\Carbon::parse($attendance->attendance_date)->format('Y-m-d'),
            'is_edit' => true // Is flag se hum page ko batayenge ki ye edit mode hai
        ]);
    }

    public function editByDate($attendance_date)
    {
        $attendances = Attendance::with('employee')
            ->visible()
            ->whereDate('attendance_date', $attendance_date)
            ->get();

        if ($attendances->isEmpty()) {
            return redirect()->back()->with('error', 'No attendance records found for this date.');
        }

        $employees = $attendances->map(function ($attendance) {
            $employee = $attendance->employee;

            $employee->old_check_in = $attendance->check_in
                ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i')
                : '';

            $employee->old_check_out = $attendance->check_out
                ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i')
                : '';

            $employee->old_status = $attendance->status;

            // edit flags
            $employee->can_edit_check_in = empty($attendance->check_in);
            $employee->can_edit_check_out = empty($attendance->check_out);

            // absent if both empty
            $employee->is_absent = empty($attendance->check_in) && empty($attendance->check_out);

            // duration
            if ($attendance->check_in && $attendance->check_out) {
                $in = strtotime($attendance->check_in);
                $out = strtotime($attendance->check_out);

                if ($out < $in) {
                    $out += 86400;
                }

                $diff = $out - $in;
                $hours = floor($diff / 3600);
                $minutes = floor(($diff % 3600) / 60);

                $employee->old_duration = $hours . 'h ' . $minutes . 'm';
            } else {
                $employee->old_duration = '--';
            }

            return $employee;
        });

        // Punch-aware priority: judge by actual check-in/check-out presence first (not just the
        // stored status label, which can be blank/stale), so problem rows always float to the top.
        $punchlessStatuses = ['wfh', 'leave', 'half_day_leave', 'unpaid_leave'];

        $employees = $employees
            ->sortBy(function ($employee) use ($punchlessStatuses) {
                $hasCheckIn = filled($employee->old_check_in);
                $hasCheckOut = filled($employee->old_check_out);
                $status = $employee->old_status;

                if ($hasCheckIn xor $hasCheckOut) {
                    $priority = 0; // one punch missing
                } elseif (!$hasCheckIn && !$hasCheckOut && !in_array($status, $punchlessStatuses, true)) {
                    $priority = 1; // both punches missing (absent / unauthorised / unmarked)
                } elseif ($status === 'wfh') {
                    $priority = 2;
                } elseif ($status === 'present') {
                    $priority = 3;
                } else {
                    $priority = 4; // leave, half day, late, etc.
                }

                return sprintf('%d-%s', $priority, strtolower($employee->name));
            })
            ->values();

        return view('payroll.add-attendance', [
            'employees' => $employees,
            'edit_date' => $attendance_date,
            'is_edit' => true
        ]);
    }

    // public function updateByDate(Request $request, $attendance_date)
    // {
    //     foreach ($request->employees as $emp) {

    //         $attendance = Attendance::whereDate('attendance_date', $attendance_date)
    //             ->where('employee_id', $emp['employee_id'])
    //             ->first();

    //         if (!$attendance) {
    //             continue;
    //         }

    //         // update values directly
    //         $attendance->check_in = $emp['check_in'];
    //         $attendance->check_out = $emp['check_out'];
    //         $attendance->status = $emp['status'];

    //         $attendance->save();
    //     }

    //     return redirect()->back()->with('success', 'Attendance updated successfully');
    // }
    public function updateByDate(Request $request, $attendance_date)
    {
        foreach ($request->employees as $emp) {

            $attendance = Attendance::whereDate('attendance_date', $attendance_date)
                ->where('employee_id', $emp['employee_id'])
                ->first();

            if (!$attendance) {
                continue;
            }

            $checkIn = !empty($emp['check_in']) ? $emp['check_in'] : null;
            $checkOut = !empty($emp['check_out']) ? $emp['check_out'] : null;

            $totalHours = 0;

            // calculate total hours if both times exist
            if ($checkIn && $checkOut) {
                try {
                    $in = \Carbon\Carbon::createFromFormat('H:i', $checkIn);
                    $out = \Carbon\Carbon::createFromFormat('H:i', $checkOut);

                    $diffMinutes = $in->diffInMinutes($out, false);

                    // handle night shift
                    if ($diffMinutes < 0) {
                        $diffMinutes += 24 * 60;
                    }

                    $totalHours = round($diffMinutes / 60, 2);

                } catch (\Exception $e) {
                    $totalHours = 0;
                }
            }

            $attendance->check_in = $checkIn;
            $attendance->check_out = $checkOut;
            $attendance->total_hours = $totalHours;
            $attendance->status = AttendanceStatusService::resolveStoredStatus(
                $emp['status'] ?? $attendance->status ?? 'absent',
                $totalHours,
                (bool) ($checkIn && $checkOut)
            );
            // Both punches set by hand — lock this record so a later device sync/rebuild
            // can't overwrite the manual correction.
            $attendance->is_manual = (bool) ($checkIn && $checkOut);

            $attendance->save();
        }

        return redirect()->route('payroll.attendance')
            ->with('success', 'Attendance updated successfully');
    }

    /**
     * Day-by-day list of dates that have at least one missing-punch record.
     */
    public function missingPunches(Request $request)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isTeamLeader = in_array($role, ['team_leader']);

        $query = Attendance::query()->visible();
        $query->join('employees', 'attendances.employee_id', '=', 'employees.id');
        $query->whereDate('attendances.attendance_date', '<=', now()->toDateString());

        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $query->where('employees.department', $department);
            }
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereDate('attendance_date', '>=', $request->start_date)
                ->whereDate('attendance_date', '<=', $request->end_date);
        }

        $select = array_merge(
            ['attendances.attendance_date'],
            $this->attendanceAggregateSelectColumns()
        );

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $attendance = $query->select($select)
            ->groupBy('attendances.attendance_date')
            ->having('missing_count', '>', 0)
            ->orderBy('attendances.attendance_date', 'desc')
            ->paginate($perPage);

        return view('payroll.attendance-missing', compact('attendance', 'perPage'));
    }

    /**
     * Bulk-fix view scoped to only the employees with a missing punch on one date.
     */
    public function editMissingPunchesByDate($attendance_date)
    {
        $attendances = Attendance::with('employee')
            ->visible()
            ->whereDate('attendance_date', $attendance_date)
            ->where('status', 'missing_punch')
            ->get();

        if ($attendances->isEmpty()) {
            return redirect()->route('payroll.attendance.missing')
                ->with('success', 'No missing punches left for this date.');
        }

        $employees = $attendances->map(function ($attendance) {
            $employee = $attendance->employee;

            $employee->old_check_in = $attendance->check_in
                ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i')
                : '';

            $employee->old_check_out = $attendance->check_out
                ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i')
                : '';

            $employee->old_status = $attendance->status;
            $employee->can_edit_check_in = empty($attendance->check_in);
            $employee->can_edit_check_out = empty($attendance->check_out);
            $employee->is_absent = false;
            $employee->old_duration = '--';

            return $employee;
        })
            ->sortBy(fn ($employee) => strtolower($employee->name))
            ->values();

        return view('payroll.add-attendance', [
            'employees' => $employees,
            'edit_date' => $attendance_date,
            'is_edit' => true,
            'missing_only' => true,
        ]);
    }

    /**
     * Get payroll record for editing
     */
    public function editPayroll($id)
    {
        try {
            $payroll = Payroll::with('employee')->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $payroll->id,
                    'employee_id' => $payroll->employee_id,
                    'employee_name' => $payroll->employee->name,
                    'department' => $payroll->employee->department,
                    'month' => $payroll->month,
                    'payable_days' => $payroll->payable_days,
                    'basic_salary' => $payroll->basic_salary,
                    'dearness_allowance' => $payroll->dearness_allowance,
                    'hra' => $payroll->hra,
                    'conveyance_allowance' => $payroll->conveyance_allowance,
                    'medical_allowance' => $payroll->medical_allowance,
                    'other_allowance' => $payroll->other_allowance,
                    'variable_earning' => $payroll->variable_earning,
                    'gross_salary' => $payroll->gross_salary,
                    'pf_deduction' => $payroll->pf_deduction,
                    'esi_deduction' => $payroll->esi_deduction,
                    'epf_deduction' => $payroll->epf_deduction,
                    'professional_tax_deduction' => $payroll->professional_tax_deduction,
                    'loan_recovery_deduction' => $payroll->loan_recovery_deduction,
                    'other_deduction' => $payroll->other_deduction,
                    'net_salary' => $payroll->net_salary,
                    'status' => $payroll->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Update payroll record
     */
    public function updatePayroll(Request $request, $id)
    {
        try {
            $data = $request->all();
            
            $payroll = Payroll::findOrFail($id);
            $payroll->update($data);

            return response()->json([
                'success' => true,
                'message' => 'Payroll updated successfully!',
                'payroll_id' => $payroll->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        try {
            $payroll = Payroll::findOrFail($id);
            $payroll->delete();

            return response()->json([
                'success' => true,
                'message' => 'Payroll deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove the specified attendance record from storage.
     */
    public function destroyAttendance($id)
    {
        try {
            $attendance = Attendance::findOrFail($id);
            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove all attendance records for a specific date.
     */
    public function destroyAttendanceByDate($date)
    {
        try {
            Attendance::where('attendance_date', $date)->delete();

            return response()->json([
                'success' => true,
                'message' => 'All attendance records for ' . $date . ' deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    // Show Employee wise attendace
    public function employeeWiseAttendace(Request $request)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);

        $empQuery = Employee::active()->orderBy('name', 'asc');
        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                // Team leaders only see plain "employee" role records in their own department —
                // not other team leaders, managers, HR, etc. who happen to share the department.
                $empQuery->where('department', $department)
                    ->whereRaw("LOWER(REPLACE(role, ' ', '_')) = 'employee'");
            }
        }
        $employees = $empQuery->get();

        $query = Attendance::query()
            ->visible()
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->select(array_merge(
                [
                    'attendances.employee_id',
                    DB::raw('employees.name as employee_name'),
                ],
                $this->attendanceAggregateSelectColumns()
            ));

        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $query->where('employees.department', $department)
                    ->whereRaw("LOWER(REPLACE(employees.role, ' ', '_')) = 'employee'");
            }
        }


        // FILTER BY EMPLOYEE NAME
        if ($request->filled('employee_id')) {
            $query->where('attendances.employee_id', $request->employee_id);
        }

        // FILTER BY SEARCH (Employee Name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('employees.name', 'like', "%{$search}%");
        }

        // FILTER BY START DATE / END DATE / QUICK RANGE (single resolved range for list + counts)
        $historyService = app(AttendanceHistoryService::class);
        [$listStartDate, $listEndDate] = $historyService->resolveDateRange($request);
        $listTotalDays = $historyService->countCalendarDays($listStartDate, $listEndDate);
        $includePaidLeave = $historyService->shouldIncludePaidLeaveForRange($listStartDate, $listEndDate);

        $query->whereDate('attendances.attendance_date', '>=', $listStartDate->toDateString())
            ->whereDate('attendances.attendance_date', '<=', $listEndDate->toDateString());

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }
            
        $attendance = $query
            ->groupBy('attendances.employee_id', 'employees.name')
            ->paginate($perPage)
            ->appends($request->all());

        $attendance->setCollection(
            $attendance->getCollection()->map(function ($row) use (
                $historyService,
                $listStartDate,
                $listEndDate,
                $listTotalDays,
                $includePaidLeave
            ) {
                $history = $historyService->buildMonthlyHistory((int) $row->employee_id, $listStartDate, $listEndDate);
                $summary = $historyService->buildMonthlySummary($history);
                $attendancePayableDays = $historyService->calculateAttendancePayableDays(
                    (int) $row->employee_id,
                    $listStartDate,
                    $listEndDate
                );
                $employee = Employee::find($row->employee_id);
                $paidLeaveDays = 0.0;
                if ($includePaidLeave && $employee) {
                    $leaveSummary = $this->getPayrollLeaveSummary($employee, $listStartDate->copy()->startOfMonth());
                    $paidLeaveDays = (float) ($leaveSummary['paid_leave_days'] ?? 0);
                }
                $counts = $historyService->summaryToListCounts(
                    $summary,
                    $attendancePayableDays,
                    $paidLeaveDays,
                    $listTotalDays,
                    $includePaidLeave
                );

                foreach ($counts as $key => $value) {
                    $row->{$key} = $value;
                }

                return $row;
            })
        );

        return view('payroll.employeeWise', compact(
            'attendance',
            'employees',
            'perPage',
            'listStartDate',
            'listEndDate'
        ));
    }

    public function employeeWiseDetails(Request $request)
    {
        try {
            $user = auth()->user();
            $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
            $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
            $isTeamLeader = in_array($role, ['team_leader']);

            // Security check for Team Leader — same department AND plain "employee" role only.
            if ($isTeamLeader) {
                $department = $user->employee->department ?? null;
                $targetEmployee = Employee::active()->find($request->employee_id);
                $targetRole = $targetEmployee ? str_replace(' ', '_', strtolower($targetEmployee->role ?? '')) : null;
                if (!$department || !$targetEmployee || $targetEmployee->department !== $department || $targetRole !== 'employee') {
                    return response()->json(['success' => false, 'message' => 'Unauthorized access to employee data.'], 403);
                }
            } elseif (!$isAdmin && $request->employee_id != $user->employee_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }

            $historyService = app(AttendanceHistoryService::class);
            [$startDate, $endDate] = $historyService->resolveDateRange($request, (int) $request->employee_id, true);
            $employeeName = Employee::active()->where('id', $request->employee_id)->value('name');

            return response()->json([
                'success' => true,
                'employee_name' => $employeeName,
                'data' => $historyService->buildDetailPayload((int) $request->employee_id, $startDate, $endDate),
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function editByName(Request $request, $employee_id)
    {
        $employee = Employee::active()->findOrFail($employee_id);

        $query = Attendance::where('employee_id', $employee_id);

        // Editing from a specific date row: load every attendance record for
        // that employee on that date so all punches for the day are edited
        // together in one submit (same pattern as editByDate for the Date Wise page).
        if ($request->filled('date')) {
            $query->whereDate('attendance_date', $request->date);
        } elseif ($request->filled('attendance_id')) {
            // Backward-compatible fallback for old single-record links.
            $query->where('id', $request->attendance_id);
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            // Editing from the employee summary row: scope to whatever date
            // range is currently selected on the Employee Wise list, not the
            // employee's entire attendance history.
            $query->whereDate('attendance_date', '>=', $request->start_date)
                ->whereDate('attendance_date', '<=', $request->end_date);
        }

        $attendance = $query
            ->orderBy('attendance_date', 'desc')
            ->get();

        return view('payroll.edit-attendance-by-name', compact('employee', 'attendance'));
    }

    public function updateByName(Request $request, $employee_id)
    {
        foreach ($request->attendance_ids as $id) {
            $checkIn = $request->check_in[$id] ?? null;
            $checkOut = $request->check_out[$id] ?? null;

            $totalHours = null;
            // Admin picked this status explicitly in the edit form — respect it as-is,
            // don't silently recompute it from hours (that overwrote manual corrections).
            $status = strtolower($request->status[$id] ?? 'absent');

            // Calculate total working hours
            if ($checkIn && $checkOut) {
                $inTime = \Carbon\Carbon::createFromFormat('H:i', $checkIn);
                $outTime = \Carbon\Carbon::createFromFormat('H:i', $checkOut);

                // support night shift
                if ($outTime->lt($inTime)) {
                    $outTime->addDay();
                }

                $minutes = $inTime->diffInMinutes($outTime);
                $totalHours = round($minutes / 60, 2);
            }

            Attendance::where('id', $id)->update([
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'status' => $status,
                'total_hours' => $totalHours,
                // Both punches set by hand — lock this record so a later device sync/rebuild
                // can't overwrite the manual correction.
                'is_manual' => (bool) ($checkIn && $checkOut),
            ]);
        }

        return redirect()->route('payroll.attendace.employee')
            ->with('success', 'Employee attendance updated successfully');
    }

    /**
     * Bulk remove selected attendance records by date.
     */
    public function bulkDestroyAttendance(Request $request)
    {
        try {
            $dates = $request->dates;
            if (!empty($dates)) {
                Attendance::whereIn('attendance_date', $dates)->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Selected attendance records deleted successfully!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Download individual PDF payslip
     */
    public function downloadPdf($id)
    {
        $payroll = Payroll::with('employee')->findOrFail($id);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.payslip_pdf', compact('payroll'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'isFontSubsettingEnabled' => true, // This is key for size reduction
            ]);

        $filename = 'payslip_' . str_replace(' ', '_', $payroll->employee->name) . '_' . $payroll->month . '.pdf';
        return $pdf->download($filename);
    }

    /**
     * Bulk download PDF payslips for a month/filter
     */
    public function bulkDownloadPdf(Request $request)
    {
        $query = Payroll::with('employee');

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payrolls = $query->get();

        if ($payrolls->count() == 0) {
            return back()->with('warning', 'No payroll records found for the selected filter.');
        }

        // Generate a single PDF with all slips separated by page breaks
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payroll.bulk_payslip_pdf', compact('payrolls'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'sans-serif',
                'isFontSubsettingEnabled' => true,
            ]);

        $filename = 'bulk_payslips_' . ($request->month ?? date('Y-m')) . '.pdf';
        return $pdf->download($filename);
    }

    public function saveRemarks(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);

        $isAdmin = DB::table('roles_master')
            ->where('slug', auth()->user()->role)
            ->whereIn('id', [1, 2, 3, 4])
            ->exists();

        // backend protection
        if ($isAdmin) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $payroll->remarks = $request->remarks;
        $payroll->is_read = false;
        if ($payroll->save()) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'error' => 'Failed to save'], 500);
    }

    public function markAsRead($id)
    {
        $payroll = Payroll::findOrFail($id);

        $isAdmin = DB::table('roles_master')
            ->where('slug', auth()->user()->role)
            ->whereIn('id', [1, 2, 3, 4])
            ->exists();

        if ($isAdmin) {
            $payroll->is_read = true;
            $payroll->save();
        }

        return response()->json(['success' => true]);
    }

    private function getEmployeeShiftMinutes(Employee $employee): int
    {
        $timeIn = $employee->time_in ?? '09:30:00';
        $timeOut = $employee->time_out ?? '18:00:00';

        try {
            $shiftStart = Carbon::parse($timeIn);
            $shiftEnd = Carbon::parse($timeOut);

            if ($shiftEnd->lessThanOrEqualTo($shiftStart)) {
                $shiftEnd->addDay();
            }

            return max($shiftStart->diffInMinutes($shiftEnd), 1);
        } catch (\Exception $e) {
            return 8 * 60 + 30;
        }
    }

    private function getPayrollLeaveSummary(Employee $employee, Carbon $monthDate): array
    {
        return $this->leaveBalanceService->getPayrollLeaveSummary($employee, $monthDate);
    }

    private function adjustLeaveAttendanceAfterPayroll(Employee $employee, Carbon $monthDate): void
    {
        $leaveSummary = $this->getPayrollLeaveSummary($employee, $monthDate);
        $paidRemaining = $leaveSummary['paid_leave_days'];
        $leaveDates = $this->leaveBalanceService->getDeductibleLeaveDatesBetween(
            $employee,
            $monthDate->copy()->startOfMonth(),
            $monthDate->copy()->endOfMonth()
        );

        foreach ($leaveDates as $date => $days) {
            if ($paidRemaining >= $days) {
                $paidRemaining -= $days;
                continue;
            }

            Attendance::where('employee_id', $employee->id)
                ->whereDate('attendance_date', $date)
                ->whereIn('status', ['leave', 'half_day_leave', 'half_day'])
                ->update([
                    'status' => 'unpaid_leave',
                    'total_hours' => 0,
                ]);
        }
    }

    private function getLeaveAllottedUntil(Employee $employee, Carbon $monthDate): float
    {
        return (float) LeaveAllotment::where('employee_id', $employee->id)
            ->get()
            ->filter(function ($allotment) use ($monthDate) {
                return Carbon::createFromDate((int) $allotment->year, (int) $allotment->month, 1)
                    ->startOfMonth()
                    ->lessThanOrEqualTo($monthDate->copy()->startOfMonth());
            })
            ->sum('leave_count');
    }

    private function getAvailableLeaveBalanceForMonth(Employee $employee, Carbon $monthDate): float
    {
        $targetMonth = $monthDate->copy()->startOfMonth();
        $cursor = $this->getFirstLeaveBalanceMonth($employee, $targetMonth);
        $balance = 0.0;

        while ($cursor->lt($targetMonth)) {
            $balance += $this->getLeaveAllottedForMonth($employee, $cursor);

            $leaveDays = $this->getDeductibleLeaveDaysBetween(
                $employee,
                $cursor->copy()->startOfMonth(),
                $cursor->copy()->endOfMonth()
            );

            $balance -= min($leaveDays, $balance);
            $cursor->addMonth();
        }

        return $balance + $this->getLeaveAllottedForMonth($employee, $targetMonth);
    }

    private function getFirstLeaveBalanceMonth(Employee $employee, Carbon $fallbackMonth): Carbon
    {
        $firstAllotment = LeaveAllotment::where('employee_id', $employee->id)
            ->orderBy('year')
            ->orderBy('month')
            ->first();

        $firstLeaveDate = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->orderBy('start_date')
            ->value('start_date');

        $dates = [$fallbackMonth->copy()->startOfMonth()];

        if ($firstAllotment) {
            $dates[] = Carbon::createFromDate((int) $firstAllotment->year, (int) $firstAllotment->month, 1)->startOfMonth();
        }

        if ($firstLeaveDate) {
            $dates[] = Carbon::parse($firstLeaveDate)->startOfMonth();
        }

        return collect($dates)->sortBy(fn ($date) => $date->timestamp)->first();
    }

    private function getLeaveAllottedForMonth(Employee $employee, Carbon $monthDate): float
    {
        return (float) LeaveAllotment::where('employee_id', $employee->id)
            ->where('year', $monthDate->format('Y'))
            ->whereIn('month', [$monthDate->format('m'), (string) (int) $monthDate->format('m')])
            ->sum('leave_count');
    }

    private function getDeductibleLeaveDaysUntil(Employee $employee, Carbon $endDate): float
    {
        if ($endDate->lt(Carbon::create(1900, 1, 1))) {
            return 0;
        }

        return $this->getDeductibleLeaveDaysBetween($employee, Carbon::create(1900, 1, 1), $endDate);
    }

    private function getDeductibleLeaveDaysBetween(Employee $employee, Carbon $startDate, Carbon $endDate): float
    {
        return array_sum($this->getDeductibleLeaveDatesBetween($employee, $startDate, $endDate));
    }

    private function getDeductibleLeaveDatesBetween(Employee $employee, Carbon $startDate, Carbon $endDate): array
    {
        $leaveDates = [];

        $leaves = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->where(function ($query) use ($startDate) {
                $query->whereDate('end_date', '>=', $startDate->toDateString())
                    ->orWhereNull('end_date');
            })
            ->orderBy('start_date')
            ->get();

        foreach ($leaves as $leave) {
            if (!$this->isDeductibleLeave($leave)) {
                continue;
            }

            $leaveStart = Carbon::parse($leave->start_date)->startOfDay()->max($startDate->copy()->startOfDay());
            $leaveEnd = Carbon::parse($leave->end_date ?: $leave->start_date)->startOfDay()->min($endDate->copy()->startOfDay());

            if ($leaveEnd->lt($leaveStart)) {
                continue;
            }

            if ($this->isHalfDayLeave($leave)) {
                $leaveDates[$leaveStart->toDateString()] = ($leaveDates[$leaveStart->toDateString()] ?? 0) + 0.5;
                continue;
            }

            $holidayDates = Holiday::whereBetween('date', [$leaveStart->toDateString(), $leaveEnd->toDateString()])
                ->pluck('date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->all();

            for ($date = $leaveStart->copy(); $date->lte($leaveEnd); $date->addDay()) {
                if ($date->isSunday() || in_array($date->toDateString(), $holidayDates, true)) {
                    continue;
                }

                $leaveDates[$date->toDateString()] = ($leaveDates[$date->toDateString()] ?? 0) + 1;
            }
        }

        ksort($leaveDates);

        return $leaveDates;
    }

    private function isDeductibleLeave(LeaveApplication $leave): bool
    {
        $category = strtolower($leave->leave_category ?? '');
        $type = strtolower($leave->leave_type ?? '');

        return !str_contains($category, 'wfh')
            && !str_contains($type, 'wfh')
            && !str_contains($category, 'gatepass')
            && !str_contains($type, 'gatepass')
            && !str_contains($category, 'early')
            && !str_contains($type, 'early');
    }

    private function isHalfDayLeave(LeaveApplication $leave): bool
    {
        $category = strtolower($leave->leave_category ?? '');
        $type = strtolower($leave->leave_type ?? '');

        return str_contains($category, 'half')
            || str_contains($type, 'half')
            || (float) $leave->total_days === 0.5;
    }

    /**
     * SQL EXISTS check — avoids holiday join row duplication in aggregates.
     */
    private function attendanceHolidayExistsSql(): string
    {
        return 'EXISTS (SELECT 1 FROM holidays h WHERE h.date = attendances.attendance_date)';
    }

    /**
     * Shared attendance history count columns (date-wise & employee-wise lists).
     */
    private function attendanceAggregateSelectColumns(): array
    {
        $holiday = $this->attendanceHolidayExistsSql();
        $fullDay = AttendanceStatusService::punchDerivedFullDaySql();
        $halfDay = AttendanceStatusService::punchDerivedHalfDaySql();

        return [
            DB::raw("COUNT(DISTINCT CASE
                WHEN attendances.status IN ('present', 'late', 'wfh', 'missing_punch', 'early_out', 'early_leave', 'overtime')
                    OR ({$fullDay})
                    OR (attendances.status = 'absent' AND {$holiday})
                THEN attendances.id END) as present_count"),
            DB::raw('COUNT(DISTINCT CASE WHEN attendances.total_hours > 9.5 THEN attendances.id END) as overtime_count'),
            DB::raw("COUNT(DISTINCT CASE
                WHEN attendances.status = 'half_day_leave'
                    OR ({$halfDay})
                THEN attendances.id END) as half_day_count"),
            DB::raw("COUNT(DISTINCT CASE WHEN attendances.status IN ('leave', 'half_day_leave', 'unpaid_leave') THEN attendances.id END) as leave_count"),
            DB::raw("COUNT(DISTINCT CASE WHEN attendances.status = 'absent' AND NOT ({$holiday}) THEN attendances.id END) as absent_count"),
            DB::raw("COUNT(DISTINCT CASE WHEN attendances.status = 'wfh' THEN attendances.id END) as wfh_count"),
            DB::raw($this->attendanceEarlyOutCountSql() . ' as early_count'),
            DB::raw("COUNT(DISTINCT CASE WHEN attendances.status = 'missing_punch' THEN attendances.id END) as missing_count"),
        ];
    }

    private function attendanceEarlyOutCountSql(): string
    {
        $fullDay = AttendanceStatusService::FULL_DAY_HOURS;

        return "COUNT(DISTINCT CASE
            WHEN attendances.status IN ('early_leave', 'early_out', 'present_activity') THEN attendances.id
            WHEN attendances.check_in IS NOT NULL
                AND attendances.check_out IS NOT NULL
                AND COALESCE(attendances.total_hours, 0) >= {$fullDay}
                AND TIME(attendances.check_out) >= '15:00:00'
                AND TIME(attendances.check_out) < '17:30:00'
            THEN attendances.id
        END)";
    }
}

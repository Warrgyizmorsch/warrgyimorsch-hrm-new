<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\Employee;
use App\Models\Holiday;
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
    /**
     * Display Attendance List
     */
    public function attendance(Request $request)
    {
        $user = auth()->user();
        $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
        $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
        $isTeamLeader = in_array($role, ['team_leader']);

        $query = Attendance::query();

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
        // ✅ GROUPING (required for your blade)
        $select = [
            'attendances.attendance_date',
            DB::raw("COUNT(CASE WHEN attendances.status = 'present' OR (attendances.status = 'absent' AND holidays.id IS NOT NULL) THEN 1 END) as present_count"),
            DB::raw("COUNT(CASE WHEN attendances.status = 'overtime' THEN 1 END) as overtime_count"),
            DB::raw("COUNT(CASE WHEN attendances.status = 'half_day' THEN 1 END) as half_day_count"),
            DB::raw("COUNT(CASE WHEN attendances.status = 'wfh' THEN 1 END) as wfh_count"),
            DB::raw("COUNT(CASE WHEN attendances.status = 'leave' OR (attendances.status = 'absent' AND holidays.id IS NULL) THEN 1 END) as leave_count"),
            DB::raw("COUNT(CASE WHEN attendances.status = 'absent' AND holidays.id IS NULL THEN 1 END) as absent_count"),
            DB::raw("SUM(CASE WHEN attendances.check_out IS NOT NULL AND ((attendances.status = 'present' AND TIME(attendances.check_out) <= SUBTIME(TIME(employees.time_out), '00:30:00')) OR (attendances.status = 'half_day' AND TIME(attendances.check_out) <= SUBTIME(ADDTIME(TIME(employees.time_in), SEC_TO_TIME(TIME_TO_SEC(TIMEDIFF(employees.time_out, employees.time_in)) / 2)), '00:30:00'))) THEN 1 ELSE 0 END) as early_count")
        ];

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

        $attendance = $query->leftJoin('holidays', 'attendances.attendance_date', '=', 'holidays.date')
            ->select($select)
            ->groupBy($groupBy)
            ->orderBy('attendances.attendance_date', 'desc')
            ->paginate(31);

        $empQuery = Employee::orderBy('name', 'asc');
        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $empQuery->where('department', $department);
            }
        }
        $employees = $empQuery->get();

        return view('payroll.attendance', compact('attendance', 'employees'));
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
            $query = Attendance::with('employee')->where('attendance_date', $date);

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
            if ($holiday) {
                $details->each(function ($attendance) use ($holiday) {
                    $attendance->is_holiday = true;
                    $attendance->holiday_title = $holiday->title;
                });
            }

            $earlyOuts = 0;
            $totalPresent = 0;
            foreach ($details as $att) {
                if ($att->status && in_array(strtolower($att->status), ['present', 'early_out', 'early_leave'])) {
                    if ($att->check_out && $att->employee && $att->employee->time_out) {
                        $totalPresent++;
                        try {
                            $punchTime = date('H:i', strtotime($att->check_out));
                            if ($punchTime >= '15:00' && $punchTime < '17:30') {
                                $earlyOuts++;
                            }
                        } catch (\Exception $e) {
                        }
                    }
                }
            }

            $isActivity = ($totalPresent > 2 && ($earlyOuts / $totalPresent) >= 0.7);

            return response()->json([
                'success' => true,
                'date' => $date ? Carbon::parse($date)->format('d M Y') : '',
                'data' => $details,
                'is_activity' => $isActivity
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

        $query = Attendance::with('employee')->join('employees', 'attendances.employee_id', '=', 'employees.id');

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
        $employees = Employee::all();
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
                    // ✅ Apply your attendance logic
                    $fullDay = 8.5;
                    $graceMinutes = 15;
                    $minFullDay = $fullDay - ($graceMinutes / 60);

                    if ($totalHours >= $minFullDay) {
                        $status = 'present';
                    } elseif ($totalHours >= 3.90) {
                        $status = 'half_day';
                    } else {
                        $status = 'absent';
                    }
                } catch (\Exception $e) {
                    $totalHours = 0;
                }
            }

            Attendance::create([
                'employee_id' => $emp['employee_id'],
                'attendance_date' => $request->attendance_date,
                'check_in' => !empty($emp['check_in']) ? $emp['check_in'] : null,
                'check_out' => !empty($emp['check_out']) ? $emp['check_out'] : null,
                'total_hours' => $totalHours,
                'status' => $emp['status'],
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

            $employee = Employee::findOrFail($employeeId);

            // 📅 Month Setup
            $date = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            $year = $date->year;
            $monthNum = $date->month;
            $totalDays = $date->daysInMonth;
            // echo $totalDays;exit;
            // 📊 Get Attendance
            $records = Attendance::where('employee_id', $employeeId)
                ->whereYear('attendance_date', $year)
                ->whereMonth('attendance_date', $monthNum)
                ->get();

            $leaves = LeaveApplication::where('employee_id', $employeeId)
                ->where('status', 'approved')
                ->where('leave_category', 'NOT LIKE', '%WFH%') // Exclude WFH from leave count
                ->where(function ($q) use ($year, $monthNum) {
                    $q->whereMonth('start_date', $monthNum)
                        ->orWhereMonth('end_date', $monthNum);
                })
                ->get();

            $leaveDays = 0;
            foreach ($leaves as $leave) {

                // If already stored → best case
                if (!empty($leave->total_days)) {
                    $leaveDays += $leave->total_days;
                } else {

                    // fallback (date diff)
                    $days = $leave->start_date->diffInDays($leave->end_date) + 1;
                    $leaveDays += $days;
                }
            }

            $attendanceDays = 0;
            $overtimeMinutes = 0;
            $overtimeDays = 0;
            $overtimeHours = 0;
            foreach ($records as $r) {

                switch ($r->status) {

                    case 'present':
                    case 'wfh':
                    case 'late':
                    case 'leave':
                        $attendanceDays += 1;
                        break;

                    case 'half_day':
                        $attendanceDays += 0.5;
                        break;

                    case 'absent':
                    case 'unpaid':
                    case 'unauthorised':
                    default:
                        break;
                }
                // 🔥 OVERTIME LOGIC

                $shiftMinutes = 8 * 60 + 30; // 510 min

                if ($r->total_hours > 0) {
                    $workedMinutes = $r->total_hours * 60;

                    if ($workedMinutes > $shiftMinutes) {
                        $overtimeMinutes += ($workedMinutes - $shiftMinutes);
                    }
                }

            }

            $overtimeHours = $overtimeMinutes / 60;

            // Convert to days (for reference)
            $overtimeDays = $overtimeMinutes / (8 * 60 + 30);


            // Leaves
            $leaveDays = 0;

            foreach ($leaves as $leave) {
                $leaveDays += $leave->total_days ??
                    ($leave->start_date->diffInDays($leave->end_date) + 1);
            }

            // Final
            $payableDays = min($attendanceDays + $leaveDays, $totalDays);

            // Safety
            $payableDays = min($payableDays, $totalDays);
            $overrideSalary = $request->override_salary ?? null;
            $unpaidDays = $totalDays - $payableDays;

            // 💰 STEP 2: Monthly Salary (Annual → Monthly)
            $monthlySalary = (
                ($employee->basic_salary ?? 0) +
                ($employee->hra ?? 0) +
                ($employee->conveyance_allowance ?? 0) +
                ($employee->medical_allowance ?? 0) +
                ($employee->other_allowance ?? 0)
            );

            // Per day salary
            $perDaySalary = $monthlySalary / $totalDays;

            // Gross Salary
            $grossSalary = $perDaySalary * $payableDays;

            // Override (HR control)
            if ($overrideSalary) {
                $grossSalary = $overrideSalary;
            }

            // 📉 STEP 3: Deductions
            $pf = 0;
            $esi = 0;

            $pf = $request->pf ?? $pf;
            $esi = $request->esi ?? $esi;
            $otherDeduction = $request->other_deduction ?? 0;

            $totalDeductions = $pf + $esi + $otherDeduction;

            // // PF (12% of basic portion only)
            // if ($employee->pf) {
            //     $basicMonthly = ($employee->basic_salary ?? 0) / 12;
            //     $pf = ($basicMonthly / $totalDays * $payableDays) * 0.12;
            // }

            // // ESI (only if salary <= 21000)
            // if ($employee->esi && $grossSalary <= 21000) {
            //     $esi = $grossSalary * 0.0075;
            // }

            // $totalDeductions = $pf + $esi;

            // 💵 STEP 4: Net Salary
            $netSalary = max(0, $grossSalary - $totalDeductions);

            // 📉 Salary Loss
            $fullMonthSalary = $monthlySalary;
            $salaryLoss = $fullMonthSalary - $grossSalary;

            // 📦 Final Data
            $payrollData = [
                'employee_id' => $employeeId,
                'month' => $month,
                'emp_name' => $employee->name,
                'overtime_hours' => round($overtimeHours, 2),
                'overtime_days' => round($overtimeDays, 2),
                // Attendance
                'payable_days' => $payableDays,
                'unpaid_days' => round($unpaidDays, 2),

                //  ORIGINAL MONTHLY SALARY (IMPORTANT)
                'basic_salary' => $employee->basic_salary,
                'hra' => $employee->hra,
                'conveyance_allowance' => $employee->conveyance_allowance,
                'medical_allowance' => $employee->medical_allowance,
                'other_allowance' => $employee->other_allowance,

                //  CALCULATED (for reference only)
                'calculated_basic' => round(($employee->basic_salary / $totalDays) * $payableDays, 2),
                'calculated_hra' => round(($employee->hra / $totalDays) * $payableDays, 2),

                // Gross
                'gross_salary' => round($grossSalary, 2),

                // Deductions
                'pf_deduction' => round($pf, 2),
                'esi_deduction' => round($esi, 2),
                'other_deduction' => $otherDeduction,
                'deductions' => round($totalDeductions, 2),

                // Final
                'salary_loss' => round($salaryLoss, 2),
                'net_salary' => round($netSalary, 2),

                'status' => 'pending',
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
            $employee = Employee::findOrFail($request->employee_id);

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
        $payroll->employee = Employee::find($employeeId);

        return $payroll;
    }

    /**
     * Store calculated payroll
     */
    public function storePayroll(Request $request)
    {
        try {
            $data = $request->all();
            $payroll = Payroll::updateOrCreate(
                ['employee_id' => $data['employee_id'], 'month' => $data['month']],
                $data
            );

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

        $payrolls = $query->orderBy('created_at', 'desc')->paginate(10);
        return view('payroll.index', compact('payrolls'));
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

        $payrolls = $query->orderBy('created_at', 'desc')->paginate(12);
        return view('payroll.emppayroll', compact('payrolls'));
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
        $start = $request->start_date;
        $end = $request->end_date;
        $employeeId = $request->employee_id;

        $filename = 'attendance_' . $start . '_to_' . $end . '.xlsx';

        return Excel::download(
            new \App\Exports\AttendanceExport($start, $end, $employeeId),
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
            $attendance->status = $emp['status'];
            $attendance->total_hours = $totalHours;

            $attendance->save();
        }

        return redirect()->route('payroll.attendance')
            ->with('success', 'Attendance updated successfully');
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

        $empQuery = Employee::orderBy('name', 'asc');
        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $empQuery->where('department', $department);
            }
        }
        $employees = $empQuery->get();

        $query = Attendance::selectRaw("
        attendances.employee_id,
        employees.name as employee_name,

        COUNT(CASE WHEN attendances.status = 'present' OR (attendances.status = 'absent' AND holidays.id IS NOT NULL) THEN 1 END) as present_count,
        COUNT(CASE WHEN attendances.total_hours > 9.50 THEN 1 END) as overtime_count,
        COUNT(CASE WHEN attendances.status = 'half_day' THEN 1 END) as half_day_count,
        COUNT(CASE WHEN attendances.status IN ('leave') THEN 1 END) as leave_count,
        COUNT(CASE WHEN attendances.status IN ('absent') AND holidays.id IS NULL THEN 1 END) as absent_count,
        COUNT(CASE WHEN attendances.status = 'wfh' THEN 1 END) as wfh_count,

        COUNT(
            CASE 
                WHEN attendances.check_out IS NOT NULL AND (
                    
                    -- ✅ Full Day Early
                    (
                        attendances.status = 'present'
                        AND TIME(attendances.check_out) <= SUBTIME(TIME(employees.time_out), '00:30:00')
                    )

                    OR

                    -- ✅ Half Day Early
                    (
                        attendances.status = 'half_day'
                        AND TIME(attendances.check_out) <= SUBTIME(
                            ADDTIME(
                                TIME(employees.time_in),
                                SEC_TO_TIME(
                                    TIME_TO_SEC(TIMEDIFF(employees.time_out, employees.time_in)) / 2
                                )
                            ),
                            '00:30:00'
                        )
                    )

                )
                THEN 1
            END
        ) as early_count
    ")
            ->join('employees', 'attendances.employee_id', '=', 'employees.id')
            ->leftJoin('holidays', 'attendances.attendance_date', '=', 'holidays.date');

        if ($isTeamLeader) {
            $department = $user->employee->department ?? null;
            if ($department) {
                $query->where('employees.department', $department);
            }
        }


        // FILTER BY EMPLOYEE NAME
        if ($request->filled('employee_id')) {
            $query->where('attendances.employee_id', $request->employee_id);
        }

        // FILTER BY START DATE
        if ($request->filled('start_date')) {
            $query->whereDate('attendances.attendance_date', '>=', $request->start_date);
        }

        // FILTER BY END DATE
        if ($request->filled('end_date')) {
            $query->whereDate('attendances.attendance_date', '<=', $request->end_date);
        }
        $attendance = $query
            ->groupBy('attendances.employee_id', 'employees.name')
            ->paginate(10)
            ->appends($request->all());

        

        return view('payroll.employeeWise', compact('attendance', 'employees'));
    }

    public function employeeWiseDetails(Request $request)
    {
        try {
            $user = auth()->user();
            $role = str_replace(' ', '_', strtolower($user->role ?? 'employee'));
            $isAdmin = in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head']);
            $isTeamLeader = in_array($role, ['team_leader']);

            $query = Attendance::with('employee')->where('employee_id', $request->employee_id);

            // Security check for Team Leader
            if ($isTeamLeader) {
                $department = $user->employee->department ?? null;
                $targetEmployee = Employee::find($request->employee_id);
                if ($department && $targetEmployee && $targetEmployee->department !== $department) {
                    return response()->json(['success' => false, 'message' => 'Unauthorized access to employee data.'], 403);
                }
            } elseif (!$isAdmin && $request->employee_id != $user->employee_id) {
                return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }

            if ($request->filled('start_date')) {
                $query->whereDate('attendance_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('attendance_date', '<=', $request->end_date);
            }

            $records = $query->orderBy('attendance_date', 'desc')->get();
            $holidayMap = Holiday::whereIn('date', $records->pluck('attendance_date')->unique())
                ->pluck('title', 'date');

            $records->each(function ($attendance) use ($holidayMap) {
                $dateKey = Carbon::parse($attendance->attendance_date)->format('Y-m-d');
                if ($holidayMap->has($dateKey)) {
                    $attendance->is_holiday = true;
                    $attendance->holiday_title = $holidayMap->get($dateKey);
                }
            });

            // Calculate activity days efficiently (avoid N+1)
            $activityDays = [];
            $allDates = $records->pluck('attendance_date')->unique()->toArray();

            if (!empty($allDates)) {
                $allDailyAtts = Attendance::with('employee')
                    ->whereIn('attendance_date', $allDates)
                    ->get()
                    ->groupBy('attendance_date');

                foreach ($allDailyAtts as $date => $dailyAtts) {
                    $earlyOuts = 0;
                    $totalPresent = 0;
                    foreach ($dailyAtts as $att) {
                        if ($att->status && in_array(strtolower($att->status), ['present', 'early_out', 'early_leave'])) {
                            if ($att->check_out && $att->employee && $att->employee->time_out) {
                                $totalPresent++;
                                try {
                                    $punchTime = date('H:i', strtotime($att->check_out));
                                    if ($punchTime >= '15:00' && $punchTime < '17:30') {
                                        $earlyOuts++;
                                    }
                                } catch (\Exception $e) {
                                }
                            }
                        }
                    }
                    if ($totalPresent > 2 && ($earlyOuts / $totalPresent) >= 0.7) {
                        $activityDays[$date] = true;
                    }
                }
            }

            $employeeName = Employee::where('id', $request->employee_id)->value('name');

            return response()->json([
                'success' => true,
                'employee_name' => $employeeName,
                'data' => $records,
                'activity_days' => $activityDays
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function editByName(Request $request, $employee_id)
    {
        $employee = Employee::findOrFail($employee_id);

        $query = Attendance::where('employee_id', $employee_id);

        // if single record edit from eye tab
        if ($request->filled('attendance_id')) {
            $query->where('id', $request->attendance_id);
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
                'status' => $request->status[$id] ?? 'absent',
                'total_hours' => $totalHours
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
}

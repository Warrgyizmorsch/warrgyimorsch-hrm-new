<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Exports\EmployeesExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Role;
use App\Models\JobApplication;
use App\Services\AttendanceHistoryService;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
    //    $role = strtoupper(auth()->user()->role ?? 'employee');
    //     $isAdmin = in_array($role, ['MANAGER', 'SUPER_ADMIN', 'HR_EXECUTIVE', 'HR_INTERN']);
            $roleSlug = auth()->user()->role;

            $roleId = DB::table('roles_master')
                ->where('slug', $roleSlug)
                ->value('id');

            $isAdmin = in_array($roleId, [1, 2, 3, 4]);
            $perPage = (int) $request->query('per_page', 20);
            $allowedPerPage = [20, 50, 100];

            if (!in_array($perPage, $allowedPerPage, true)) {
                $perPage = 20;
            }

        $search = trim((string) $request->query('search', ''));
        $employeeFilter = $request->query('employee_id');
        $roleFilter = trim((string) $request->query('role', ''));
        $departmentFilter = trim((string) $request->query('department', ''));
        $statusFilter = trim((string) $request->query('status', ''));
        $pfFilter = trim((string) $request->query('pf', ''));
        $esiFilter = trim((string) $request->query('esi', ''));
        $insuranceFilter = trim((string) $request->query('insurance', ''));

        $baseQuery = Employee::query();

        if (!$isAdmin) {
            $baseQuery->where('id', auth()->user()->employee_id);
        }

        $employeeFilterOptions = (clone $baseQuery)
            ->orderBy('name')
            ->get();

        $employees = $baseQuery
            ->with('user')
            ->leftJoin('users', 'users.employee_id', '=', 'employees.id')
            ->select('employees.*')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('employees.name', 'like', "%{$search}%")
                        ->orWhere('employees.employee_code', 'like', "%{$search}%")
                        ->orWhere('employees.department', 'like', "%{$search}%")
                        ->orWhere('employees.role', 'like', "%{$search}%");
                });
            })
            ->when($employeeFilter, function ($query) use ($employeeFilter) {
                $query->where('employees.id', $employeeFilter);
            })
            ->when($roleFilter !== '', function ($query) use ($roleFilter) {
                $normalizedRole = strtolower(str_replace([' ', '-'], '_', trim($roleFilter)));

                $query->whereRaw(
                    "LOWER(REPLACE(REPLACE(employees.role, ' ', '_'), '-', '_')) = ?",
                    [$normalizedRole]
                );
            })
            ->when($departmentFilter !== '', function ($query) use ($departmentFilter) {
                $normalizedDepartment = strtolower(str_replace('_', ' ', trim($departmentFilter)));

                $query->where(function ($subQuery) use ($departmentFilter, $normalizedDepartment) {
                    $subQuery->whereRaw('LOWER(TRIM(employees.department)) = LOWER(TRIM(?))', [$departmentFilter])
                        ->orWhereRaw(
                            "LOWER(REPLACE(employees.department, '_', ' ')) = ?",
                            [$normalizedDepartment]
                        );
                });
            })
            ->when($statusFilter === 'active', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('users.account_status')
                        ->orWhere('users.account_status', 'active');
                });
            })
            ->when($statusFilter === 'inactive', function ($query) {
                $query->where('users.account_status', 'inactive');
            })
            // No explicit status filter and no search term: hide inactive accounts by default.
            // A search term is allowed to surface inactive matches too.
            ->when($statusFilter === '' && $search === '', function ($query) {
                $query->where(function ($subQuery) {
                    $subQuery->whereNull('users.account_status')
                        ->orWhere('users.account_status', 'active');
                });
            })
            ->when($pfFilter === 'yes', fn ($query) => $query->where('employees.pf', true))
            ->when($pfFilter === 'no', fn ($query) => $query->where('employees.pf', false))
            ->when($esiFilter === 'yes', fn ($query) => $query->where('employees.esi', true))
            ->when($esiFilter === 'no', fn ($query) => $query->where('employees.esi', false))
            ->when($insuranceFilter === 'yes', fn ($query) => $query->where('employees.insurance', true))
            ->when($insuranceFilter === 'no', fn ($query) => $query->where('employees.insurance', false))
            ->orderByRaw("CASE WHEN COALESCE(users.account_status, 'active') = 'inactive' THEN 1 ELSE 0 END")
            ->orderBy('employees.name')
            ->paginate($perPage)
            ->appends($request->query());

        return view('employees.index', compact(
            'employees',
            'perPage',
            'employeeFilterOptions',
            'search',
            'employeeFilter',
            'roleFilter',
            'departmentFilter',
            'statusFilter',
            'pfFilter',
            'esiFilter',
            'insuranceFilter'
        ));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->orderBy('name')->get();
        $roles = Role::where('is_active', true)->orderBy('name')->get();
        return view('employees.create', compact('departments', 'designations', 'roles'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'employee_code' => 'required|string|max:50|unique:employees,employee_code',
                'name' => 'required|string|max:255',
                'email' => 'nullable|email|unique:users,email',
                'mobile_number' => 'required|string|max:20',
                'department' => 'required|string',
                'designation' => 'required|string',
                'role' => 'required|string',
                'bank_name' => 'required|string|max:255',
                'account_number' => 'required|string|max:50',
                'ifsc_code' => 'required|string|max:20',
                'basic_salary' => 'required|numeric|min:0',
                'working_mode' => 'required|in:Office,Work from home',
            ]);

            return DB::transaction(function () use ($request) {
                $data = $request->all();

                // Password handling: store plain in employees table for admin viewing, hash for users table
                $rawPassword = $request->filled('password') ? $request->password : '12345678';
                $data['password'] = Hash::make($rawPassword);

                // Defaults
                $data['gender'] = $request->gender ?? 'male';
                $data['time_in'] = $request->time_in ?? '09:00';
                $data['time_out'] = $request->time_out ?? '19:00';
                $data['leave'] = $request->leave ?? 0;

                // Toggles
                $data['pf'] = $request->has('pf');
                $data['esi'] = $request->has('esi');
                $data['insurance'] = $request->has('insurance');

                // Salary Defaults
                $data['hra'] = $request->hra ?? 0;
                $data['conveyance_allowance'] = $request->conveyance_allowance ?? 0;
                $data['medical_allowance'] = $request->medical_allowance ?? 0;
                $data['other_allowance'] = $request->other_allowance ?? 0;

                // Handle Photo upload
                if ($request->hasFile('photo')) {
                    $data['photo'] = $request->file('photo')->store('employees', 'public');
                }

                // Create employee
                $employee = Employee::create($data);

                // Link back to the recruitment pipeline if this employee was created from a hired application
                if ($request->filled('from_job_application_id')) {
                    JobApplication::where('id', $request->from_job_application_id)
                        ->update(['hired_employee_id' => $employee->id]);
                }

                // Create User for login if email is present
                if ($request->filled('email')) {
                    User::create([
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'password' => $rawPassword,
                        'role' => $employee->role,
                        'employee_id' => $employee->id,
                    ]);
                }

                return redirect()->route('employees.index')
                    ->with('success', 'Employee added and User account created successfully! ✓');
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employee = Employee::findOrFail($id);
        return view('employees.show', compact('employee'));
    }

    /**
     * Get employee as JSON for modal display
     */
    public function getJson($id)
    {
        $employee = Employee::findOrFail($id);
        return response()->json($employee);
    }

    /**
     * Get employee attendance for modal tab
     */
    // public function getAttendance(Request $request, $id)
    // {
    //     $employee = Employee::findOrFail($id);

    //     // Security Check: Only Admin or the Employee themselves can see this data
    //     $roleSlug = auth()->user()->role; // e.g. "manager"

    //     $roleId = DB::table('roles_master')
    //         ->where('slug', $roleSlug)
    //         ->value('id');

    //     $isAdmin = in_array($roleId, [1, 2, 3, 4]);
    //     if ($isAdmin) {
    //         return response()->json(['success' => false, 'message' => 'Unauthorized Access'], 403);
    //     }
        
    //     // Determine selected month and year
    //     if ($request->filled('month')) {
    //         $parts = explode('-', $request->month);
    //         $year = $parts[0];
    //         $month = $parts[1];
    //     } else {
    //         $year = date('Y');
    //         $month = date('m');
    //     }

    //     $startDate = \Carbon\Carbon::create($year, $month, 1);
    //     $endDate = $startDate->copy()->endOfMonth();
        
    //     // If current month, only show up to today
    //     if ($year == date('Y') && $month == date('m')) {
    //         $endDate = \Carbon\Carbon::today();
    //     }
        
    //     // Fetch data once to avoid queries in loop
    //     $attendances = \App\Models\Attendance::where('employee_id', $id)
    //         ->whereBetween('attendance_date', [$startDate, $endDate])
    //         ->get()
    //         ->keyBy(function($item) { return $item->attendance_date->format('Y-m-d'); });

    //     $holidays = \App\Models\Holiday::whereBetween('date', [$startDate, $endDate])
    //         ->get()
    //         ->keyBy(function($item) { return \Carbon\Carbon::parse($item->date)->format('Y-m-d'); });

    //     $leaves = \App\Models\LeaveApplication::where('employee_id', $id)
    //         ->where('status', 'approved')
    //         ->where(function($q) use ($startDate, $endDate) {
    //             $q->whereBetween('start_date', [$startDate, $endDate])
    //               ->orWhereBetween('end_date', [$startDate, $endDate]);
    //         })
    //         ->get();

    //     $history = [];
    //     for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
    //         $dayStr = $date->format('Y-m-d');
    //         $record = $attendances->get($dayStr);
    //         $holiday = $holidays->get($dayStr);
            
    //         // Check if date falls within any approved leave
    //         $onLeave = $leaves->first(function($l) use ($date) {
    //             return $date->between($l->start_date, $l->end_date ?? $l->start_date);
    //         });

    //         $status = 'Absent';
    //         $statusClass = 'bg-soft-danger text-danger';
    //         $punch_in = '--:--';
    //         $punch_out = '--:--';
    //         $total_hours_val = 0;
    //         $total_hours_str = '0.0 hrs';

    //         if ($record) {
    //             $status = $record->status;
    //             $punch_in = $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('h:i A') : '--:--';
    //             $punch_out = $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('h:i A') : '--:--';
    //             $total_hours_val = (float) $record->total_hours;
    //             $total_hours_str = number_format($total_hours_val, 1) . ' hrs';
                
    //             // Smarter status detection
    //             if ($total_hours_val > 0 && $total_hours_val < 5) {
    //                 $status = 'Half Day';
    //             } elseif ($record->check_in && !$record->check_out) {
    //                 $status = 'Present (Single Punch)';
    //             } elseif ($record->check_in && $record->check_out) {
    //                 $status = 'Present';
    //             }
    //         } elseif ($holiday) {
    //             $status = 'Holiday (' . $holiday->title . ')';
    //             $statusClass = 'bg-soft-primary text-primary';
    //         } elseif ($onLeave) {
    //             $status = 'Leave (' . $onLeave->leave_type . ')';
    //             $statusClass = 'bg-soft-info text-info';
    //         } elseif ($date->isSunday()) {
    //             $status = 'Sunday (Weekly Off)';
    //             $statusClass = 'bg-soft-secondary text-secondary';
    //         }

    //         if ($record) {
    //             $statusClass = [
    //                 'present' => 'bg-soft-success text-success',
    //                 'present (single punch)' => 'bg-soft-success text-success',
    //                 'absent' => 'bg-soft-danger text-danger',
    //                 'leave' => 'bg-soft-info text-info',
    //                 'half day' => 'bg-soft-warning text-warning',
    //                 'half_day' => 'bg-soft-warning text-warning',
    //                 'late' => 'bg-soft-warning text-warning',
    //                 'wfh' => 'bg-soft-success text-success'
    //             ][strtolower($status)] ?? 'bg-light';
    //         }

    //         $history[] = [
    //             'date' => $date->format('d M, Y (D)'),
    //             'status' => $status,
    //             'statusClass' => $statusClass,
    //             'punch_in' => $punch_in,
    //             'punch_out' => $punch_out,
    //             'total_hours' => $total_hours_str
    //         ];
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'employee_name' => $employee->name,
    //         'history' => array_reverse($history) // Latest first
    //     ]);
    // }

    public function getAttendance(Request $request, $id = null)
    {
        $user = auth()->user();
        $employeeId = $id ?? $user->employee_id;
        // dd($employeeId);
        if (!$employeeId) {
            abort(403, 'No employee linked');
        }

        $employee = Employee::findOrFail($employeeId);

        // FIXED AUTH LOGIC
        $roleSlug = $user->role;
        $roleId = DB::table('roles_master')
            ->where('slug', $roleSlug)
            ->value('id');

        $isAdmin = in_array($roleId, [1, 2, 3, 4 ,5]);

        if (!$isAdmin && $user->employee_id != $employeeId) {
            abort(403, 'Unauthorized Access');
        }

        // Month filter
        if ($request->filled('month')) {
            [$year, $month] = explode('-', $request->month);
        } else {
            $year = date('Y');
            $month = date('m');
        }

        $startDate = \Carbon\Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        if ($year == date('Y') && $month == date('m')) {
            $endDate = \Carbon\Carbon::today();
        }

        $history = app(AttendanceHistoryService::class)->buildMonthlyHistory(
            (int) $employeeId,
            $startDate,
            $endDate
        );

        $summary = app(AttendanceHistoryService::class)->buildMonthlySummary($history);

        return view('payroll.attendance-history', [
            'employee' => $employee,
            'history' => $history,
            'summary' => $summary,
            'selectedMonth' => "$year-$month"
        ]);
    }

    public function employeeDays() {
        $employees = Employee::active()->get();
        return view('employees.employeeDay', compact('employees'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $employee = Employee::findOrFail($id);
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $designations = Designation::where('is_active', true)->orderBy('name')->get();
        $roles = Role::where('is_active', true)->orderBy('name')->get();
        return view('employees.edit', compact('employee', 'departments', 'designations', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $employee = Employee::findOrFail($id);
            // Find existing user by employee_id or email
            $user = User::where('employee_id', $employee->id)
                ->orWhere('email', $employee->email)
                ->first();
            $userId = $user ? $user->id : 'NULL';

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'mobile_number' => 'required|string|max:20',
                'department' => 'required|string',
                'designation' => 'required|string',
                'role' => 'required|string',
                'email' => 'nullable|email|unique:users,email,' . $userId,
                'employee_code' => 'nullable|string|max:50|unique:employees,employee_code,' . $employee->id,
                'working_mode' => 'required|in:Office,Work from home',
            ]);

            return DB::transaction(function () use ($request, $employee, $user) {
                $updateData = [
                    'name' => $request->name,
                    'email' => $request->email,
                    'mobile_number' => $request->mobile_number,
                    'role' => $request->role,
                    'department' => $request->department,
                    'designation' => $request->designation,
                    'date_of_joining' => $request->date_of_joining,
                    'date_of_birth' => $request->date_of_birth,
                    'gender' => $request->gender ?? 'male',
                    'employee_code' => $request->employee_code,
                    'aadhaar_number' => $request->aadhaar_number,
                    'pan_number' => $request->pan_number,
                    'address' => $request->address,
                    'time_in' => $request->time_in ?? '09:00',
                    'time_out' => $request->time_out ?? '19:00',
                    'leave' => $request->leave ?? 0,
                    'pf' => $request->has('pf'),
                    'pf_number' => $request->pf_number,
                    'esi' => $request->has('esi'),
                    'esi_number' => $request->esi_number,
                    'insurance' => $request->has('insurance'),
                    'insurance_provider' => $request->insurance_provider,
                    'insurance_policy_number' => $request->insurance_policy_number,
                    'bank_name' => $request->bank_name,
                    'account_number' => $request->account_number,
                    'ifsc_code' => $request->ifsc_code,
                    'basic_salary' => $request->basic_salary ?? 0,
                    'hra' => $request->hra ?? 0,
                    'conveyance_allowance' => $request->conveyance_allowance ?? 0,
                    'medical_allowance' => $request->medical_allowance ?? 0,
                    'other_allowance' => $request->other_allowance ?? 0,
                    'working_mode' => $request->working_mode,
                ];

                // Only update password if provided
                if ($request->filled('password')) {
                    $updateData['password'] = $request->password;
                    
                    if ($user) {
                        $user->update(['password' => $request->password]);
                    }
                }

                $employee->update($updateData);

                // Handle photo upload
                if ($request->hasFile('photo')) {
                    $request->validate([
                        'photo' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                    ]);

                    $file = $request->file('photo');
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $path = $file->storeAs('employees', $filename, 'public');

                    if ($employee->photo && \Storage::disk('public')->exists($employee->photo)) {
                        \Storage::disk('public')->delete($employee->photo);
                    }

                    $employee->update(['photo' => $path]);
                }

                // Sync with User table
                if ($request->filled('email')) {
                    $user = User::where('employee_id', $employee->id)->orWhere('email', $employee->email)->first();

                    $userData = [
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'role' => $employee->role,
                        'employee_id' => $employee->id,
                    ];

                    if ($request->filled('password')) {
                        $userData['password'] = Hash::make($request->password);
                    }

                    if ($user) {
                        $user->update($userData);
                    } else {
                        User::create($userData + ['password' => Hash::make($request->password ?? '12345678')]);
                    }
                }

                return redirect()->route('employees.index')
                    ->with('success', 'Employee and User account updated successfully! ✓');
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function updateAccountStatus(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|in:active,inactive',
            'last_working_day' => 'nullable|date',
        ]);

        $user = User::findOrFail($request->user_id);

        if ($request->status === 'inactive') {
            $user->update([
                'account_status' => 'inactive',
                'last_working_day' => $request->filled('last_working_day')
                    ? $request->last_working_day
                    : now()->toDateString(),
            ]);
        } else {
            $user->update([
                'account_status' => 'active',
                'last_working_day' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        return redirect()->route('employees.index')
            ->with('success', 'Employee deleted successfully! ✓');
    }

    public function export()
    {
        $employees = Employee::orderBy('name', 'asc')->get();
        $filename = "employees_" . date('Y-m-d_H-i-s') . ".xlsx";
        return Excel::download(new EmployeesExport($employees), $filename);
    }
}

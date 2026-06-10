<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyTaskController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\LeaveApplicationController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\VacancyController;
use App\Http\Controllers\ZKTController;

$adminRoles = 'super_admin,manager,hr_executive,hr_intern,business_operation_head';
$TeamLeaderRoles = 'super_admin,manager,hr_executive,hr_intern,business_operation_head,team_leader';

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/sync-attendance', [ZKTController::class, 'syncAttendance'])
    ->middleware(['auth', 'role.access:super_admin,manager,hr_executive,hr_intern,business_operation_head'])
    ->name('sync.attendance');

Route::get('/payroll/attendance', [PayrollController::class, 'attendance'])
            ->middleware(['auth', 'role.access:super_admin,manager,hr_executive,hr_intern,business_operation_head'])
            ->name('payroll.attendance');


Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/payroll/employee', [PayrollController::class, 'payrollEmployee'])->name('payroll.empindex');
    Route::get('/payroll/export', [PayrollController::class, 'export'])->name('payroll.export');
    Route::post('/payroll/{id}/remarks', [PayrollController::class, 'saveRemarks']);
    Route::get('/payroll/{id}', [PayrollController::class, 'show'])->name('payroll.show');
});



Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard/summary', [DashboardController::class, 'getMonthlySummary'])->name('dashboard.summary');
    Route::get('/dashboard/chart', [DashboardController::class, 'getChartData'])->name('dashboard.chart');

    Route::get('/holidays', [HolidayController::class, 'index'])->name('holidays.index');

    Route::get('/daily-tasks', [DailyTaskController::class, 'index'])->name('daily-tasks.index');
    Route::post('/daily-tasks', [DailyTaskController::class, 'store'])->name('daily-tasks.store');
    Route::put('/daily-tasks/{dailyTask}', [DailyTaskController::class, 'update'])->name('daily-tasks.update');
    Route::patch('/daily-tasks/{dailyTask}/status', [DailyTaskController::class, 'updateStatus'])->name('daily-tasks.update-status');
    Route::patch('/daily-tasks/{dailyTask}/priority', [DailyTaskController::class, 'updatePriority'])->name('daily-tasks.update-priority');
    Route::delete('/daily-tasks/{dailyTask}', [DailyTaskController::class, 'destroy'])->name('daily-tasks.destroy');
    Route::post('/daily-tasks/bulk-delete', [DailyTaskController::class, 'bulkDestroy'])->name('daily-tasks.bulk-delete');
    Route::post('/daily-tasks/follow-up', [DailyTaskController::class, 'storeFollowUp'])->name('daily-tasks.follow-up.store');
    Route::put('/daily-tasks/follow-up/{id}', [DailyTaskController::class, 'updateFollowUp'])->name('daily-tasks.follow-up.update');
    Route::delete('/daily-tasks/follow-up/{id}', [DailyTaskController::class, 'destroyFollowUp'])->name('daily-tasks.follow-up.destroy');
    Route::get('/daily-tasks/{taskId}/follow-ups', [DailyTaskController::class, 'getFollowUps']);
    Route::get('/daily-task-history/{task}', [DailyTaskController::class, 'statusHistory']);

    Route::get('/employee-review', [ReviewController::class, 'index'])->name('employee.review');
    Route::post('/employee-review/store', [ReviewController::class, 'store']);
    Route::post('/employee-review/{id}/update', [ReviewController::class, 'update']);
    Route::get('/review-details/{id}', [ReviewController::class, 'details'])->name('employee.review.details');
    Route::get('/technical-review', [ReviewController::class, 'technicalReview'])->name('technical.review');
    Route::post('/technical-review/store', [ReviewController::class, 'technicalReviewStore']);
    Route::post('/technical-review/{id}/update', [ReviewController::class, 'technicalReviewUpdate']);
    Route::get('/technical-review-details/{id}', [ReviewController::class, 'technicalReviewDetails']);
    Route::post('/technical-review-evaluation/store', [ReviewController::class, 'storeTechnicalEvaluation']);
    Route::get('/technical-review-evaluation/fetch', [ReviewController::class, 'fetchByDepartment']);
    
    Route::get('/celebrations', [EmployeeController::class, 'employeeDays'])->name('employees.employeeDays');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::get('/help', fn () => view('pages.help'))->name('help');
    Route::get('/terms', fn () => view('pages.terms'))->name('terms');
    Route::get('/privacy', fn () => view('pages.privacy'))->name('privacy');
});

Route::middleware(['auth', "role.access:$adminRoles"])->group(function () {
    
    Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employees/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employees/{id}', [EmployeeController::class, 'show'])->name('employees.show');
    Route::get('/employees/{id}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::put('/employees/{id}', [EmployeeController::class, 'update'])->name('employees.update');
    Route::delete('/employees/{id}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::get('/employees-export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::post('/employee/update-accountStatus', [EmployeeController::class, 'updateAccountStatus'])->name('users.update-accountStatus');

    Route::get('/broadcasts/{id}/edit', [BroadcastController::class, 'edit'])->name('broadcasts.edit');
    Route::put('/broadcasts/{id}', [BroadcastController::class, 'update'])->name('broadcasts.update');

    Route::get('/broadcasts/{id}/recipients', [BroadcastController::class, 'getRecipients']);


    Route::get('/api/employees/{id}', [EmployeeController::class, 'getJson']);
    Route::get('/api/employees/{id}/attendance', [EmployeeController::class, 'getAttendance']);

    Route::get('/master/departments', [MasterController::class, 'departments'])->name('master.departments');
    Route::get('/master/designations', [MasterController::class, 'designations'])->name('master.designations');
    Route::get('/master/roles', [MasterController::class, 'roles'])->name('master.roles');

    Route::post('/master/department', [MasterController::class, 'storeDepartment'])->name('master.department.store');
    Route::put('/master/department/{id}', [MasterController::class, 'updateDepartment'])->name('master.department.update');
    Route::delete('/master/department/{id}', [MasterController::class, 'destroyDepartment'])->name('master.department.destroy');

    Route::post('/master/designation', [MasterController::class, 'storeDesignation'])->name('master.designation.store');
    Route::put('/master/designation/{id}', [MasterController::class, 'updateDesignation'])->name('master.designation.update');
    Route::delete('/master/designation/{id}', [MasterController::class, 'destroyDesignation'])->name('master.designation.destroy');

    Route::post('/master/role', [MasterController::class, 'storeRole'])->name('master.role.store');
    Route::put('/master/role/{id}', [MasterController::class, 'updateRole'])->name('master.role.update');
    Route::delete('/master/role/{id}', [MasterController::class, 'destroyRole'])->name('master.role.destroy');

    Route::post('/holidays', [HolidayController::class, 'store'])->name('holidays.store');
    Route::get('/holidays/{id}/edit', [HolidayController::class, 'edit'])->name('holidays.edit');
    Route::put('/holidays/{id}', [HolidayController::class, 'update'])->name('holidays.update');
    Route::delete('/holidays/{id}', [HolidayController::class, 'destroy'])->name('holidays.destroy');

    Route::get('/leave/allotment', [LeaveController::class, 'allotment'])->name('leave.allotment');
    Route::post('/leave/allotment', [LeaveController::class, 'storeAllotment'])->name('leave.storeAllotment');
    Route::get('/leave/balance', [LeaveController::class, 'allotment'])->name('leave.balance');
    Route::get('/leave/balance/export', [LeaveController::class, 'exportBalances'])->name('leave.balance.export');
    Route::get('/api/leave/balance', [LeaveController::class, 'apiBalanceList']);

    Route::get('/leave/history', [LeaveApplicationController::class, 'index'])->name('leave.history');
    Route::get('/leave/export', [LeaveApplicationController::class, 'export'])->name('leave.export');
    Route::post('/leave/action', [LeaveApplicationController::class, 'updateAction'])->name('leave.updateAction');
    Route::delete('/leave/application/{id}', [LeaveApplicationController::class, 'destroy'])->name('leave.application.destroy');
    Route::get('/api/leave/details/{id}', [LeaveApplicationController::class, 'getDetails']);
    Route::get('/api/leave/employee/{employeeId}', [LeaveApplicationController::class, 'getEmployeeLeaves']);

   
    Route::get('/payroll/attendance/get', [PayrollController::class, 'getAttendance'])->name('payroll.attendance.get');
    Route::get('/payroll/attendance/add', [PayrollController::class, 'addAttendance'])->name('payroll.attendance.add');
    Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
    Route::get('/payroll/get', [PayrollController::class, 'getPayroll'])->name('payroll.get');
    Route::get('/payroll/calculation', [PayrollController::class, 'calculation'])->name('payroll.calculation');
    Route::post('/payroll/calculate', [PayrollController::class, 'calculatePayroll'])->name('payroll.calculate');
    Route::post('/payroll/sendDateRange', [PayrollController::class, 'calculateInRage'])->name('payroll.sendDateRange');
    Route::post('/payroll/store', [PayrollController::class, 'storePayroll'])->name('payroll.store');
    Route::get('/payroll/{id}/edit', [PayrollController::class, 'editPayroll'])->name('payroll.edit');
    Route::put('/payroll/{id}', [PayrollController::class, 'updatePayroll'])->name('payroll.update');
  
    Route::post('/payroll/{id}/status', [PayrollController::class, 'updateStatus'])->name('payroll.status');
    Route::delete('/payroll/{id}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
   
    Route::post('/payroll/{id}/mark-read', [PayrollController::class, 'markAsRead']);
  
    Route::get('/payroll/attendance/{id}/edit', [PayrollController::class, 'edit'])->name('payroll.attendance.edit');
    Route::get('/payroll/attendance/date/{attendance_date}/edit', [PayrollController::class, 'editByDate'])->name('payroll.attendance.editByDate');
    Route::put('/payroll/attendance/date/{attendance_date}', [PayrollController::class, 'updateByDate'])->name('payroll.attendance.updateByDate');
    Route::post('/payroll/attendance', [PayrollController::class, 'storeAttendance'])->name('payroll.attendance.store');
    Route::get('/payroll/attendance/export', [PayrollController::class, 'exportAttendance'])->name('payroll.attendance.export');
    Route::post('/payroll/attendance/import', [PayrollController::class, 'import'])->name('payroll.attendance.import');
    Route::get('/payroll/attendance/details', [PayrollController::class, 'getAttendanceDetails'])->name('payroll.attendance.details');
    Route::delete('/payroll/attendance/{id}', [PayrollController::class, 'destroyAttendance'])->name('payroll.attendance.destroy');
    Route::delete('/payroll/attendance/date/{date}', [PayrollController::class, 'destroyAttendanceByDate'])->name('payroll.attendance.destroyByDate');
    Route::post('/payroll/attendance/delete-bulk', [PayrollController::class, 'bulkDestroyAttendance'])->name('payroll.attendance.bulkDestroy');
    Route::get('/payroll/attendance/employee', [PayrollController::class, 'employeeWiseAttendace'])->name('payroll.attendace.employee');
    Route::get('/payroll/attendance/employee-wise-details', [PayrollController::class, 'employeeWiseDetails'])->name('payroll.attendance.employee.details');
    Route::get('/payroll/attendace/employee/{employee_id}/edit', [PayrollController::class, 'editByName'])->name('payroll.attendance.employee.editByName');
    Route::put('/payroll/attendance/employee/{employee_id}/update', [PayrollController::class, 'updateByName'])->name('payroll.attendance.employee.updateByName');
});

Route::middleware(['auth', "role.access:$TeamLeaderRoles"])->group(function () {

    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects/check-name', [ProjectController::class, 'checkName'])->name('projects.check-name');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::patch('/projects/{project}/update-field', [ProjectController::class, 'updateField'])->name('projects.update-field');
    Route::post('/projects/bulk-delete', [ProjectController::class, 'bulkDelete'])->name('projects.bulk-delete');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::get('/projects/{project}/tasks-summary', [ProjectController::class, 'tasksSummary'])->name('projects.tasks-summary');

    Route::get('/job-vacancy', [VacancyController::class, 'show'])->name('vacancy.show');
    Route::post('/job-vacancy/store', [VacancyController::class, 'store'])->name('job.store');
    Route::post('/job-applications/update-status/{id}', [VacancyController::class, 'updateStatus']);
    Route::get('/job-requirement', [VacancyController::class, 'showRequirements'])->name('requirement.show');
    Route::post('/job-requirement/store', [VacancyController::class, 'storeRequirement'])->name('requirement.store');
    Route::post('/job-requirement/update-status', [VacancyController::class, 'updateStatusofRequirement'])->name('requirements.update-status');

    Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcasts.index');
  
   
});

Route::post('/broadcasts/{id}/read', [BroadcastController::class, 'markAsRead'])
    ->middleware('auth')
    ->name('broadcasts.read');

Route::middleware(['auth'])->group(function () {
    Route::post('/broadcasts', [BroadcastController::class, 'store'])->name('broadcasts.store');
    // Route::post('/broadcasts/{id}/read', [BroadcastController::class, 'markAsRead']);
    Route::get('/attendance-history', [EmployeeController::class, 'getAttendance'])->name('attendance-history');
    Route::post('/leave/apply', [LeaveApplicationController::class, 'store'])->name('leave.apply');

    Route::get('/profile/details', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/profile/leave-balance', [ProfileController::class, 'leaveBalance'])->name('profile.leave-balance');
    Route::get('/profile/leave-history', [ProfileController::class, 'leaveHistory'])->name('profile.leave-history');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
});

require __DIR__ . '/auth.php';
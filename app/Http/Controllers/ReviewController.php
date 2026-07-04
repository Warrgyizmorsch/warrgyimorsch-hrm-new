<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\DailyTask;
use App\Models\EmployeeReviewDetail;
use App\Models\EmployeeReview;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\TaskFollowUp;
use App\Models\TechnicalReview;
use App\Models\TechnicalReviewDetail;
use App\Models\TechnicalReviewEvaluation;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    protected function resolveRoleFlags($user): array
    {
        $role = str_replace(' ', '_', strtolower(trim((string) ($user->role ?? 'employee'))));
        $roleId = DB::table('roles_master')
            ->where('slug', $role)
            ->orWhereRaw('LOWER(REPLACE(name, " ", "_")) = ?', [$role])
            ->value('id');

        return [
            'isAdmin' => in_array($role, ['super_admin', 'manager'], true)
                || in_array((int) $roleId, [1, 2], true),
            'isTeamLeader' => $role === 'team_leader' || (int) $roleId === 5,
        ];
    }

    protected function canManageEmployeeReviews($user): bool
    {
        ['isAdmin' => $isAdmin] = $this->resolveRoleFlags($user);

        return $isAdmin;
    }

    protected function canAccessReviewEmployee($user, ?Employee $targetEmployee): bool
    {
        if (!$targetEmployee) {
            return false;
        }

        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader] = $this->resolveRoleFlags($user);

        if ($isAdmin) {
            return true;
        }

        $employeeRecord = $this->resolveEmployeeRecord($user);

        if ($isTeamLeader) {
            return $employeeRecord
                && $employeeRecord->department
                && $targetEmployee->department === $employeeRecord->department;
        }

        return (int) $targetEmployee->id === (int) ($employeeRecord?->id ?? 0);
    }

    protected function resolveEmployeeRecord($user): ?Employee
    {
        if (!$user) {
            return null;
        }

        if ($user->relationLoaded('employee') && $user->employee) {
            return $user->employee;
        }

        if (!empty($user->employee_id)) {
            $employee = Employee::active()->find($user->employee_id);
            if ($employee) {
                return $employee;
            }
        }

        if (!empty($user->email)) {
            $employee = Employee::active()->where('email', $user->email)->first();
            if ($employee) {
                return $employee;
            }
        }

        return Employee::active()->find($user->id);
    }

    public function index(Request $request) {
        $user = auth()->user();
        $canViewReviewAnalytics = $this->canManageEmployeeReviews($user);
        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader] = $this->resolveRoleFlags($user);

        $employeeRecord = $this->resolveEmployeeRecord($user);
        
        $query = EmployeeReview::with(['employee', 'details']);
        
        if ($isTeamLeader && !$isAdmin) {
            $department = $employeeRecord->department ?? null;
            $employeeIds = $department
                ? Employee::active()->where('department', $department)->pluck('id')
                : collect([$employeeRecord?->id ?? 0]);

            $query->whereIn('employee_id', $employeeIds);
        } elseif (!$canViewReviewAnalytics) {
            $empId = $employeeRecord ? $employeeRecord->id : 0;
            $query->where('employee_id', $empId);
        }
        
        if ($isAdmin) {
            $employees = Employee::active()->orderBy('name')->get();
        } elseif ($isTeamLeader && $employeeRecord?->department) {
            $employees = Employee::active()
                ->where('department', $employeeRecord->department)
                ->orderBy('name')
                ->get();
        } else {
            $employees = Employee::active()
                ->where('id', $employeeRecord?->id ?? 0)
                ->orderBy('name')
                ->get();
        }
        
        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }
        $reviewMonths = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];
        $monthOrder = array_flip($reviewMonths);
        $selectedReviewMonth = $request->query('month_filter', now()->format('F'));
        $showAllReviewMonths = $selectedReviewMonth === 'all';

        if (!$showAllReviewMonths && !in_array($selectedReviewMonth, $reviewMonths, true)) {
            $selectedReviewMonth = now()->format('F');
        }

        if (!$showAllReviewMonths) {
            $query->where('month', $selectedReviewMonth);
        }

        $groupedReviews = $query->get()
            ->groupBy(fn ($review) => $review->employee_id . '|' . $review->month)
            ->map(function ($group) {
                $selfAssessmentTotal = function ($review): float {
                    if (!$review) {
                        return 0;
                    }

                    return (float) $review->self_total;
                };

                $firstHalf = $group->first(fn ($review) => strcasecmp($review->period, 'First Half') === 0);
                $secondHalf = $group->first(fn ($review) => strcasecmp($review->period, 'Second Half') === 0);
                $firstReview = $group->first();

                return (object) [
                    'employee' => $firstReview->employee,
                    'employee_name' => $firstReview->employee->name ?? 'N/A',
                    'month' => $firstReview->month,
                    'firstHalf' => $firstHalf,
                    'secondHalf' => $secondHalf,
                    'combined_total' => $selfAssessmentTotal($firstHalf) + $selfAssessmentTotal($secondHalf),
                ];
            })
            ->map(function ($group) {
                $group->objective = $this->buildObjectiveReviewResult(
                    (int) ($group->employee->id ?? 0),
                    $group->month,
                    (float) $group->combined_total
                );

                return $group;
            });

        $rankedReviews = $groupedReviews
            ->sortByDesc(fn ($group) => $group->objective['score'])
            ->values()
            ->each(fn ($group, $index) => $group->objective['rank'] = $index + 1);

        $groupedReviews = $canViewReviewAnalytics
            ? $rankedReviews->values()
            : $rankedReviews
                ->sortBy([
                    fn ($a, $b) => strcasecmp($a->employee_name, $b->employee_name),
                    fn ($a, $b) => ($monthOrder[$a->month] ?? 99) <=> ($monthOrder[$b->month] ?? 99),
                ])
                ->values();

        $page = LengthAwarePaginator::resolveCurrentPage();
        $reviews = new LengthAwarePaginator(
            $groupedReviews->forPage($page, $perPage)->values(),
            $groupedReviews->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $modalReviews = $reviews->getCollection()
            ->flatMap(fn ($reviewGroup) => collect([$reviewGroup->firstHalf, $reviewGroup->secondHalf])->filter())
            ->values();
            

        return view('review.review', compact(
            'reviews',
            'modalReviews',
            'isAdmin',
            'isTeamLeader',
            'employees',
            'perPage',
            'reviewMonths',
            'selectedReviewMonth',
            'showAllReviewMonths',
            'canViewReviewAnalytics',
            'employeeRecord'
        ));
    }

    private function buildObjectiveReviewResult(int $employeeId, string $month, float $reviewTotal): array
    {
        if (!$employeeId) {
            return [
                'score' => max(0, min(100, round($reviewTotal, 1))),
                'rank' => null,
                'late_days' => 0,
                'late_minutes' => 0,
                'missed_reports' => 0,
                'report_days' => 0,
                'completed_tasks' => 0,
                'completed_report_days' => 0,
                'pending_tasks' => 0,
                'technical_score' => 0,
                'technical_bonus' => 0,
                'leave_days' => 0,
                'leave_penalty' => 0,
                'late_penalty' => 0,
                'report_penalty' => 0,
                'pending_penalty' => 0,
                'task_bonus' => 0,
                'no_leave_bonus' => 0,
                'penalty' => 0,
                'bonus' => 0,
            ];
        }

        [$startDate, $endDate] = $this->getReviewMonthDateRange($month);

        $attendanceRecords = Attendance::with('employee')
            ->where('employee_id', $employeeId)
            ->whereBetween('attendance_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $leaveApplications = LeaveApplication::where('employee_id', $employeeId)
            ->whereIn('status', ['approved', 'unpaid', 'unauthorised'])
            ->where('leave_category', 'NOT LIKE', '%WFH%')
            ->whereDate('start_date', '<=', $endDate->toDateString())
            ->where(function ($query) use ($startDate) {
                $query->whereDate('end_date', '>=', $startDate->toDateString())
                    ->orWhereNull('end_date');
            })
            ->get();

        $leaveDateMap = [];
        foreach ($leaveApplications as $leave) {
            $leaveStart = Carbon::parse($leave->start_date)->greaterThan($startDate)
                ? Carbon::parse($leave->start_date)
                : $startDate->copy();
            $leaveEnd = $leave->end_date
                ? Carbon::parse($leave->end_date)
                : Carbon::parse($leave->start_date);
            $leaveEnd = $leaveEnd->lessThan($endDate) ? $leaveEnd : $endDate->copy();

            for ($date = $leaveStart->copy(); $date->lte($leaveEnd); $date->addDay()) {
                $leaveDateMap[$date->toDateString()] = true;
            }
        }

        $activityDates = $this->getAttendanceActivityDates($attendanceRecords);
        $lateMinutes = 0;
        $lateDays = 0;
        $reportDates = [];
        $attendanceByDate = $attendanceRecords->keyBy(fn ($attendance) => Carbon::parse($attendance->attendance_date)->toDateString());
        $nonReportingStatuses = [
            'absent',
            'leave',
            'unpaid_leave',
            'unauthorised',
            'missing_punch',
            'holiday',
            'week_off',
            'weekly_off',
        ];

        foreach ($attendanceRecords as $attendance) {
            $status = strtolower(str_replace(' ', '_', $attendance->status ?? ''));
            $attendanceDate = Carbon::parse($attendance->attendance_date);

            $minutes = $this->getAttendanceLateMinutes($attendance, $activityDates);
            if ($minutes > 0) {
                $lateDays++;
                $lateMinutes += $minutes;
            }
        }

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateKey = $date->toDateString();
            $attendance = $attendanceByDate->get($dateKey);
            $status = $attendance
                ? strtolower(str_replace(' ', '_', $attendance->status ?? ''))
                : '';

            if ($date->isSunday() || isset($leaveDateMap[$dateKey]) || in_array($status, $nonReportingStatuses, true)) {
                continue;
            }

            $reportDates[$dateKey] = true;
        }

        $completedReportDates = TaskFollowUp::query()
            ->join('daily_tasks', 'daily_tasks.id', '=', 'task_follow_ups.daily_task_id')
            ->where('daily_tasks.employee_id', $employeeId)
            ->whereBetween('task_follow_ups.created_at', [
                $startDate->copy()->startOfDay(),
                $endDate->copy()->endOfDay(),
            ])
            ->selectRaw('DATE(task_follow_ups.created_at) as report_date')
            ->selectRaw('SUM(CAST(task_follow_ups.time_taken AS DECIMAL(10,2))) as total_hours')
            ->groupByRaw('DATE(task_follow_ups.created_at)')
            ->havingRaw('SUM(CAST(task_follow_ups.time_taken AS DECIMAL(10,2))) >= 8')
            ->pluck('total_hours', 'report_date')
            ->keys()
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip()
            ->all();

        $missedReports = collect(array_keys($reportDates))
            ->reject(fn ($date) => isset($completedReportDates[$date]))
            ->count();

        $completedTasks = DailyTask::where('employee_id', $employeeId)
            ->whereIn('status', ['Completed', 'Review'])
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('status_changed_at', [
                    $startDate->copy()->startOfDay(),
                    $endDate->copy()->endOfDay(),
                ])->orWhere(function ($fallback) use ($startDate, $endDate) {
                    $fallback->whereNull('status_changed_at')
                        ->whereBetween('updated_at', [
                            $startDate->copy()->startOfDay(),
                            $endDate->copy()->endOfDay(),
                        ]);
                });
            })
            ->count();

        $pendingTasks = DailyTask::where('employee_id', $employeeId)
            ->whereNotIn('status', ['Completed', 'Review'])
            ->whereDate('end_date', '<=', $endDate->toDateString())
            ->whereDate('end_date', '>=', $startDate->toDateString())
            ->count();

        $technicalReview = TechnicalReview::where('employee_id', $employeeId)
            ->where('month', $month)
            ->first();

        $technicalScore = 0;
        if ($technicalReview) {
            $technicalScore = (float) $technicalReview->admin_total > 0
                ? (float) $technicalReview->admin_total
                : ((float) $technicalReview->author_total > 0
                    ? (float) $technicalReview->author_total
                    : (float) $technicalReview->self_total);
        }

        $leaveDays = $leaveApplications
            ->sum(function ($leave) {
                return (float) ($leave->total_days ?? 0);
            });

        $latePenalty = min($lateDays * 2.5, 25);
        $reportPenalty = min($missedReports * 3, 30);
        $pendingPenalty = min($pendingTasks * 2, 20);
        $leavePenalty = min($leaveDays * 3, 20);
        $taskBonus = min($completedTasks * 1, 15);
        $technicalBonus = 0;
        $noLeaveBonus = $leaveDays <= 0 ? 10 : 0;
        $penalty = $latePenalty + $reportPenalty + $pendingPenalty + $leavePenalty;
        $totalBonus = $taskBonus + $technicalBonus + $noLeaveBonus;
        $score = max(0, min(100, $reviewTotal - $penalty + $totalBonus));

        return [
            'score' => round($score, 1),
            'rank' => null,
            'late_days' => $lateDays,
            'late_minutes' => $lateMinutes,
            'missed_reports' => $missedReports,
            'report_days' => count($reportDates),
            'completed_report_days' => count($completedReportDates),
            'completed_tasks' => $completedTasks,
            'pending_tasks' => $pendingTasks,
            'technical_score' => round($technicalScore, 1),
            'technical_bonus' => round($technicalBonus, 1),
            'leave_days' => round($leaveDays, 2),
            'leave_penalty' => round($leavePenalty, 1),
            'late_penalty' => round($latePenalty, 1),
            'report_penalty' => round($reportPenalty, 1),
            'pending_penalty' => round($pendingPenalty, 1),
            'task_bonus' => round($taskBonus, 1),
            'no_leave_bonus' => round($noLeaveBonus, 1),
            'penalty' => round($penalty, 1),
            'bonus' => round($totalBonus, 1),
        ];
    }

    private function getReviewMonthDateRange(string $month): array
    {
        try {
            $startDate = Carbon::createFromFormat('F Y', trim($month) . ' ' . now()->year)->startOfMonth();
        } catch (\Exception $e) {
            $startDate = now()->startOfMonth();
        }

        return [$startDate, $startDate->copy()->endOfMonth()];
    }

    private function getAttendanceLateMinutes(Attendance $attendance, array $activityDates = []): int
    {
        if (!$attendance->employee || !$attendance->check_in || !$attendance->check_out) {
            return 0;
        }

        $checkIn = $this->parseAttendancePunch($attendance, $attendance->check_in);
        $checkOut = $this->parseAttendancePunch($attendance, $attendance->check_out);

        if ($checkOut->lessThanOrEqualTo($checkIn)) {
            $checkOut->addDay();
        }

        $workedMinutes = $checkIn->diffInMinutes($checkOut);
        $isApprovedHalfDay = $this->isApprovedHalfDayAttendance($attendance);
        $requiredMinutes = $isApprovedHalfDay ? 4 * 60 : 8 * 60 + 30;

        if (!$isApprovedHalfDay && $this->hasOneHourEarlyOutAllowance($attendance, $activityDates)) {
            $requiredMinutes = max($requiredMinutes - 60, 0);
        }

        return max($requiredMinutes - $workedMinutes, 0);
    }

    private function parseAttendancePunch(Attendance $attendance, $time): Carbon
    {
        $date = Carbon::parse($attendance->attendance_date)->toDateString();

        return Carbon::parse($date . ' ' . Carbon::parse($time)->format('H:i:s'));
    }

    private function getAttendanceActivityDates($attendanceRecords): array
    {
        $activityDates = [];

        foreach ($attendanceRecords->groupBy(fn ($attendance) => Carbon::parse($attendance->attendance_date)->toDateString()) as $date => $dailyAttendances) {
            $earlyOuts = 0;
            $totalPresent = 0;

            foreach ($dailyAttendances as $attendance) {
                $status = strtolower($attendance->status ?? '');

                if (!in_array($status, ['present', 'late', 'half_day', 'early_out', 'early_leave'])) {
                    continue;
                }

                if (!$attendance->check_out || !$attendance->employee || !$attendance->employee->time_out) {
                    continue;
                }

                $totalPresent++;
                $punchTime = Carbon::parse($attendance->check_out)->format('H:i');

                if ($punchTime >= '16:50' && $punchTime < '17:30') {
                    $earlyOuts++;
                }
            }

            if ($totalPresent > 2 && ($earlyOuts / $totalPresent) >= 0.7) {
                $activityDates[$date] = true;
            }
        }

        return $activityDates;
    }

    private function hasOneHourEarlyOutAllowance(Attendance $attendance, array $activityDates): bool
    {
        $date = Carbon::parse($attendance->attendance_date)->toDateString();

        return isset($activityDates[$date]) || $this->isEarlyLeaveAttendance($attendance);
    }

    private function isEarlyLeaveAttendance(Attendance $attendance): bool
    {
        $status = strtolower(str_replace(' ', '_', $attendance->status ?? ''));

        if (in_array($status, ['early_leave', 'early_out'])) {
            return true;
        }

        return LeaveApplication::where('employee_id', $attendance->employee_id)
            ->whereIn('status', ['approved', 'unpaid', 'unauthorised'])
            ->whereDate('start_date', '<=', $attendance->attendance_date)
            ->where(function ($query) use ($attendance) {
                $query->whereDate('end_date', '>=', $attendance->attendance_date)
                    ->orWhere(function ($sub) use ($attendance) {
                        $sub->whereNull('end_date')
                            ->whereDate('start_date', $attendance->attendance_date);
                    });
            })
            ->where(function ($query) {
                $query->whereRaw('LOWER(leave_category) LIKE ?', ['%gatepass%'])
                    ->orWhereRaw('LOWER(leave_type) LIKE ?', ['%gatepass%'])
                    ->orWhereRaw('LOWER(leave_category) LIKE ?', ['%early leave%'])
                    ->orWhereRaw('LOWER(leave_type) LIKE ?', ['%early leave%']);
            })
            ->exists();
    }

    private function isApprovedHalfDayAttendance(Attendance $attendance): bool
    {
        $status = strtolower(str_replace(' ', '_', $attendance->status ?? ''));

        if ($status === 'half_day_leave') {
            return true;
        }

        return LeaveApplication::where('employee_id', $attendance->employee_id)
            ->whereIn('status', ['approved', 'unpaid', 'unauthorised'])
            ->whereDate('start_date', '<=', $attendance->attendance_date)
            ->where(function ($query) use ($attendance) {
                $query->whereDate('end_date', '>=', $attendance->attendance_date)
                    ->orWhere(function ($sub) use ($attendance) {
                        $sub->whereNull('end_date')
                            ->whereDate('start_date', $attendance->attendance_date);
                    });
            })
            ->where(function ($query) {
                $query->whereRaw('LOWER(leave_category) LIKE ?', ['%half%'])
                    ->orWhereRaw('LOWER(leave_type) LIKE ?', ['%half%'])
                    ->orWhere('total_days', 0.5);
            })
            ->exists();
    }

    public function store(Request $request) {
        $user = auth()->user();
        $canManageEmployeeReviews = $this->canManageEmployeeReviews($user);
        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader] = $this->resolveRoleFlags($user);
        $employeeRecord = $this->resolveEmployeeRecord($user);
        
        if (!$employeeRecord) {
            return back()->withErrors('Employee profile could not be found for this user.');
        }

        $validated = $request->validate([
            'user_id' => (($isAdmin || $isTeamLeader) ? 'required' : 'nullable') . '|exists:employees,id',
            'month' => 'required|string',
            'period' => 'required|string',
            'criteria_name' => 'required|array|min:1',
            'criteria_point' => 'required|array|size:' . count($request->criteria_name ?? []),
            'self_review' => 'required|array|size:' . count($request->criteria_name ?? []),
            'author_review' => (($isTeamLeader || $isAdmin) ? 'required' : 'nullable') . '|array',
            'admin_review' => ($isAdmin ? 'required' : 'nullable') . '|array',
            'self_review.*' => 'nullable|numeric|min:0',
            'author_review.*' => 'nullable|numeric|min:0',
            'admin_review.*' => 'nullable|numeric|min:0',
        ]);

        if (($isAdmin || $isTeamLeader) && !empty($validated['user_id'])) {
            $employeeRecord = Employee::active()->find($validated['user_id']);
        } else {
            $employeeRecord = $this->resolveEmployeeRecord($user);
        }
        
        if (!$employeeRecord) {
            return back()->withErrors('Employee profile could not be found for the selected user.');
        }

        if (!$this->canAccessReviewEmployee($user, $employeeRecord)) {
            return back()->withErrors('You are not authorized to submit review for this employee.');
        }

        // Validate duplicates based on employee_id instead of auth user id
        $exists = EmployeeReview::where('employee_id', $employeeRecord->id)
            ->where('month', $request->month)
            ->where('period', $request->period)
            ->exists();

        if ($exists) {
            return back()->withErrors('A review form has already been submitted for this time period.');
        }

        $selfTotal = array_sum(array_map('floatval', $validated['self_review'] ?? []));

        $authorTotal = array_sum(array_map('floatval', $validated['author_review'] ?? []));

        $adminTotal = array_sum(array_map('floatval', $validated['admin_review'] ?? []));

        $review = EmployeeReview::create([
            'employee_id'  => $employeeRecord->id,
            'month'        => $validated['month'],
            'period'       => $validated['period'],
            'self_total'   => $selfTotal,
            'author_total' => $authorTotal ?? 0,
            'admin_total' => $adminTotal ?? 0,
        ]);

        foreach ($validated['criteria_name'] as $key => $row) {
            EmployeeReviewDetail::create([
                'review_id'      => $review->id,
                'criteria_name'  => $validated['criteria_name'][$key],
                'criteria_point' => $validated['criteria_point'][$key],
                'self_review'    => $validated['self_review'][$key] ?? 0,
                'author_review'  => $validated['author_review'][$key] ?? 0,
                'admin_review'  => $validated['admin_review'][$key] ?? 0
            ]);
        }

        return back()->with('success', 'Review securely processed and logged.');
    }

    public function update(Request $request, $id) {
        $user = auth()->user();
        $canManageEmployeeReviews = $this->canManageEmployeeReviews($user);
        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader] = $this->resolveRoleFlags($user);

        if (!$canManageEmployeeReviews && !$isTeamLeader) {
            return back()->withErrors('You are not authorized to update this review.');
        }

        $review = EmployeeReview::with(['details', 'employee'])->find($id);
        if (!$review) {
            return back()->withErrors('Review not found.');
        }

        if (!$this->canAccessReviewEmployee($user, $review->employee)) {
            return back()->withErrors('You are not authorized to update this review.');
        }

        $detailsCount = $review->details->count();

        $rules = [];
        if ($isTeamLeader || $isAdmin) {
            $rules['author_review'] = 'required|array|size:' . $detailsCount;
            $rules['author_review.*'] = 'nullable|numeric|min:0';
        } else {
            $rules['author_review'] = 'nullable|array';
        }

        if ($isAdmin) {
            $rules['admin_review'] = 'required|array|size:' . $detailsCount;
            $rules['admin_review.*'] = 'nullable|numeric|min:0';
        } else {
            $rules['admin_review'] = 'nullable|array';
        }

        $validated = $request->validate($rules);

        // Update each detail in order
        foreach ($review->details as $index => $detail) {
            $detail->author_review = $validated['author_review'][$index] ?? $detail->author_review;
            $detail->admin_review = $validated['admin_review'][$index] ?? $detail->admin_review;
            $detail->save();
        }

        // Recalculate totals
        $authorTotal = $review->details->sum(fn($d) => (float) $d->author_review);
        $adminTotal  = $review->details->sum(fn($d) => (float) $d->admin_review);

        $review->author_total = $authorTotal;
        $review->admin_total = $adminTotal;
        $review->save();

        return back()->with('success', 'Review updated successfully.');
    }

    public function details($id) {
        $review = EmployeeReview::with('employee')->findOrFail($id);
        $employeeRecord = $this->resolveEmployeeRecord(auth()->user());

        if (!$this->canAccessReviewEmployee(auth()->user(), $review->employee)) {
            abort(403, 'Unauthorized access');
        }

        return response()->json(EmployeeReviewDetail::where('review_id', $id)->get());
    }

    
    // Technical Review

    public function technicalReview(Request $request)
    {
        $user = auth()->user();
        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader]
            = $this->resolveRoleFlags($user);

        $employeeRecord = $this->resolveEmployeeRecord($user);
        $query = TechnicalReview::with(['employee', 'details']);
        if ($isAdmin) {
            $query->latest();
        } elseif ($isTeamLeader) {
            $userDepartment = $employeeRecord->department ?? null;
            if ($userDepartment) {
                $employeeIds = Employee::active()
                    ->where('department', $userDepartment)
                    ->pluck('id');

                $query->whereIn('employee_id', $employeeIds)
                    ->latest();
            } else {
                $query->where('employee_id', $employeeRecord->id)
                    ->latest();
            }
        } else {
            $query->where('employee_id', $employeeRecord->id)
                ->latest();
        }
        $employees = $isTeamLeader && !$isAdmin && $employeeRecord
            ? Employee::active()
                ->where('department', $employeeRecord->department)
                ->orderBy('name')
                ->get()
            : Employee::active()
                ->orderBy('name')
                ->get();

        $departments = Department::all();
        $evaluations = TechnicalReviewEvaluation::where(['status' => 1,'department' => $employeeRecord->department ?? null])
            ->orderBy('sort_order')
            ->get();

        $perPage = (int) $request->query('per_page', 20);

        $reviews = $query->paginate($perPage);

        return view(
            'review.technical-review', compact('reviews', 'isAdmin', 'isTeamLeader', 'employees', 'perPage', 'employeeRecord', 'departments', 'evaluations')
        );
    }

    public function technicalReviewStore(Request $request)
    {
        $user = auth()->user();

        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader]
            = $this->resolveRoleFlags($user);

        $employeeRecord = $this->resolveEmployeeRecord($user);

        $validated = $request->validate([
            'user_id' => (($isAdmin || $isTeamLeader)
                ? 'required'
                : 'nullable') . '|exists:employees,id',

            'month' => 'required',

            'criteria_name' => 'required|array',
            'criteria_point' => 'required|array',
            'self_review' => 'required|array',

            'author_review' => (($isAdmin || $isTeamLeader) ? 'required' : 'nullable') . '|array',
            'admin_review' => ($isAdmin ? 'required' : 'nullable') . '|array',
        ]);

        if (($isAdmin || $isTeamLeader) && !empty($validated['user_id'])) {
            $employeeRecord = Employee::active()->find($validated['user_id']);
        }

        if (!$employeeRecord) {
            return back()->withErrors('Employee profile could not be found for the selected user.');
        }

        if (!$this->canAccessReviewEmployee($user, $employeeRecord)) {
            return back()->withErrors('You are not authorized to submit technical review for this employee.');
        }

        $exists = TechnicalReview::where('employee_id', $employeeRecord->id)
            ->where('month', $request->month)
            ->exists();

        if ($exists) {
            return back()->withErrors(
                'Technical review already exists for this month.'
            );
        }

        $review = TechnicalReview::create([
            'employee_id' => $employeeRecord->id,
            'month' => $request->month,
            'self_total' => array_sum($request->self_review ?? []),
            'author_total' => array_sum($request->author_review ?? []),
            'admin_total' => array_sum($request->admin_review ?? []),
        ]);

        foreach ($request->criteria_name as $key => $criteria) {

            TechnicalReviewDetail::create([
                'review_id' => $review->id,
                'criteria_name' => $criteria,
                'criteria_point' => $request->criteria_point[$key],
                'self_review' => $request->self_review[$key] ?? 0,
                'author_review' => $request->author_review[$key] ?? 0,
                'admin_review' => $request->admin_review[$key] ?? 0,
            ]);
        }

        return back()->with(
            'success',
            'Technical Review created successfully.'
        );
    }

    public function technicalReviewUpdate(Request $request, $id)
    {
        $user = auth()->user();
        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader]
            = $this->resolveRoleFlags($user);

        if (!$isAdmin && !$isTeamLeader) {
            return back()->withErrors('You are not authorized to update this technical review.');
        }

        $review = TechnicalReview::with(['details', 'employee'])->findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:employees,id',
            'month' => 'required|string',
            'self_review' => 'nullable|array',
            'author_review' => 'nullable|array',
            'admin_review' => 'nullable|array',
            'detail_id' => 'nullable|array',
            'detail_id.*' => 'nullable|integer',
            'self_review.*' => 'nullable|numeric|min:0',
            'author_review.*' => 'nullable|numeric|min:0',
            'admin_review.*' => 'nullable|numeric|min:0',
        ]);

        $targetEmployee = Employee::active()->find($validated['user_id']);

        if (!$this->canAccessReviewEmployee($user, $review->employee)
            || !$this->canAccessReviewEmployee($user, $targetEmployee)) {
            return back()->withErrors('You are not authorized to update this technical review.');
        }

        $exists = TechnicalReview::where('employee_id', $validated['user_id'])
            ->where('month', $validated['month'])
            ->where('id', '!=', $review->id)
            ->exists();

        if ($exists) {
            return back()->withErrors(
                'Technical review already exists for this month.'
            );
        }

        $review->employee_id = $validated['user_id'];
        $review->month = $validated['month'];

        $detailsById = $review->details->keyBy('id');

        foreach ($validated['detail_id'] ?? [] as $index => $detailId) {
            $detail = $detailsById->get((int) $detailId);

            if (!$detail) {
                continue;
            }

            $detail->self_review =
                array_key_exists($index, $validated['self_review'] ?? [])
                    ? ($validated['self_review'][$index] ?? 0)
                    : $detail->self_review;

            $detail->author_review =
                array_key_exists($index, $validated['author_review'] ?? [])
                    ? ($validated['author_review'][$index] ?? 0)
                    : $detail->author_review;

            $detail->admin_review =
                array_key_exists($index, $validated['admin_review'] ?? [])
                    ? ($validated['admin_review'][$index] ?? 0)
                    : $detail->admin_review;

            $detail->save();
        }

        $review->self_total =
            $review->details()->sum('self_review');

        $review->author_total =
            $review->details()->sum('author_review');

        $review->admin_total =
            $review->details()->sum('admin_review');

        $review->save();

        return back()->with(
            'success',
            'Technical Review updated successfully.'
        );
    }

    public function technicalReviewDetails($id)
    {
        $review = TechnicalReview::with('employee')->findOrFail($id);

        if (!$this->canAccessReviewEmployee(auth()->user(), $review->employee)) {
            abort(403, 'Unauthorized access');
        }

        return response()->json(
            TechnicalReviewDetail::where('review_id', $id)
                ->orderBy('id')
                ->get()
        );
    }


    // Add Review Criteria
    public function storeTechnicalEvaluation(Request $request)
    {
        $validated = $request->validate([
            'department' => 'required|string',
            'criterianame' => 'nullable|array',
            'criterianame.*' => 'nullable|string',
            'maxpoint' => 'nullable|array',
            'maxpoint.*' => 'nullable|numeric|min:0',
        ]);

        $criteriaNames = $validated['criterianame'] ?? [];
        $maxPoints = $validated['maxpoint'] ?? [];
        $department = $validated['department'];

        $rows = [];

        foreach ($criteriaNames as $index => $criteria) {
            $criteria = trim((string) $criteria);

            if ($criteria === '') {
                continue;
            }

            $rows[] = [
                'department' => $department,
                'criteria_name' => $criteria,
                'max_point' => $maxPoints[$index] ?? 0,
                'sort_order' => count($rows) + 1,
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::transaction(function () use ($department, $rows) {
            TechnicalReviewEvaluation::where('department', $department)->delete();

            if (!empty($rows)) {
                TechnicalReviewEvaluation::insert($rows);
            }
        });

        return back()->with('success', 'Evaluation saved successfully.');
    }

    public function fetchByDepartment(Request $request) 
    {
        $department = $request->get('department');
        
        // Grabs database array lines for criteria matching this exact selected department text
        $savedData = TechnicalReviewEvaluation::where('department', $department)->get();

        return response()->json($savedData);
    }
}

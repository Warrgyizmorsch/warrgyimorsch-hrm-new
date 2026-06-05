<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeeReviewDetail;
use App\Models\EmployeeReview;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    protected function resolveRoleFlags($user): array
    {
        $roleId = DB::table('roles_master')
            ->where('slug', $user->role)
            ->value('id');

        return [
            'isAdmin' => in_array($roleId, [1, 2, 3, 4]),
            'isTeamLeader' => (int) $roleId === 5,
        ];
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

    public function index() {
        $user = auth()->user();
        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader] = $this->resolveRoleFlags($user);

        $employeeRecord = $this->resolveEmployeeRecord($user);
        
        $query = EmployeeReview::with(['employee', 'details']);
        
        if ($isAdmin) {
            // Admin sees everything
            $query->latest();
        } elseif ($isTeamLeader) {
            // Team leader sees their department's employees
            $userDepartment = $employeeRecord->department ?? null;
            
            if ($userDepartment) {
                $employeeIds = Employee::active()->where('department', $userDepartment)->pluck('id');
                $query->whereIn('employee_id', $employeeIds)->latest();
            } else {
                // Fallback: if no department found, show only their own reviews
                $empId = $employeeRecord ? $employeeRecord->id : 0;
                $query->where('employee_id', $empId)->latest();
            }
        } else {
            // Regular employees only see their own reviews matching their employee profile ID
            $empId = $employeeRecord ? $employeeRecord->id : 0;
            $query->where('employee_id', $empId)->latest();
        }
        
        $employees = $isTeamLeader && !$isAdmin && $employeeRecord
            ? Employee::active()->where('department', $employeeRecord->department)->orderBy('name')->get()
            : Employee::active()->orderBy('name')->get();
        
        $reviews = $query->paginate(10);
        return view('review.review', compact('reviews', 'isAdmin', 'isTeamLeader', 'employees'));
    }

    public function store(Request $request) {
        $user = auth()->user();
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
            'author_review' => 'required|array|size:' . count($request->criteria_name ?? []),
            'admin_review' => 'required|array|size:' . count($request->criteria_name ?? []),
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

        // Validate duplicates based on employee_id instead of auth user id
        $exists = EmployeeReview::where('employee_id', $employeeRecord->id)
            ->where('month', $request->month)
            ->where('period', $request->period)
            ->exists();

        if ($exists) {
            return back()->withErrors('A review form has already been submitted for this time period.');
        }

        $selfTotal = array_sum(array_map('floatval', $validated['self_review']));
        $authorTotal = array_sum(array_map('floatval', $validated['author_review']));
        $adminTotal = array_sum(array_map('floatval', $validated['admin_review']));

        $review = EmployeeReview::create([
            'employee_id'  => $employeeRecord->id,
            'month'        => $validated['month'],
            'period'       => $validated['period'],
            'self_total'   => $selfTotal,
            'author_total' => $authorTotal,
            'admin_total' => $adminTotal
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

    public function details($id) {
        return response()->json(EmployeeReviewDetail::where('review_id', $id)->get());
    }
}

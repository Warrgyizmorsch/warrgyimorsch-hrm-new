<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use App\Models\EmployeeReviewDetail;
use App\Models\EmployeeReview;
use App\Models\Employee;
use App\Models\TechnicalReview;
use App\Models\TechnicalReviewDetail;
use App\Models\TechnicalReviewEvaluation;
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

    public function index(Request $request) {
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
        
        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }
        $reviews = $query->paginate($perPage);
        return view('review.review', compact('reviews', 'isAdmin', 'isTeamLeader', 'employees', 'perPage'));
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
        ['isAdmin' => $isAdmin, 'isTeamLeader' => $isTeamLeader] = $this->resolveRoleFlags($user);

        if (!($isAdmin || $isTeamLeader)) {
            return back()->withErrors('You are not authorized to update this review.');
        }

        $review = EmployeeReview::with('details')->find($id);
        if (!$review) {
            return back()->withErrors('Review not found.');
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
        $evaluations = TechnicalReviewEvaluation::where('status', 1)
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
            'period' => 'required',

            'criteria_name' => 'required|array',
            'criteria_point' => 'required|array',
            'self_review' => 'required|array',

            'author_review' => 'nullable|array',
            'admin_review' => 'nullable|array',
        ]);

        if (($isAdmin || $isTeamLeader) && !empty($validated['user_id'])) {
            $employeeRecord = Employee::find($validated['user_id']);
        }

        $exists = TechnicalReview::where('employee_id', $employeeRecord->id)
            ->where('month', $request->month)
            ->where('period', $request->period)
            ->exists();

        if ($exists) {
            return back()->withErrors(
                'Technical review already exists for this period.'
            );
        }

        $review = TechnicalReview::create([
            'employee_id' => $employeeRecord->id,
            'month' => $request->month,
            'period' => $request->period,
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
        $review = TechnicalReview::with('details')->findOrFail($id);

        foreach ($review->details as $index => $detail) {

            $detail->author_review =
                $request->author_review[$index]
                ?? $detail->author_review;

            $detail->admin_review =
                $request->admin_review[$index]
                ?? $detail->admin_review;

            $detail->save();
        }

        $review->author_total =
            $review->details->sum('author_review');

        $review->admin_total =
            $review->details->sum('admin_review');

        $review->save();

        return back()->with(
            'success',
            'Technical Review updated successfully.'
        );
    }

    public function technicalReviewDetails($id)
    {
        return response()->json(
            TechnicalReviewDetail::where('review_id', $id)->get()
        );
    }


    // Add Review Criteria
    public function storeTechnicalEvaluation(Request $request)
    {
        $criteriaNames = $request->criterianame ?? [];
        $maxPoints = $request->maxpoint ?? [];
        $department = $request->department;

        $submittedCriteria = [];

        foreach ($criteriaNames as $index => $criteria) {
            $criteria = trim($criteria);

            if ($criteria === '') {
                continue;
            }

            $submittedCriteria[] = $criteria;

            TechnicalReviewEvaluation::updateOrCreate(
                [
                    'department' => $department,
                    'criteria_name' => $criteria,
                ],
                [
                    'max_point' => $maxPoints[$index] ?? 0,
                ]
            );
        }

        TechnicalReviewEvaluation::where('department', $department)
            ->whereNotIn('criteria_name', $submittedCriteria)
            ->delete();

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

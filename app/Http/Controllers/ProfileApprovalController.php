<?php

namespace App\Http\Controllers;

use App\Models\EmployeeProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * HR side of self-service details: review what an employee submitted, correct it if needed,
 * then approve (applies it to the employee and unlocks a new account) or send it back.
 */
class ProfileApprovalController extends Controller
{
    public function index()
    {
        $requests = EmployeeProfileRequest::with('employee.departmentRef')
            ->whereIn('status', [EmployeeProfileRequest::STATUS_PENDING, EmployeeProfileRequest::STATUS_RETURNED])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('submitted_at')
            ->get();

        return view('employees.profile-approvals.index', compact('requests'));
    }

    public function show(EmployeeProfileRequest $profileRequest)
    {
        $profileRequest->load('employee.departmentRef', 'employee.documents', 'reviewer');

        return view('employees.profile-approvals.show', [
            'profileRequest' => $profileRequest,
            'employee' => $profileRequest->employee,
            'current' => EmployeeProfileRequest::currentValues($profileRequest->employee),
            'changed' => $profileRequest->changedFields(),
        ]);
    }

    public function approve(Request $request, EmployeeProfileRequest $profileRequest)
    {
        abort_unless($profileRequest->status === EmployeeProfileRequest::STATUS_PENDING, 422, 'This request is not waiting for approval.');

        // HR may have corrected values on the review screen — validate those, not the original.
        $request->merge(EmployeeProfileRequest::normalize($request->all()));
        $data = $request->validate(EmployeeProfileRequest::rules(false), EmployeeProfileRequest::messages());
        $data = array_intersect_key($data, EmployeeProfileRequest::FIELDS);

        DB::transaction(function () use ($profileRequest, $data) {
            $employee = $profileRequest->employee;
            $wasOnboarding = $employee->isProfileLocked();

            $employee->update($data + ($wasOnboarding ? ['profile_status' => null] : []));

            $profileRequest->update([
                'data' => $data,
                'status' => EmployeeProfileRequest::STATUS_APPROVED,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
        });

        $name = $profileRequest->employee->name;

        return redirect()->route('profile-approvals.index')->with('success', $profileRequest->type === EmployeeProfileRequest::TYPE_ONBOARDING
            ? "{$name}'s profile is approved — their account is now fully active."
            : "{$name}'s changes are approved and applied.");
    }

    public function sendBack(Request $request, EmployeeProfileRequest $profileRequest)
    {
        abort_unless($profileRequest->status === EmployeeProfileRequest::STATUS_PENDING, 422, 'This request is not waiting for approval.');

        $validated = $request->validate(['review_note' => 'required|string|max:1000'], [
            'review_note.required' => 'Tell the employee what to fix.',
        ]);

        DB::transaction(function () use ($profileRequest, $validated) {
            $profileRequest->update([
                'status' => EmployeeProfileRequest::STATUS_RETURNED,
                'review_note' => $validated['review_note'],
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            if ($profileRequest->employee->isProfileLocked()) {
                $profileRequest->employee->update(['profile_status' => \App\Models\Employee::PROFILE_RETURNED]);
            }
        });

        return redirect()->route('profile-approvals.index')
            ->with('success', "Sent back to {$profileRequest->employee->name} with your note.");
    }
}

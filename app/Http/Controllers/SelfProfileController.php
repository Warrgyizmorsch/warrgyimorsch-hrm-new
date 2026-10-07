<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\EmployeeProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Employee side of self-service details: a new employee completes their profile here before
 * the portal unlocks, and an active employee requests changes here. Either way the details
 * go to HR as an EmployeeProfileRequest and are only applied once approved.
 */
class SelfProfileController extends Controller
{
    public function edit()
    {
        $employee = $this->employee();
        $openRequest = $employee->openProfileRequest();

        $values = $openRequest?->data ?? EmployeeProfileRequest::currentValues($employee);
        if (!$openRequest && $employee->profile_status === Employee::PROFILE_PENDING) {
            // employees.gender is NOT NULL, so a placeholder is stored at account creation —
            // don't pre-select it; the employee must choose.
            $values['gender'] = null;
        }

        return view('profile.complete', [
            'employee' => $employee->load('departmentRef', 'documents'),
            'openRequest' => $openRequest,
            'values' => $values,
            'isOnboarding' => $employee->isProfileLocked(),
        ]);
    }

    public function submit(Request $request)
    {
        $employee = $this->employee();
        $request->merge(EmployeeProfileRequest::normalize($request->all()));

        $documentRules = [];
        foreach (EmployeeProfileRequest::DOCUMENT_TYPES as $type) {
            $documentRules["documents.$type"] = 'nullable|' . EmployeeDocument::TYPES[$type]['rules'];
        }

        $validated = $request->validate(
            EmployeeProfileRequest::rules() + $documentRules + ['photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048'],
            EmployeeProfileRequest::messages(),
            EmployeeDocument::validationAttributes()
        );

        $data = array_intersect_key($validated, EmployeeProfileRequest::FIELDS);

        DB::transaction(function () use ($request, $employee, $data) {
            $isOnboarding = $employee->isProfileLocked();
            $open = $employee->openProfileRequest();

            // One open request at a time: resubmitting (before review, or after being sent
            // back) updates it rather than piling up duplicates for HR.
            ($open ?? new EmployeeProfileRequest(['employee_id' => $employee->id]))->fill([
                'type' => $open->type ?? ($isOnboarding ? EmployeeProfileRequest::TYPE_ONBOARDING : EmployeeProfileRequest::TYPE_CHANGE),
                'data' => $data,
                'status' => EmployeeProfileRequest::STATUS_PENDING,
                'review_note' => null,
                'submitted_at' => now(),
            ])->save();

            // Documents and photo are evidence for HR's review, so they're stored right away
            // (private disk) rather than held back with the text fields.
            foreach (EmployeeProfileRequest::DOCUMENT_TYPES as $type) {
                if ($request->hasFile("documents.$type")) {
                    EmployeeDocument::storeFor($employee, $type, $request->file("documents.$type"));
                }
            }
            if ($request->hasFile('photo')) {
                $employee->update(['photo' => $request->file('photo')->store('employees', 'public')]);
            }

            if ($isOnboarding) {
                $employee->update(['profile_status' => Employee::PROFILE_SUBMITTED]);
            }
        });

        return redirect()->route('profile.complete')
            ->with('success', 'Thanks! Your details were sent to HR for approval.');
    }

    private function employee(): Employee
    {
        $employee = auth()->user()->employee;
        abort_unless($employee, 404, 'No employee record is linked to this login.');

        return $employee;
    }
}

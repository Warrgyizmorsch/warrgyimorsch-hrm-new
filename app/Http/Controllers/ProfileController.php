<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\Employee;
use App\Models\Attendance;
use App\Models\LeaveApplication;
use App\Models\LeaveAllotment;
use App\Models\Holiday;
use App\Services\LeaveBalanceService;

class ProfileController extends Controller
{
    public function __construct(private LeaveBalanceService $leaveBalanceService)
    {
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $employee = Employee::find($user->employee_id);

        $request->validate([
            'name' => 'required|string|max:255',
            'mobile_number' => 'nullable|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $user->update([
            'name' => $request->name,
        ]);

        if ($employee) {
            $updateData = [
                'name' => $request->name,
                'mobile_number' => $request->mobile_number
            ];

            if ($request->hasFile('photo')) {
                $file = $request->file('photo');
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('employees', $filename, 'public');

                if ($employee->photo && \Storage::disk('public')->exists($employee->photo)) {
                    \Storage::disk('public')->delete($employee->photo);
                }
                $updateData['photo'] = $path;
            }

            $employee->update($updateData);
        }

        return Redirect::route('profile.show')->with('success', 'Profile updated successfully! ✓');
    }

    public function show(Request $request): View
    {
        $user = $request->user();
        $employee = Employee::find($user->employee_id);
        
        $userRole = strtolower($user->role ?? '');
        $all_employees = collect();
        
        // Match 'admin', 'super admin', 'administrator', etc.
        if (str_contains($userRole, 'admin')) {
            $all_employees = \App\Models\User::with('employee')->orderBy('name')->get();
        }

        return view('profile.show', [
            'user' => $user,
            'employee' => $employee,
            'all_employees' => $all_employees
        ]);
    }

    public function leaveBalance(Request $request): View
    {
        $user = $request->user();
        // echo ($user->employee_id);
        $employee = Employee::find($user->employee_id);
        
        $balances = [];
        $totalLeaveCycle = [
            'allotted' => 0,
            'used' => 0,
            'available' => 0,
        ];

        if ($employee) {
            $currentSummary = $this->leaveBalanceService->getEmployeeBalanceSummary($employee->id);
            $totalLeaveCycle = [
                'allotted' => $currentSummary['total_allotted'],
                'used' => $currentSummary['total_taken'],
                'available' => $currentSummary['balance'],
            ];

            $balances = $this->leaveBalanceService->buildMonthlyBalanceTimeline($employee->id);
        }

        return view('profile.leave-balance', [
            'user' => $user,
            'employee' => $employee,
            'balances' => $balances,
            'totalLeaveCycle' => $totalLeaveCycle,
            'total_allotted' => $balances[0]['allotted'] ?? 0,
            'total_used' => $balances[0]['used'] ?? 0,
            'balance' => $balances[0]['available'] ?? 0
        ]);
    }

    public function leaveHistory(Request $request): View
    {
        $user = $request->user();
        $employee = Employee::find($user->employee_id);
        $holidays = Holiday::pluck('date')
            ->map(fn ($date) => \Carbon\Carbon::parse($date)->format('Y-m-d'))
            ->values();

        $leaves = collect([]);
        if ($employee) {
            $leaves = LeaveApplication::where('employee_id', $employee->id)->orderBy('created_at', 'desc')->get();
        }

        $total_allotted = $employee
            ? LeaveAllotment::where('employee_id', $employee->id)->sum('leave_count')
            : 0;
        $total_used = $employee
            ? $this->leaveBalanceService->getDeductibleLeaveDaysForEmployee($employee->id)
            : 0;

        $totalLeaveCycle = [
            'allotted' => $total_allotted,
            'used' => $total_used,
            'available' => max(0, $total_allotted - $total_used),
        ];

        return view('profile.leave-history', [
            'user' => $user,
            'employee' => $employee,
            'leaves' => $leaves,
            'holidays' => $holidays,
            'totalLeaveCycle' => $totalLeaveCycle,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'min:8'],
            'target_user_id' => ['nullable', 'exists:users,id'],
        ]);

        $currentUser = $request->user();
        $targetUser = $currentUser;
        $userRole = strtolower($currentUser->role ?? '');

        // If admin/super-admin is changing another user's password
        if ($request->filled('target_user_id') && str_contains($userRole, 'admin')) {
            $targetUser = \App\Models\User::find($request->target_user_id);
        }

        $newPassword = $validated['password'];

        $targetUser->update([
            'password' => \Illuminate\Support\Facades\Hash::make($newPassword),
        ]);

        $employee = Employee::find($targetUser->employee_id);
        if ($employee) {
            $employee->update(['password' => \Illuminate\Support\Facades\Hash::make($newPassword)]);
        }

        return Redirect::route('profile.show')->with('success', 'Password updated successfully! ✓');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

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

class ProfileController extends Controller
{
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
        if ($employee) {
            // Allotment: Treating the leave_count as a monthly quota
            $total_allotted = LeaveAllotment::where('employee_id', $employee->id)->sum('leave_count');

            // Usage: Approved leaves that START in this month
            $total_used = LeaveApplication::where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('leave_category', 'NOT LIKE', '%WFH%') // Exclude WFH from used leaves
                // ->whereYear('start_date', date('Y'))
                // ->whereMonth('start_date', date('m'))
                ->sum('total_days');
            // echo $total_used;exit;
            $totalLeaveCycle = [
                'allotted' => $total_allotted,
                'used' => $total_used,
                'available' => max(0, $total_allotted - $total_used),
            ];

            // Monthly Rows
            $monthlyAllotments = LeaveAllotment::where('employee_id', $employee->id)
                ->orderBy('year', 'asc')
                ->orderBy('month', 'asc')
                ->get();

            $monthlyBalances = [];
            $carryForward = 0;

            foreach ($monthlyAllotments as $allotment) {

                $used = LeaveApplication::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->where('leave_category', 'NOT LIKE', '%WFH%')
                    ->whereYear('start_date', $allotment->year)
                    ->whereMonth('start_date', $allotment->month)
                    ->sum('total_days');

                $available = max(
                    0,
                    $carryForward + $allotment->leave_count - $used
                );

                $monthlyBalances[] = [
                    'type' => strtoupper(date(
                        'F',
                        mktime(0, 0, 0, $allotment->month, 1)
                    )) . " ({$allotment->year})",
                    'allotted' => $allotment->leave_count,
                    'used' => $used,
                    'available' => $available,
                    'reference' => 'Monthly Quota'
                ];

                $carryForward = $available;
            }

            // Reverse so latest month appears first
            $monthlyBalances = array_reverse($monthlyBalances);

            foreach ($monthlyBalances as $row) {
                $balances[] = $row;
            }
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

        // Total remaining balance
        $total_allotted = LeaveAllotment::where('employee_id', $employee->id)->sum('leave_count');
        $total_used = LeaveApplication::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where('leave_category', 'NOT LIKE', '%WFH%') // Exclude WFH from used leaves
            // ->whereYear('start_date', date('Y'))
            // ->whereMonth('start_date', date('m'))
            ->sum('total_days');
    
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

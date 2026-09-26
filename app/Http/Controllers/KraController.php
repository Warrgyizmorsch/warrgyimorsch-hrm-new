<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\KraAssignment;
use App\Models\TechnicalReviewEvaluation;
use Illuminate\Http\Request;

class KraController extends Controller
{
    public function index(Request $request)
    {
        $query = KraAssignment::with(['employee.departmentRef', 'items']);

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $assignments = $query->orderByDesc('month')->paginate(20)->appends($request->query());
        $employees = Employee::active()->orderBy('name')->get();

        return view('performance.kra.index', compact('assignments', 'employees'));
    }

    public function assign(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        if (KraAssignment::where('employee_id', $employee->id)->where('month', $request->month)->exists()) {
            return back()->with('error', 'A KRA is already assigned to this employee for this month.');
        }

        $criteria = TechnicalReviewEvaluation::where('department_id', $employee->department_id)
            ->where('status', 1)
            ->orderBy('sort_order')
            ->get();

        if ($criteria->isEmpty()) {
            return back()->with('error', 'No KRA criteria configured for this employee\'s department yet. Set them up under Employee Review > Technical Review Criteria first.');
        }

        $assignment = KraAssignment::create([
            'employee_id' => $employee->id,
            'month' => $request->month,
            'assigned_by' => auth()->id(),
        ]);

        foreach ($criteria as $c) {
            $assignment->items()->create([
                'technical_review_evaluation_id' => $c->id,
                'criteria_name' => $c->criteria_name,
                'max_point' => $c->max_point,
            ]);
        }

        return back()->with('success', 'KRA assigned to ' . $employee->name . ' for ' . $request->month . '.');
    }

    public function destroy($id)
    {
        KraAssignment::findOrFail($id)->delete();

        return back()->with('success', 'KRA assignment removed.');
    }

    /**
     * Employee acknowledges their own assignment. Admins can also acknowledge on behalf of
     * an employee if needed, but the normal path is the employee themselves.
     */
    public function acknowledge($id)
    {
        $assignment = KraAssignment::with('employee')->findOrFail($id);
        $user = auth()->user();

        if (!$this->isAdmin() && (int) $user->employee_id !== (int) $assignment->employee_id) {
            abort(403);
        }

        $assignment->update(['acknowledged_at' => now()]);

        return back()->with('success', 'KRA acknowledged.');
    }

    private function isAdmin(): bool
    {
        $role = str_replace(' ', '_', strtolower(trim((string) (auth()->user()->role ?? 'employee'))));
        return in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head'], true);
    }
}

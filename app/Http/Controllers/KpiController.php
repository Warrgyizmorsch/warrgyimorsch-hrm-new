<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Kpi;
use App\Models\KpiAssignment;
use App\Services\KpiAutoMetricService;
use Illuminate\Http\Request;

class KpiController extends Controller
{
    // --- Master KPI templates (per department) ---

    public function index(Request $request)
    {
        $perPage = (int) ($request->query('show', 20));
        if (!in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = Kpi::with('department')->orderBy('department_id')->orderBy('sort_order');

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active');
        }

        $kpis = $query->paginate($perPage)->appends($request->query());
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('performance.kpi.index', [
            'kpis' => $kpis,
            'departments' => $departments,
            'totalCount' => Kpi::count(),
            'activeCount' => Kpi::where('status', true)->count(),
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Kpi::create($data);

        return back()->with('success', 'KPI added successfully!');
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);
        $kpi = Kpi::findOrFail($id);
        $kpi->update($data + ['status' => $request->has('status')]);

        return back()->with('success', 'KPI updated successfully!');
    }

    public function destroy($id)
    {
        Kpi::findOrFail($id)->delete();

        return back()->with('success', 'KPI deleted successfully!');
    }

    private function validated(Request $request): array
    {
        if ($request->auto_metric === '') {
            $request->merge(['auto_metric' => null]);
        }

        return $request->validate([
            'department_id' => 'required|exists:departments,id',
            'title' => 'required|string|max:255',
            'unit' => 'nullable|string|max:30',
            'target_value' => 'required|numeric|min:0',
            'weightage' => 'nullable|numeric|min:0|max:100',
            'auto_metric' => 'nullable|string|in:' . implode(',', array_keys(Kpi::AUTO_METRICS)),
        ]);
    }

    // --- Per-employee assignment ---

    public function assignments(Request $request)
    {
        $query = KpiAssignment::with(['employee.departmentRef', 'items']);

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        $assignments = $query->orderByDesc('month')->paginate(20)->appends($request->query());
        $employees = Employee::active()->orderBy('name')->get();

        return view('performance.kpi.assignments', compact('assignments', 'employees'));
    }

    public function assign(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $employee = Employee::findOrFail($request->employee_id);

        if (KpiAssignment::where('employee_id', $employee->id)->where('month', $request->month)->exists()) {
            return back()->with('error', 'KPIs are already assigned to this employee for this month.');
        }

        $kpis = Kpi::active()->where('department_id', $employee->department_id)->orderBy('sort_order')->get();

        if ($kpis->isEmpty()) {
            return back()->with('error', 'No active KPIs configured for this employee\'s department yet.');
        }

        $assignment = KpiAssignment::create([
            'employee_id' => $employee->id,
            'month' => $request->month,
            'assigned_by' => auth()->id(),
        ]);

        foreach ($kpis as $kpi) {
            $actual = $kpi->auto_metric
                ? KpiAutoMetricService::compute($kpi->auto_metric, $employee->id, $request->month)
                : null;

            $assignment->items()->create([
                'kpi_id' => $kpi->id,
                'title' => $kpi->title,
                'unit' => $kpi->unit,
                'target_value' => $kpi->target_value,
                'weightage' => $kpi->weightage,
                'auto_metric' => $kpi->auto_metric,
                'actual_value' => $actual,
            ]);
        }

        return back()->with('success', 'KPIs assigned to ' . $employee->name . ' for ' . $request->month . '.');
    }

    /**
     * Re-run the auto-computation for this assignment's auto-metric items — useful when
     * assigned mid-month, since more tasks/attendance may have landed since then. Manually
     * entered items (no auto_metric) are left untouched.
     */
    public function recompute($id)
    {
        $assignment = KpiAssignment::with('items')->findOrFail($id);

        foreach ($assignment->items as $item) {
            if (!$item->auto_metric) {
                continue;
            }

            $actual = KpiAutoMetricService::compute($item->auto_metric, $assignment->employee_id, $assignment->month);
            $item->update(['actual_value' => $actual]);
        }

        return back()->with('success', 'Auto-calculated KPIs recomputed.');
    }

    public function destroyAssignment($id)
    {
        KpiAssignment::findOrFail($id)->delete();

        return back()->with('success', 'KPI assignment removed.');
    }

    /**
     * Admin/manager records actual achievement per KPI item for an assignment.
     */
    public function updateActuals(Request $request, $id)
    {
        $assignment = KpiAssignment::with('items')->findOrFail($id);

        $request->validate([
            'actual_value' => 'required|array',
            'actual_value.*' => 'nullable|numeric|min:0',
        ]);

        foreach ($assignment->items as $item) {
            if (array_key_exists($item->id, $request->actual_value)) {
                $item->update(['actual_value' => $request->actual_value[$item->id]]);
            }
        }

        return back()->with('success', 'KPI actuals updated.');
    }

    public function acknowledge($id)
    {
        $assignment = KpiAssignment::findOrFail($id);
        $user = auth()->user();

        if (!$this->isAdmin() && (int) $user->employee_id !== (int) $assignment->employee_id) {
            abort(403);
        }

        $assignment->update(['acknowledged_at' => now()]);

        return back()->with('success', 'KPI acknowledged.');
    }

    private function isAdmin(): bool
    {
        $role = str_replace(' ', '_', strtolower(trim((string) (auth()->user()->role ?? 'employee'))));
        return in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head'], true);
    }
}

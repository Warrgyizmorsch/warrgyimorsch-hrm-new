<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeIncrement;
use App\Services\IncrementService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Salary increments for one employee (modal on the employee list). Admin roles only — see routes. */
class EmployeeIncrementController extends Controller
{
    public function __construct(private IncrementService $increments)
    {
    }

    public function index(int $id): JsonResponse
    {
        $employee = Employee::with('increments.creator')->findOrFail($id);
        $this->increments->applyDue($employee->id); // in case the daily job hasn't run yet
        $employee->refresh()->load('increments.creator');

        $current = $this->increments->components($employee);
        $last = $employee->increments->first();
        $nextDue = $this->increments->nextDueDate($employee, $last);

        return response()->json([
            'employee' => ['id' => $employee->id, 'name' => $employee->name],
            'current_gross' => $this->increments->gross($current),
            'date_of_joining' => $employee->date_of_joining ? Carbon::parse($employee->date_of_joining)->format('d M Y') : null,
            'next_due' => $nextDue?->format('d M Y'),
            'next_due_iso' => $nextDue?->toDateString(),
            'min_date' => $last?->effective_date->copy()->addDay()->toDateString(),
            'cycle_months' => (int) config('increment.cycle_months', 12),
            'increments' => $employee->increments->map(fn (EmployeeIncrement $i) => [
                'id' => $i->id,
                'effective_date' => $i->effective_date->format('d M Y'),
                'previous_gross' => $i->previous_gross,
                'increment_amount' => $i->increment_amount,
                'new_gross' => $i->new_gross,
                'increment_percent' => $i->increment_percent,
                'remarks' => $i->remarks,
                'applied' => $i->isApplied(),
                'created_by' => $i->creator?->name,
            ])->values(),
        ]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $employee = Employee::findOrFail($id);
        $data = $request->validate([
            'effective_date' => 'required|date',
            'increment_amount' => 'required|numeric|min:1|max:10000000',
            'remarks' => 'nullable|string|max:500',
        ]);

        $date = Carbon::parse($data['effective_date'])->startOfDay();
        $last = $employee->increments()->first();
        // Increments must stay in date order so each one builds on the previous salary.
        if ($last && $date->lte($last->effective_date)) {
            throw ValidationException::withMessages([
                'effective_date' => 'Must be after the last increment (' . $last->effective_date->format('d M Y') . ').',
            ]);
        }
        if ($employee->date_of_joining && $date->lt(Carbon::parse($employee->date_of_joining))) {
            throw ValidationException::withMessages(['effective_date' => 'Cannot be before the date of joining.']);
        }

        $increment = $this->increments->record($employee, $date, (float) $data['increment_amount'], $data['remarks'] ?? null, auth()->id());

        return response()->json([
            'message' => $increment->isApplied()
                ? 'Increment saved and the salary has been updated.'
                : 'Increment saved. The salary will be updated on ' . $increment->effective_date->format('d M Y') . '.',
        ]);
    }

    /** Only an increment that hasn't been applied yet can be removed (an applied one already changed the salary). */
    public function destroy(int $id): JsonResponse
    {
        $increment = EmployeeIncrement::findOrFail($id);
        if ($increment->isApplied()) {
            return response()->json(['message' => 'This increment is already applied to the salary and cannot be deleted.'], 422);
        }
        $increment->delete();

        return response()->json(['message' => 'Scheduled increment removed.']);
    }
}

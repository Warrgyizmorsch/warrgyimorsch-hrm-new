<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Sop;
use App\Models\SopVersion;
use Illuminate\Http\Request;

class SopController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) ($request->query('show', 20));
        if (!in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = Sop::with('department')->orderBy('department_id')->orderBy('title');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")->orWhere('role', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status === 'active');
        }

        $sops = $query->paginate($perPage)->appends($request->query());
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('performance.sop.index', [
            'sops' => $sops,
            'departments' => $departments,
            'totalCount' => Sop::count(),
            'activeCount' => Sop::where('status', true)->count(),
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $sop = Sop::create($data + ['version' => 1]);

        // Record version 1 immediately so history is complete from the start.
        SopVersion::create([
            'sop_id' => $sop->id,
            'version' => $sop->version,
            'title' => $sop->title,
            'content' => $sop->content,
            'changed_by' => auth()->id(),
        ]);

        return back()->with('success', 'SOP added successfully!');
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);
        $sop = Sop::findOrFail($id);

        // Bump the version whenever the content actually changes, so prior acknowledgements
        // (tied to a specific version) no longer count and employees are prompted to re-ack.
        // The full old content is kept in sop_versions — editing never destroys history.
        if ($sop->content !== $data['content']) {
            $data['version'] = $sop->version + 1;
        }

        $sop->update($data + ['status' => $request->has('status')]);

        // updateOrCreate: a title/role-only edit doesn't bump the version, so this just
        // refreshes that version's snapshot rather than colliding on the unique constraint.
        SopVersion::updateOrCreate(
            ['sop_id' => $sop->id, 'version' => $sop->version],
            ['title' => $sop->title, 'content' => $sop->content, 'changed_by' => auth()->id()]
        );

        return back()->with('success', 'SOP updated successfully!');
    }

    public function destroy($id)
    {
        Sop::findOrFail($id)->delete();

        return back()->with('success', 'SOP deleted successfully!');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]);
    }

    /**
     * Any authenticated employee acknowledges the SOP's current version.
     */
    public function acknowledge($id)
    {
        $sop = Sop::findOrFail($id);

        $sop->acknowledgements()->updateOrCreate(
            ['user_id' => auth()->id(), 'version' => $sop->version],
            ['acknowledged_at' => now()]
        );

        return back()->with('success', 'SOP acknowledged.');
    }

    /**
     * Admin marks an employee as having acknowledged the current version on their behalf
     * (e.g. a signed paper copy, or a verbal confirmation outside the portal).
     */
    public function acknowledgeFor($id, $employeeId)
    {
        $sop = Sop::findOrFail($id);
        $employee = Employee::with('user')->findOrFail($employeeId);

        if (!$employee->user) {
            return response()->json(['success' => false, 'message' => 'This employee has no login account to attach the acknowledgement to.'], 422);
        }

        $sop->acknowledgements()->updateOrCreate(
            ['user_id' => $employee->user->id, 'version' => $sop->version],
            ['acknowledged_at' => now()]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Full revision history: every past version's title/content, who changed it, and when.
     */
    public function history($id)
    {
        $sop = Sop::with(['versions.changedBy'])->findOrFail($id);

        return response()->json([
            'sop' => $sop->only('id', 'title', 'version'),
            'versions' => $sop->versions->map(fn ($v) => [
                'version' => $v->version,
                'title' => $v->title,
                'content' => $v->content,
                'is_current' => $v->version === $sop->version,
                'changed_by' => $v->changedBy->name ?? '—',
                'changed_at' => $v->created_at->format('d M Y, h:i A'),
            ])->values(),
        ]);
    }

    /**
     * Admin view of who has/hasn't acknowledged the current version of an SOP, plus the
     * SOP's own details, so this one popup answers "what is this, and who's seen it."
     */
    public function recipients($id)
    {
        $sop = Sop::with('department')->findOrFail($id);
        $sop->load(['acknowledgements' => fn ($q) => $q->where('version', $sop->version)->with('user')]);

        $employeesQuery = Employee::active();
        if ($sop->department_id) {
            $employeesQuery->where('department_id', $sop->department_id);
        }
        if ($sop->role) {
            $role = str_replace(' ', '_', strtolower(trim($sop->role)));
            $employeesQuery->whereRaw("LOWER(REPLACE(role, ' ', '_')) = ?", [$role]);
        }
        $applicableEmployees = $employeesQuery->with('user')->get();

        $acknowledgedUserIds = $sop->acknowledgements->pluck('user_id')->all();

        $acknowledged = $sop->acknowledgements->map(fn ($a) => [
            'name' => $a->user->name ?? '—',
            'acknowledged_at' => $a->acknowledged_at->format('d M Y, h:i A'),
        ])->values();

        $pending = $applicableEmployees
            ->filter(fn ($emp) => $emp->user && !in_array($emp->user->id, $acknowledgedUserIds, true))
            ->map(fn ($emp) => ['id' => $emp->id, 'name' => $emp->name])
            ->values();

        return response()->json([
            'sop' => [
                'id' => $sop->id,
                'title' => $sop->title,
                'version' => $sop->version,
                'department' => $sop->department->name ?? 'All Departments',
                'role' => $sop->role ?: 'All Roles',
                'status' => $sop->status ? 'Active' : 'Inactive',
            ],
            'applicable_count' => $applicableEmployees->count(),
            'acknowledged' => $acknowledged,
            'pending' => $pending,
        ]);
    }
}

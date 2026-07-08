<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use App\Models\JobApplication;
use App\Models\JobRequirement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VacancyController extends Controller
{
    public function show(Request $request)
    {
        $departments = Department::select('id', 'name')->get();
        $designations = Designation::select('id', 'name')->get();
        $employees = Employee::whereIn('role', ['super_admin', 'manager', 'hr-executive', 'team_leader'])
                ->select('id', 'name')
                ->get();
        $requirements = JobRequirement::with('designation')->latest()->get();

        $selectedRole = $request->query('role');

        $applicationsQuery = JobApplication::with(['candidate', 'department', 'interviewer', 'requirement.designation']);
        $statsQuery = JobApplication::query();

        if (!empty($selectedRole)) {
            $applicationsQuery->where('designation', $selectedRole);
            $statsQuery->where('designation', $selectedRole);
        }

        if ($request->filled('stage')) {
            $applicationsQuery->where('status', $request->stage);
            $statsQuery->where('status', $request->stage);
        }

        if ($request->filled('department_id')) {
            $applicationsQuery->where('department_id', $request->department_id);
            $statsQuery->where('department_id', $request->department_id);
        }

        if ($request->filled('job_requirement_id')) {
            $applicationsQuery->where('job_requirement_id', $request->job_requirement_id);
            $statsQuery->where('job_requirement_id', $request->job_requirement_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $applicationsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $applications = $applicationsQuery->latest()->paginate($perPage)->withQueryString();

        $stageCounts = [];
        foreach (JobApplication::STAGES as $key => $label) {
            $stageCounts[$key] = (clone $statsQuery)->where('status', $key)->count();
        }

        return view('vacancy.index', compact(
            'departments', 'designations', 'employees', 'requirements',
            'applications', 'stageCounts', 'selectedRole', 'perPage'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'designation' => 'required|string|max:255',
            'job_requirement_id' => 'nullable|exists:job_requirements,id',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:50',
            'interview_date' => 'nullable|date',
            'interview_time' => 'nullable',
            'interviewer_id' => 'nullable|exists:employees,id',
            'interview_details' => 'nullable|string',
            'status' => 'nullable|in:' . implode(',', array_keys(JobApplication::STAGES)),
            'remarks' => 'nullable|string',
            'resume' => 'nullable|mimes:pdf,doc,docx|max:2048',
        ]);

        $candidate = $this->findOrCreateCandidate($validated);

        $data = $validated;
        $data['candidate_id'] = $candidate->id;
        $data['status'] = $validated['status'] ?? 'applied';

        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume')->store('resumes', 'public');
            $candidate->update(['resume' => $data['resume']]);
        }

        JobApplication::create($data);

        return back()->with('success', 'Candidate saved successfully');
    }

    public function edit($id)
    {
        $application = JobApplication::with('candidate')->findOrFail($id);

        return response()->json($application);
    }

    public function update(Request $request, $id)
    {
        $application = JobApplication::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'department_id' => 'required|exists:departments,id',
            'designation' => 'required|string|max:255',
            'job_requirement_id' => 'nullable|exists:job_requirements,id',
            'qualification' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:50',
            'interview_date' => 'nullable|date',
            'interview_time' => 'nullable',
            'interviewer_id' => 'nullable|exists:employees,id',
            'interview_details' => 'nullable|string',
            'status' => 'nullable|in:' . implode(',', array_keys(JobApplication::STAGES)),
            'remarks' => 'nullable|string',
            'resume' => 'nullable|mimes:pdf,doc,docx|max:2048',
        ]);

        if ($request->hasFile('resume')) {
            $validated['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        $application->update($validated);

        if ($application->candidate) {
            $application->candidate->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'],
                'qualification' => $validated['qualification'] ?? null,
                'experience' => $validated['experience'] ?? null,
            ]);
        }

        return back()->with('success', 'Candidate updated successfully');
    }

    public function destroy($id)
    {
        JobApplication::findOrFail($id)->delete();

        return back()->with('success', 'Candidate removed successfully');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = (array) $request->input('ids', []);

        if (empty($ids)) {
            return response()->json(['error' => 'No candidates selected.'], 400);
        }

        JobApplication::whereIn('id', $ids)->delete();

        return response()->json(['success' => 'Candidates deleted successfully!']);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(JobApplication::STAGES)),
        ]);

        $app = JobApplication::findOrFail($id);
        $wasAlreadyHired = $app->status === 'hired';
        $app->status = $request->status;
        $app->save();

        if ($app->job_requirement_id) {
            $this->syncRequirementStatus($app->requirement);
        }

        if ($request->status === 'hired') {
            if ($app->hired_employee_id) {
                return back()->with('success', 'Already linked to an employee record.');
            }

            return redirect()->route('employees.create')->withInput([
                'name' => $app->name,
                'email' => $app->email,
                'mobile_number' => $app->phone,
                'department' => $app->department->name ?? '',
                'designation' => $app->designation,
                'date_of_joining' => now()->toDateString(),
                'from_job_application_id' => $app->id,
            ])->with('success', 'Candidate marked as hired — complete their employee profile to finish onboarding.');
        }

        return back()->with('success', 'Status updated successfully');
    }

    private function syncRequirementStatus(?JobRequirement $requirement): void
    {
        if (!$requirement) {
            return;
        }

        if ($requirement->hiredApplicationsCount() >= $requirement->positions_count) {
            $requirement->update(['status' => 'hired']);
        }
    }

    private function findOrCreateCandidate(array $data): Candidate
    {
        $email = strtolower(trim($data['email']));

        return Candidate::firstOrCreate(
            ['email' => $email],
            [
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'qualification' => $data['qualification'] ?? null,
                'experience' => $data['experience'] ?? null,
            ]
        );
    }

    public function showRequirements(Request $request)
    {
        $roles = DB::table('designations')->get();
        $departments = Department::select('id', 'name')->get();

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $requirementsQuery = JobRequirement::with(['designation', 'department'])
            ->withCount('applications')
            ->withCount(['applications as hired_count' => function ($q) {
                $q->where('status', 'hired');
            }]);

        if ($request->filled('department_id')) {
            $requirementsQuery->where('department_id', $request->department_id);
        }

        $requirements = $requirementsQuery->latest()->paginate($perPage)->withQueryString();

        $designations = Designation::select('id', 'name')->get();
        $employees = Employee::whereIn('role', ['super_admin', 'manager', 'hr-executive', 'team_leader'])
                ->select('id', 'name')
                ->get();

        return view('vacancy.job_requirement', compact(
            'roles', 'requirements', 'departments', 'designations', 'employees', 'perPage'
        ));
    }

    public function storeRequirement(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:designations,id',
            'department_id' => 'nullable|exists:departments,id',
            'priority' => 'required',
            'date' => 'required',
            'candidate_type' => 'required',
            'positions_count' => 'nullable|integer|min:1',
            'skills' => 'required',
        ]);

        JobRequirement::create([
            'role_id' => $request->role_id,
            'department_id' => $request->department_id,
            'priority' => $request->priority,
            'date' => $request->date,
            'candidate_type' => $request->candidate_type,
            'minimum_experience' => $request->candidate_type == 'Experience'
                ? $request->minimum_experience
                : null,
            'positions_count' => $request->positions_count ?: 1,
            'skills' => array_map('trim', explode(',', $request->skills)),
        ]);

        return back()->with('success', 'Saved Successfully');
    }

    public function destroyRequirement($id)
    {
        JobRequirement::findOrFail($id)->delete();

        return back()->with('success', 'Requirement removed successfully');
    }

    public function updateStatusofRequirement(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:job_requirements,id',
            'status' => 'required|in:hold,hiring,hired',
        ]);

        $requirement = JobRequirement::findOrFail($request->id);
        $requirement->status = $request->status;
        $requirement->save();

        return response()->json(['success' => true]);
    }
}

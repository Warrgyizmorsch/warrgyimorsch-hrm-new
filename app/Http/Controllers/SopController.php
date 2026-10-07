<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Sop;
use App\Models\SopVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

        if ($data['content'] === null && !$request->hasFile('attachment')) {
            return $this->missingBodyError();
        }

        if ($request->hasFile('attachment')) {
            $data += $this->storeAttachment($request);
        }

        $sop = Sop::create($data + ['version' => 1]);

        // Record version 1 immediately so history is complete from the start.
        SopVersion::create([
            'sop_id' => $sop->id,
            'version' => $sop->version,
            'changed_by' => auth()->id(),
        ] + $this->snapshot($sop));

        return back()->with('success', 'SOP added successfully!');
    }

    public function update(Request $request, $id)
    {
        $data = $this->validated($request);
        $sop = Sop::findOrFail($id);

        $removeAttachment = $request->boolean('remove_attachment') && !$request->hasFile('attachment');
        $keepsAttachment = $request->hasFile('attachment') || ($sop->attachment_path && !$removeAttachment);

        if ($data['content'] === null && !$keepsAttachment) {
            return $this->missingBodyError();
        }

        $fileChanged = false;
        if ($request->hasFile('attachment')) {
            $data += $this->storeAttachment($request);
            $fileChanged = true;
        } elseif ($removeAttachment && $sop->attachment_path) {
            $data += ['attachment_path' => null, 'attachment_name' => null];
            $fileChanged = true;
        }

        // Bump the version whenever the content or document actually changes, so prior
        // acknowledgements (tied to a specific version) no longer count and employees are
        // prompted to re-ack. Old content and files are kept in sop_versions — editing never
        // destroys history (which is also why replaced files aren't deleted from disk).
        if ($sop->content !== $data['content'] || $fileChanged) {
            $data['version'] = $sop->version + 1;
        }

        $sop->update($data + ['status' => $request->has('status')]);

        // updateOrCreate: a title/role-only edit doesn't bump the version, so this just
        // refreshes that version's snapshot rather than colliding on the unique constraint.
        SopVersion::updateOrCreate(
            ['sop_id' => $sop->id, 'version' => $sop->version],
            $this->snapshot($sop) + ['changed_by' => auth()->id()]
        );

        return back()->with('success', 'SOP updated successfully!');
    }

    public function destroy($id)
    {
        $sop = Sop::with('versions')->findOrFail($id);
        $paths = $sop->versions->pluck('attachment_path')->push($sop->attachment_path)->filter()->unique();

        $sop->delete();
        Storage::disk(Sop::DISK)->delete($paths->all());

        return back()->with('success', 'SOP deleted successfully!');
    }

    /**
     * Open an SOP's document — the current one, or (admins only) a past version's from
     * history. Employees can only open SOPs that apply to them. PDFs/images open in the
     * browser; Word/Excel/PowerPoint download.
     */
    public function file(Request $request, $id)
    {
        $sop = Sop::findOrFail($id);
        $isAdmin = $this->isAdmin();

        if (!$isAdmin) {
            $employee = auth()->user()->employee;
            $applies = $employee && $sop->status && Sop::whereKey($sop->id)->applicableTo($employee)->exists();
            if (!$applies) {
                abort(403);
            }
        }

        $source = $sop;
        if ($request->filled('version') && (int) $request->version !== $sop->version) {
            abort_unless($isAdmin, 403);
            $source = $sop->versions()->where('version', (int) $request->version)->firstOrFail();
        }

        $disk = Storage::disk(Sop::DISK);
        if (!$source->attachment_path || !$disk->exists($source->attachment_path)) {
            abort(404, 'File not found.');
        }

        $name = $source->attachment_name ?: basename($source->attachment_path);
        $mime = $disk->mimeType($source->attachment_path);
        $inline = str_starts_with((string) $mime, 'image/') || $mime === 'application/pdf';

        return $inline ? $disk->response($source->attachment_path, $name) : $disk->download($source->attachment_path, $name);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'department_id' => 'nullable|exists:departments,id',
            'role' => 'nullable|string|max:100',
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'attachment' => 'nullable|' . Sop::ATTACHMENT_RULES,
        ], [
            'attachment.mimes' => 'The document must be a PDF, Word, Excel, PowerPoint or image file.',
            'attachment.max' => 'The document may not be larger than 10 MB.',
        ]);

        unset($data['attachment']);

        // The editor submits "<p><br></p>" when left empty — treat that as no content.
        $text = trim(html_entity_decode(strip_tags((string) ($data['content'] ?? '')), ENT_QUOTES | ENT_HTML5), " \t\n\r\0\x0B\xC2\xA0");
        if ($text === '' && !preg_match('/<(img|table|iframe)\b/i', (string) ($data['content'] ?? ''))) {
            $data['content'] = null;
        }

        return $data;
    }

    private function storeAttachment(Request $request): array
    {
        $file = $request->file('attachment');

        return [
            'attachment_path' => $file->store('sops', Sop::DISK),
            'attachment_name' => $file->getClientOriginalName(),
        ];
    }

    private function snapshot(Sop $sop): array
    {
        return $sop->only('title', 'content', 'attachment_path', 'attachment_name');
    }

    private function missingBodyError()
    {
        return back()
            ->withErrors(['content' => 'Write the procedure or upload a document — at least one is required.'])
            ->withInput();
    }

    private function isAdmin(): bool
    {
        $role = str_replace(' ', '_', strtolower(trim((string) (auth()->user()->role ?? 'employee'))));

        return in_array($role, ['super_admin', 'manager', 'hr_executive', 'hr_intern', 'business_operation_head'], true);
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
                'attachment_name' => $v->attachment_name,
                'attachment_url' => $v->attachment_path ? route('sops.file', ['id' => $sop->id, 'version' => $v->version]) : null,
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

<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Designation;
use App\Models\Project;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MasterController extends Controller
{
    public function departments(Request $request)
    {
        [$departments, $stats, $perPage] = $this->masterListing(
            Department::class,
            $request,
            ['name', 'short_name']
        );

        $allProjects = Project::select('id', 'name', 'slug', 'status', 'department')->get();

        $projectsByDepartment = [];
        foreach ($departments as $dept) {
            $projectsByDepartment[$dept->id] = $allProjects->filter(function ($project) use ($dept) {
                return in_array($dept->name, (array) $project->department, true);
            })->values();
        }

        return view('master.departments', [
            'departments' => $departments,
            'totalCount' => $stats['total'],
            'activeCount' => $stats['active'],
            'perPage' => $perPage,
            'projectsByDepartment' => $projectsByDepartment,
        ]);
    }

    public function designations(Request $request)
    {
        [$designations, $stats, $perPage] = $this->masterListing(
            Designation::class,
            $request,
            ['name', 'short_name']
        );

        return view('master.designations', [
            'designations' => $designations,
            'totalCount' => $stats['total'],
            'activeCount' => $stats['active'],
            'perPage' => $perPage,
        ]);
    }

    public function roles(Request $request)
    {
        [$roles, $stats, $perPage] = $this->masterListing(
            Role::class,
            $request,
            ['name', 'slug']
        );

        return view('master.roles', [
            'roles' => $roles,
            'totalCount' => $stats['total'],
            'activeCount' => $stats['active'],
            'perPage' => $perPage,
        ]);
    }

    private function masterListing(string $modelClass, Request $request, array $searchColumns): array
    {
        $perPage = (int) ($request->query('show', 20));
        if (! in_array($perPage, [20, 50, 100], true)) {
            $perPage = 20;
        }

        $query = $modelClass::query()->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search, $searchColumns) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $items = $query->paginate($perPage)->appends($request->query());

        return [
            $items,
            [
                'total' => $modelClass::count(),
                'active' => $modelClass::where('is_active', true)->count(),
            ],
            $perPage,
        ];
    }

    // DEPARTMENT
    public function storeDepartment(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Department::create($request->only('name', 'short_name'));
        return redirect()->route('master.departments')->with('success', 'Department added successfully!');
    }

    public function updateDepartment(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $dept = Department::findOrFail($id);
        $dept->update([
            'name' => $request->name,
            'short_name' => $request->short_name,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);
        return redirect()->route('master.departments')->with('success', 'Department updated successfully!');
    }

    public function destroyDepartment($id)
    {
        Department::findOrFail($id)->delete();
        return redirect()->route('master.departments')->with('success', 'Department deleted successfully!');
    }

    // DESIGNATION
    public function storeDesignation(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Designation::create($request->only('name', 'short_name'));
        return redirect()->route('master.designations')->with('success', 'Designation added successfully!');
    }

    public function updateDesignation(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $desg = Designation::findOrFail($id);
        $desg->update([
            'name' => $request->name,
            'short_name' => $request->short_name,
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);
        return redirect()->route('master.designations')->with('success', 'Designation updated successfully!');
    }

    public function destroyDesignation($id)
    {
        Designation::findOrFail($id)->delete();
        return redirect()->route('master.designations')->with('success', 'Designation deleted successfully!');
    }

    // ROLE
    public function storeRole(Request $request)
    {
        $request->validate(['name' => 'required|string|max:255']);
        Role::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name, '_'),
        ]);
        return redirect()->route('master.roles')->with('success', 'Role added successfully!');
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:255']);
        $role = Role::findOrFail($id);
        $role->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name, '_'),
            'is_active' => $request->has('is_active') ? 1 : 0,
        ]);
        return redirect()->route('master.roles')->with('success', 'Role updated successfully!');
    }

    public function destroyRole($id)
    {
        Role::findOrFail($id)->delete();
        return redirect()->route('master.roles')->with('success', 'Role deleted successfully!');
    }
}

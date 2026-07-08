<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use Illuminate\Http\Request;

class CandidateController extends Controller
{
    public function index(Request $request)
    {
        $query = Candidate::withCount('applications');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $candidates = $query->latest()->paginate($perPage)->withQueryString();

        return view('candidates.index', compact('candidates', 'perPage'));
    }

    public function show($id)
    {
        $candidate = Candidate::with(['applications' => function ($q) {
            $q->with(['department', 'requirement.designation', 'interviewer'])->latest();
        }])->findOrFail($id);

        return view('candidates.show', compact('candidate'));
    }
}

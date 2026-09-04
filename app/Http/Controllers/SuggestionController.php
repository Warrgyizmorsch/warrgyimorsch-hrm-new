<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = Suggestion::with('user.employee')->orderBy('created_at', 'desc');

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere(function ($nameQ) use ($search) {
                        $nameQ->where('is_anonymous', false)
                            ->whereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'like', "%{$search}%");
                            });
                    });
            });
        }

        $suggestions = $query->paginate($perPage)->withQueryString();

        $totalSuggestions = Suggestion::count();
        $thisMonth = Suggestion::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();

        return view('suggestions.index', compact('suggestions', 'perPage', 'totalSuggestions', 'thisMonth'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|in:' . implode(',', Suggestion::CATEGORIES),
            'message' => 'required|string|max:5000',
            'is_anonymous' => 'nullable|boolean',
        ]);

        $isAnonymous = $request->boolean('is_anonymous');

        Suggestion::create([
            'user_id' => $isAnonymous ? null : auth()->id(),
            'is_anonymous' => $isAnonymous,
            'category' => $validated['category'],
            'message' => $validated['message'],
        ]);

        return back()->with('success', 'Thank you! Your suggestion has been submitted.');
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|string|in:' . implode(',', Suggestion::STATUSES),
            'reply' => 'nullable|string|max:3000',
        ]);

        $suggestion = Suggestion::findOrFail($id);
        $suggestion->status = $validated['status'];

        if (!empty($validated['reply'])) {
            $suggestion->manager_reply = $validated['reply'];
            $suggestion->replied_by = auth()->id();
            $suggestion->replied_at = now();
        }

        $suggestion->save();

        return response()->json([
            'success' => true,
            'status' => $suggestion->status,
            'manager_reply' => $suggestion->manager_reply,
            'replied_by' => $suggestion->repliedBy?->name,
            'replied_at' => $suggestion->replied_at?->diffForHumans(),
        ]);
    }

    public function toggleAppreciation($id)
    {
        $suggestion = Suggestion::findOrFail($id);
        $suggestion->appreciated = !$suggestion->appreciated;
        $suggestion->save();

        return response()->json([
            'success' => true,
            'appreciated' => $suggestion->appreciated,
        ]);
    }
}

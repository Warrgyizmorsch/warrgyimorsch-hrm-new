<?php

namespace App\Http\Controllers;
use App\Models\Broadcast;
use App\Models\Department;
use Carbon\Carbon;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BroadcastController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', 20);
        $allowedPerPage = [20, 50, 100];

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = Broadcast::withCount('readByUsers')->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('department', 'like', "%{$search}%");
            });
        }

        $broadcasts = $query->paginate($perPage)->withQueryString();
        $departments = Department::orderBy('name', 'asc')->get();

        $totalBroadcasts = Broadcast::count();
        $sentThisMonth = Broadcast::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $totalReads = DB::table('broadcast_user')->count();
        $avgReads = $totalBroadcasts > 0 ? round($totalReads / $totalBroadcasts, 1) : 0;

        return view('broadcast.index', compact(
            'broadcasts', 'departments', 'perPage',
            'totalBroadcasts', 'sentThisMonth', 'totalReads', 'avgReads'
        ));
    }

    // Save standard entries
    public function store(Request $request)
    {
        $validated = $request->validate([
            'department' => 'required|string',
            'message'    => 'required|string|max:5000',
        ]);

        Broadcast::create($validated);

        return redirect()->route('broadcasts.index')->with('success', 'Broadcast created successfully.');
    }

    // Capture the target record and serve it back to the view workspace
    public function edit($id)
    {
        $broadcasts = Broadcast::orderBy('created_at', 'desc')->get();
        $broadcastToEdit = Broadcast::findOrFail($id);
        $departments = Department::orderBy('name', 'asc')->get();

        return view('broadcast.index', compact('broadcasts', 'broadcastToEdit', 'departments'));
    }

    // Handle updates 
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'department' => 'required|string',
            'message'    => 'required|string|max:5000',
        ]);

        $broadcast = Broadcast::findOrFail($id);
        $broadcast->update($validated);

        return redirect()->route('broadcasts.index')->with('success', 'Broadcast updated successfully.');
    }

    public function markAsRead($id)
    {
        $broadcast = Broadcast::findOrFail($id);
        
        // Attach the current user to the pivot table with a timestamp if not already attached
        $broadcast->readByUsers()->syncWithoutDetaching([
            auth()->id() => ['read_at' => Carbon::now()]
        ]);

        return response()->json(['success' => true]);
    }

    public function getRecipients($id)
    {
        $broadcast = Broadcast::with('readByUsers')->findOrFail($id);
        // dd($broadcast->readByUsers);
        // Map the relationship collection into a clean array structure for your AJAX modal
        $recipients = $broadcast->readByUsers->map(function($user) {
            return [
                'name'     => $user->name,
                'time_ago' => $user->pivot->read_at
                    ? Carbon::parse($user->pivot->read_at)->diffForHumans()
                    : 'Just now'
            ];
        });

        return response()->json($recipients);
    }
}

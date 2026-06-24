<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\Request;

use function PHPSTORM_META\type;

class TicketController extends Controller
{
    public function store(Request $request){
        $request->validate([
            'type'=> 'required',
            'description' => 'required'
        ]);
        Ticket::create([
            'user_id' => Auth()->id(),
            'type' => $request->type,
            'description' => $request->description,
            'status' => 'Open',
        ]);

        return back()->with('success', 'Ticket Raised Successfully.');
    }

    public function index() 
    { 
        $user = auth()->user();

        if ($user->role == 'super_admin') {
            $tickets = Ticket::with('user')->latest()->get();

        // } elseif ($user->role == 'team_leader') {
        //     $tickets = Ticket::whereHas('user', function ($query) use ($user) {
        //         $query->where('department_id', $user->department_id);
        //     })->with('user')
        //     ->latest()
        //     ->get();

        } else {
            $tickets = Ticket::where(
                'user_id',
                $user->id
            )->latest()->get();
        }
        return view('tickets.index', compact('tickets')
        );
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required',
            'comment' => 'nullable|string'
        ]);

        $ticket = Ticket::findOrFail($id);
        $oldStatus = $ticket->status;

        $ticket->update([
            'status' => $request->status
        ]);

        // Save status history tracking
        \App\Models\TicketStatusHistory::create([
            'ticket_id' => $ticket->id,
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'comment' => $request->comment,
            'updated_by' => auth()->id(),
        ]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => 'Status updated successfully.'
            ]);
        }

        return redirect()->back()->with('success', 'Status updated successfully.');
    }

    public function history($id)
    {
        $history = \App\Models\TicketStatusHistory::where('ticket_id', $id)
            ->with('user')
            ->latest()
            ->get();

        return response()->json($history);
    }
}

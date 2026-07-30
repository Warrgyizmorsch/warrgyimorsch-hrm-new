<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\Http\Request;

class NoteController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:' . implode(',', Note::TYPES),
            'title' => 'required|string|max:255',
            'remind_at' => 'nullable|date',
        ]);

        $note = Note::create([
            'user_id' => auth()->id(),
            'type' => $validated['type'],
            'title' => $validated['title'],
            'remind_at' => $validated['remind_at'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'note' => [
                'id' => $note->id,
                'type' => $note->type,
                'title' => $note->title,
                'remind_at' => $note->remind_at?->format('d M, h:i A'),
                'remind_at_iso' => $note->remind_at?->toIso8601String(),
                'is_completed' => $note->is_completed,
            ],
        ]);
    }

    public function toggle($id)
    {
        $note = Note::where('user_id', auth()->id())->findOrFail($id);
        $note->is_completed = !$note->is_completed;
        $note->save();

        return response()->json(['success' => true, 'is_completed' => $note->is_completed]);
    }

    public function destroy($id)
    {
        $note = Note::where('user_id', auth()->id())->findOrFail($id);
        $note->delete();

        return response()->json(['success' => true]);
    }
}

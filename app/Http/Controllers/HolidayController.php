<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
 public function index(Request $request)
{
    $search = $request->search;
    $perPage = $request->show ?? 10;

    if ($perPage > 20) {
        $perPage = 20;
    }

    $holidays = Holiday::when($search, function ($q) use ($search) {
        $q->where('title', 'like', "%$search%");
    })
    ->orderBy('date', 'desc')
    ->paginate($perPage)
    ->withQueryString();

    $today = now()->startOfDay();
    $yearStart = now()->startOfYear();
    $yearEnd = now()->endOfYear();

    $totalCount = Holiday::count();
    $upcomingCount = Holiday::whereDate('date', '>=', $today)->count();
    $thisYearCount = Holiday::whereBetween('date', [$yearStart, $yearEnd])->count();
    $nextHoliday = Holiday::whereDate('date', '>=', $today)->orderBy('date')->first();

    return view('holidays.index', compact(
        'holidays',
        'totalCount',
        'upcomingCount',
        'thisYearCount',
        'nextHoliday',
        'perPage'
    ));
}

    public function store(Request $request)
{
    $request->validate([
        'title' => 'required|string|max:255',
        'date' => 'required|date',
    ]);

    $holiday = Holiday::create([
        'title' => strtoupper($request->title),
        'date' => $request->date,
    ]);

    if ($request->expectsJson() || $request->wantsJson()) {
        return response()->json(['success' => true, 'id' => $holiday->id]);
    }

    return back()->with('success', 'Holiday added');
}
    public function edit($id)
{
    $holiday = Holiday::findOrFail($id);
    return view('holidays.edit', compact('holiday'));
}

public function update(Request $request, $id)
{
    $holiday = Holiday::findOrFail($id);

    $holiday->update([
        'title' => strtoupper($request->title),
        'date' => $request->date,
    ]);

    return redirect()->route('holidays.index')->with('success', 'Updated');
}

public function destroy($id)
{
    Holiday::findOrFail($id)->delete();

    if (request()->expectsJson() || request()->wantsJson()) {
        return response()->json(['success' => true]);
    }

    return redirect()->route('holidays.index')->with('success', 'Deleted');
}
}
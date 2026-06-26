<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function index()
    {
        $assets = Asset::with('activeAllocation.user')
            ->get()
            ->sortBy([
                fn ($asset) => $asset->activeAllocation ? 0 : 1,
                fn ($asset) => strtolower($asset->activeAllocation->user->name ?? ''),
                fn ($asset) => strtolower($asset->name ?? ''),
            ])
            ->values();
        $requests = AssetRequest::with(['user', 'asset'])->latest()->get();
        $availableAssets = Asset::where('status', 'Available')->get();
        $users = \App\Models\User::orderBy('name')->get();

        return view('assets.index', compact('assets', 'requests', 'availableAssets', 'users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'assets' => 'required|array',
            'assets.*.name' => 'required|string',
            'assets.*.type' => 'required|string',
            'assets.*.serial_number' => 'nullable|string|distinct|unique:assets,serial_number',
            'assets.*.system_configuration' => 'nullable|string',
            'assets.*.status' => 'required|in:Available,Allocated,Maintenance,Faulty',
            'assign_user_id' => 'nullable|exists:users,id',
        ]);

        if (
            !$request->filled('assign_user_id') &&
            collect($request->assets)->contains(fn ($asset) => ($asset['status'] ?? null) === 'Allocated')
        ) {
            return back()
                ->withInput()
                ->with('error', 'Please select one employee at the top before adding assets as Allocated.');
        }

        DB::transaction(function () use ($request) {
            foreach ($request->assets as $assetData) {
                if ($request->filled('assign_user_id')) {
                    $assetData['status'] = 'Allocated';
                }

                $asset = Asset::create($assetData);

                if ($request->filled('assign_user_id')) {
                    AssetRequest::create([
                        'user_id' => $request->assign_user_id,
                        'asset_id' => $asset->id,
                        'asset_type' => $asset->type,
                        'reason' => 'Allocated while adding inventory',
                        'status' => 'Allocated',
                        'allocated_at' => now(),
                    ]);
                }
            }
        });

        return back()->with('success', 'Assets added successfully.');
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        $request->validate([
            'name' => 'required|string',
            'type' => 'required|string',
            'serial_number' => 'nullable|string|unique:assets,serial_number,' . $id,
            'system_configuration' => 'nullable|string',
            'status' => 'required|in:Available,Allocated,Maintenance,Faulty',
        ]);

        $asset->update($request->only(['name', 'type', 'serial_number', 'system_configuration', 'status']));

        return back()->with('success', 'Asset updated successfully.');
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();

        return back()->with('success', 'Asset deleted successfully.');
    }

    public function allocate(Request $request, $id)
    {
        $assetRequest = AssetRequest::findOrFail($id);

        $request->validate([
            'asset_id' => 'required|exists:assets,id',
        ]);

        $asset = Asset::findOrFail($request->asset_id);

        if ($asset->status !== 'Available') {
            return back()->with('error', 'Selected asset is not available.');
        }

        $assetRequest->update([
            'asset_id' => $asset->id,
            'status' => 'Allocated',
            'allocated_at' => now(),
        ]);

        $asset->update([
            'status' => 'Allocated',
        ]);

        return back()->with('success', 'Asset allocated successfully.');
    }

    public function reject($id)
    {
        $assetRequest = AssetRequest::findOrFail($id);

        $assetRequest->update([
            'status' => 'Rejected',
        ]);

        return back()->with('success', 'Asset request rejected.');
    }

    public function markReturned(Request $request, $id)
    {
        $assetRequest = AssetRequest::findOrFail($id);

        if ($assetRequest->status !== 'Allocated' || !$assetRequest->asset_id) {
            return back()->with('error', 'Request is not in allocated state.');
        }

        $asset = Asset::findOrFail($assetRequest->asset_id);
        $condition = $request->input('condition', 'Good');

        $assetRequest->update([
            'status' => 'Returned',
            'returned_at' => now(),
        ]);

        if ($condition === 'Maintenance') {
            $asset->update([
                'status' => 'Maintenance',
            ]);
            $msg = 'Asset marked as returned and sent to Maintenance.';
        } elseif ($condition === 'Faulty') {
            $asset->update([
                'status' => 'Faulty',
            ]);
            $msg = 'Asset marked as returned and set to Faulty (permanent damage).';
        } else {
            $asset->update([
                'status' => 'Available',
            ]);
            $msg = 'Asset returned successfully and is now Available.';
        }

        return back()->with('success', $msg);
    }

    // Employee Views & Actions
    public function employeeView()
    {
        $userRequests = AssetRequest::where('user_id', auth()->id())
            ->with('asset')
            ->latest()
            ->get();

        $allocatedAssets = AssetRequest::where('user_id', auth()->id())
            ->where('status', 'Allocated')
            ->with('asset')
            ->get();

        return view('assets.employee', compact('userRequests', 'allocatedAssets'));
    }

    public function requestAsset(Request $request)
    {
        $request->validate([
            'asset_type' => 'required|string',
            'reason' => 'required|string',
        ]);

        AssetRequest::create([
            'user_id' => auth()->id(),
            'asset_type' => $request->asset_type,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        return back()->with('success', 'Asset request submitted successfully.');
    }

    public function allocateManual(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        if ($asset->status !== 'Available') {
            return back()->with('error', 'Selected asset is not available.');
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        AssetRequest::create([
            'user_id' => $request->user_id,
            'asset_id' => $asset->id,
            'asset_type' => $asset->type,
            'reason' => 'Manually allocated by Admin',
            'status' => 'Allocated',
            'allocated_at' => now(),
        ]);

        $asset->update([
            'status' => 'Allocated',
        ]);

        return back()->with('success', 'Asset allocated manually successfully.');
    }
}

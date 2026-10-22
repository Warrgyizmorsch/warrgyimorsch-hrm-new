<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetIncident;
use App\Models\AssetRequest;
use App\Models\User;
use App\Services\AssetService;
use App\Support\AccessControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssetController extends Controller
{
    /** Optional asset photo: JPG/PNG/WebP up to 2 MB. */
    private const IMAGE_RULE = 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048';

    public function __construct(private AssetService $assets)
    {
    }

    /** Validation for the register fields shared by add / edit-details. $prefix = 'assets.*.' for the batch form. */
    public static function registerRules(string $prefix = ''): array
    {
        return [
            $prefix . 'brand' => 'nullable|string|max:100',
            $prefix . 'model' => 'nullable|string|max:100',
            $prefix . 'condition' => ['nullable', Rule::in(Asset::CONDITIONS)],
            $prefix . 'location' => 'nullable|string|max:150',
            $prefix . 'purchase_date' => 'nullable|date|before_or_equal:today',
            $prefix . 'purchase_cost' => 'nullable|numeric|min:0|max:9999999999',
            $prefix . 'vendor' => 'nullable|string|max:150',
            $prefix . 'invoice_number' => 'nullable|string|max:100',
            $prefix . 'warranty_expiry' => 'nullable|date',
            $prefix . 'notes' => 'nullable|string|max:2000',
        ];
    }

    private const REGISTER_FIELDS = [
        'brand', 'model', 'condition', 'location', 'purchase_date', 'purchase_cost',
        'vendor', 'invoice_number', 'warranty_expiry', 'notes',
    ];

    public function index()
    {
        $user = auth()->user();
        $isTeamLeader = AccessControl::isTeamLeader($user);
        $isAdmin = AccessControl::isAdmin($user);

        $assetsQuery = Asset::with(['activeAllocation.user.employee.departmentRef']);
        $requestsQuery = AssetRequest::with(['user.employee.departmentRef', 'asset', 'creator', 'verifier'])->latest();
        $teamUserIds = collect();

        if ($isTeamLeader) {
            // Team leaders: only assets held by employee-level staff in the departments they lead.
            $teamUserIds = AccessControl::assetTeamUserIds($user);

            $assetsQuery->whereHas('activeAllocation', fn ($q) => $q->whereIn('user_id', $teamUserIds));
            $requestsQuery->whereIn('user_id', $teamUserIds);
        }

        $assets = $assetsQuery->get()
            ->sortBy([
                fn ($asset) => $asset->activeAllocation ? 0 : 1,
                fn ($asset) => strtolower($asset->activeAllocation->user->name ?? ''),
                fn ($asset) => strtolower($asset->name ?? ''),
            ])
            ->values();
        $requests = $requestsQuery->get();
        $availableAssets = $isTeamLeader ? collect() : Asset::where('status', 'Available')->orderBy('asset_tag')->get();
        $users = ($isTeamLeader ? User::whereIn('id', $teamUserIds) : User::query())
            ->where(fn ($q) => $q->whereNull('account_status')->orWhere('account_status', 'active'))
            ->orderBy('name')->get();

        // Devices still allocated to employees whose account has since been deactivated.
        $deactivatedAllocations = $isAdmin
            ? AssetRequest::where('status', 'Allocated')
                ->whereHas('user', fn ($q) => $q->where('account_status', 'inactive'))
                ->with(['user', 'asset'])
                ->get()
            : collect();

        // Declarations this viewer may verify/reject (never their own, never ones they entered).
        $verifiableIds = $requests
            ->filter(fn ($r) => AccessControl::canVerifyDeclaration($r, $user, $teamUserIds))
            ->pluck('id');

        return view('assets.index', compact('assets', 'requests', 'availableAssets', 'users', 'deactivatedAllocations', 'verifiableIds'));
    }

    /** Full record of one asset: details, current holder, handover history, incidents, timeline. */
    public function show($id)
    {
        $asset = Asset::withTrashed()->with([
            'activeAllocation.user.employee.departmentRef',
            'activeAllocation.allocatedBy',
            'allocations.user', 'allocations.allocatedBy', 'allocations.returnedBy',
            'incidents.user', 'incidents.reporter', 'incidents.resolver',
        ])->findOrFail($id);

        $authUser = auth()->user();
        $isAdmin = AccessControl::isAdmin($authUser);
        $teamUserIds = $isAdmin ? collect() : AccessControl::assetTeamUserIds($authUser);
        $holderId = $asset->activeAllocation?->user_id;

        abort_unless($isAdmin || ($holderId && $teamUserIds->contains($holderId)), 403, 'Unauthorized access');

        $events = $asset->events()->with(['performer', 'fromUser', 'toUser', 'incident'])->limit(300)->get();

        $users = ($isAdmin ? User::query() : User::whereIn('id', $teamUserIds))
            ->where(fn ($q) => $q->whereNull('account_status')->orWhere('account_status', 'active'))
            ->orderBy('name')->get(['id', 'name', 'role']);

        return view('assets.show', compact('asset', 'events', 'users', 'isAdmin'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'assets' => 'required|array|min:1',
            'assets.*.name' => 'required|string|max:150',
            'assets.*.type' => 'required|string|max:100',
            'assets.*.serial_number' => 'nullable|string|max:150|distinct|unique:assets,serial_number',
            'assets.*.system_configuration' => 'nullable|string',
            'assets.*.status' => ['required', Rule::in(AssetService::STATUSES)],
            'assets.*.image' => self::IMAGE_RULE,
            'assign_user_id' => 'nullable|exists:users,id',
        ] + self::registerRules('assets.*.'));

        if (
            !$request->filled('assign_user_id') &&
            collect($request->assets)->contains(fn ($asset) => ($asset['status'] ?? null) === 'Allocated')
        ) {
            return back()->withInput()
                ->with('error', 'Please select one employee at the top before adding assets as Allocated.');
        }

        $uploaded = [];

        $response = $this->attempt(function () use ($request, &$uploaded) {
            DB::transaction(function () use ($request, &$uploaded) {
                foreach ($request->assets as $index => $row) {
                    $data = collect($row)
                        ->only(array_merge(['name', 'type', 'serial_number', 'system_configuration', 'status'], self::REGISTER_FIELDS))
                        ->map(fn ($v) => $v === '' ? null : $v)
                        ->all();
                    $data['condition'] = $data['condition'] ?? 'Good';

                    if ($file = $request->file("assets.$index.image")) {
                        $data['image_path'] = $uploaded[] = $file->store('assets', Asset::IMAGE_DISK);
                    }

                    if ($request->filled('assign_user_id')) {
                        // Create in stock, then allocate through the service (checks the user is active).
                        $data['status'] = 'Available';
                        $asset = Asset::create($data);
                        $this->assets->allocate($asset, (int) $request->assign_user_id, 'Allocated while adding inventory');
                    } else {
                        Asset::create($data);
                    }
                }
            });

            $uploaded = []; // saved: keep the files

            return back()->with('success', 'Assets added successfully.');
        });

        // The save failed and was rolled back, so drop any photos stored for it.
        foreach ($uploaded as $path) {
            Storage::disk(Asset::IMAGE_DISK)->delete($path);
        }

        return $response;
    }

    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $authUser = auth()->user();
        $isTeamLeader = AccessControl::isTeamLeader($authUser);

        if ($isTeamLeader) {
            $teamUserIds = AccessControl::assetTeamUserIds($authUser);
            $currentAllocation = $asset->activeAllocation;

            if (!$currentAllocation || !$teamUserIds->contains($currentAllocation->user_id)) {
                abort(403, 'Unauthorized access');
            }
        }

        $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|string|max:100',
            'serial_number' => ['nullable', 'string', 'max:150', Rule::unique('assets', 'serial_number')->ignore($asset->id)],
            'system_configuration' => 'nullable|string',
            // Team leaders can't change status (the field is disabled for them); it stays Allocated.
            'status' => $isTeamLeader ? 'nullable' : ['required', Rule::in(Asset::STATUSES)],
            'assign_user_id' => $isTeamLeader ? 'required|exists:users,id' : 'nullable|exists:users,id',
            'image' => self::IMAGE_RULE,
            'remove_image' => 'nullable|boolean',
        ]);

        if ($isTeamLeader && !$teamUserIds->contains((int) $request->assign_user_id)) {
            abort(403, 'Unauthorized access');
        }

        $status = $isTeamLeader ? 'Allocated' : $request->status;
        $holder = $request->filled('assign_user_id') ? (int) $request->assign_user_id : null;

        $oldImage = $asset->image_path;
        $newImage = $request->hasFile('image') ? $request->file('image')->store('assets', Asset::IMAGE_DISK) : null;
        $removeImage = $request->boolean('remove_image');

        $response = $this->attempt(function () use ($asset, $request, $holder, $status, $newImage, $removeImage) {
            DB::transaction(function () use ($asset, $request, $holder, $status, $newImage, $removeImage) {
                $data = $request->only(['name', 'type', 'serial_number', 'system_configuration']);
                if ($newImage) {
                    $data['image_path'] = $newImage;
                } elseif ($removeImage) {
                    $data['image_path'] = null;
                }

                $asset->update($data);
                $this->assets->applyHolderAndStatus($asset, $holder, $status);
            });

            return back()->with('success', 'Asset updated successfully.');
        });

        $this->cleanupImages($asset, $oldImage, $newImage);

        return $response;
    }

    /** Register details from the asset page (admin): name, make, purchase, warranty, location... */
    public function updateDetails(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:150',
            'type' => 'required|string|max:100',
            'serial_number' => ['nullable', 'string', 'max:150', Rule::unique('assets', 'serial_number')->ignore($asset->id)],
            'system_configuration' => 'nullable|string',
            'image' => self::IMAGE_RULE,
            'remove_image' => 'nullable|boolean',
        ] + self::registerRules());

        $oldImage = $asset->image_path;
        $newImage = $request->hasFile('image') ? $request->file('image')->store('assets', Asset::IMAGE_DISK) : null;

        $data = collect($data)->except(['image', 'remove_image'])->map(fn ($v) => $v === '' ? null : $v)->all();
        $data['condition'] = $data['condition'] ?? $asset->condition;
        if ($newImage) {
            $data['image_path'] = $newImage;
        } elseif ($request->boolean('remove_image')) {
            $data['image_path'] = null;
        }

        $asset->update($data);
        $this->cleanupImages($asset, $oldImage, $newImage);

        return back()->with('success', 'Asset details saved.');
    }

    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);

        return $this->attempt(function () use ($asset) {
            $this->assets->delete($asset);

            return redirect()->route('assets.index')->with('success', 'Asset deleted successfully.');
        });
    }

    public function allocate(Request $request, $id)
    {
        $assetRequest = AssetRequest::findOrFail($id);
        if ($assetRequest->isDeclaration()) {
            return back()->with('error', 'Declarations are verified, not allocated. Use Verify instead.');
        }
        $request->validate(['asset_id' => 'required|exists:assets,id']);
        $asset = Asset::findOrFail($request->asset_id);

        return $this->attempt(function () use ($asset, $assetRequest) {
            $this->assets->allocate($asset, (int) $assetRequest->user_id, $assetRequest->reason ?? 'Requested', $assetRequest);

            return back()->with('success', 'Asset allocated. The employee will be asked to confirm receipt.');
        });
    }

    public function reject($id)
    {
        $assetRequest = AssetRequest::findOrFail($id);

        return $this->attempt(function () use ($assetRequest) {
            $this->assets->reject($assetRequest);

            return back()->with('success', 'Asset request rejected.');
        });
    }

    /** Return from an allocation row (inventory list / allocated tab). */
    public function markReturned(Request $request, $id)
    {
        $assetRequest = AssetRequest::findOrFail($id);

        if ($assetRequest->status !== 'Allocated' || !$assetRequest->asset_id) {
            return back()->with('error', 'Request is not in allocated state.');
        }

        $asset = Asset::withTrashed()->findOrFail($assetRequest->asset_id);

        return $this->returnInto($request, $asset);
    }

    /** Return from the asset page. */
    public function returnAsset(Request $request, $id)
    {
        return $this->returnInto($request, Asset::findOrFail($id));
    }

    public function allocateManual(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'condition' => ['nullable', Rule::in(Asset::CONDITIONS)],
            'note' => 'nullable|string|max:500',
        ]);

        return $this->attempt(function () use ($asset, $data) {
            $this->assets->allocate($asset, (int) $data['user_id'], ($data['note'] ?? null) ?: 'Manually allocated by Admin', null, array_filter([
                'condition' => $data['condition'] ?? null,
            ]));

            return back()->with('success', 'Asset allocated. The employee will be asked to confirm receipt.');
        });
    }

    /** Hand an allocated asset straight to another employee (HR/Admin: anyone; TL: within their team). */
    public function transfer(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $authUser = auth()->user();

        $data = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'reason' => 'required|string|max:500',
            'condition' => ['nullable', Rule::in(Asset::CONDITIONS)],
        ]);

        if (!AccessControl::isAdmin($authUser)) {
            $team = AccessControl::assetTeamUserIds($authUser);
            $holderId = $asset->activeAllocation?->user_id;
            abort_unless($holderId && $team->contains($holderId) && $team->contains((int) $data['to_user_id']), 403, 'You can only move assets between members of your own team.');
        }

        return $this->attempt(function () use ($asset, $data) {
            $this->assets->transfer($asset, (int) $data['to_user_id'], $data['reason'], array_filter(['condition' => $data['condition'] ?? null]));

            return back()->with('success', 'Asset transferred. The new holder will be asked to confirm receipt.');
        });
    }

    public function retire(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $data = $request->validate(['reason' => 'required|string|max:500']);

        return $this->attempt(function () use ($asset, $data) {
            $this->assets->retire($asset, $data['reason']);

            return back()->with('success', 'Asset retired. It stays in the register for history.');
        });
    }

    public function reactivate(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $data = $request->validate(['note' => 'nullable|string|max:500']);

        return $this->attempt(function () use ($asset, $data) {
            $this->assets->reactivate($asset, $data['note'] ?? null);

            return back()->with('success', 'Asset is back in stock as Available.');
        });
    }

    // Employee Views & Actions
    public function employeeView()
    {
        $userRequests = AssetRequest::where('user_id', auth()->id())
            ->with(['asset', 'creator'])
            ->latest()
            ->get();

        $allocatedAssets = AssetRequest::where('user_id', auth()->id())
            ->where('status', 'Allocated')
            ->with(['asset.openIncidents', 'allocatedBy'])
            ->get()
            ->sortBy(fn ($a) => $a->isAwaitingAcknowledgement() ? 0 : 1)
            ->values();

        $myIncidents = AssetIncident::where('user_id', auth()->id())
            ->with('asset')
            ->latest()
            ->get();

        // Team leaders also see (read-only) devices allocated to the team members they lead.
        $teamUserIds = AccessControl::assetTeamUserIds();
        $teamAllocatedAssets = $teamUserIds->isEmpty()
            ? collect()
            : AssetRequest::whereIn('user_id', $teamUserIds)
                ->where('status', 'Allocated')
                ->with(['asset', 'user'])
                ->get();

        return view('assets.employee', compact('userRequests', 'allocatedAssets', 'teamAllocatedAssets', 'myIncidents'));
    }

    /** Employee confirms (accept) or disputes receipt of an allocated asset. */
    public function acknowledge(Request $request, $allocationId)
    {
        $allocation = AssetRequest::with('asset')->findOrFail($allocationId);
        abort_unless((int) $allocation->user_id === (int) auth()->id(), 403);

        $data = $request->validate([
            'decision' => 'required|in:accept,dispute',
            'note' => 'nullable|string|max:500',
        ]);

        return $this->attempt(function () use ($allocation, $data) {
            $accept = $data['decision'] === 'accept';
            $this->assets->acknowledge($allocation, auth()->user(), $accept, $data['note'] ?? null);

            return back()->with('success', $accept
                ? 'Thanks - receipt confirmed.'
                : 'Issue reported to HR. They will contact you.');
        });
    }

    public function requestAsset(Request $request)
    {
        $request->validate([
            'asset_type' => 'required|string|max:100',
            'reason' => 'required|string|max:1000',
        ]);

        AssetRequest::create([
            'user_id' => auth()->id(),
            'asset_type' => $request->asset_type,
            'reason' => $request->reason,
            'status' => 'Pending',
        ]);

        return back()->with('success', 'Asset request submitted successfully.');
    }

    /**
     * Declare a company device someone already holds. Employees declare for themselves;
     * team leaders may also declare for a member of their team, HR/Admin for anyone.
     * Always lands as Pending until a different person verifies it.
     */
    public function declare(Request $request)
    {
        $authUser = auth()->user();

        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'asset_type' => 'required|string|max:100',
            'declared_name' => 'required|string|max:150',
            'declared_serial_number' => 'nullable|string|max:150',
            'declared_configuration' => 'nullable|string|max:2000',
            'reason' => 'nullable|string|max:1000',
            'image' => self::IMAGE_RULE,
        ]);

        $ownerId = (int) ($data['user_id'] ?? $authUser->id);

        if ($ownerId !== (int) $authUser->id) {
            $allowed = AccessControl::isAdmin($authUser)
                || AccessControl::assetTeamUserIds($authUser)->contains($ownerId);
            abort_unless($allowed, 403, 'You can only add assets for members of your own team.');
        }

        $serial = trim((string) ($data['declared_serial_number'] ?? ''));
        if ($serial !== '') {
            $duplicate = AssetRequest::where('source', AssetRequest::SOURCE_DECLARATION)
                ->where('status', 'Pending')
                ->where('declared_serial_number', $serial)
                ->exists();
            if ($duplicate) {
                return back()->withInput()->with('error', "A declaration for serial {$serial} is already waiting for verification.");
            }

            $holder = AssetRequest::where('status', 'Allocated')
                ->whereHas('asset', fn ($q) => $q->where('serial_number', $serial))
                ->with('user')->first();
            if ($holder) {
                return back()->withInput()->with('error', "Serial {$serial} is already allocated to " . ($holder->user->name ?? 'someone') . '.');
            }
        }

        AssetRequest::create([
            'user_id' => $ownerId,
            'source' => AssetRequest::SOURCE_DECLARATION,
            'asset_type' => $data['asset_type'],
            'reason' => $data['reason'] ?: ($ownerId === (int) $authUser->id ? 'Declared by employee' : 'Added by team leader / HR'),
            'status' => 'Pending',
            'declared_name' => $data['declared_name'],
            'declared_serial_number' => $serial !== '' ? $serial : null,
            'declared_configuration' => $data['declared_configuration'] ?? null,
            'declared_image_path' => $request->hasFile('image') ? $request->file('image')->store('assets', Asset::IMAGE_DISK) : null,
            'created_by' => $authUser->id,
        ]);

        return back()->with('success', 'Asset declared. It will appear as allocated once it is verified.');
    }

    public function verifyDeclaration($id)
    {
        $declaration = AssetRequest::findOrFail($id);
        abort_unless(AccessControl::canVerifyDeclaration($declaration), 403, 'You cannot verify this declaration.');

        return $this->attempt(function () use ($declaration) {
            $this->assets->verifyDeclaration($declaration, auth()->user());

            return back()->with('success', 'Declaration verified. The asset is now recorded as allocated.');
        });
    }

    public function rejectDeclaration(Request $request, $id)
    {
        $declaration = AssetRequest::findOrFail($id);
        abort_unless(AccessControl::canVerifyDeclaration($declaration), 403, 'You cannot reject this declaration.');

        $data = $request->validate(['rejection_reason' => 'required|string|max:255']);

        return $this->attempt(function () use ($declaration, $data) {
            $this->assets->rejectDeclaration($declaration, auth()->user(), $data['rejection_reason']);

            return back()->with('success', 'Declaration rejected.');
        });
    }

    private function returnInto(Request $request, Asset $asset)
    {
        $data = $request->validate([
            'condition' => 'nullable|in:Good,Maintenance,Faulty',
            'note' => 'nullable|string|max:500',
        ]);
        $condition = $data['condition'] ?? 'Good';
        $newStatus = $condition === 'Good' ? 'Available' : $condition;

        return $this->attempt(function () use ($asset, $newStatus, $data) {
            $this->assets->release($asset, $newStatus, [
                'condition' => $newStatus === 'Available' ? 'Good' : 'Damaged',
                'note' => $data['note'] ?? null,
            ]);

            $msg = match ($newStatus) {
                'Maintenance' => 'Asset marked as returned and sent to Maintenance.',
                'Faulty' => 'Asset marked as returned and set to Faulty (permanent damage).',
                default => 'Asset returned successfully and is now Available.',
            };

            return back()->with('success', $msg);
        });
    }

    private function cleanupImages(Asset $asset, ?string $oldImage, ?string $newImage): void
    {
        $asset->refresh();
        if ($asset->image_path !== $oldImage && $oldImage) {
            Storage::disk(Asset::IMAGE_DISK)->delete($oldImage);
        }
        if ($newImage && $asset->image_path !== $newImage) {
            Storage::disk(Asset::IMAGE_DISK)->delete($newImage); // update was rejected
        }
    }

    /** Turn AssetService rule failures into the same flash "error" message the views already show. */
    private function attempt(callable $action)
    {
        try {
            return $action();
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }
    }
}

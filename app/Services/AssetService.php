<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Every change to who holds an asset, or to an asset's status, goes through here.
 *
 * Rules kept in one place:
 *  - An asset has at most ONE open allocation (asset_requests.status = 'Allocated').
 *  - Asset status 'Allocated' <=> it has an open allocation.
 *  - Each change runs in a transaction with the asset row locked, so two quick clicks
 *    can't hand the same laptop to two people.
 *  - Only active user accounts can receive assets.
 */
class AssetService
{
    public const STATUSES = ['Available', 'Allocated', 'Maintenance', 'Faulty'];

    /** Give an asset to a user. Optionally fulfils an existing pending request row. */
    public function allocate(Asset $asset, int $userId, string $reason, ?AssetRequest $fromRequest = null): AssetRequest
    {
        return DB::transaction(function () use ($asset, $userId, $reason, $fromRequest) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();

            if ($asset->status !== 'Available' || $asset->activeAllocation()->exists()) {
                $this->fail('asset_id', 'This asset is no longer available.');
            }

            $this->assertActiveUser($userId);

            if ($fromRequest) {
                $fromRequest = AssetRequest::whereKey($fromRequest->id)->lockForUpdate()->firstOrFail();

                if (!in_array($fromRequest->status, ['Pending', 'Approved'], true)) {
                    $this->fail('asset_id', "This request is already {$fromRequest->status}.");
                }

                $fromRequest->update([
                    'asset_id' => $asset->id,
                    'status' => 'Allocated',
                    'allocated_at' => now(),
                ]);
                $allocation = $fromRequest;
            } else {
                $allocation = AssetRequest::create([
                    'user_id' => $userId,
                    'asset_id' => $asset->id,
                    'asset_type' => $asset->type,
                    'reason' => $reason,
                    'status' => 'Allocated',
                    'allocated_at' => now(),
                ]);
            }

            $asset->update(['status' => 'Allocated']);

            return $allocation;
        });
    }

    /**
     * Close the open allocation (if any) and put the asset into $newStatus
     * (Available / Maintenance / Faulty).
     */
    public function release(Asset $asset, string $newStatus = 'Available'): void
    {
        if (!in_array($newStatus, ['Available', 'Maintenance', 'Faulty'], true)) {
            $this->fail('status', 'Invalid status after return.');
        }

        DB::transaction(function () use ($asset, $newStatus) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();

            AssetRequest::where('asset_id', $asset->id)
                ->where('status', 'Allocated')
                ->update(['status' => 'Returned', 'returned_at' => now()]);

            $asset->update(['status' => $newStatus]);
        });
    }

    /**
     * Apply an edit-form result: who should hold it ($userId, null = nobody) and
     * which status it should have. Keeps status and allocation consistent:
     *  - Maintenance / Faulty / Available always close any open allocation.
     *  - Allocated requires a holder.
     */
    public function applyHolderAndStatus(Asset $asset, ?int $userId, string $status): void
    {
        DB::transaction(function () use ($asset, $userId, $status) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();
            $current = $asset->activeAllocation()->first();

            if ($status !== 'Allocated') {
                if ($current) {
                    $current->update(['status' => 'Returned', 'returned_at' => now()]);
                }
                $asset->update(['status' => $status]);

                return;
            }

            if (!$userId) {
                $this->fail('assign_user_id', 'Pick an employee to mark this asset as Allocated.');
            }

            if ($current && (int) $current->user_id === $userId) {
                $asset->update(['status' => 'Allocated']);

                return; // same holder, nothing to change
            }

            $this->assertActiveUser($userId);

            if ($current) {
                $current->update(['status' => 'Returned', 'returned_at' => now()]);
            }

            AssetRequest::create([
                'user_id' => $userId,
                'asset_id' => $asset->id,
                'asset_type' => $asset->type,
                'reason' => $current ? 'Reassigned via Edit' : 'Assigned via Edit',
                'status' => 'Allocated',
                'allocated_at' => now(),
            ]);

            $asset->update(['status' => 'Allocated']);
        });
    }

    public function reject(AssetRequest $request): void
    {
        DB::transaction(function () use ($request) {
            $request = AssetRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (!in_array($request->status, ['Pending', 'Approved'], true)) {
                $this->fail('request', "This request is already {$request->status}.");
            }

            $request->update(['status' => 'Rejected']);
        });
    }

    /** Soft-delete an asset; refuses while someone still holds it. */
    public function delete(Asset $asset): void
    {
        DB::transaction(function () use ($asset) {
            $asset = Asset::whereKey($asset->id)->lockForUpdate()->firstOrFail();

            if ($asset->activeAllocation()->exists()) {
                $this->fail('asset', 'This asset is still allocated. Mark it returned before deleting.');
            }

            $asset->delete();
        });
    }

    /**
     * Verify a declaration: link it to the existing asset with the same serial number (if that
     * asset is free), or create the asset from the declared details — then allocate it to the
     * declaring employee using the declaration row itself as the allocation record.
     */
    public function verifyDeclaration(AssetRequest $declaration, User $verifier): Asset
    {
        return DB::transaction(function () use ($declaration, $verifier) {
            $declaration = AssetRequest::whereKey($declaration->id)->lockForUpdate()->firstOrFail();

            if (!$declaration->isDeclaration() || $declaration->status !== 'Pending') {
                $this->fail('request', 'This declaration is no longer pending.');
            }

            $serial = trim((string) $declaration->declared_serial_number);
            $asset = $serial !== '' ? Asset::where('serial_number', $serial)->lockForUpdate()->first() : null;

            if ($asset) {
                $holder = $asset->activeAllocation()->with('user')->first();
                if ($holder) {
                    $this->fail('request', "Serial {$serial} is already recorded as allocated to " . ($holder->user->name ?? 'another employee') . '.');
                }
                if ($asset->status !== 'Available') {
                    $this->fail('request', "Serial {$serial} exists in inventory with status {$asset->status}. Fix that asset first.");
                }
                if (!$asset->image_path && $declaration->declared_image_path) {
                    $asset->update(['image_path' => $declaration->declared_image_path]);
                }
            } else {
                $asset = Asset::create([
                    'name' => $declaration->declared_name ?: $declaration->asset_type,
                    'type' => $declaration->asset_type,
                    'serial_number' => $serial !== '' ? $serial : null,
                    'system_configuration' => $declaration->declared_configuration,
                    'image_path' => $declaration->declared_image_path,
                    'status' => 'Available',
                ]);
            }

            $this->allocate($asset, (int) $declaration->user_id, $declaration->reason ?? 'Declared', $declaration);

            $declaration->update(['verified_by' => $verifier->id, 'verified_at' => now()]);

            return $asset;
        });
    }

    public function rejectDeclaration(AssetRequest $declaration, User $verifier, string $reason): void
    {
        DB::transaction(function () use ($declaration, $verifier, $reason) {
            $declaration = AssetRequest::whereKey($declaration->id)->lockForUpdate()->firstOrFail();

            if (!$declaration->isDeclaration() || $declaration->status !== 'Pending') {
                $this->fail('request', 'This declaration is no longer pending.');
            }

            $declaration->update([
                'status' => 'Rejected',
                'rejection_reason' => $reason,
                'verified_by' => $verifier->id,
                'verified_at' => now(),
            ]);
        });
    }

    private function assertActiveUser(int $userId): void
    {
        $user = User::find($userId);

        if (!$user || ($user->account_status !== null && $user->account_status !== 'active')) {
            $this->fail('assign_user_id', 'Assets can only be given to active employees.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}

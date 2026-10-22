<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AssetIncident;
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
 *  - Lost / Retired are end states, reached only through their own actions.
 *  - Each change runs in a transaction with the asset row locked, so two quick clicks
 *    can't hand the same laptop to two people.
 *  - Only active user accounts can receive assets.
 *  - Every change writes a line to asset_events (who, when, from whom, to whom, why).
 */
class AssetService
{
    /** Statuses an admin can pick on the add / edit forms. */
    public const STATUSES = ['Available', 'Allocated', 'Maintenance', 'Faulty'];

    /** Statuses an asset can be returned into. */
    public const RETURN_STATUSES = ['Available', 'Maintenance', 'Faulty'];

    // ------------------------------------------------------------------ allocation

    /**
     * Give an available asset to a user. Optionally fulfils an existing pending request row.
     * $options: condition (string), require_ack (bool, default true), note (string)
     */
    public function allocate(Asset $asset, int $userId, string $reason, ?AssetRequest $fromRequest = null, array $options = []): AssetRequest
    {
        return DB::transaction(function () use ($asset, $userId, $reason, $fromRequest, $options) {
            $asset = $this->lock($asset);

            if ($asset->status !== 'Available' || $asset->activeAllocation()->exists()) {
                $this->fail('asset_id', 'This asset is no longer available.');
            }

            $this->assertActiveUser($userId);

            return $this->assign($asset, $userId, $reason, $fromRequest, $options);
        });
    }

    /**
     * Move an allocated asset straight from its current holder to another employee.
     * Closes the current allocation and opens a new one (pending acknowledgement).
     */
    public function transfer(Asset $asset, int $toUserId, string $reason, array $options = []): AssetRequest
    {
        return DB::transaction(function () use ($asset, $toUserId, $reason, $options) {
            $asset = $this->lock($asset);
            $current = $asset->activeAllocation()->first();

            if (!$current) {
                $this->fail('asset', 'This asset is not with anyone. Use Allocate instead.');
            }
            if ((int) $current->user_id === $toUserId) {
                $this->fail('to_user_id', 'This asset is already with that employee.');
            }

            $this->assertActiveUser($toUserId);

            $fromName = $current->user->name ?? 'previous holder';
            $toName = User::whereKey($toUserId)->value('name');
            $condition = $options['condition'] ?? null;

            $this->closeAllocation($current, 'Returned', $condition, trim("Transferred to {$toName}. {$reason}"));

            $new = $this->openAllocation($asset, $toUserId, trim("Transferred from {$fromName}. {$reason}"), null, $options);

            if ($condition && $condition !== $asset->condition) {
                $this->setState($asset, ['condition' => $condition]);
            }

            AssetEvent::record($asset, 'transferred', [
                'from_user_id' => $current->user_id,
                'to_user_id' => $toUserId,
                'from_status' => 'Allocated',
                'to_status' => 'Allocated',
                'asset_request_id' => $new->id,
                'notes' => $reason,
                'meta' => array_filter(['condition' => $condition]),
            ]);

            return $new;
        });
    }

    /**
     * Take the asset back from its holder (if any) and put it into $newStatus
     * (Available / Maintenance / Faulty). $options: condition, note
     */
    public function release(Asset $asset, string $newStatus = 'Available', array $options = []): void
    {
        if (!in_array($newStatus, self::RETURN_STATUSES, true)) {
            $this->fail('status', 'Invalid status after return.');
        }

        DB::transaction(function () use ($asset, $newStatus, $options) {
            $asset = $this->lock($asset);
            $current = $asset->activeAllocation()->first();
            $fromStatus = $asset->status;
            $condition = $options['condition'] ?? null;
            $note = $options['note'] ?? null;

            if ($current) {
                $this->closeAllocation($current, 'Returned', $condition, $note);
            }

            $this->setState($asset, array_filter(['status' => $newStatus, 'condition' => $condition]));

            AssetEvent::record($asset, $current ? 'returned' : 'status_changed', [
                'from_user_id' => $current?->user_id,
                'from_status' => $fromStatus,
                'to_status' => $newStatus,
                'asset_request_id' => $current?->id,
                'notes' => $note,
                'meta' => array_filter(['condition' => $condition]),
            ]);
        });
    }

    /**
     * Apply an edit-form result: who should hold it ($userId, null = nobody) and
     * which status it should have. Keeps status and allocation consistent:
     *  - Maintenance / Faulty / Available always close any open allocation.
     *  - Allocated requires a holder; a different holder means a transfer.
     *  - Lost / Retired can't be set or left from here.
     */
    public function applyHolderAndStatus(Asset $asset, ?int $userId, string $status): void
    {
        DB::transaction(function () use ($asset, $userId, $status) {
            $asset = $this->lock($asset);
            $current = $asset->activeAllocation()->first();

            if ($asset->isClosed() || in_array($status, ['Lost', 'Retired'], true)) {
                if ($status === $asset->status) {
                    return; // details-only edit of a lost / retired asset
                }
                $this->fail('status', 'Lost and Retired are changed with their own actions (Report lost, Mark found, Retire, Back in service).');
            }

            if ($status !== 'Allocated') {
                if ($current || $status !== $asset->status) {
                    $this->release($asset, $status, ['note' => $current ? 'Returned via Edit' : null]);
                }

                return;
            }

            if (!$userId) {
                $this->fail('assign_user_id', 'Pick an employee to mark this asset as Allocated.');
            }

            if ($current && (int) $current->user_id === $userId) {
                if ($asset->status !== 'Allocated') {
                    $this->setState($asset, ['status' => 'Allocated']);
                }

                return; // same holder, nothing to change
            }

            if ($current) {
                $this->transfer($asset, $userId, 'Reassigned via Edit');

                return;
            }

            $this->assertActiveUser($userId);
            $this->assign($asset, $userId, 'Assigned via Edit');
        });
    }

    /** Employee confirms (or disputes) that they have received an allocated asset. */
    public function acknowledge(AssetRequest $allocation, User $user, bool $accept, ?string $note = null): void
    {
        DB::transaction(function () use ($allocation, $user, $accept, $note) {
            $allocation = AssetRequest::whereKey($allocation->id)->lockForUpdate()->firstOrFail();

            if ((int) $allocation->user_id !== (int) $user->id || $allocation->status !== 'Allocated') {
                $this->fail('allocation', 'This asset is not allocated to you.');
            }

            $allowed = $accept
                ? [AssetRequest::ACK_PENDING, AssetRequest::ACK_DISPUTED]
                : [AssetRequest::ACK_PENDING];
            if (!in_array($allocation->acknowledgement_status, $allowed, true)) {
                $this->fail('allocation', 'This handover has already been confirmed.');
            }
            if (!$accept && blank($note)) {
                $this->fail('note', 'Please describe the problem (e.g. not received, different serial, damaged).');
            }

            $allocation->update([
                'acknowledgement_status' => $accept ? AssetRequest::ACK_ACCEPTED : AssetRequest::ACK_DISPUTED,
                'acknowledged_at' => now(),
                'acknowledgement_note' => $note,
            ]);

            AssetEvent::record($allocation->asset, $accept ? 'acknowledged' : 'disputed', [
                'to_user_id' => $user->id,
                'asset_request_id' => $allocation->id,
                'notes' => $note,
            ]);
        });
    }

    // ------------------------------------------------------------------ damage / loss

    /**
     * Open a damage or loss case.
     * $data: incident_date, description, attachment_path, cost, send_to_repair (damage only)
     *  - damage: condition becomes Damaged; holder keeps it unless it's sent for repair.
     *  - lost:   the allocation ends as 'Lost' and the asset status becomes Lost.
     */
    public function reportIncident(Asset $asset, string $type, array $data): AssetIncident
    {
        if (!array_key_exists($type, AssetIncident::TYPES)) {
            $this->fail('type', 'Unknown incident type.');
        }

        return DB::transaction(function () use ($asset, $type, $data) {
            $asset = $this->lock($asset);

            if ($asset->isClosed()) {
                $this->fail('asset', "This asset is already {$asset->status}.");
            }
            if ($asset->openIncidents()->where('type', $type)->exists()) {
                $this->fail('asset', 'There is already an open ' . strtolower(AssetIncident::TYPES[$type]) . ' case for this asset.');
            }

            $current = $asset->activeAllocation()->with('user')->first();
            $fromStatus = $asset->status;

            $incident = AssetIncident::create([
                'asset_id' => $asset->id,
                'asset_request_id' => $current?->id,
                'user_id' => $current?->user_id,
                'user_name' => $current?->user?->name,
                'reported_by' => auth()->id(),
                'type' => $type,
                'incident_date' => $data['incident_date'] ?? now()->toDateString(),
                'description' => $data['description'],
                'attachment_path' => $data['attachment_path'] ?? null,
                'cost' => $data['cost'] ?? null,
                'status' => AssetIncident::STATUS_OPEN,
            ]);

            if ($type === AssetIncident::TYPE_LOST) {
                if ($current) {
                    $this->closeAllocation($current, 'Lost', null, 'Reported lost');
                }
                $this->setState($asset, ['status' => 'Lost']);

                AssetEvent::record($asset, 'lost_reported', [
                    'from_user_id' => $current?->user_id,
                    'from_status' => $fromStatus,
                    'to_status' => 'Lost',
                    'asset_incident_id' => $incident->id,
                    'notes' => $data['description'],
                ]);

                return $incident;
            }

            $this->setState($asset, ['condition' => 'Damaged']);

            AssetEvent::record($asset, 'damage_reported', [
                'from_user_id' => $current?->user_id,
                'asset_incident_id' => $incident->id,
                'notes' => $data['description'],
            ]);

            if (!empty($data['send_to_repair'])) {
                $this->sendForRepair($incident);
            }

            return $incident->fresh();
        });
    }

    /** Damage case: take the device from its holder and put it into Maintenance. */
    public function sendForRepair(AssetIncident $incident, ?string $note = null): void
    {
        DB::transaction(function () use ($incident, $note) {
            $incident = AssetIncident::whereKey($incident->id)->lockForUpdate()->firstOrFail();

            if ($incident->type !== AssetIncident::TYPE_DAMAGE || $incident->status !== AssetIncident::STATUS_OPEN) {
                $this->fail('incident', 'Only an open damage case can be sent for repair.');
            }

            $asset = $this->lock($incident->asset);
            $current = $asset->activeAllocation()->first();
            $fromStatus = $asset->status;

            if ($current) {
                $this->closeAllocation($current, 'Returned', 'Damaged', 'Sent for repair');
            }
            $this->setState($asset, ['status' => 'Maintenance', 'condition' => 'Damaged']);
            $incident->update(['status' => AssetIncident::STATUS_IN_REPAIR]);

            AssetEvent::record($asset, 'sent_to_repair', [
                'from_user_id' => $current?->user_id,
                'from_status' => $fromStatus,
                'to_status' => 'Maintenance',
                'asset_incident_id' => $incident->id,
                'notes' => $note,
            ]);
        });
    }

    /**
     * Close a case. $resolution must be one of AssetIncident::RESOLUTIONS[type].
     * $data: condition, resolution_notes + any money fields (see applyMoney()).
     */
    public function resolveIncident(AssetIncident $incident, string $resolution, array $data = []): void
    {
        DB::transaction(function () use ($incident, $resolution, $data) {
            $incident = AssetIncident::whereKey($incident->id)->lockForUpdate()->firstOrFail();

            if (!$incident->isOpen()) {
                $this->fail('incident', 'This case is already closed.');
            }
            if (!array_key_exists($resolution, AssetIncident::RESOLUTIONS[$incident->type] ?? [])) {
                $this->fail('resolution', 'That resolution does not apply to this case.');
            }

            $asset = $this->lock($incident->asset);
            $fromStatus = $asset->status;
            $notes = $data['resolution_notes'] ?? null;
            $event = 'incident_closed';
            $toStatus = null;

            switch ($resolution) {
                case 'repaired':
                case 'no_action':
                    $condition = $data['condition'] ?? ($resolution === 'repaired' ? 'Good' : 'Fair');
                    $state = ['condition' => $condition];
                    if (in_array($asset->status, ['Maintenance', 'Faulty'], true)) {
                        $state['status'] = $toStatus = 'Available';
                    }
                    $this->setState($asset, $state);
                    $event = $resolution === 'repaired' ? 'repaired' : 'incident_closed';
                    break;

                case 'found':
                    if ($asset->status === 'Lost') {
                        $this->setState($asset, ['status' => $toStatus = 'Available', 'condition' => $data['condition'] ?? 'Good']);
                    }
                    $event = 'found';
                    break;

                case 'written_off':
                    if ($current = $asset->activeAllocation()->first()) {
                        $this->closeAllocation($current, 'Returned', 'Damaged', 'Written off');
                    }
                    $this->setState($asset, ['status' => $toStatus = 'Retired']);
                    $event = 'written_off';
                    break;
            }

            $this->applyMoney($incident, $data);
            $incident->fill([
                'status' => AssetIncident::STATUS_CLOSED,
                'resolution' => $resolution,
                'resolution_notes' => $notes,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ])->save();

            AssetEvent::record($asset, $event, [
                'from_status' => $toStatus ? $fromStatus : null,
                'to_status' => $toStatus,
                'asset_incident_id' => $incident->id,
                'notes' => trim($incident->reference . ': ' . $incident->resolution_label . ($notes ? ". {$notes}" : '')),
            ]);
        });
    }

    /** Update cost / recovery on a case (open or closed). */
    public function updateRecovery(AssetIncident $incident, array $data): void
    {
        DB::transaction(function () use ($incident, $data) {
            $incident = AssetIncident::whereKey($incident->id)->lockForUpdate()->firstOrFail();
            $before = $incident->only(['cost', 'recovery_amount', 'recovery_status', 'recovered_amount']);

            $this->applyMoney($incident, $data);
            $incident->save();

            AssetEvent::record($incident->asset, 'recovery_updated', [
                'asset_incident_id' => $incident->id,
                'notes' => $incident->reference . ': ' . AssetIncident::RECOVERY_STATUSES[$incident->recovery_status]
                    . ($incident->recovery_amount > 0 ? ' - ' . number_format((float) $incident->recovered_amount, 2) . ' of ' . number_format((float) $incident->recovery_amount, 2) : ''),
                'meta' => ['before' => $before, 'after' => $incident->only(array_keys($before))],
            ]);
        });
    }

    // ------------------------------------------------------------------ lifecycle

    public function retire(Asset $asset, string $reason): void
    {
        DB::transaction(function () use ($asset, $reason) {
            $asset = $this->lock($asset);

            if ($asset->status === 'Retired') {
                $this->fail('asset', 'This asset is already retired.');
            }
            if ($asset->activeAllocation()->exists()) {
                $this->fail('asset', 'This asset is still with an employee. Mark it returned first.');
            }

            $from = $asset->status;
            $this->setState($asset, ['status' => 'Retired']);

            AssetEvent::record($asset, 'retired', ['from_status' => $from, 'to_status' => 'Retired', 'notes' => $reason]);
        });
    }

    /** Bring a retired asset (or a lost one with no open case) back into stock. */
    public function reactivate(Asset $asset, ?string $note = null): void
    {
        DB::transaction(function () use ($asset, $note) {
            $asset = $this->lock($asset);

            if (!$asset->isClosed()) {
                $this->fail('asset', 'Only a retired or lost asset can be brought back into service.');
            }
            if ($asset->openIncidents()->exists()) {
                $this->fail('asset', 'Close the open incident for this asset first.');
            }

            $from = $asset->status;
            $this->setState($asset, ['status' => 'Available']);

            AssetEvent::record($asset, 'reactivated', ['from_status' => $from, 'to_status' => 'Available', 'notes' => $note]);
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
            $asset = $this->lock($asset);

            if ($asset->activeAllocation()->exists()) {
                $this->fail('asset', 'This asset is still allocated. Mark it returned before deleting.');
            }

            AssetEvent::record($asset, 'deleted', ['from_status' => $asset->status]);
            $asset->delete();
        });
    }

    // ------------------------------------------------------------------ declarations

    /**
     * Verify a declaration: link it to the existing asset with the same serial number (if that
     * asset is free), or create the asset from the declared details — then allocate it to the
     * declaring employee using the declaration row itself as the allocation record.
     * No acknowledgement is needed: the employee told us they have it.
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

            $this->allocate($asset, (int) $declaration->user_id, $declaration->reason ?? 'Declared', $declaration, ['require_ack' => false]);

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

    // ------------------------------------------------------------------ internals

    /** Open an allocation on an already-locked asset and mark it Allocated. Caller checks availability. */
    private function assign(Asset $asset, int $userId, string $reason, ?AssetRequest $fromRequest = null, array $options = []): AssetRequest
    {
        $fromStatus = $asset->status;
        $allocation = $this->openAllocation($asset, $userId, $reason, $fromRequest, $options);

        $this->setState($asset, array_filter(['status' => 'Allocated', 'condition' => $options['condition'] ?? null]));

        AssetEvent::record($asset, 'allocated', [
            'to_user_id' => $userId,
            'from_status' => $fromStatus,
            'to_status' => 'Allocated',
            'asset_request_id' => $allocation->id,
            'notes' => $reason,
        ]);

        return $allocation;
    }

    private function openAllocation(Asset $asset, int $userId, string $reason, ?AssetRequest $fromRequest, array $options): AssetRequest
    {
        $requireAck = $options['require_ack'] ?? true;
        $attributes = [
            'asset_id' => $asset->id,
            'status' => 'Allocated',
            'allocated_at' => now(),
            'allocated_by' => auth()->id(),
            'condition_on_allocation' => $options['condition'] ?? $asset->condition,
            'acknowledgement_status' => $requireAck ? AssetRequest::ACK_PENDING : AssetRequest::ACK_ACCEPTED,
            'acknowledged_at' => $requireAck ? null : now(),
        ];

        if ($fromRequest) {
            $fromRequest = AssetRequest::whereKey($fromRequest->id)->lockForUpdate()->firstOrFail();

            if (!in_array($fromRequest->status, ['Pending', 'Approved'], true)) {
                $this->fail('asset_id', "This request is already {$fromRequest->status}.");
            }

            $fromRequest->update($attributes);

            return $fromRequest;
        }

        return AssetRequest::create($attributes + [
            'user_id' => $userId,
            'asset_type' => $asset->type,
            'reason' => $reason,
        ]);
    }

    private function closeAllocation(AssetRequest $allocation, string $status, ?string $condition, ?string $note): void
    {
        $allocation->update([
            'status' => $status,
            'returned_at' => now(),
            'returned_by' => auth()->id(),
            'condition_on_return' => $condition,
            'return_note' => $note ? mb_substr($note, 0, 500) : null,
        ]);
    }

    /**
     * Money fields on a case, validated together:
     * cost, recovery_amount, recovery_status (none|pending|recovered|waived), recovered_amount, recovered_at.
     */
    private function applyMoney(AssetIncident $incident, array $data): void
    {
        foreach (['cost', 'recovery_amount', 'recovery_status', 'recovered_amount', 'recovered_at'] as $key) {
            if (array_key_exists($key, $data)) {
                $incident->{$key} = $data[$key] === '' ? null : $data[$key];
            }
        }

        $status = $incident->recovery_status ?: 'none';
        if (!array_key_exists($status, AssetIncident::RECOVERY_STATUSES)) {
            $this->fail('recovery_status', 'Invalid recovery status.');
        }

        $amount = round((float) $incident->recovery_amount, 2);
        $recovered = round((float) $incident->recovered_amount, 2);

        if ($amount < 0 || $recovered < 0 || ($incident->cost !== null && (float) $incident->cost < 0)) {
            $this->fail('recovery_amount', 'Amounts cannot be negative.');
        }

        if ($status === 'none') {
            $amount = $recovered = 0;
            $incident->recovered_at = null;
        } elseif ($amount <= 0) {
            $this->fail('recovery_amount', 'Enter the amount to recover from the employee.');
        } elseif ($recovered > $amount) {
            $this->fail('recovered_amount', 'Recovered amount cannot be more than the amount to recover.');
        } elseif ($status === 'recovered') {
            // "Recovered" means settled: the full amount unless a (lower) settled figure was entered now.
            $recovered = (array_key_exists('recovered_amount', $data) && $data['recovered_amount'] !== null && $data['recovered_amount'] !== '' && $recovered > 0)
                ? $recovered
                : $amount;
            $incident->recovered_at ??= now()->toDateString();
        }

        $incident->recovery_status = $status;
        $incident->recovery_amount = $amount;
        $incident->recovered_amount = $recovered;
    }

    private function lock(Asset $asset): Asset
    {
        return Asset::withTrashed()->whereKey($asset->id)->lockForUpdate()->firstOrFail();
    }

    /** Status / condition changes are logged by the caller with context, so skip the details_updated hook. */
    private function setState(Asset $asset, array $state): void
    {
        $asset->forceFill($state)->saveQuietly();
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

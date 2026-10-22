<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\AssetAuditItem;
use App\Models\AssetEvent;
use App\Models\AssetIncident;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Physical verification: snapshot where every asset should be, check each one,
 * then (optionally) open loss / damage cases for whatever wasn't right.
 */
class AssetAuditService
{
    public function __construct(private AssetService $assets)
    {
    }

    /** $types: limit the audit to these asset types (empty = all). Lost and retired assets are skipped. */
    public function start(string $title, ?string $notes = null, array $types = []): AssetAudit
    {
        return DB::transaction(function () use ($title, $notes, $types) {
            $assets = Asset::with('activeAllocation.user')
                ->whereNotIn('status', ['Lost', 'Retired'])
                ->when($types, fn ($q) => $q->whereIn('type', $types))
                ->orderBy('type')->orderBy('asset_tag')
                ->get();

            if ($assets->isEmpty()) {
                $this->fail('title', 'No assets match this audit.');
            }

            $audit = AssetAudit::create([
                'title' => $title,
                'notes' => $notes,
                'status' => AssetAudit::STATUS_IN_PROGRESS,
                'started_by' => auth()->id(),
            ]);

            $now = now();
            AssetAuditItem::insert($assets->map(fn (Asset $asset) => [
                'asset_audit_id' => $audit->id,
                'asset_id' => $asset->id,
                'expected_user_id' => $asset->activeAllocation?->user_id,
                'expected_user_name' => $asset->activeAllocation?->user?->name,
                'expected_status' => $asset->status,
                'result' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());

            return $audit;
        });
    }

    /** $results: [item_id => ['result' => ..., 'notes' => ...]] */
    public function record(AssetAudit $audit, array $results): int
    {
        $this->assertInProgress($audit);

        return DB::transaction(function () use ($audit, $results) {
            $items = $audit->items()->whereIn('id', array_keys($results))->get()->keyBy('id');
            $changed = 0;

            foreach ($results as $itemId => $row) {
                $item = $items->get((int) $itemId);
                if (!$item) {
                    continue;
                }

                $result = $row['result'] ?? 'pending';
                if (!array_key_exists($result, AssetAuditItem::RESULTS)) {
                    $this->fail('results', 'Invalid audit result.');
                }
                $notes = isset($row['notes']) ? mb_substr(trim((string) $row['notes']), 0, 500) : null;

                if ($item->result === $result && (string) $item->notes === (string) $notes) {
                    continue;
                }

                $item->fill([
                    'result' => $result,
                    'notes' => $notes ?: null,
                    'checked_by' => $result === 'pending' ? null : auth()->id(),
                    'checked_at' => $result === 'pending' ? null : now(),
                ])->save();
                $changed++;
            }

            return $changed;
        });
    }

    /**
     * Close the audit. With $raiseIncidents, every "missing" item becomes a loss case and every
     * "damaged" one a damage case (unless the asset already has an open case of that kind).
     *
     * @return array{incidents: int, unchecked: int}
     */
    public function complete(AssetAudit $audit, bool $raiseIncidents): array
    {
        $this->assertInProgress($audit);

        return DB::transaction(function () use ($audit, $raiseIncidents) {
            $audit = AssetAudit::whereKey($audit->id)->lockForUpdate()->firstOrFail();
            $this->assertInProgress($audit);

            $items = $audit->items()->with('asset')->get();
            $incidents = 0;

            foreach ($items->where('result', '!=', 'pending') as $item) {
                $asset = $item->asset;
                if (!$asset || $asset->trashed()) {
                    continue;
                }

                AssetEvent::record($asset, 'audited', [
                    'notes' => $audit->title . ': ' . AssetAuditItem::RESULTS[$item->result] . ($item->notes ? " - {$item->notes}" : ''),
                    'meta' => ['audit_id' => $audit->id, 'result' => $item->result],
                ]);

                $type = match ($item->result) {
                    'missing' => AssetIncident::TYPE_LOST,
                    'damaged' => AssetIncident::TYPE_DAMAGE,
                    default => null,
                };

                if (!$raiseIncidents || !$type || $asset->fresh()->isClosed()
                    || $asset->openIncidents()->where('type', $type)->exists()) {
                    continue;
                }

                $incident = $this->assets->reportIncident($asset, $type, [
                    'incident_date' => now()->toDateString(),
                    'description' => "Found {$item->result} during audit \"{$audit->title}\"." . ($item->notes ? " {$item->notes}" : ''),
                ]);
                $item->update(['asset_incident_id' => $incident->id]);
                $incidents++;
            }

            $audit->update([
                'status' => AssetAudit::STATUS_COMPLETED,
                'completed_by' => auth()->id(),
                'completed_at' => now(),
            ]);

            return ['incidents' => $incidents, 'unchecked' => $items->where('result', 'pending')->count()];
        });
    }

    public function cancel(AssetAudit $audit): void
    {
        $this->assertInProgress($audit);
        $audit->update(['status' => AssetAudit::STATUS_CANCELLED, 'completed_by' => auth()->id(), 'completed_at' => now()]);
    }

    private function assertInProgress(AssetAudit $audit): void
    {
        if (!$audit->isInProgress()) {
            $this->fail('audit', 'This audit is already closed.');
        }
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}

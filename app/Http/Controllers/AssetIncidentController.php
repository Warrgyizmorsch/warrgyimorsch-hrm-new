<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetIncident;
use App\Services\AssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Damage & loss cases: report, send for repair, close (repaired / found / written off),
 * and track the cost and what is recovered from the employee.
 */
class AssetIncidentController extends Controller
{
    private const ATTACHMENT_RULE = 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096';

    public function __construct(private AssetService $assets)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'status' => 'nullable|in:open,in_repair,closed,all',
            'type' => ['nullable', Rule::in(array_keys(AssetIncident::TYPES))],
            'recovery' => ['nullable', Rule::in(array_keys(AssetIncident::RECOVERY_STATUSES))],
            'q' => 'nullable|string|max:100',
        ]);
        $status = $filters['status'] ?? 'open_any';

        $incidents = AssetIncident::with(['asset', 'user', 'reporter', 'resolver'])
            ->when($status === 'open_any', fn ($q) => $q->open())
            ->when(in_array($status, ['open', 'in_repair', 'closed'], true), fn ($q) => $q->where('status', $status))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where('type', $type))
            ->when($filters['recovery'] ?? null, fn ($q, $r) => $q->where('recovery_status', $r))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $term) . '%';
                $q->where(fn ($w) => $w->where('user_name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('asset', fn ($a) => $a->withTrashed()->where('asset_tag', 'like', $like)->orWhere('name', 'like', $like)));
            })
            ->latest('incident_date')->latest('id')
            ->paginate(25)->withQueryString();

        $summary = [
            'open' => AssetIncident::where('status', AssetIncident::STATUS_OPEN)->count(),
            'in_repair' => AssetIncident::where('status', AssetIncident::STATUS_IN_REPAIR)->count(),
            'cost' => (float) AssetIncident::sum('cost'),
            'outstanding' => (float) AssetIncident::where('recovery_status', 'pending')->selectRaw('COALESCE(SUM(recovery_amount - recovered_amount), 0) as s')->value('s'),
            'recovered' => (float) AssetIncident::whereIn('recovery_status', ['pending', 'recovered'])->sum('recovered_amount'),
        ];

        $reportableAssets = Asset::with('activeAllocation.user')
            ->whereNotIn('status', ['Lost', 'Retired'])
            ->orderBy('asset_tag')->get(['id', 'asset_tag', 'name', 'type', 'status']);

        return view('assets.incidents', compact('incidents', 'summary', 'filters', 'status', 'reportableAssets'));
    }

    /** HR/Admin reports damage or loss on any asset. */
    public function store(Request $request)
    {
        $data = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'type' => ['required', Rule::in(array_keys(AssetIncident::TYPES))],
            'incident_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:2000',
            'cost' => 'nullable|numeric|min:0',
            'send_to_repair' => 'nullable|boolean',
            'attachment' => self::ATTACHMENT_RULE,
        ]);

        return $this->report(Asset::findOrFail($data['asset_id']), $data, $request);
    }

    /** An employee reports damage / loss of a device they hold. */
    public function reportByEmployee(Request $request)
    {
        $data = $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'type' => ['required', Rule::in(array_keys(AssetIncident::TYPES))],
            'incident_date' => 'required|date|before_or_equal:today',
            'description' => 'required|string|max:2000',
            'attachment' => self::ATTACHMENT_RULE,
        ]);

        $asset = Asset::findOrFail($data['asset_id']);
        abort_unless((int) $asset->activeAllocation?->user_id === (int) auth()->id(), 403, 'You can only report devices allocated to you.');

        return $this->report($asset, $data, $request);
    }

    public function sendToRepair(Request $request, AssetIncident $incident)
    {
        $data = $request->validate(['note' => 'nullable|string|max:500']);

        return $this->attempt(function () use ($incident, $data) {
            $this->assets->sendForRepair($incident, $data['note'] ?? null);

            return back()->with('success', 'Asset taken back and moved to Maintenance.');
        });
    }

    public function resolve(Request $request, AssetIncident $incident)
    {
        $data = $request->validate([
            'resolution' => ['required', Rule::in(array_keys(AssetIncident::RESOLUTIONS[$incident->type] ?? []))],
            'condition' => ['nullable', Rule::in(Asset::CONDITIONS)],
            'resolution_notes' => 'nullable|string|max:2000',
        ] + $this->moneyRules());

        return $this->attempt(function () use ($incident, $data) {
            $this->assets->resolveIncident($incident, $data['resolution'], $data);

            return back()->with('success', "{$incident->reference} closed.");
        });
    }

    public function updateRecovery(Request $request, AssetIncident $incident)
    {
        $data = $request->validate($this->moneyRules());

        return $this->attempt(function () use ($incident, $data) {
            $this->assets->updateRecovery($incident, $data);

            return back()->with('success', "Cost and recovery saved for {$incident->reference}.");
        });
    }

    private function moneyRules(): array
    {
        return [
            'cost' => 'nullable|numeric|min:0',
            'recovery_status' => ['required', Rule::in(array_keys(AssetIncident::RECOVERY_STATUSES))],
            'recovery_amount' => 'nullable|numeric|min:0',
            'recovered_amount' => 'nullable|numeric|min:0',
            'recovered_at' => 'nullable|date|before_or_equal:today',
        ];
    }

    private function report(Asset $asset, array $data, Request $request)
    {
        $path = $request->hasFile('attachment')
            ? $request->file('attachment')->store('asset-incidents', AssetIncident::ATTACHMENT_DISK)
            : null;

        $saved = false;
        $response = $this->attempt(function () use ($asset, $data, $path, &$saved) {
            $incident = $this->assets->reportIncident($asset, $data['type'], [
                'incident_date' => $data['incident_date'],
                'description' => $data['description'],
                'cost' => $data['cost'] ?? null,
                'attachment_path' => $path,
                'send_to_repair' => !empty($data['send_to_repair']),
            ]);
            $saved = true;

            return back()->with('success', "{$incident->reference} recorded: " . AssetIncident::TYPES[$incident->type] . " - {$asset->name}.");
        });

        if ($path && !$saved) {
            Storage::disk(AssetIncident::ATTACHMENT_DISK)->delete($path); // report was rejected
        }

        return $response;
    }

    private function attempt(callable $action)
    {
        try {
            return $action();
        } catch (ValidationException $e) {
            return back()->withInput()->with('error', collect($e->errors())->flatten()->first());
        }
    }
}

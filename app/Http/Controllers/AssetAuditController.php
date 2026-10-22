<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\AssetAuditItem;
use App\Services\AssetAuditService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AssetAuditController extends Controller
{
    public function __construct(private AssetAuditService $audits)
    {
    }

    public function index()
    {
        $audits = AssetAudit::with(['starter', 'completer'])
            ->withCount([
                'items',
                'items as checked_count' => fn ($q) => $q->where('result', '!=', 'pending'),
                'items as missing_count' => fn ($q) => $q->where('result', 'missing'),
                'items as damaged_count' => fn ($q) => $q->where('result', 'damaged'),
                'items as wrong_holder_count' => fn ($q) => $q->where('result', 'wrong_holder'),
            ])
            ->latest()
            ->paginate(20);

        $types = Asset::whereNotIn('status', ['Lost', 'Retired'])->distinct()->orderBy('type')->pluck('type');

        return view('assets.audits.index', compact('audits', 'types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'notes' => 'nullable|string|max:2000',
            'types' => 'nullable|array',
            'types.*' => 'string|max:100',
        ]);

        return $this->attempt(function () use ($data) {
            $audit = $this->audits->start($data['title'], $data['notes'] ?? null, $data['types'] ?? []);

            return redirect()->route('assets.audits.show', $audit)->with('success', 'Audit started with ' . $audit->items()->count() . ' assets.');
        });
    }

    public function show(Request $request, AssetAudit $audit)
    {
        $filter = $request->validate(['result' => ['nullable', Rule::in(array_keys(AssetAuditItem::RESULTS))]])['result'] ?? null;

        $audit->load(['starter', 'completer']);
        $items = $audit->items()
            ->with(['asset.activeAllocation.user', 'expectedUser', 'checker', 'incident'])
            ->when($filter, fn ($q) => $q->where('result', $filter))
            ->get()
            ->sortBy(fn ($i) => [$i->asset->type ?? '', $i->asset->asset_tag ?? ''])
            ->values();

        $counts = $audit->items()->selectRaw('result, COUNT(*) as c')->groupBy('result')->pluck('c', 'result');

        return view('assets.audits.show', compact('audit', 'items', 'counts', 'filter'));
    }

    public function record(Request $request, AssetAudit $audit)
    {
        $data = $request->validate([
            'items' => 'required|array',
            'items.*.result' => ['required', Rule::in(array_keys(AssetAuditItem::RESULTS))],
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        return $this->attempt(function () use ($audit, $data) {
            $changed = $this->audits->record($audit, $data['items']);

            return back()->with('success', $changed ? "{$changed} item(s) updated." : 'No changes.');
        });
    }

    public function complete(Request $request, AssetAudit $audit)
    {
        $raise = $request->boolean('raise_incidents');

        return $this->attempt(function () use ($audit, $raise) {
            $result = $this->audits->complete($audit, $raise);

            $msg = 'Audit completed.';
            if ($result['incidents']) {
                $msg .= " {$result['incidents']} damage/loss case(s) opened.";
            }
            if ($result['unchecked']) {
                $msg .= " {$result['unchecked']} item(s) were not checked.";
            }

            return back()->with('success', $msg);
        });
    }

    public function cancel(AssetAudit $audit)
    {
        return $this->attempt(function () use ($audit) {
            $this->audits->cancel($audit);

            return redirect()->route('assets.audits.index')->with('success', 'Audit cancelled.');
        });
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

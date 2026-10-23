<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetAudit;
use App\Models\AssetEvent;
use App\Models\AssetIncident;
use App\Models\AssetRequest;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Asset overview for HR/Admin, and the full register as a CSV download.
 */
class AssetDashboardController extends Controller
{
    public function index()
    {
        $statusCounts = Asset::selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $byType = Asset::selectRaw('type, status, COUNT(*) as c')
            ->groupBy('type', 'status')
            ->get()
            ->groupBy('type')
            ->map(fn ($rows) => $rows->pluck('c', 'status'))
            ->sortKeys();

        $inService = Asset::whereNotIn('status', ['Lost', 'Retired']);
        $value = [
            'in_service' => (float) (clone $inService)->sum('purchase_cost'),
            'with_cost' => (clone $inService)->whereNotNull('purchase_cost')->count(),
            'in_service_count' => (clone $inService)->count(),
            'lost_or_retired' => (float) Asset::whereIn('status', ['Lost', 'Retired'])->sum('purchase_cost'),
            'incident_cost' => (float) AssetIncident::sum('cost'),
            'outstanding' => (float) AssetIncident::where('recovery_status', 'pending')
                ->selectRaw('COALESCE(SUM(recovery_amount - recovered_amount), 0) as s')->value('s'),
            'recovered' => (float) AssetIncident::whereIn('recovery_status', ['pending', 'recovered'])->sum('recovered_amount'),
        ];

        $awaitingAck = AssetRequest::awaitingAcknowledgement()->with(['user', 'asset'])->oldest('allocated_at')->get();
        $disputed = AssetRequest::where('status', 'Allocated')
            ->where('acknowledgement_status', AssetRequest::ACK_DISPUTED)
            ->with(['user', 'asset'])->latest('acknowledged_at')->get();

        $openIncidents = AssetIncident::open()->with(['asset', 'user'])->latest('incident_date')->get();

        $warranty = Asset::whereNotIn('status', ['Lost', 'Retired'])
            ->whereNotNull('warranty_expiry')
            ->whereDate('warranty_expiry', '<=', now()->addDays(60))
            ->whereDate('warranty_expiry', '>=', now()->subDays(30))
            ->with('activeAllocation.user')
            ->orderBy('warranty_expiry')
            ->get();

        // Assets in Maintenance, with the time of their last status change (≈ when they went in).
        $inRepair = Asset::where('status', 'Maintenance')
            ->addSelect(['since' => AssetEvent::select('created_at')
                ->whereColumn('asset_id', 'assets.id')
                ->whereNotNull('to_status')
                ->latest('created_at')->limit(1)])
            ->whereDoesntHave('openIncidents', fn ($q) => $q->where('status', AssetIncident::STATUS_IN_REPAIR))
            ->get()
            ->sortBy('since')
            ->values();

        $recentEvents = AssetEvent::with(['asset', 'performer', 'fromUser', 'toUser'])
            ->latest('created_at')->latest('id')->limit(15)->get();

        $lastAudit = AssetAudit::withCount([
            'items',
            'items as missing_count' => fn ($q) => $q->where('result', 'missing'),
            'items as checked_count' => fn ($q) => $q->where('result', '!=', 'pending'),
        ])->latest()->first();

        $pendingRequests = AssetRequest::whereIn('status', ['Pending', 'Approved'])->count();

        return view('assets.dashboard', compact(
            'statusCounts', 'byType', 'value', 'awaitingAck', 'disputed', 'openIncidents',
            'warranty', 'inRepair', 'recentEvents', 'lastAudit', 'pendingRequests'
        ));
    }

    /** Full asset register as CSV (opens in Excel). */
    public function export(): StreamedResponse
    {
        $filename = 'asset-register-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel shows ₹ and names correctly

            fputcsv($out, [
                'Asset Tag', 'Name', 'Type', 'Brand', 'Model', 'Serial Number', 'Status', 'Condition',
                'Current Holder', 'Holder Since', 'Receipt', 'Location', 'Purchase Date', 'Purchase Cost',
                'Vendor', 'Invoice No.', 'Warranty Expiry', 'Open Incidents', 'Configuration', 'Notes',
            ]);

            Asset::with(['activeAllocation.user', 'openIncidents'])
                ->orderBy('type')->orderBy('asset_tag')
                ->chunk(200, function ($assets) use ($out) {
                    foreach ($assets as $a) {
                        $alloc = $a->activeAllocation;
                        fputcsv($out, [
                            $a->asset_tag, $a->name, $a->type, $a->brand, $a->model, $a->serial_number,
                            $a->status, $a->condition,
                            $alloc?->user?->name,
                            $alloc?->allocated_at?->format('Y-m-d'),
                            $alloc ? ucfirst($alloc->acknowledgement_status ?? 'accepted') : null,
                            $a->location,
                            $a->purchase_date?->format('Y-m-d'),
                            $a->purchase_cost,
                            $a->vendor, $a->invoice_number,
                            $a->warranty_expiry?->format('Y-m-d'),
                            $a->openIncidents->map(fn ($i) => $i->reference . ' ' . $i->type)->implode('; '),
                            $a->system_configuration,
                            $a->notes,
                        ]);
                    }
                });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

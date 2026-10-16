<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Services\HeaderNotificationService;
use Illuminate\Http\Request;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function index()
    {
        // $role = strtoupper(auth()->user()->role);
        $notifications = [];

        $roleSlug = auth()->user()->role; // e.g. "manager"

        $roleId = DB::table('roles_master')
            ->where('slug', $roleSlug)
            ->value('id');

        $isAdmin = in_array($roleId, [1, 2, 3]);

        // if ($role == 'ADMIN' || $role == 'SUPER ADMIN') {
        if ($isAdmin) {
            // $notifications = LeaveApplication::with('employee')
            //     ->whereIn('status', ['pending', 'Pending'])
            //     ->latest()
            //     ->paginate(20);

            $leaveNotifications = LeaveApplication::with('employee')
                ->latest()
                ->get();

            $payrollNotifications = Payroll::with('employee')
                ->whereNotNull('remarks')
                ->where('remarks', '!=', '')
                ->latest()
                ->get();

            $notifications = $leaveNotifications
                ->concat($payrollNotifications)
                ->sortByDesc(function ($item) {
                    return isset($item->remarks) 
                        ? $item->updated_at 
                        : $item->created_at;
                });
                
        } else {
            $notifications = LeaveApplication::where('employee_id', auth()->user()->employee_id)
                ->whereIn('status', ['approved', 'rejected', 'Approved', 'Rejected'])
                ->where('updated_at', '>=', now()->subDays(30)) // Show more history on the full page
                ->latest()
                ->paginate(20);
        }

        return view('notifications.index', compact('notifications', 'isAdmin'));
    }

    /**
     * Live bell feed — polled by the header every ~20 s so the count and list update
     * without reloading the page.
     */
    public function feed(HeaderNotificationService $service)
    {
        $bell = $service->build(auth()->user());

        return response()->json([
            'count' => $bell['count'],
            'items' => $service->keys($bell),
            'html' => view('layouts.partials.notification-items', $bell)->render(),
        ])->header('Cache-Control', 'no-store');
    }
}

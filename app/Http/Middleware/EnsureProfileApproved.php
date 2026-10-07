<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A new employee whose profile isn't approved yet can only reach the "Complete your profile"
 * page (plus logout and their own document downloads). Everyone else is unaffected.
 */
class EnsureProfileApproved
{
    private const ALLOWED_ROUTES = [
        'profile.complete',
        'profile.complete.submit',
        'logout',
        'employee-documents.download',
        'profile.password.update',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user()?->employee;

        if (!$employee || !$employee->isProfileLocked() || in_array($request->route()?->getName(), self::ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Please complete your profile and wait for HR approval.'], 403);
        }

        return redirect()->route('profile.complete');
    }
}

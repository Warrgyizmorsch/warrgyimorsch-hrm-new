<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
     public function handle(Request $request, Closure $next, ...$allowedRoles)
    {
        $role = str_replace(' ', '_', strtolower(trim((string) (auth()->user()->role ?? 'employee'))));

        if (!in_array($role, $allowedRoles)) {
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}

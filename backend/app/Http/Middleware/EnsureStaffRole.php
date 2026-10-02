<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts a route to one or more staff roles, e.g. ->middleware('role:hr,headmaster').
 * Staff who are HR always pass, since HR has system-wide access.
 */
class EnsureStaffRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || (! in_array($user->role, $roles, true) && $user->role !== 'hr')) {
            abort(403, 'You do not have access to this.');
        }

        return $next($request);
    }
}

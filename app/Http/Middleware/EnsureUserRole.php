<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && ! $user->is_suspended && in_array($user->role, $roles, true), 403);

        if ($user->role === 'doctor') {
            abort_unless($user->doctorProfile?->approval_status === 'approved', 403);
        }

        return $next($request);
    }
}

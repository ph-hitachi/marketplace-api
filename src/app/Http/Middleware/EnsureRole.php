<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\AuthenticationException;

class EnsureRole
{
    /**
     * Allow only users whose role matches one of the given roles.
     *
     * Usage in routes:
     *   ->middleware('role:admin')
     *   ->middleware('role:seller,admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            throw new AuthenticationException('Unauthenticated.');
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use App\Exceptions\AccountDeactivatedException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Block deactivated users and revoke their token immediately.
     *
     * @throws AccountDeactivatedException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            // Revoke the current token so it cannot be reused
            $user->currentAccessToken()->delete();

            throw new AccountDeactivatedException();
        }

        return $next($request);
    }
}

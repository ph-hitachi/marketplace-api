<?php

namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\UserDeleteBlockedException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @tags Admin/Users
 */
class UserController extends Controller
{
    /**
     * List registered users.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::with('shop')
            ->latest()
            ->cached(300)
            ->paginate(20);

        return response()->json($users)
            ->header('X-Cache-Status', Cache::status());
    }

    /**
     * View specific user.
     */
    public function show(User $user): JsonResponse
    {
        $userData = User::where('id', $user->id)
            ->with(['shop', 'addresses'])
            ->cached(300)
            ->firstOrFail();

        return response()->json(['user' => $userData])
            ->header('X-Cache-Status', Cache::status());
    }

    /**
     * Reactivate user.
     */
    public function activate(User $user): Response
    {
        $user->update(['is_active' => true]);

        return response()->noContent();
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): Response
    {
        // JWT is stateless — there are no stored tokens to revoke.
        // The is_active flag is checked on every request by the
        // EnsureUserIsActive middleware, so the user is effectively
        // locked out immediately.
        $user->update(['is_active' => false]);

        return response()->noContent();
    }

    /**
     * Delete user account.
     */
    public function destroy(User $user): Response
    {
        $hasOrders = $user->customerOrders()
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($hasOrders) {
            throw new UserDeleteBlockedException();
        }

        $user->delete();

        return response()->noContent();
    }
}

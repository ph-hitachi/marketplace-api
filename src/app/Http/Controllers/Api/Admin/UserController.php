<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use App\Exceptions\UserDeleteBlockedException;

/**
 * @tags Admin/Users
 */
class UserController extends Controller
{
    /**
     * List users.
     */
    public function index(): JsonResponse
    {
        $users = User::with('sellerProfile')->latest()->paginate(20);

        return response()->json($users);
    }

    /**
     * View single user.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['sellerProfile', 'addresses']);

        return response()->json(['user' => $user]);
    }

    /**
     * Activate user.
     */
    public function activate(User $user): \Illuminate\Http\Response
    {
        $user->update(['is_active' => true]);

        return response()->noContent();
    }

    /**
     * Deactivate user.
     *
     * JWT is stateless — there are no stored tokens to revoke.
     * The is_active flag is checked on every request by the
     * EnsureUserIsActive middleware, so the user is effectively
     * locked out immediately.
     */
    public function deactivate(User $user): \Illuminate\Http\Response
    {
        $user->update(['is_active' => false]);

        return response()->noContent();
    }

    /**
     * Delete user.
     */
    public function destroy(User $user): \Illuminate\Http\Response
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

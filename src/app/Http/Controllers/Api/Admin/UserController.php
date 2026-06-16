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
    public function activate(User $user): JsonResponse
    {
        $user->update(['is_active' => true]);

        return response()->json(['message' => 'User activated.', 'user' => $user]);
    }

    /**
     * Deactivate user.
     */
    public function deactivate(User $user): JsonResponse
    {
        $user->update(['is_active' => false]);
        $user->tokens()->delete();

        return response()->json(['message' => 'User deactivated and all tokens revoked.']);
    }

    /**
     * Delete user.
     * 
     */
    public function destroy(User $user): JsonResponse
    {
        $hasOrders = $user->customerOrders()
            ->where('status', '!=', 'cancelled')
            ->exists();

        if ($hasOrders) {
            throw new UserDeleteBlockedException();
        }

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }
}

<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @tags User/Profile
 */
class ProfileController extends Controller
{
    /**
     * Retrieve authenticated user profile.
     */
    public function me(): JsonResponse
    {
        $user = Auth::guard('api')->user()->load('shop');

        return response()->json(['user' => $user]);
    }

    /**
     * Update profile details.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->authorize('update', $user);

        $data = $request->validated();

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user'    => $user,
        ]);
    }
}

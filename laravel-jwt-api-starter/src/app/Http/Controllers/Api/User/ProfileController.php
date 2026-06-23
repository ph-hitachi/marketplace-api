<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Http\Requests\User\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Dedoc\Scramble\Attributes\Group;

#[Group('User/Profile', weight: 2)]
class ProfileController extends Controller
{
    /**
     * Get profile.
     *
     * Retrieve the authenticated user's profile details.
     *
     * @response UserResource
     */
    public function me(): UserResource
    {
        $user = Auth::guard('api')->user();

        return new UserResource($user);
    }

    /**
     * Update profile.
     *
     * Update the authenticated user's profile information (name and email).
     *
     * @param UpdateProfileRequest $request
     *
     * @see \App\Policies\UserPolicy::update()
     * @response UserResource
     */
    public function update(UpdateProfileRequest $request): UserResource
    {
        $user = $request->user();

        $this->authorize('update', $user);

        $user->update($request->validated());

        return new UserResource($user);
    }

    /**
     * Update password.
     *
     * Change the authenticated user's password after validating the current password.
     *
     * @param UpdatePasswordRequest $request
     *
     * @see \App\Policies\UserPolicy::update()
     * @response UserResource
     */
    public function updatePassword(UpdatePasswordRequest $request): UserResource
    {
        $user = $request->user();

        $this->authorize('update', $user);

        $user->update([
            'password' => $request->validated('password')
        ]);

        return new UserResource($user);
    }
}

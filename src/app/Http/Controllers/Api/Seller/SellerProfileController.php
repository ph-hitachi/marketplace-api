<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateSellerProfileRequest;
use App\Models\SellerProfile;
use Illuminate\Http\JsonResponse;

/**
 * @tags Seller/Profile
 */
class SellerProfileController extends Controller
{
    /**
     * Update seller profile.
     */
    public function update(UpdateSellerProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $profile = $user->sellerProfile ?: new SellerProfile(['user_id' => $user->id]);

        $this->authorize('update', $profile);

        $data = $request->validated();

        $profile->fill([
            'shop_name'        => $data['shop_name'],
            'shop_description' => $data['shop_description'],
        ]);
        $profile->save();

        return response()->json([
            'message' => 'Seller profile updated successfully.',
            'profile' => $profile,
        ]);
    }
}

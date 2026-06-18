<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seller\UpdateSellerProfileRequest;
use App\Models\Shop;
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

        $shop = $user->shop ?: new Shop(['user_id' => $user->id]);

        $this->authorize('update', $shop);

        $data = $request->validated();

        $shop->fill([
            'shop_name'        => $data['shop_name'],
            'shop_description' => $data['shop_description'],
        ]);
        $shop->save();

        return response()->json([
            'message' => 'Seller profile updated successfully.',
            'profile' => $shop,
        ]);
    }
}

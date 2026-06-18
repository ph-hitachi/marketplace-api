<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * @tags Shops
 */
class ShopController extends Controller
{
    /**
     * Display a listing of shops.
     */
    public function index(Request $request): JsonResponse
    {
        $shops = Shop::latest()->paginate(15);
        return response()->json($shops);
    }

    /**
     * Display the specified shop.
     */
    public function show(Shop $shop): JsonResponse
    {
        return response()->json([
            'name'        => $shop->shop_name,
            'description' => $shop->shop_description,
            'products'    => $shop->products()->where('is_active', true)->get(),
        ]);
    }
}

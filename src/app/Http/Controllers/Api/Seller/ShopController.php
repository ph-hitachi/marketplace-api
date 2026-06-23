<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Support\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Shops
 */
class ShopController extends Controller
{
    /**
     * List all available shops.
     */
    public function index(Request $request): JsonResponse
    {
        $shops = Shop::latest()
            ->cached(900)
            ->paginate(15);

        return response()->json($shops)
            ->header('X-Cache-Status', Cache::status());
    }

    /**
     * Retrieve a specific shop profile and active products.
     */
    public function show(Shop $shop): JsonResponse
    {
        $shopData = Shop::where('id', $shop->id)
            ->with(['products' => fn($q) => $q->where('is_active', true)])
            ->cached(900)
            ->firstOrFail();

        return response()->json([
            'name'        => $shopData['shop_name'],
            'description' => $shopData['shop_description'],
            'products'    => $shopData['products'],
        ])->header('X-Cache-Status', Cache::status());
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Public/Products
 */
class ProductController extends Controller
{
    /**
     * List all active products.
     */
    public function index(Request $request): JsonResponse
    {
        $products = Product::available()
            ->with('shop')
            ->latest()
            ->cached(900)
            ->paginate(15);

        return response()->json($products)
            ->header('X-Cache-Status', Cache::status());
    }

    /**
     * Retrieve details for an active product.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::available()
            ->with('shop')
            ->where('id', $id)
            ->cached(86400)
            ->firstOrFail();

        return response()->json(['product' => $product])
            ->header('X-Cache-Status', Cache::status());
    }
}

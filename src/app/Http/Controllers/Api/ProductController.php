<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

/**
 * @tags Public/Products
 */
class ProductController extends Controller
{
    /**
     * List products.
     */
    public function index(): JsonResponse
    {
        $products = Product::available()
            ->with('seller.sellerProfile')
            ->latest()
            ->paginate(15);

        return response()->json($products);
    }

    /**
     * View product.
     */
    public function show(string $id): JsonResponse
    {
        $product = Product::available()
            ->with('seller.sellerProfile')
            ->findOrFail($id);

        return response()->json(['product' => $product]);
    }
}

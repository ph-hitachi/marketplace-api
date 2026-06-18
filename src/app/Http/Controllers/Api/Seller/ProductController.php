<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Seller/Products
 */
class ProductController extends Controller
{
    /**
     * List own products.
     */
    public function index(Request $request): JsonResponse
    {
        $shopId = $request->user()->shop->id;
        $products = Product::where('shop_id', $shopId)
            ->withTrashed()     // include soft-deleted for seller visibility
            ->latest()
            ->paginate(15);

        return response()->json($products);
    }

    /**
     * Create product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data            = $request->validated();
        $data['shop_id'] = $request->user()->shop->id;

        $product = Product::create($data);

        return response()->json(['product' => $product], 201);
    }

    /**
     * View own product.
     */
    public function show(Request $request, Product $product): JsonResponse
    {
        $this->authorize('view', $product);

        return response()->json(['product' => $product]);
    }

    /**
     * Update product.
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $product->update($request->validated());

        return response()->json(['product' => $product]);
    }

    /**
     * Delete product.
     */
    public function destroy(Request $request, Product $product): \Illuminate\Http\Response
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->noContent();
    }

    /**
     * Activate product.
     */
    public function activate(Request $request, Product $product): \Illuminate\Http\Response
    {
        $this->authorize('update', $product);

        $product->update(['is_active' => true]);

        return response()->noContent();
    }

    /**
     * Deactivate product.
     */
    public function deactivate(Request $request, Product $product): \Illuminate\Http\Response
    {
        $this->authorize('update', $product);

        $product->update(['is_active' => false]);

        return response()->noContent();
    }
}

<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Cart\StoreCartRequest;

use App\Models\Cart;
use Illuminate\Http\JsonResponse;

/**
 * @tags Customer/Cart
 */
class CartController extends Controller
{
    /**
     * List saved items in the cart.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Cart::class);

        $carts = Cart::with('product')
            ->where('customer_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json($carts);
    }

    /**
     * Add an item to the cart.
     */
    public function store(StoreCartRequest $request): JsonResponse
    {
        $this->authorize('create', Cart::class);

        $validated = $request->validated();

        $cart = Cart::firstOrCreate([
            'customer_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
        ]);

        return response()->json([
            'message' => 'Product added to cart.',
            'cart' => $cart->load('product'),
        ], 201);
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(Cart $cart, Request $request): JsonResponse
    {
        $this->authorize('delete', $cart);

        $cart->delete();

        return response()->json([
            'message' => 'Product removed from cart.',
        ]);
    }
}

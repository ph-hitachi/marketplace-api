<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\Cache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Admin/Orders
 */
class OrderController extends Controller
{
    /**
     * List all orders.
     *
     * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\OrderResource>
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['customer', 'shop', 'address', 'items.product'])
            ->latest()
            ->cached(300)
            ->paginate(20);

        return response()->json($orders)
            ->header('X-Cache-Status', Cache::status());
    }

    /**
     * View any order details.
     *
     * @response array{order: \App\Http\Resources\OrderResource}
     */
    public function show(Order $order): JsonResponse
    {
        $orderData = Order::where('id', $order->id)
            ->with(['items.product', 'customer', 'shop', 'address'])
            ->cached(300)
            ->firstOrFail();

        return response()->json(['order' => $orderData])
            ->header('X-Cache-Status', Cache::status());
    }
}

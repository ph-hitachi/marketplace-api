<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

/**
 * @tags Admin/Orders
 */
class OrderController extends Controller
{
    /**
     * List orders.
     * 
     * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\OrderResource>
     */
    public function index(): JsonResponse
    {
        $orders = Order::with(['customer', 'shop', 'address', 'items.product'])
            ->latest()
            ->paginate(20);

        return response()->json($orders);
    }

    /**
     * View single order.
     * 
     * @response array{order: \App\Http\Resources\OrderResource}
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['items.product', 'customer', 'shop', 'address']);

        return response()->json(['order' => $order]);
    }
}

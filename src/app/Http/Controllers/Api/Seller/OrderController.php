<?php

namespace App\Http\Controllers\Api\Seller;

use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\OrderInTransitException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Http\Requests\Order\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Services\OrderService;
use App\Services\ProductStockService;
use App\Services\OrderPaymentService;
use App\Support\Cache;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * @tags Seller/Orders
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('shop_id', $request->user()->shop->id)
            ->with(['customer:id,name', 'address'])
            ->latest()
            ->cached(300)
            ->paginate(15);

        return response()->json($orders)
            ->header('X-Cache-Status', Cache::status());
    }

    /**
     * View specific seller order.
     * 
     * @response array{order: \App\Http\Resources\OrderResource}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('viewAsSeller', $order);

        $orderData = Order::where('id', $order->id)
            ->with(['items.product', 'customer:id,name', 'address'])
            ->cached(300)
            ->firstOrFail();

        return response()->json(['order' => $orderData])
            ->header('X-Cache-Status', Cache::status());
    }

    /**
     * Advance status.
     *
     * @throws InvalidStatusTransitionException
     * @response array{message: string, order: \App\Http\Resources\OrderResource}
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        $this->authorize('updateStatus', $order);

        $newStatus = $request->validated('status');

        if ($newStatus === 'confirmed') {
            throw new AuthorizationException('This action is unauthorized.');
        }

        $expectedNext = match ($order->status) {
            'pending' => 'shipped',
            'shipped' => 'delivered',
            default => null,
        };

        if ($newStatus !== $expectedNext) {
            throw new InvalidStatusTransitionException($order->status, $newStatus);
        }

        $updates = ['status' => $newStatus];

        if ($newStatus === 'shipped') {
            $updates['shipped_at'] = now();
        } elseif ($newStatus === 'delivered') {
            $updates['delivered_at'] = now();
        }

        $order->update($updates);

        return response()->json([
            'message' => 'Order status updated.',
            'order' => $order->fresh(),
        ]);
    }

    /**
     * Cancel order.
     * 
     * @response array{message: string, order: \App\Http\Resources\OrderResource}
     */
    public function cancel(CancelOrderRequest $request, Order $order, ProductStockService $stockService, OrderPaymentService $paymentService): JsonResponse
    {
        $this->authorize('cancelAsSeller', $order);


        DB::transaction(function () use ($request, $order, $stockService, $paymentService): void {

            $user = $request->user();

            if ($user->role === 'seller' && $order->status === 'delivered') {
                throw new InvalidStatusTransitionException($order->status, 'cancelled');
            }

            if ($order->status === 'shipped') {
                throw new OrderInTransitException('Order is already shipped and cannot be cancelled.');
            }

            if (in_array($order->status, ['confirmed', 'cancelled'])) {
                throw new InvalidStatusTransitionException($order->status, 'cancelled');
            }

            $order->update([
                'status' => 'cancelled',
                'cancel_at' => now(),
                'cancel_reason' => $request->cancel_reason,
                'cancel_reason_notes' => $request->cancel_reason_notes,
            ]);

            foreach ($order->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $stockService->restore($item->product, $item->quantity);
            }

            $paymentService->refund($order);
        });

        return response()->json([
            'message' => 'Order cancelled and refund issued.',
            'order' => $order->fresh(),
        ]);
    }
}


<?php

namespace App\Http\Controllers\Api\Customer;

use App\Exceptions\InsufficientStockException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Exceptions\OrderInTransitException;
use App\Exceptions\ProductUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Http\Requests\Order\CancelOrderRequest;
use App\Models\Order;
use App\Models\Product;
use App\Services\OrderService;
use App\Services\ProductStockService;
use App\Services\OrderPaymentService;
use App\Support\Cache;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @tags Customer/Orders
 */
class OrderController extends Controller
{
    /**
     * Place order.
     * 
     * @response array{
     *   message: string,
     *   batch_ref: string|null,
     *   orders: list<\App\Http\Resources\OrderResource>,
     *   total_deducted: float,
     *   balance_remaining: float|null
     * }
     */
    public function store(PlaceOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $result = $orderService->placeBatchOrder($request);

        return response()->json([
            'message' => 'Orders placed successfully.',
            'batch_ref' => $result['batch_ref'] ?? null,
            'orders' => $result['orders'] ?? [],
            'total_deducted' => $result['total_deducted'] ?? 0,
            'balance_remaining' => $result['balance_remaining'] ?? 0,
        ], 201);
    }

    /**
     * List orders.
     * 
     * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\OrderResource>
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->with(['items.product', 'shop', 'address'])
            ->orderByDesc('created_at')
            ->cached(300)
            ->paginate(15);

        return response()->json($orders)
            ->header('X-Cache-Status', Cache::status());
     }
 
     /**
      * View order details.
      * 
      * @response array{order: \App\Http\Resources\OrderResource}
      */
     public function show(Request $request, Order $order): JsonResponse
     {
         $this->authorize('viewAsCustomer', $order);
 
         $orderData = Order::where('id', $order->id)
             ->with(['items.product', 'shop', 'address'])
             ->cached(300)
             ->firstOrFail();

         return response()->json(['order' => $orderData])
             ->header('X-Cache-Status', Cache::status());
     }

    /**
     * Cancel order.
     */
    public function cancel(CancelOrderRequest $request, Order $order, ProductStockService $stockService, OrderPaymentService $paymentService): JsonResponse
    {
        $this->authorize('cancel', $order);

        DB::transaction(function () use ($request, $order, $stockService, $paymentService): void {

            $user = $request->user();
            if ($order->status === 'shipped') {
                throw new OrderInTransitException('Order is already shipped and cannot be cancelled.');
            }

            if ($user->role === 'seller' && $order->status === 'delivered') {
                throw new InvalidStatusTransitionException($order->status, 'cancelled');
            }

            if (in_array($order->status, ['confirmed', 'cancelled'])) {
                throw new InvalidStatusTransitionException($order->status, 'cancelled');
            }

            $order->update([
                'cancel_reason'       => $request->cancel_reason,
                'cancel_reason_notes' => $request->cancel_reason_notes,
                'cancel_at'           => now(),
                'status'              => 'cancelled',
            ]);

            foreach ($order->items as $item) {
                if (!$item->product) {
                    continue;
                }

                $stockService->restore(
                    $item->product,
                    $item->quantity
                );
            }

            $paymentService->refund($order);
        });

        return response()->json(['message' => 'Order cancelled and refund issued.']);
    }

    /**
     * Confirm receipt to release funds.
     */
    public function confirm(Request $request, Order $order, OrderPaymentService $paymentService): JsonResponse
    {
        $this->authorize('confirm', $order);

        DB::transaction(function () use ($order, $paymentService): void {
            if ($order->status === 'confirmed' || $order->status === 'cancelled') {
                throw new InvalidStatusTransitionException($order->status, 'confirmed');
            }

            $order->status = 'confirmed';
            $order->completed_at = now();
            $order->save();

            $paymentService->release($order);
        });

        return response()->json(['message' => 'Order confirmed and payment released.']);
    }
}

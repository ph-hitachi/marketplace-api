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
     * List order history.
     * 
     * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\OrderResource>
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->with(['items.product', 'shop', 'address'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return response()->json($orders);
    }

    /**
     * View order.
     * 
     * @response array{order: \App\Http\Resources\OrderResource}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('viewAsCustomer', $order);

        $order->load(['items.product', 'shop', 'address']);

        return response()->json(['order' => $order]);
    }

    /**
     * Cancel order
     */
    public function cancel(CancelOrderRequest $request, Order $order, ProductStockService $stockService, OrderPaymentService $paymentService): JsonResponse
    {
        $this->authorize('cancel', $order);

        $data = $request->validated();
        $user = $request->user();
        $cancelReason = (int) $data['cancel_reason'];
        $cancelReasonNotes = $data['cancel_reason_notes'] ?? null;

        DB::transaction(function () use ($order, $user, $cancelReason, $cancelReasonNotes, $stockService, $paymentService): void {
            if ($order->status === 'shipped') {
                throw new OrderInTransitException('Order is already shipped and cannot be cancelled.');
            }

            if (
                ($user->role === 'seller' && $order->status === 'delivered') ||
                $order->status === 'confirmed' ||
                $order->status === 'cancelled'
            ) {
                throw new InvalidStatusTransitionException($order->status, 'cancelled');
            }

            $order->status = 'cancelled';
            $order->cancel_at = now();

            if ($cancelReason !== null) {
                $order->cancel_reason = $cancelReason;
            }

            if ($cancelReasonNotes !== null) {
                $order->cancel_reason_notes = $cancelReasonNotes;
            }

            $order->save();

            foreach ($order->items as $item) {
                if ($item->product) {
                    $stockService->restore($item->product, $item->quantity);
                }
            }

            $paymentService->refund($order);
        });

        return response()->json(['message' => 'Order cancelled and refund issued.']);
    }

    /**
     * Confirm delivery of an order
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

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

    /**
     * List all orders assigned to this seller.
     * 
     * @response \Illuminate\Http\Resources\Json\AnonymousResourceCollection<\App\Http\Resources\OrderResource>
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('seller_id', $request->user()->id)
            ->with(['customer:id,name', 'address'])
            ->latest()
            ->paginate(15);

        return response()->json($orders);
    }

    /**
     * Show a single order assigned to this seller.
     * 
     * @response array{order: \App\Http\Resources\OrderResource}
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorize('viewAsSeller', $order);

        $order->load(['items.product', 'customer:id,name', 'address']);

        return response()->json(['order' => $order]);
    }

    /**
     * Advance an order's status (pending→processing→shipped→delivered).
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
     * Cancel a pending or processing order — restores stock and refunds wallet.
     * 
     * @response array{message: string, order: \App\Http\Resources\OrderResource}
     */
    public function cancel(CancelOrderRequest $request, Order $order, ProductStockService $stockService, OrderPaymentService $paymentService): JsonResponse
    {
        $this->authorize('cancelAsSeller', $order);

        $data = $request->validated();
        $user = $request->user();
        $cancelReason = (int) $data['cancel_reason'];
        $cancelReasonNotes = $data['cancel_reason_notes'] ?? null;

        DB::transaction(function () use ($order, $user, $cancelReason, $cancelReasonNotes, $stockService, $paymentService): void {
            if ($user->role === 'seller' && $order->status === 'delivered') {
                throw new InvalidStatusTransitionException($order->status, 'cancelled');
            }

            if ($order->status === 'shipped') {
                throw new OrderInTransitException('Order is already shipped and cannot be cancelled.');
            }

            if ($order->status === 'confirmed' || $order->status === 'cancelled') {
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

        return response()->json([
            'message' => 'Order cancelled and refund issued.',
            'order' => $order->fresh(),
        ]);
    }
}


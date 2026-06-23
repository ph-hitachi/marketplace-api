<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductUnavailableException;
use App\Exceptions\OrderInTransitException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Wallet;
use App\Services\ProductStockService;
use App\Services\OrderPaymentService;
use App\Http\Requests\Order\PlaceOrderRequest;
use App\Support\Cache;
use App\Support\CacheKey;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;

class OrderService
{
    public function __construct(
        private ProductStockService $stockService,
        private OrderPaymentService $paymentService
    ) {
    }
    /**
     * Place a batch order from a single customer.
     * Items from different sellers each get their own Order record.
     * Everything runs inside one DB transaction.
     *
     * @param  PlaceOrderRequest $request
     * @return array{batch_ref: string, orders: list<\App\Models\Order>, total_deducted: float, balance_remaining: float|null}
     *
     * @throws ProductUnavailableException
     * @throws InsufficientStockException
     * @throws InsufficientBalanceException
     */
    public function placeBatchOrder(PlaceOrderRequest $request): array
    {
        return DB::transaction(function () use ($request) {
            $customer = $request->user();
            $data = $request->validated();
            $items = collect($data['items']);

            $products = $this->loadProducts($items);
            $groups = $this->groupItemsBySeller($items, $products);

            $batchRef = Str::uuid()->toString();

            $orders = $this->createOrders(
                $groups,
                $customer,
                $data,
                $batchRef
            );

            $result = $this->handlePayment(
                $orders,
                $data['payment_method'] ?? 'wallet',
                isset($data['wallet_id']) ? (int) $data['wallet_id'] : null,
                $batchRef
            );

            return $result;
        });
    }

    private function loadProducts(Collection $items): Collection
    {
        return Product::query()
            ->whereIn('id', $items->pluck('product_id'))
            ->where('is_active', true)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function groupItemsBySeller(
        Collection $items,
        Collection $products
    ): Collection {
        return $items->map(function ($item) use ($products) {
            $product = $products->get($item['product_id']);

            if (!$product) {
                throw new ProductUnavailableException((int) $item['product_id']);
            }

            if ($product->stock < $item['quantity']) {
                throw new InsufficientStockException(
                    $product->name,
                    $product->stock
                );
            }

            return [
                'shop_id' => $product->shop_id,
                'product' => $product,
                'quantity' => $item['quantity'],
            ];
        })->groupBy('shop_id');
    }

    private function createOrders(
        Collection $groups,
        User $customer,
        array $data,
        string $batchRef
    ): Collection {
        return $groups->map(function ($shopItems, $shopId) use ($customer, $data, $batchRef) {
            $order = Order::create([
                'customer_id' => $customer->id,
                'shop_id' => $shopId,
                'address_id' => $data['address_id'],
                'wallet_id' => $data['payment_method'] === 'wallet' ? ($data['wallet_id'] ?? null) : null,
                'payment_method' => $data['payment_method'],
                'status' => 'pending',
                'total_amount' => 0,
                'batch_ref' => $batchRef,
            ]);

            $items = $shopItems->map(function ($entry) {
                $product = $entry['product'];
                $quantity = $entry['quantity'];

                $this->stockService->deduct($product, $quantity);

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'subtotal' => $product->price * $quantity,
                ];
            });

            $order->items()->createMany($items->all());

            $total = $items->sum('subtotal');

            $order->update([
                'total_amount' => $total,
            ]);

            return $order->load('items');
        })->values();
    }

    private function handlePayment(
        Collection $orders,
        string $paymentMethod,
        ?int $walletId,
        string $batchRef
    ): array {
        $grandTotal = (float) $orders->sum('total_amount');
        $balance = null;

        if ($paymentMethod === 'wallet') {
            $orders->each(fn ($order) => $this->paymentService->pay($order));
            $balance = (float) Wallet::findOrFail($walletId)->balance;
        }

        return [
            'batch_ref' => $batchRef,
            'orders' => $orders->all(),
            'total_deducted' => $paymentMethod === 'wallet' ? $grandTotal : 0.0,
            'balance_remaining' => $paymentMethod === 'wallet' ? $balance : null,
        ];
    }

}

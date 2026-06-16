<?php
namespace Database\Factories;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;
class OrderItemFactory extends Factory {
    protected $model = OrderItem::class;
    public function definition() {
        return [
            'order_id' => 1,
            'product_id' => 1,
            'product_name' => 'Premium Wireless Headphones',
            'unit_price' => 1500.50,
            'quantity' => 1,
            'subtotal' => 1500.50,
        ];
    }
}
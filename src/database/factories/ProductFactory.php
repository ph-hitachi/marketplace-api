<?php
namespace Database\Factories;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
class ProductFactory extends Factory {
    protected $model = Product::class;
    public function definition() {
        return [
            'shop_id' => \App\Models\Shop::factory(),
            'name' => 'Premium Wireless Headphones',
            'description' => 'High quality noise cancelling headphones.',
            'price' => 1500.50,
            'stock' => 50,
            'is_active' => true,
        ];
    }
}
<?php

namespace Database\Factories;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShopFactory extends Factory
{
    protected $model = Shop::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['role' => 'seller']),
            'shop_name' => $this->faker->unique()->company() . ' Shop',
            'shop_description' => $this->faker->sentence(),
        ];
    }
}

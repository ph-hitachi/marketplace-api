<?php
namespace Database\Factories;
use App\Models\ShopProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
class ShopProfileFactory extends Factory {
    protected $model = ShopProfile::class;
    public function definition() {
        return [
            'user_id' => 2,
            'store_name' => 'Tech Gadgets PH',
            'description' => 'The best tech gadgets in the Philippines.',
            'contact_number' => '+639123456789',
        ];
    }
}
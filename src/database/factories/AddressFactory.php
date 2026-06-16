<?php
namespace Database\Factories;
use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;
class AddressFactory extends Factory {
    protected $model = Address::class;
    public function definition() {
        return [
            'user_id' => 1,
            'label' => 'Home',
            'recipient_name' => 'John Doe',
            'recipient_phone' => '+639123456789',
            'street' => '123 Main St',
            'barangay' => 'San Antonio',
            'city' => 'Makati City',
            'province' => 'Metro Manila',
            'country' => 'Philippines',
            'postal_code' => '1200',
            'is_default' => true,
        ];
    }
}
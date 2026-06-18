<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ─────────────────────────────────────────────────────────
        User::create([
            'name'      => 'Admin',
            'email'     => 'admin@marketplace.com',
            'password'  => Hash::make('Admin1234!'),
            'role'      => 'admin',
            'is_active' => true,
        ]);

        // ── Sellers ───────────────────────────────────────────────────────
        $seller1 = User::create([
            'name'      => 'Tech Haven Store',
            'email'     => 'seller1@marketplace.com',
            'password'  => Hash::make('Seller1234!'),
            'role'      => 'seller',
            'is_active' => true,
        ]);

        $shop1 = Shop::create([
            'user_id'          => $seller1->id,
            'shop_name'        => 'Tech Haven',
            'shop_description' => 'Your one-stop shop for gadgets and electronics.',
        ]);

        $seller2 = User::create([
            'name'      => 'Fashion Hub Store',
            'email'     => 'seller2@marketplace.com',
            'password'  => Hash::make('Seller1234!'),
            'role'      => 'seller',
            'is_active' => true,
        ]);

        $shop2 = Shop::create([
            'user_id'          => $seller2->id,
            'shop_name'        => 'Fashion Hub',
            'shop_description' => 'Trendy clothes and accessories for every style.',
        ]);

        // ── Products for Seller 1 ─────────────────────────────────────────
        $products1 = [
            ['name' => 'Wireless Earbuds Pro',  'price' => 1299.00, 'stock' => 50,  'tags' => ['electronics', 'audio', 'wireless']],
            ['name' => 'USB-C Hub 7-in-1',      'price' => 799.00,  'stock' => 30,  'tags' => ['electronics', 'accessories']],
            ['name' => 'Mechanical Keyboard',   'price' => 2499.00, 'stock' => 20,  'tags' => ['electronics', 'peripherals', 'gaming']],
            ['name' => 'Portable Power Bank',   'price' => 999.00,  'stock' => 100, 'tags' => ['electronics', 'accessories']],
            ['name' => 'Webcam 1080p HD',       'price' => 1599.00, 'stock' => 15,  'tags' => ['electronics', 'peripherals']],
        ];

        foreach ($products1 as $p) {
            Product::create(array_merge($p, ['shop_id' => $shop1->id, 'is_active' => true, 'description' => "High-quality {$p['name']} from Tech Haven."]));
        }

        // ── Products for Seller 2 ─────────────────────────────────────────
        $products2 = [
            ['name' => 'Classic White Sneakers', 'price' => 1850.00, 'stock' => 40, 'tags' => ['fashion', 'footwear', 'unisex']],
            ['name' => 'Denim Jacket',            'price' => 2200.00, 'stock' => 25, 'tags' => ['fashion', 'tops', 'unisex']],
            ['name' => 'Leather Crossbody Bag',   'price' => 1450.00, 'stock' => 30, 'tags' => ['fashion', 'accessories', 'bags']],
            ['name' => 'Floral Summer Dress',     'price' => 950.00,  'stock' => 60, 'tags' => ['fashion', 'dresses', 'women']],
            ['name' => 'Sports Jogger Pants',     'price' => 750.00,  'stock' => 80, 'tags' => ['fashion', 'bottoms', 'activewear']],
        ];

        foreach ($products2 as $p) {
            Product::create(array_merge($p, ['shop_id' => $shop2->id, 'is_active' => true, 'description' => "Premium {$p['name']} from Fashion Hub."]));
        }

        // ── Customers ─────────────────────────────────────────────────────
        $customer1 = User::create([
            'name'      => 'Juan dela Cruz',
            'email'     => 'customer1@marketplace.com',
            'password'  => Hash::make('Customer1234!'),
            'role'      => 'customer',
            'is_active' => true,
        ]);
        $customer1->wallets()->where('label', 'Default')->first()?->update(['balance' => 5000.00]);

        Address::create([
            'user_id'       => $customer1->id,
            'label'         => 'Home',
            'phone'         => '09171234567',
            'address_line1' => '123 Rizal Street',
            'city'          => 'Makati',
            'province'      => 'Metro Manila',
            'postal_code'   => '1200',
            'country'       => 'Philippines',
            'is_default'    => true,
        ]);

        $customer2 = User::create([
            'name'      => 'Maria Santos',
            'email'     => 'customer2@marketplace.com',
            'password'  => Hash::make('Customer1234!'),
            'role'      => 'customer',
            'is_active' => true,
        ]);
        $customer2->wallets()->where('label', 'Default')->first()?->update(['balance' => 3000.00]);

        Address::create([
            'user_id'       => $customer2->id,
            'label'         => 'Home',
            'phone'         => '09281234567',
            'address_line1' => '456 Mabini Avenue',
            'city'          => 'Quezon City',
            'province'      => 'Metro Manila',
            'postal_code'   => '1100',
            'country'       => 'Philippines',
            'is_default'    => true,
        ]);
    }
}

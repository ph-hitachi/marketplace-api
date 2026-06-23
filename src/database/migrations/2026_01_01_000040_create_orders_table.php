<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('shop_id')->constrained('shops');
            $table->foreignId('address_id')->constrained('addresses');
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->enum('payment_method', ['wallet', 'cod'])->default('wallet');
            $table->enum('status', ['pending', 'shipped', 'delivered', 'cancelled', 'confirmed'])
                ->default('pending');
            $table->uuid('batch_ref')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->enum('cancel_reason', ['1', '2', '3', '4', '5'])->nullable();
            $table->string('cancel_reason_notes')->nullable()->comment('Required if cancel_reason is 5');
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('cancel_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('shop_id');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('product_name');
            $table->decimal('unit_price', 14, 2);
            $table->integer('quantity');
            $table->decimal('subtotal', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('order_items');
    }
};

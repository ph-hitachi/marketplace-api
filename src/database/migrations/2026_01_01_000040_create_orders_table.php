<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->foreignId('seller_id')->constrained('users');
            $table->foreignId('address_id')->constrained('addresses');
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete();
            $table->enum('payment_method', ['wallet', 'cod'])->default('wallet');
            $table->enum('status', ['pending', 'shipped', 'delivered', 'cancelled', 'confirmed'])
                  ->default('pending');
            $table->uuid('batch_ref')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->integer('cancel_reason')->nullable();
            $table->string('cancel_reason_notes')->nullable();
            $table->dateTime('shipped_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('cancel_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index('customer_id');
            $table->index('seller_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

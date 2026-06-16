<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->enum('type', ['topup', 'purchase', 'refund', 'sales']);
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_before', 14, 2);
            $table->decimal('balance_after', 14, 2);
            $table->unsignedBigInteger('reference_id')->nullable(); // orders.id for purchase/refund/sales
            $table->string('status')->default('on_hold');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->nullable();            // no updated_at

            $table->index('wallet_id');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};

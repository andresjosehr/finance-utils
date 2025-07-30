<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_book_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('p2p_market_snapshot_id')->constrained()->onDelete('cascade');
            $table->enum('side', ['bid', 'ask']); // bid = buy orders, ask = sell orders
            $table->decimal('price', 20, 8); // Price per unit
            $table->decimal('quantity', 20, 8); // Available quantity
            $table->decimal('total_amount', 20, 8); // Total fiat amount
            $table->decimal('min_order_limit', 20, 8)->nullable(); // Minimum order limit
            $table->decimal('max_order_limit', 20, 8)->nullable(); // Maximum order limit
            $table->string('merchant_name', 100)->nullable(); // Merchant/trader name
            $table->string('merchant_id', 50)->nullable(); // Binance merchant ID
            $table->integer('completion_rate')->nullable(); // Merchant completion rate (0-100)
            $table->integer('trade_count')->nullable(); // Number of trades completed by merchant
            $table->json('payment_methods')->nullable(); // Available payment methods
            $table->json('merchant_metadata')->nullable(); // Additional merchant info
            $table->boolean('is_pro_merchant')->default(false); // Pro merchant flag
            $table->boolean('is_kyc_verified')->default(false); // KYC verification status
            $table->decimal('avg_pay_time', 8, 2)->nullable(); // Average payment time in minutes
            $table->decimal('avg_release_time', 8, 2)->nullable(); // Average release time in minutes
            $table->timestamps();

            // Primary indexes for fast lookups
            $table->index(['p2p_market_snapshot_id', 'side', 'price']);
            $table->index(['side', 'price', 'quantity']);
            $table->index(['merchant_id', 'side']);
            $table->index('price');
            $table->index('is_pro_merchant');
            $table->index('completion_rate');
            
            // Composite index for order book reconstruction
            $table->index(['p2p_market_snapshot_id', 'side', 'price', 'quantity'], 'order_book_reconstruction_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_book_entries');
    }
};
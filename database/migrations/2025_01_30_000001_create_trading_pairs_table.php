<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trading_pairs', function (Blueprint $table) {
            $table->id();
            $table->string('asset', 10); // e.g., 'USDT', 'BTC'
            $table->string('fiat', 10); // e.g., 'VES', 'USD'
            $table->string('pair_symbol', 20)->unique(); // e.g., 'USDT/VES'
            $table->boolean('is_active')->default(true);
            $table->integer('collection_interval_minutes')->default(5);
            $table->json('collection_config')->nullable(); // Additional API parameters
            $table->decimal('min_trade_amount', 20, 8)->nullable();
            $table->decimal('max_trade_amount', 20, 8)->nullable();
            $table->timestamps();

            $table->index(['asset', 'fiat']);
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trading_pairs');
    }
};

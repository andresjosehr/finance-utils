<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('p2p_market_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trading_pair_id')->constrained()->onDelete('cascade');
            $table->enum('trade_type', ['BUY', 'SELL']);
            $table->timestamp('collected_at');
            $table->json('raw_data'); // Store complete API response
            $table->integer('total_ads')->default(0);
            $table->decimal('data_quality_score', 5, 4)->nullable(); // 0-1 quality score
            $table->json('collection_metadata')->nullable(); // API response time, etc.
            $table->timestamps();

            $table->index(['trading_pair_id', 'trade_type', 'collected_at']);
            $table->index('collected_at');
            $table->index('data_quality_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('p2p_market_snapshots');
    }
};
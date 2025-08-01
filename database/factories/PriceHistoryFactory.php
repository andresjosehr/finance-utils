<?php

namespace Database\Factories;

use App\Models\PriceHistory;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PriceHistory>
 */
class PriceHistoryFactory extends Factory
{
    protected $model = PriceHistory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tradeType = $this->faker->randomElement(['BUY', 'SELL']);
        $basePrice = $this->faker->randomFloat(2, 35, 40); // USDT/VES typical range

        // Generate realistic price variations
        $bestPrice = $basePrice + $this->faker->randomFloat(2, -2, 2);
        $worstPrice = $bestPrice + ($tradeType === 'BUY' ? -1 : 1) * $this->faker->randomFloat(2, 0.5, 3);
        $avgPrice = ($bestPrice + $worstPrice) / 2;
        $medianPrice = $avgPrice + $this->faker->randomFloat(2, -0.5, 0.5);

        $totalVolume = $this->faker->randomFloat(2, 1000, 50000);
        $activeOrders = $this->faker->numberBetween(5, 50);
        $merchantCount = $this->faker->numberBetween(3, min(30, $activeOrders));
        $proMerchantCount = $this->faker->numberBetween(0, intval($merchantCount * 0.4));

        $priceSpread = abs($worstPrice - $bestPrice);
        $priceSpreadPercentage = $bestPrice > 0 ? ($priceSpread / $bestPrice) * 100 : 0;

        return [
            'trading_pair_id' => TradingPair::factory(),
            'recorded_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'trade_type' => $tradeType,
            'best_price' => $bestPrice,
            'avg_price' => $avgPrice,
            'worst_price' => $worstPrice,
            'median_price' => $medianPrice,
            'total_volume' => $totalVolume,
            'total_fiat_volume' => $totalVolume * $avgPrice,
            'active_orders' => $activeOrders,
            'merchant_count' => $merchantCount,
            'pro_merchant_count' => $proMerchantCount,
            'price_spread' => $priceSpread,
            'price_spread_percentage' => $priceSpreadPercentage,
            'volume_concentration' => $this->faker->randomFloat(4, 0.2, 0.8),
            'liquidity_score' => $this->faker->randomFloat(4, 0.5, 1.0),
            'avg_completion_rate' => $this->faker->randomFloat(4, 0.85, 0.99),
            'avg_pay_time' => $this->faker->randomFloat(2, 1, 15),
            'data_quality_score' => $this->faker->randomFloat(4, 0.7, 1.0),
            'price_percentiles' => [
                '10th' => $bestPrice + $this->faker->randomFloat(2, 0, 0.5),
                '25th' => $bestPrice + $this->faker->randomFloat(2, 0.2, 0.8),
                '75th' => $bestPrice + $this->faker->randomFloat(2, 1, 2),
                '90th' => $bestPrice + $this->faker->randomFloat(2, 2, 3),
            ],
            'volume_distribution' => [
                'small_orders' => $this->faker->randomFloat(2, 0.1, 0.3), // < 500 USDT
                'medium_orders' => $this->faker->randomFloat(2, 0.4, 0.6), // 500-2000 USDT
                'large_orders' => $this->faker->randomFloat(2, 0.1, 0.3), // > 2000 USDT
            ],
        ];
    }

    /**
     * Create price history with high liquidity
     */
    public function highLiquidity(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_volume' => $this->faker->randomFloat(2, 50000, 200000),
            'active_orders' => $this->faker->numberBetween(30, 100),
            'merchant_count' => $this->faker->numberBetween(20, 50),
            'liquidity_score' => $this->faker->randomFloat(4, 0.8, 1.0),
            'price_spread_percentage' => $this->faker->randomFloat(4, 0.1, 1.0),
        ]);
    }

    /**
     * Create price history with low liquidity
     */
    public function lowLiquidity(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_volume' => $this->faker->randomFloat(2, 100, 5000),
            'active_orders' => $this->faker->numberBetween(1, 10),
            'merchant_count' => $this->faker->numberBetween(1, 5),
            'liquidity_score' => $this->faker->randomFloat(4, 0.1, 0.5),
            'price_spread_percentage' => $this->faker->randomFloat(4, 3.0, 10.0),
        ]);
    }

    /**
     * Create recent price history
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
        ]);
    }

    /**
     * Create historical price data
     */
    public function historical(): static
    {
        return $this->state(fn (array $attributes) => [
            'recorded_at' => $this->faker->dateTimeBetween('-30 days', '-1 day'),
        ]);
    }

    /**
     * Create BUY trade type history
     */
    public function buy(): static
    {
        return $this->state(fn (array $attributes) => [
            'trade_type' => 'BUY',
        ]);
    }

    /**
     * Create SELL trade type history
     */
    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'trade_type' => 'SELL',
        ]);
    }

    /**
     * Create high-quality data
     */
    public function highQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_quality_score' => $this->faker->randomFloat(4, 0.9, 1.0),
            'avg_completion_rate' => $this->faker->randomFloat(4, 0.95, 0.99),
            'avg_pay_time' => $this->faker->randomFloat(2, 1, 5),
        ]);
    }

    /**
     * Create volatile market conditions
     */
    public function volatile(): static
    {
        return $this->state(function (array $attributes) {
            $basePrice = $this->faker->randomFloat(2, 35, 40);
            $volatility = $this->faker->randomFloat(2, 3, 8); // High volatility

            $bestPrice = $basePrice + $this->faker->randomFloat(2, -$volatility, $volatility);
            $worstPrice = $bestPrice + $this->faker->randomFloat(2, -$volatility, $volatility);
            $avgPrice = ($bestPrice + $worstPrice) / 2;

            return [
                'best_price' => $bestPrice,
                'avg_price' => $avgPrice,
                'worst_price' => $worstPrice,
                'price_spread' => abs($worstPrice - $bestPrice),
                'price_spread_percentage' => $bestPrice > 0 ? (abs($worstPrice - $bestPrice) / $bestPrice) * 100 : 0,
            ];
        });
    }

    /**
     * Create stable market conditions
     */
    public function stable(): static
    {
        return $this->state(function (array $attributes) {
            $basePrice = $this->faker->randomFloat(2, 35, 40);
            $volatility = $this->faker->randomFloat(2, 0.1, 0.5); // Low volatility

            $bestPrice = $basePrice + $this->faker->randomFloat(2, -$volatility, $volatility);
            $worstPrice = $bestPrice + $this->faker->randomFloat(2, -$volatility / 2, $volatility / 2);
            $avgPrice = ($bestPrice + $worstPrice) / 2;

            return [
                'best_price' => $bestPrice,
                'avg_price' => $avgPrice,
                'worst_price' => $worstPrice,
                'price_spread' => abs($worstPrice - $bestPrice),
                'price_spread_percentage' => $bestPrice > 0 ? (abs($worstPrice - $bestPrice) / $bestPrice) * 100 : 0,
            ];
        });
    }
}

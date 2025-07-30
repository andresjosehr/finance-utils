<?php

namespace Database\Factories;

use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TradingPair>
 */
class TradingPairFactory extends Factory
{
    protected $model = TradingPair::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $asset = $this->faker->randomElement(['USDT', 'BTC', 'ETH', 'USDC', 'BUSD']);
        $fiat = $this->faker->randomElement(['VES', 'USD', 'EUR', 'COP', 'ARS', 'BRL']);
        
        return [
            'asset' => $asset,
            'fiat' => $fiat,
            'pair_symbol' => "{$asset}/{$fiat}",
            'is_active' => $this->faker->boolean(85), // 85% chance of being active
            'collection_interval_minutes' => $this->faker->randomElement([5, 10, 15, 30]),
            'collection_config' => [
                'rows' => $this->faker->numberBetween(20, 100),
                'pages' => $this->faker->numberBetween(1, 3),
                'quality_threshold' => $this->faker->randomFloat(2, 0.7, 1.0),
                'timeout_seconds' => $this->faker->numberBetween(20, 60),
                'retry_attempts' => $this->faker->numberBetween(2, 5),
            ],
            'min_trade_amount' => $this->faker->randomFloat(2, 10, 100),
            'max_trade_amount' => $this->faker->randomFloat(2, 1000, 50000),
        ];
    }

    /**
     * Indicate that the trading pair is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a USDT/VES pair (most common)
     */
    public function usdtVes(): static
    {
        return $this->state(fn (array $attributes) => [
            'asset' => 'USDT',
            'fiat' => 'VES',
            'pair_symbol' => 'USDT/VES',
            'min_trade_amount' => 50.00,
            'max_trade_amount' => 10000.00,
        ]);
    }

    /**
     * Create a high-frequency collection pair
     */
    public function highFrequency(): static
    {
        return $this->state(fn (array $attributes) => [
            'collection_interval_minutes' => 1,
            'collection_config' => [
                'rows' => 100,
                'pages' => 2,
                'quality_threshold' => 0.9,
                'timeout_seconds' => 15,
                'retry_attempts' => 5,
            ],
        ]);
    }
}
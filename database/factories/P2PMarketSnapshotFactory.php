<?php

namespace Database\Factories;

use App\Models\P2PMarketSnapshot;
use App\Models\TradingPair;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\P2PMarketSnapshot>
 */
class P2PMarketSnapshotFactory extends Factory
{
    protected $model = P2PMarketSnapshot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tradeType = $this->faker->randomElement(['BUY', 'SELL']);
        $totalAds = $this->faker->numberBetween(5, 50);

        // Generate realistic raw data structure similar to Binance P2P API
        $rawData = $this->generateBinanceP2PData($tradeType, $totalAds);

        return [
            'trading_pair_id' => TradingPair::factory(),
            'trade_type' => $tradeType,
            'collected_at' => $this->faker->dateTimeBetween('-7 days', 'now'),
            'raw_data' => $rawData,
            'total_ads' => $totalAds,
            'data_quality_score' => $this->faker->randomFloat(4, 0.6, 1.0),
            'collection_metadata' => [
                'response_time_ms' => $this->faker->numberBetween(200, 2000),
                'api_endpoint' => 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search',
                'collector_version' => '1.0.0',
                'user_agent' => 'Mozilla/5.0 (compatible; P2PCollector/1.0)',
            ],
        ];
    }

    /**
     * Generate realistic Binance P2P API response data
     */
    private function generateBinanceP2PData(string $tradeType, int $totalAds): array
    {
        $basePrice = $this->faker->randomFloat(2, 35, 40); // USDT/VES typical range
        $data = [];

        for ($i = 0; $i < $totalAds; $i++) {
            // Price variation based on order depth
            $priceVariation = $tradeType === 'BUY'
                ? $this->faker->randomFloat(2, -0.5, 0.2) // Buy prices decrease with depth
                : $this->faker->randomFloat(2, -0.2, 0.5); // Sell prices increase with depth

            $price = $basePrice + ($priceVariation * $i * 0.1);
            $quantity = $this->faker->randomFloat(2, 100, 5000);

            $data[] = [
                'adv' => [
                    'advNo' => $this->faker->uuid(),
                    'classify' => $this->faker->randomElement(['mass', 'profession', 'fiat_trade']),
                    'tradeType' => $tradeType,
                    'asset' => 'USDT',
                    'fiatUnit' => 'VES',
                    'price' => (string) $price,
                    'surplusAmount' => (string) $quantity,
                    'tradableQuantity' => (string) ($quantity * $price),
                    'minSingleTransAmount' => (string) $this->faker->randomFloat(2, 50, 500),
                    'maxSingleTransAmount' => (string) $this->faker->randomFloat(2, 1000, min(10000, $quantity * $price)),
                    'tradeMethods' => $this->generatePaymentMethods(),
                ],
                'advertiser' => [
                    'userNo' => $this->faker->regexify('[A-F0-9]{16}'),
                    'realName' => null,
                    'nickName' => $this->faker->userName(),
                    'userType' => $this->faker->randomElement(['user', 'merchant']),
                    'userGrade' => $this->faker->numberBetween(0, 5),
                    'orderCompletionRate' => $this->faker->numberBetween(85, 100).'%',
                    'monthOrderCount' => $this->faker->numberBetween(10, 500),
                    'monthFinishRate' => $this->faker->randomFloat(2, 0.85, 1.0),
                    'positiveRate' => $this->faker->randomFloat(2, 0.90, 1.0),
                    'avgPayTime' => $this->faker->randomElement(['1.5 min', '2 min', '5 min', '10 min', '15 min']),
                    'avgReleaseTime' => $this->faker->randomElement(['30 sec', '1 min', '2 min', '5 min']),
                ],
            ];
        }

        return [
            'code' => '000000',
            'message' => null,
            'messageDetail' => null,
            'data' => $data,
            'total' => $totalAds,
            'success' => true,
        ];
    }

    /**
     * Generate realistic payment methods
     */
    private function generatePaymentMethods(): array
    {
        $allMethods = [
            ['identifier' => 'BanescoPagoMovil', 'tradeMethodName' => 'Banesco Pago Móvil'],
            ['identifier' => 'VenezuelaTransfer', 'tradeMethodName' => 'Transferencia Bancaria'],
            ['identifier' => 'MercantilPagoMovil', 'tradeMethodName' => 'Mercantil Pago Móvil'],
            ['identifier' => 'Zelle', 'tradeMethodName' => 'Zelle'],
            ['identifier' => 'PayPal', 'tradeMethodName' => 'PayPal'],
            ['identifier' => 'BODPagoMovil', 'tradeMethodName' => 'BOD Pago Móvil'],
        ];

        $methodCount = $this->faker->numberBetween(1, 3);

        return $this->faker->randomElements($allMethods, $methodCount);
    }

    /**
     * Create a snapshot with high quality data
     */
    public function highQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_quality_score' => $this->faker->randomFloat(4, 0.9, 1.0),
            'total_ads' => $this->faker->numberBetween(30, 50),
            'collection_metadata' => [
                'response_time_ms' => $this->faker->numberBetween(200, 800),
                'api_endpoint' => 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search',
                'collector_version' => '1.0.0',
                'user_agent' => 'Mozilla/5.0 (compatible; P2PCollector/1.0)',
            ],
        ]);
    }

    /**
     * Create a snapshot with low quality data
     */
    public function lowQuality(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_quality_score' => $this->faker->randomFloat(4, 0.3, 0.7),
            'total_ads' => $this->faker->numberBetween(1, 10),
            'collection_metadata' => [
                'response_time_ms' => $this->faker->numberBetween(3000, 8000),
                'api_endpoint' => 'https://p2p.binance.com/bapi/c2c/v2/friendly/c2c/adv/search',
                'collector_version' => '1.0.0',
                'user_agent' => 'Mozilla/5.0 (compatible; P2PCollector/1.0)',
                'errors' => ['Timeout occurred', 'Partial data received'],
            ],
        ]);
    }

    /**
     * Create a recent snapshot
     */
    public function recent(): static
    {
        return $this->state(fn (array $attributes) => [
            'collected_at' => $this->faker->dateTimeBetween('-2 hours', 'now'),
        ]);
    }

    /**
     * Create an old snapshot
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'collected_at' => $this->faker->dateTimeBetween('-30 days', '-7 days'),
        ]);
    }

    /**
     * Create a BUY type snapshot
     */
    public function buy(): static
    {
        return $this->state(fn (array $attributes) => [
            'trade_type' => 'BUY',
        ]);
    }

    /**
     * Create a SELL type snapshot
     */
    public function sell(): static
    {
        return $this->state(fn (array $attributes) => [
            'trade_type' => 'SELL',
        ]);
    }
}

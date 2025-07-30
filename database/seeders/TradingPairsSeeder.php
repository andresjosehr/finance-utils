<?php

namespace Database\Seeders;

use App\Models\TradingPair;
use Illuminate\Database\Seeder;

class TradingPairsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pairs = [
            [
                'asset' => 'USDT',
                'fiat' => 'VES',
                'collection_interval_minutes' => 5,
                'collection_config' => [
                    'rows' => 50,
                    'priority' => 'high'
                ]
            ],
            [
                'asset' => 'USDT',
                'fiat' => 'USD',
                'collection_interval_minutes' => 5,
                'collection_config' => [
                    'rows' => 50,
                    'priority' => 'high'
                ]
            ],
            [
                'asset' => 'BTC',
                'fiat' => 'VES',
                'collection_interval_minutes' => 10,
                'collection_config' => [
                    'rows' => 30,
                    'priority' => 'medium'
                ]
            ],
            [
                'asset' => 'BTC',
                'fiat' => 'USD',
                'collection_interval_minutes' => 10,
                'collection_config' => [
                    'rows' => 30,
                    'priority' => 'medium'
                ]
            ],
            [
                'asset' => 'ETH',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low'
                ]
            ],
            [
                'asset' => 'ETH',
                'fiat' => 'USD',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low'
                ]
            ],
            [
                'asset' => 'USDC',
                'fiat' => 'VES',
                'collection_interval_minutes' => 10,
                'collection_config' => [
                    'rows' => 30,
                    'priority' => 'medium'
                ]
            ],
            [
                'asset' => 'DAI',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low'
                ]
            ]
        ];

        foreach ($pairs as $pairData) {
            $pairSymbol = $pairData['asset'] . '/' . $pairData['fiat'];
            
            TradingPair::updateOrCreate(
                [
                    'asset' => $pairData['asset'],
                    'fiat' => $pairData['fiat']
                ],
                [
                    'pair_symbol' => $pairSymbol,
                    'is_active' => true,
                    'collection_interval_minutes' => $pairData['collection_interval_minutes'],
                    'collection_config' => $pairData['collection_config'],
                    'min_trade_amount' => null,
                    'max_trade_amount' => null,
                ]
            );
        }

        $this->command->info('Created ' . count($pairs) . ' trading pairs');
    }
}
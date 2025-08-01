<?php

namespace Database\Seeders;

use App\Models\TradingPair;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TradingPairsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');
        // Erease all pairs
        TradingPair::truncate();

        $pairs = [
            [
                'asset' => 'USDT',
                'fiat' => 'VES',
                'collection_interval_minutes' => 1,
                'collection_config' => [
                    'rows' => 50,
                    'priority' => 'high',
                ],
            ],
            [
                'asset' => 'BTC',
                'fiat' => 'VES',
                'collection_interval_minutes' => 10,
                'collection_config' => [
                    'rows' => 30,
                    'priority' => 'medium',
                ],
            ],
            [
                'asset' => 'USDC',
                'fiat' => 'VES',
                'collection_interval_minutes' => 10,
                'collection_config' => [
                    'rows' => 30,
                    'priority' => 'medium',
                ],
            ],
            [
                'asset' => 'FDUSD',
                'fiat' => 'VES',
                'collection_interval_minutes' => 10,
                'collection_config' => [
                    'rows' => 30,
                    'priority' => 'medium',
                ],
            ],
            [
                'asset' => 'BNB',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low',
                ],
            ],
            [
                'asset' => 'ETH',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low',
                ],
            ],
            [
                'asset' => 'DOGE',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low',
                ],
            ],
            [
                'asset' => 'ADA',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low',
                ],
            ],
            [
                'asset' => 'XRP',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low',
                ],
            ],
            [
                'asset' => 'WLD',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low',
                ],
            ],
            [
                'asset' => 'TRUMP',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low',
                ],
            ],
            [
                'asset' => 'SOL',
                'fiat' => 'VES',
                'collection_interval_minutes' => 15,
                'collection_config' => [
                    'rows' => 20,
                    'priority' => 'low',
                ],
            ],
        ];

        foreach ($pairs as $pairData) {
            $pairSymbol = $pairData['asset'].'/'.$pairData['fiat'];

            TradingPair::updateOrCreate(
                [
                    'asset' => $pairData['asset'],
                    'fiat' => $pairData['fiat'],
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

        // Enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->command->info('Created '.count($pairs).' trading pairs');
    }
}

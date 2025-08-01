<?php

namespace App\Console\Commands;

use App\Models\TradingPair;
use Illuminate\Console\Command;

class ConfigureVolumeSampling extends Command
{
    protected $signature = 'p2p:configure-volume-sampling 
                           {--pair= : Specific trading pair (e.g., USDT/VES)}
                           {--enable : Enable volume sampling}
                           {--disable : Disable volume sampling}
                           {--ranges= : Comma-separated volume ranges (e.g., "100,500,1000,2500,5000")}
                           {--default-volume= : Default sample volume for single-point collection}
                           {--list : List current configuration}';

    protected $description = 'Configure volume sampling strategy for P2P data collection';

    public function handle()
    {
        if ($this->option('list')) {
            return $this->listCurrentConfiguration();
        }

        $pairSymbol = $this->option('pair');
        $pairs = $pairSymbol
            ? TradingPair::where('pair_symbol', $pairSymbol)->get()
            : TradingPair::all();

        if ($pairs->isEmpty()) {
            $this->error($pairSymbol ? "Trading pair '{$pairSymbol}' not found." : 'No trading pairs found.');

            return 1;
        }

        $enable = $this->option('enable');
        $disable = $this->option('disable');
        $rangesOption = $this->option('ranges');
        $defaultVolume = $this->option('default-volume');

        if ($enable && $disable) {
            $this->error('Cannot both enable and disable volume sampling.');

            return 1;
        }

        if (! $enable && ! $disable && ! $rangesOption && ! $defaultVolume) {
            $this->error('Please specify an action: --enable, --disable, --ranges, or --default-volume');

            return 1;
        }

        foreach ($pairs as $pair) {
            $this->configurePair($pair, $enable, $disable, $rangesOption, $defaultVolume);
        }

        $this->info('Volume sampling configuration updated successfully.');

        return 0;
    }

    private function configurePair(TradingPair $pair, bool $enable, bool $disable, ?string $rangesOption, ?string $defaultVolume)
    {
        $updates = [];

        if ($enable) {
            $updates['use_volume_sampling'] = true;

            // Set default ranges if not specified
            if (! $rangesOption && empty($pair->volume_ranges)) {
                $updates['volume_ranges'] = [100, 500, 1000, 2500, 5000];
            }

            $this->info("✓ Enabled volume sampling for {$pair->pair_symbol}");
        }

        if ($disable) {
            $updates['use_volume_sampling'] = false;
            $this->info("✓ Disabled volume sampling for {$pair->pair_symbol}");
        }

        if ($rangesOption) {
            $ranges = array_map('intval', array_filter(explode(',', $rangesOption)));
            if (empty($ranges)) {
                $this->error("Invalid ranges format for {$pair->pair_symbol}. Use comma-separated numbers.");

                return;
            }
            $updates['volume_ranges'] = $ranges;
            $this->info("✓ Set volume ranges for {$pair->pair_symbol}: ".implode(', ', $ranges));
        }

        if ($defaultVolume) {
            $volume = (float) $defaultVolume;
            if ($volume <= 0) {
                $this->error("Invalid default volume for {$pair->pair_symbol}. Must be positive.");

                return;
            }
            $updates['default_sample_volume'] = $volume;
            $this->info("✓ Set default sample volume for {$pair->pair_symbol}: {$volume}");
        }

        if (! empty($updates)) {
            $pair->update($updates);
        }
    }

    private function listCurrentConfiguration()
    {
        $pairs = TradingPair::all();

        $this->info('Current Volume Sampling Configuration:');
        $this->line('');

        $headers = ['Pair', 'Sampling Enabled', 'Volume Ranges', 'Default Volume'];
        $rows = [];

        foreach ($pairs as $pair) {
            $rows[] = [
                $pair->pair_symbol,
                $pair->use_volume_sampling ? '✓ Yes' : '✗ No',
                $pair->volume_ranges ? implode(', ', $pair->volume_ranges) : 'Not set',
                $pair->default_sample_volume ?? '500.00',
            ];
        }

        $this->table($headers, $rows);

        // Show summary statistics
        $enabledCount = $pairs->where('use_volume_sampling', true)->count();
        $totalCount = $pairs->count();

        $this->line('');
        $this->info("Summary: {$enabledCount}/{$totalCount} pairs have volume sampling enabled");

        return 0;
    }
}

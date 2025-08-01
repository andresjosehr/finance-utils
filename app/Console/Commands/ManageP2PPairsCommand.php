<?php

namespace App\Console\Commands;

use App\Models\TradingPair;
use Illuminate\Console\Command;

class ManageP2PPairsCommand extends Command
{
    protected $signature = 'p2p:pairs
                           {action : Action to perform: list, add, remove, activate, deactivate, update}
                           {--asset= : Asset symbol (e.g., USDT, BTC)}
                           {--fiat= : Fiat symbol (e.g., VES, USD)}
                           {--pair= : Trading pair symbol (e.g., USDT/VES)}
                           {--interval= : Collection interval in minutes (default: 5)}
                           {--min-amount= : Minimum trade amount}
                           {--max-amount= : Maximum trade amount}
                           {--all : Apply action to all pairs}
                           {--json : Output in JSON format}';

    protected $description = 'Manage P2P trading pairs';

    public function handle(): int
    {
        $action = $this->argument('action');

        try {
            match ($action) {
                'list' => $this->listPairs(),
                'add' => $this->addPair(),
                'remove' => $this->removePair(),
                'activate' => $this->activatePair(),
                'deactivate' => $this->deactivatePair(),
                'update' => $this->updatePair(),
                default => $this->error("❌ Unknown action: {$action}")
            };

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error('❌ Error: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * List trading pairs
     */
    private function listPairs(): void
    {
        $pairs = TradingPair::orderBy('pair_symbol')->get();

        if ($this->option('json')) {
            $this->line(json_encode($pairs->toArray(), JSON_PRETTY_PRINT));

            return;
        }

        $this->info('📊 Trading Pairs');
        $this->line('──────────────');

        if ($pairs->isEmpty()) {
            $this->warn('No trading pairs configured');

            return;
        }

        $headers = ['ID', 'Pair', 'Active', 'Interval', 'Min Amount', 'Max Amount', 'Created'];
        $rows = [];

        foreach ($pairs as $pair) {
            $rows[] = [
                $pair->id,
                $pair->pair_symbol,
                $pair->is_active ? '✅' : '❌',
                $pair->collection_interval_minutes.'m',
                $pair->min_trade_amount ?? '-',
                $pair->max_trade_amount ?? '-',
                $pair->created_at->format('Y-m-d H:i'),
            ];
        }

        $this->table($headers, $rows);
        $this->newLine();
        $this->info("Total: {$pairs->count()} pairs");
    }

    /**
     * Add a new trading pair
     */
    private function addPair(): void
    {
        $asset = $this->option('asset');
        $fiat = $this->option('fiat');

        if (! $asset || ! $fiat) {
            $this->error('❌ Both --asset and --fiat options are required');

            return;
        }

        $asset = strtoupper($asset);
        $fiat = strtoupper($fiat);
        $pairSymbol = "{$asset}/{$fiat}";

        // Check if pair already exists
        if (TradingPair::where('pair_symbol', $pairSymbol)->exists()) {
            $this->error("❌ Trading pair {$pairSymbol} already exists");

            return;
        }

        $config = [];
        $interval = (int) ($this->option('interval') ?? 5);
        $minAmount = $this->option('min-amount');
        $maxAmount = $this->option('max-amount');

        $pair = TradingPair::create([
            'asset' => $asset,
            'fiat' => $fiat,
            'pair_symbol' => $pairSymbol,
            'is_active' => true,
            'collection_interval_minutes' => $interval,
            'collection_config' => $config,
            'min_trade_amount' => $minAmount ? (float) $minAmount : null,
            'max_trade_amount' => $maxAmount ? (float) $maxAmount : null,
        ]);

        $this->info("✅ Added trading pair: {$pairSymbol} (ID: {$pair->id})");
        $this->line("   Interval: {$interval} minutes");
        if ($minAmount) {
            $this->line("   Min amount: {$minAmount}");
        }
        if ($maxAmount) {
            $this->line("   Max amount: {$maxAmount}");
        }
    }

    /**
     * Remove a trading pair
     */
    private function removePair(): void
    {
        $pair = $this->findPair();
        if (! $pair) {
            return;
        }

        $snapshotsCount = $pair->marketSnapshots()->count();

        if ($snapshotsCount > 0) {
            $this->warn("⚠️  This pair has {$snapshotsCount} snapshots that will also be deleted");

            if (! $this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled');

                return;
            }
        }

        $pairSymbol = $pair->pair_symbol;
        $pair->delete();

        $this->info("✅ Removed trading pair: {$pairSymbol}");
        if ($snapshotsCount > 0) {
            $this->line("   Deleted {$snapshotsCount} snapshots");
        }
    }

    /**
     * Activate trading pair(s)
     */
    private function activatePair(): void
    {
        if ($this->option('all')) {
            $count = TradingPair::where('is_active', false)->update(['is_active' => true]);
            $this->info("✅ Activated {$count} trading pair(s)");

            return;
        }

        $pair = $this->findPair();
        if (! $pair) {
            return;
        }

        if ($pair->is_active) {
            $this->warn("⚠️  Trading pair {$pair->pair_symbol} is already active");

            return;
        }

        $pair->update(['is_active' => true]);
        $this->info("✅ Activated trading pair: {$pair->pair_symbol}");
    }

    /**
     * Deactivate trading pair(s)
     */
    private function deactivatePair(): void
    {
        if ($this->option('all')) {
            $count = TradingPair::where('is_active', true)->update(['is_active' => false]);
            $this->info("✅ Deactivated {$count} trading pair(s)");

            return;
        }

        $pair = $this->findPair();
        if (! $pair) {
            return;
        }

        if (! $pair->is_active) {
            $this->warn("⚠️  Trading pair {$pair->pair_symbol} is already inactive");

            return;
        }

        $pair->update(['is_active' => false]);
        $this->info("✅ Deactivated trading pair: {$pair->pair_symbol}");
    }

    /**
     * Update a trading pair
     */
    private function updatePair(): void
    {
        $pair = $this->findPair();
        if (! $pair) {
            return;
        }

        $updates = [];

        if ($interval = $this->option('interval')) {
            $updates['collection_interval_minutes'] = (int) $interval;
        }

        if ($minAmount = $this->option('min-amount')) {
            $updates['min_trade_amount'] = (float) $minAmount;
        }

        if ($maxAmount = $this->option('max-amount')) {
            $updates['max_trade_amount'] = (float) $maxAmount;
        }

        if (empty($updates)) {
            $this->warn('⚠️  No updates specified. Use --interval, --min-amount, or --max-amount');

            return;
        }

        $pair->update($updates);

        $this->info("✅ Updated trading pair: {$pair->pair_symbol}");
        foreach ($updates as $field => $value) {
            $this->line("   {$field}: {$value}");
        }
    }

    /**
     * Find trading pair by options
     */
    private function findPair(): ?TradingPair
    {
        if ($pairSymbol = $this->option('pair')) {
            $pair = TradingPair::where('pair_symbol', strtoupper($pairSymbol))->first();

            if (! $pair) {
                $this->error("❌ Trading pair not found: {$pairSymbol}");

                return null;
            }

            return $pair;
        }

        if ($asset = $this->option('asset')) {
            $query = TradingPair::where('asset', strtoupper($asset));

            if ($fiat = $this->option('fiat')) {
                $query->where('fiat', strtoupper($fiat));
            }

            $pairs = $query->get();

            if ($pairs->isEmpty()) {
                $this->error('❌ No trading pairs found matching criteria');

                return null;
            }

            if ($pairs->count() > 1) {
                $this->error('❌ Multiple pairs found. Please specify --pair option');
                $this->line('Found pairs:');
                foreach ($pairs as $p) {
                    $this->line("  - {$p->pair_symbol}");
                }

                return null;
            }

            return $pairs->first();
        }

        $this->error('❌ Please specify --pair or --asset option');

        return null;
    }
}

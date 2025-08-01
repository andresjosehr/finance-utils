<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TradingPair;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class TradingPairController extends Controller
{
    /**
     * Display a listing of the trading pairs.
     */
    public function index()
    {
        $pairs = TradingPair::orderBy('pair_symbol')->get()->map(function ($pair) {
            return [
                'id' => $pair->id,
                'asset' => $pair->asset,
                'fiat' => $pair->fiat,
                'pair_symbol' => $pair->pair_symbol,
                'is_active' => $pair->is_active,
                'collection_interval_minutes' => $pair->collection_interval_minutes,
                'collection_config' => $pair->collection_config,
                'min_trade_amount' => $pair->min_trade_amount,
                'max_trade_amount' => $pair->max_trade_amount,
                'volume_ranges' => $pair->volume_ranges,
                'use_volume_sampling' => $pair->use_volume_sampling,
                'default_sample_volume' => $pair->default_sample_volume,
                'last_collection' => $pair->latestSnapshot()?->collected_at?->format('Y-m-d H:i:s'),
                'collection_status' => $pair->getCollectionStatus(),
            ];
        });

        return Inertia::render('Admin/TradingPairs/Index', [
            'pairs' => $pairs,
        ]);
    }

    /**
     * Show the form for creating a new trading pair.
     */
    public function create()
    {
        return Inertia::render('Admin/TradingPairs/Create');
    }

    /**
     * Store a newly created trading pair in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset' => 'required|string|max:10',
            'fiat' => 'required|string|max:10',
            'is_active' => 'boolean',
            'collection_interval_minutes' => 'required|integer|min:1|max:1440',
            'collection_config' => 'array',
            'collection_config.rows' => 'integer|min:1|max:100',
            'collection_config.priority' => 'string|in:low,medium,high',
            'min_trade_amount' => 'nullable|numeric|min:0',
            'max_trade_amount' => 'nullable|numeric|min:0',
            'use_volume_sampling' => 'boolean',
            'volume_ranges' => 'nullable|array',
            'volume_ranges.*' => 'numeric|min:1',
            'default_sample_volume' => 'nullable|numeric|min:1',
        ]);

        // Generate pair symbol
        $pairSymbol = strtoupper($validated['asset']) . '/' . strtoupper($validated['fiat']);
        
        // Check if pair already exists
        if (TradingPair::where('pair_symbol', $pairSymbol)->exists()) {
            return back()->withErrors(['pair_symbol' => 'This trading pair already exists.']);
        }

        $validated['asset'] = strtoupper($validated['asset']);
        $validated['fiat'] = strtoupper($validated['fiat']);
        $validated['pair_symbol'] = $pairSymbol;
        $validated['is_active'] = $validated['is_active'] ?? true;

        TradingPair::create($validated);

        return redirect()->route('admin.trading-pairs.index')
            ->with('success', 'Trading pair created successfully.');
    }

    /**
     * Display the specified trading pair.
     */
    public function show(TradingPair $tradingPair)
    {
        $pair = [
            'id' => $tradingPair->id,
            'asset' => $tradingPair->asset,
            'fiat' => $tradingPair->fiat,
            'pair_symbol' => $tradingPair->pair_symbol,
            'is_active' => $tradingPair->is_active,
            'collection_interval_minutes' => $tradingPair->collection_interval_minutes,
            'collection_config' => $tradingPair->collection_config,
            'min_trade_amount' => $tradingPair->min_trade_amount,
            'max_trade_amount' => $tradingPair->max_trade_amount,
            'volume_ranges' => $tradingPair->volume_ranges,
            'use_volume_sampling' => $tradingPair->use_volume_sampling,
            'default_sample_volume' => $tradingPair->default_sample_volume,
            'collection_status' => $tradingPair->getCollectionStatus(),
            'recent_snapshots' => $tradingPair->recentSnapshots(24)->take(10)->get()->map(function ($snapshot) {
                return [
                    'id' => $snapshot->id,
                    'trade_type' => $snapshot->trade_type,
                    'collected_at' => $snapshot->collected_at->format('Y-m-d H:i:s'),
                    'total_ads' => $snapshot->total_ads,
                    'data_quality_score' => $snapshot->data_quality_score,
                ];
            }),
        ];

        return Inertia::render('Admin/TradingPairs/Show', [
            'pair' => $pair,
        ]);
    }

    /**
     * Show the form for editing the specified trading pair.
     */
    public function edit(TradingPair $tradingPair)
    {
        $pair = [
            'id' => $tradingPair->id,
            'asset' => $tradingPair->asset,
            'fiat' => $tradingPair->fiat,
            'pair_symbol' => $tradingPair->pair_symbol,
            'is_active' => $tradingPair->is_active,
            'collection_interval_minutes' => $tradingPair->collection_interval_minutes,
            'collection_config' => $tradingPair->collection_config ?? [],
            'min_trade_amount' => $tradingPair->min_trade_amount,
            'max_trade_amount' => $tradingPair->max_trade_amount,
            'volume_ranges' => $tradingPair->volume_ranges ?? [],
            'use_volume_sampling' => $tradingPair->use_volume_sampling,
            'default_sample_volume' => $tradingPair->default_sample_volume ?? 500,
        ];

        return Inertia::render('Admin/TradingPairs/Edit', [
            'pair' => $pair,
        ]);
    }

    /**
     * Update the specified trading pair in storage.
     */
    public function update(Request $request, TradingPair $tradingPair)
    {
        $validated = $request->validate([
            'asset' => 'required|string|max:10',
            'fiat' => 'required|string|max:10',
            'is_active' => 'boolean',
            'collection_interval_minutes' => 'required|integer|min:1|max:1440',
            'collection_config' => 'array',
            'collection_config.rows' => 'integer|min:1|max:100',
            'collection_config.priority' => 'string|in:low,medium,high',
            'min_trade_amount' => 'nullable|numeric|min:0',
            'max_trade_amount' => 'nullable|numeric|min:0',
            'use_volume_sampling' => 'boolean',
            'volume_ranges' => 'nullable|array',
            'volume_ranges.*' => 'numeric|min:1',
            'default_sample_volume' => 'nullable|numeric|min:1',
        ]);

        // Generate new pair symbol
        $newPairSymbol = strtoupper($validated['asset']) . '/' . strtoupper($validated['fiat']);
        
        // Check if changing to an existing pair symbol
        if ($newPairSymbol !== $tradingPair->pair_symbol && 
            TradingPair::where('pair_symbol', $newPairSymbol)->exists()) {
            return back()->withErrors(['pair_symbol' => 'This trading pair already exists.']);
        }

        $validated['asset'] = strtoupper($validated['asset']);
        $validated['fiat'] = strtoupper($validated['fiat']);
        $validated['pair_symbol'] = $newPairSymbol;

        $tradingPair->update($validated);

        return redirect()->route('admin.trading-pairs.index')
            ->with('success', 'Trading pair updated successfully.');
    }

    /**
     * Remove the specified trading pair from storage.
     */
    public function destroy(TradingPair $tradingPair)
    {
        // Check if pair has data
        $snapshotCount = $tradingPair->marketSnapshots()->count();
        
        if ($snapshotCount > 0) {
            return back()->withErrors([
                'delete' => "Cannot delete trading pair with {$snapshotCount} market snapshots. Please clean up data first."
            ]);
        }

        $tradingPair->delete();

        return redirect()->route('admin.trading-pairs.index')
            ->with('success', 'Trading pair deleted successfully.');
    }

    /**
     * Toggle the active status of a trading pair.
     */
    public function toggleActive(TradingPair $tradingPair)
    {
        $tradingPair->update([
            'is_active' => !$tradingPair->is_active
        ]);

        $status = $tradingPair->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Trading pair {$status} successfully.");
    }

    /**
     * Bulk update volume sampling configuration.
     */
    public function bulkUpdateVolumeSampling(Request $request)
    {
        $validated = $request->validate([
            'pair_ids' => 'required|array',
            'pair_ids.*' => 'integer|exists:trading_pairs,id',
            'action' => 'required|string|in:enable,disable,update_ranges,update_volume',
            'volume_ranges' => 'nullable|array',
            'volume_ranges.*' => 'numeric|min:1',
            'default_sample_volume' => 'nullable|numeric|min:1',
        ]);

        $pairs = TradingPair::whereIn('id', $validated['pair_ids'])->get();
        $updatedCount = 0;

        foreach ($pairs as $pair) {
            $updates = [];

            switch ($validated['action']) {
                case 'enable':
                    $updates['use_volume_sampling'] = true;
                    if (empty($pair->volume_ranges) && !empty($validated['volume_ranges'])) {
                        $updates['volume_ranges'] = $validated['volume_ranges'];
                    }
                    break;
                
                case 'disable':
                    $updates['use_volume_sampling'] = false;
                    break;
                
                case 'update_ranges':
                    if (!empty($validated['volume_ranges'])) {
                        $updates['volume_ranges'] = $validated['volume_ranges'];
                    }
                    break;
                
                case 'update_volume':
                    if (!empty($validated['default_sample_volume'])) {
                        $updates['default_sample_volume'] = $validated['default_sample_volume'];
                    }
                    break;
            }

            if (!empty($updates)) {
                $pair->update($updates);
                $updatedCount++;
            }
        }

        return back()->with('success', "{$updatedCount} trading pairs updated successfully.");
    }

    /**
     * Get collection statistics for all pairs.
     */
    public function statistics()
    {
        $pairs = TradingPair::all();
        
        $stats = [
            'total_pairs' => $pairs->count(),
            'active_pairs' => $pairs->where('is_active', true)->count(),
            'inactive_pairs' => $pairs->where('is_active', false)->count(),
            'volume_sampling_enabled' => $pairs->where('use_volume_sampling', true)->count(),
            'volume_sampling_disabled' => $pairs->where('use_volume_sampling', false)->count(),
            'pairs_by_fiat' => $pairs->groupBy('fiat')->map->count(),
            'pairs_by_asset' => $pairs->groupBy('asset')->map->count(),
            'collection_intervals' => $pairs->groupBy('collection_interval_minutes')->map->count(),
        ];

        return Inertia::render('Admin/TradingPairs/Statistics', [
            'statistics' => $stats,
            'pairs' => $pairs->map(function ($pair) {
                return [
                    'id' => $pair->id,
                    'pair_symbol' => $pair->pair_symbol,
                    'is_active' => $pair->is_active,
                    'use_volume_sampling' => $pair->use_volume_sampling,
                    'collection_interval_minutes' => $pair->collection_interval_minutes,
                    'collection_status' => $pair->getCollectionStatus(),
                ];
            }),
        ]);
    }
}
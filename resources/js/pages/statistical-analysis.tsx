import React, { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { StatisticalAnalysisDashboard } from '@/components/statistical-analysis-dashboard';
import { OutlierAnalysisChart } from '@/components/outlier-analysis-chart';
import { ComprehensiveMarketAnalysis } from '@/components/comprehensive-market-analysis';
import { VolatilityAnalysisChart } from '@/components/volatility-analysis-chart';

interface TradingPairsResponse {
    assets: string[];
    fiats: string[];
    pairs: Array<{
        asset: string;
        fiat: string;
        pair_symbol: string;
    }>;
    asset_fiat_mapping: Record<string, string[]>;
    fiat_asset_mapping: Record<string, string[]>;
}


export default function StatisticalAnalysisPage() {
    const [selectedAsset, setSelectedAsset] = useState('USDT');
    const [selectedFiat, setSelectedFiat] = useState('VES');
    const [selectedTradeType, setSelectedTradeType] = useState('BUY');
    const [tradingPairsData, setTradingPairsData] = useState<TradingPairsResponse | null>(null);
    const [loading, setLoading] = useState(true);

    // Asset name mapping for display labels
    const assetLabels: Record<string, string> = {
        'USDT': 'USDT (Tether)',
        'BTC': 'BTC (Bitcoin)',
        'ETH': 'ETH (Ethereum)',
        'BNB': 'BNB (Binance Coin)',
        'BUSD': 'BUSD (Binance USD)',
        'USDC': 'USDC (USD Coin)',
        'ADA': 'ADA (Cardano)',
        'DOT': 'DOT (Polkadot)',
        'MATIC': 'MATIC (Polygon)',
        'DOGE': 'DOGE (Dogecoin)',
    };

    const fiatLabels: Record<string, string> = {
        'VES': 'VES (Venezuelan Bolívar)',
        'USD': 'USD (US Dollar)',
        'EUR': 'EUR (Euro)',
        'GBP': 'GBP (British Pound)',
        'CNY': 'CNY (Chinese Yuan)',
        'ARS': 'ARS (Argentine Peso)',
        'COP': 'COP (Colombian Peso)',
        'PEN': 'PEN (Peruvian Sol)',
        'BRL': 'BRL (Brazilian Real)',
        'MXN': 'MXN (Mexican Peso)',
    };

    // Fetch trading pairs data on component mount
    useEffect(() => {
        const fetchTradingPairs = async () => {
            try {
                const response = await fetch('/api/binance-p2p/trading-pairs');
                if (response.ok) {
                    const data: TradingPairsResponse = await response.json();
                    setTradingPairsData(data);
                    
                    // Set default selections based on available data
                    if (data.assets.length > 0 && data.fiats.length > 0) {
                        const defaultAsset = data.assets.includes('USDT') ? 'USDT' : data.assets[0];
                        const availableFiatsForAsset = data.asset_fiat_mapping[defaultAsset] || [];
                        const defaultFiat = availableFiatsForAsset.includes('VES') ? 'VES' : availableFiatsForAsset[0];
                        
                        setSelectedAsset(defaultAsset);
                        setSelectedFiat(defaultFiat);
                    }
                } else {
                    console.error('Failed to fetch trading pairs');
                }
            } catch (error) {
                console.error('Error fetching trading pairs:', error);
            } finally {
                setLoading(false);
            }
        };

        fetchTradingPairs();
    }, []);

    // Handle asset selection change
    const handleAssetChange = (newAsset: string) => {
        setSelectedAsset(newAsset);
        
        // Update fiat selection to the first available option for this asset
        if (tradingPairsData) {
            const availableFiats = tradingPairsData.asset_fiat_mapping[newAsset] || [];
            if (availableFiats.length > 0 && !availableFiats.includes(selectedFiat)) {
                setSelectedFiat(availableFiats[0]);
            }
        }
    };

    // Handle fiat selection change
    const handleFiatChange = (newFiat: string) => {
        setSelectedFiat(newFiat);
        
        // Update asset selection to the first available option for this fiat
        if (tradingPairsData) {
            const availableAssets = tradingPairsData.fiat_asset_mapping[newFiat] || [];
            if (availableAssets.length > 0 && !availableAssets.includes(selectedAsset)) {
                setSelectedAsset(availableAssets[0]);
            }
        }
    };

    // Get filtered options based on current selections
    const getAvailableAssets = () => {
        if (!tradingPairsData) return [];
        
        return tradingPairsData.assets.map(asset => ({
            value: asset,
            label: assetLabels[asset] || asset,
            disabled: false
        }));
    };

    const getAvailableFiats = () => {
        if (!tradingPairsData) return [];
        
        const availableFiats = tradingPairsData.asset_fiat_mapping[selectedAsset] || [];
        
        return tradingPairsData.fiats.map(fiat => ({
            value: fiat,
            label: fiatLabels[fiat] || fiat,
            disabled: !availableFiats.includes(fiat)
        }));
    };

    return (
        <AppLayout>
            <Head title="Análisis Estadístico" />
            
            <div className="space-y-4 p-8 max-w-none">
                {/* Page Header */}
                <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold text-foreground">Análisis Estadístico</h1>
                        <p className="text-muted-foreground text-sm mt-1">
                            Análisis estadístico avanzado para mercados P2P de criptomonedas con detección de valores atípicos y análisis de tendencias
                        </p>
                    </div>
                    
                    <div className="flex flex-wrap gap-2 lg:flex-nowrap">
                        <Select value={selectedAsset} onValueChange={handleAssetChange} disabled={loading}>
                            <SelectTrigger className="w-52">
                                <SelectValue placeholder={loading ? "Cargando..." : "Seleccionar activo"} />
                            </SelectTrigger>
                            <SelectContent>
                                {getAvailableAssets().map((asset) => (
                                    <SelectItem 
                                        key={asset.value} 
                                        value={asset.value}
                                        disabled={asset.disabled}
                                    >
                                        {asset.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        
                        <Select value={selectedFiat} onValueChange={handleFiatChange} disabled={loading}>
                            <SelectTrigger className="w-52">
                                <SelectValue placeholder={loading ? "Cargando..." : "Seleccionar moneda"} />
                            </SelectTrigger>
                            <SelectContent>
                                {getAvailableFiats().map((fiat) => (
                                    <SelectItem 
                                        key={fiat.value} 
                                        value={fiat.value}
                                        disabled={fiat.disabled}
                                    >
                                        {fiat.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        
                        <Select value={selectedTradeType} onValueChange={setSelectedTradeType}>
                            <SelectTrigger className="w-52">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="BUY">Comprar</SelectItem>
                                <SelectItem value="SELL">Vender</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>


                {/* Analysis Tabs */}
                <Tabs defaultValue="comprehensive" className="space-y-4">
                    <TabsList className="grid w-full grid-cols-4">
                        <TabsTrigger value="comprehensive">Integral</TabsTrigger>
                        <TabsTrigger value="detailed">Análisis Detallado</TabsTrigger>
                        <TabsTrigger value="outliers">Detección de Atípicos</TabsTrigger>
                        <TabsTrigger value="volatility">Volatilidad</TabsTrigger>
                    </TabsList>

                    <TabsContent value="comprehensive" className="space-y-4">
                        <ComprehensiveMarketAnalysis
                            asset={selectedAsset}
                            fiat={selectedFiat}
                        />
                    </TabsContent>

                    <TabsContent value="detailed" className="space-y-4">
                        <StatisticalAnalysisDashboard
                            asset={selectedAsset}
                            fiat={selectedFiat}
                            tradeType={selectedTradeType}
                        />
                    </TabsContent>

                    <TabsContent value="outliers" className="space-y-4">
                        <OutlierAnalysisChart
                            asset={selectedAsset}
                            fiat={selectedFiat}
                            tradeType={selectedTradeType}
                        />
                    </TabsContent>

                    <TabsContent value="volatility" className="space-y-4">
                        <VolatilityAnalysisChart
                            asset={selectedAsset}
                            fiat={selectedFiat}
                            tradeType={selectedTradeType}
                        />
                    </TabsContent>
                </Tabs>

            </div>
        </AppLayout>
    );
}
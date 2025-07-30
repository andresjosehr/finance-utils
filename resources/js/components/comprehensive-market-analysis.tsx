import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Alert, AlertDescription, AlertTitle } from './ui/alert';
import { Skeleton } from './ui/skeleton';
import { cn } from '@/lib/utils';
import { HistoricalPriceChart } from './historical-price-chart';

interface ComprehensiveAnalysisData {
    asset: string;
    fiat: string;
    timestamp: string;
    buy_analysis: MarketAnalysis | null;
    sell_analysis: MarketAnalysis | null;
    market_comparison: MarketComparison | null;
}

interface MarketAnalysis {
    raw_statistics: StatisticalMeasures;
    cleaned_statistics: StatisticalMeasures;
    outlier_analysis: OutlierAnalysis;
    weighted_averages: WeightedAverages;
    confidence_intervals: ConfidenceInterval;
    percentile_analysis: PercentileAnalysis;
    trend_analysis: TrendAnalysis;
    volatility_analysis: VolatilityAnalysis;
    quality_metrics: QualityMetrics;
}

interface StatisticalMeasures {
    count: number;
    mean: number;
    median: number;
    standard_deviation: number;
    coefficient_of_variation: number;
    min: number;
    max: number;
    range: number;
}

interface OutlierAnalysis {
    outliers_detected: number;
    outlier_percentage: number;
}

interface WeightedAverages {
    volume_weighted: number;
    trade_count_weighted: number;
    reliability_weighted: number;
    amount_weighted: number;
}

interface ConfidenceInterval {
    confidence_level: number;
    mean: number;
    lower_bound: number;
    upper_bound: number;
    margin_of_error: number;
}

interface PercentileAnalysis {
    P25: number;
    P50: number;
    P75: number;
    P90: number;
    P95: number;
}

interface TrendAnalysis {
    trend_direction: string;
    trend_strength: string;
    slope: number;
    r_squared: number;
}

interface VolatilityAnalysis {
    relative_volatility: number;
    volatility_classification: string;
}

interface QualityMetrics {
    quality_score: number;
    data_retention_rate: number;
    outlier_rate: number;
}

interface MarketComparison {
    price_spread: {
        absolute: number;
        percentage: number;
        assessment: string;
    };
    volatility_comparison: {
        buy_volatility: number;
        sell_volatility: number;
        volatility_difference: number;
    };
    liquidity_comparison: {
        buy_sample_size: number;
        sell_sample_size: number;
        liquidity_balance: number;
    };
    quality_comparison: {
        buy_quality_score: number;
        sell_quality_score: number;
        quality_difference: number;
    };
    arbitrage_opportunity: {
        exists: boolean;
        potential_profit_percentage: number;
        risk_assessment: string;
    };
}

interface ComprehensiveMarketAnalysisProps {
    asset?: string;
    fiat?: string;
    className?: string;
}

export function ComprehensiveMarketAnalysis({
    asset = 'USDT',
    fiat = 'VES',
    className,
}: ComprehensiveMarketAnalysisProps) {
    const [data, setData] = useState<ComprehensiveAnalysisData | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [outlierMethod, setOutlierMethod] = useState<string>('iqr');
    const [confidenceLevel, setConfidenceLevel] = useState<number>(0.95);
    const [sampleSize, setSampleSize] = useState<number>(50);

    const fetchComprehensiveAnalysis = async () => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams({
                asset,
                fiat,
                outlier_method: outlierMethod,
                confidence_level: confidenceLevel.toString(),
                rows: sampleSize.toString(),
            });

            const response = await fetch(`/api/binance-p2p/comprehensive-analysis?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            setData(result);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error al obtener el análisis integral');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchComprehensiveAnalysis();
    }, [asset, fiat, outlierMethod, confidenceLevel, sampleSize]);

    const formatNumber = (value: number | null, decimals = 2): string => {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        return value.toLocaleString('en-US', { 
            minimumFractionDigits: decimals, 
            maximumFractionDigits: decimals 
        });
    };

    const formatPercentage = (value: number | null, decimals = 2): string => {
        if (value === null || value === undefined || isNaN(value)) return 'N/A';
        return `${value.toFixed(decimals)}%`;
    };

    const getSpreadBadge = (assessment: string): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "secondary";
        
        switch (assessment) {
            case 'tight':
                variant = "default";
                break;
            case 'normal':
                variant = "secondary";
                break;
            case 'wide':
                variant = "outline";
                break;
            default:
                variant = "secondary";
        }

        return <Badge variant={variant}>{assessment}</Badge>;
    };

    const getArbitrageRiskBadge = (risk: string): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "secondary";
        
        switch (risk) {
            case 'very_low':
                variant = "default";
                break;
            case 'low':
                variant = "secondary";
                break;
            case 'moderate':
                variant = "outline";
                break;
            case 'high':
                variant = "destructive";
                break;
            case 'very_high':
                variant = "destructive";
                break;
        }

        return <Badge variant={variant}>{risk.replace('_', ' ')}</Badge>;
    };

    const getTrendBadge = (direction: string): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "secondary";
        
        switch (direction) {
            case 'upward':
                variant = "default";
                break;
            case 'stable':
                variant = "secondary";
                break;
            case 'downward':
                variant = "destructive";
                break;
        }

        return <Badge variant={variant}>{direction}</Badge>;
    };

    const getQualityBadge = (score: number): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "default";
        
        if (score >= 0.8) variant = "default";
        else if (score >= 0.6) variant = "secondary";
        else if (score >= 0.4) variant = "outline";
        else variant = "destructive";

        return <Badge variant={variant}>{formatPercentage(score * 100)}</Badge>;
    };

    if (loading) {
        return (
            <div className={cn("space-y-6", className)}>
                <div className="flex items-center justify-between">
                    <Skeleton className="h-8 w-64" />
                    <Skeleton className="h-10 w-32" />
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {Array.from({ length: 6 }).map((_, i) => (
                        <Card key={i}>
                            <CardHeader>
                                <Skeleton className="h-6 w-32" />
                                <Skeleton className="h-4 w-48" />
                            </CardHeader>
                            <CardContent>
                                <Skeleton className="h-32 w-full" />
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <Alert className={cn("border-destructive/50 bg-destructive/10", className)}>
                <AlertTitle className="text-foreground">Error</AlertTitle>
                <AlertDescription className="text-muted-foreground">{error}</AlertDescription>
            </Alert>
        );
    }

    if (!data) {
        return (
            <Alert className={cn("border-border bg-muted/20", className)}>
                <AlertTitle className="text-foreground">Sin Datos</AlertTitle>
                <AlertDescription className="text-muted-foreground">No hay datos de análisis integral disponibles.</AlertDescription>
            </Alert>
        );
    }

    return (
        <div className={cn("space-y-6", className)}>
            {/* Header with Controls */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold text-foreground">
                        Análisis Integral de Mercado: {data.asset}/{data.fiat}
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Compara mercados de compra y venta con análisis estadístico avanzado
                    </p>
                </div>
                
                <div className="flex flex-wrap gap-2">
                    <Select value={outlierMethod} onValueChange={setOutlierMethod}>
                        <SelectTrigger className="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="iqr">Método IQR</SelectItem>
                            <SelectItem value="zscore">Puntuación Z</SelectItem>
                            <SelectItem value="modified_zscore">Puntuación Z Modificada</SelectItem>
                        </SelectContent>
                    </Select>
                    
                    <Select value={confidenceLevel.toString()} onValueChange={(value) => setConfidenceLevel(parseFloat(value))}>
                        <SelectTrigger className="w-24">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="0.90">90%</SelectItem>
                            <SelectItem value="0.95">95%</SelectItem>
                            <SelectItem value="0.99">99%</SelectItem>
                        </SelectContent>
                    </Select>
                    
                    <Select value={sampleSize.toString()} onValueChange={(value) => setSampleSize(parseInt(value))}>
                        <SelectTrigger className="w-20">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="25">25</SelectItem>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                        </SelectContent>
                    </Select>
                    
                    <Button onClick={fetchComprehensiveAnalysis} size="sm">
                        Actualizar
                    </Button>
                </div>
            </div>

            {/* Market Comparison Overview */}
            {data.market_comparison && (
                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between text-foreground">
                            Resumen de Comparación de Mercados
                            {data.market_comparison.arbitrage_opportunity.exists && (
                                <Badge variant="default" className="bg-muted text-foreground">
                                    Oportunidad de Arbitraje
                                </Badge>
                            )}
                        </CardTitle>
                        <CardDescription className="text-muted-foreground">
                            Diferencias clave entre mercados de compra y venta
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="text-center p-4 bg-muted/30 rounded-lg border border-border">
                                <div className="text-xl font-bold text-foreground">
                                    {formatNumber(data.market_comparison.price_spread.absolute, 4)}
                                </div>
                                <div className="text-sm text-muted-foreground mb-1">Diferencial de Precio</div>
                                {getSpreadBadge(data.market_comparison.price_spread.assessment)}
                            </div>
                            
                            <div className="text-center p-4 bg-muted/30 rounded-lg border border-border">
                                <div className="text-xl font-bold text-foreground">
                                    {formatPercentage(data.market_comparison.price_spread.percentage)}
                                </div>
                                <div className="text-sm text-muted-foreground mb-1">Diferencial %</div>
                                <div className="text-xs text-muted-foreground/80">
                                    {formatPercentage(data.market_comparison.arbitrage_opportunity.potential_profit_percentage)} ganancia
                                </div>
                            </div>
                            
                            <div className="text-center p-4 bg-muted/30 rounded-lg border border-border">
                                <div className="text-xl font-bold text-foreground">
                                    {formatNumber(data.market_comparison.volatility_comparison.volatility_difference, 2)}
                                </div>
                                <div className="text-sm text-muted-foreground mb-1">Dif. Volatilidad</div>
                                <div className="text-xs text-muted-foreground/80">
                                    Compra: {formatPercentage(data.market_comparison.volatility_comparison.buy_volatility)} | 
                                    Venta: {formatPercentage(data.market_comparison.volatility_comparison.sell_volatility)}
                                </div>
                            </div>
                            
                            <div className="text-center p-4 bg-muted/30 rounded-lg border border-border">
                                <div className="text-xl font-bold text-foreground">
                                    {getArbitrageRiskBadge(data.market_comparison.arbitrage_opportunity.risk_assessment)}
                                </div>
                                <div className="text-sm text-muted-foreground mb-1">Nivel de Riesgo</div>
                                <div className="text-xs text-muted-foreground/80">
                                    Calidad: {formatPercentage((data.market_comparison.quality_comparison.buy_quality_score + 
                                                            data.market_comparison.quality_comparison.sell_quality_score) / 2 * 100)}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Side-by-Side Market Analysis */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Buy Market Analysis */}
                {data.buy_analysis && (
                    <Card className="border-border bg-card">
                        <CardHeader>
                            <CardTitle className="text-foreground flex items-center justify-between">
                                Análisis de Mercado de Compra
                                {getQualityBadge(data.buy_analysis.quality_metrics.quality_score)}
                            </CardTitle>
                            <CardDescription className="text-muted-foreground">Análisis estadístico de órdenes de compra</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Key Statistics */}
                            <div>
                                <h4 className="font-semibold mb-2 text-foreground">Estadísticas de Precio</h4>
                                <div className="grid grid-cols-2 gap-2 text-sm">
                                    <div className="flex justify-between">
                                        <span>Media:</span>
                                        <span className="font-semibold">{formatNumber(data.buy_analysis.cleaned_statistics.mean, 4)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Mediana:</span>
                                        <span className="font-semibold">{formatNumber(data.buy_analysis.cleaned_statistics.median, 4)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Desv. Est.:</span>
                                        <span className="font-semibold">{formatNumber(data.buy_analysis.cleaned_statistics.standard_deviation, 4)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>CV:</span>
                                        <span className="font-semibold">{formatPercentage(data.buy_analysis.cleaned_statistics.coefficient_of_variation)}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Weighted Averages */}
                            <div>
                                <h4 className="font-semibold mb-2 text-foreground">Promedios Ponderados</h4>
                                <div className="grid grid-cols-1 gap-1 text-sm">
                                    <div className="flex justify-between">
                                        <span>Volumen:</span>
                                        <span className="font-semibold">{formatNumber(data.buy_analysis.weighted_averages.volume_weighted, 4)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Confiabilidad:</span>
                                        <span className="font-semibold">{formatNumber(data.buy_analysis.weighted_averages.reliability_weighted, 4)}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Confidence Interval */}
                            <div>
                                <h4 className="font-semibold mb-2">Intervalo de Confianza ({formatPercentage(data.buy_analysis.confidence_intervals.confidence_level * 100)})</h4>
                                <div className="bg-muted/30 border border-border p-2 rounded text-center text-sm">
                                    <div className="font-semibold text-foreground">
                                        {formatNumber(data.buy_analysis.confidence_intervals.lower_bound, 4)} - {formatNumber(data.buy_analysis.confidence_intervals.upper_bound, 4)}
                                    </div>
                                    <div className="text-xs text-muted-foreground">
                                        ± {formatNumber(data.buy_analysis.confidence_intervals.margin_of_error, 4)}
                                    </div>
                                </div>
                            </div>

                            {/* Trend and Volatility */}
                            <div className="flex justify-between items-center">
                                <div>
                                    <div className="text-sm text-muted-foreground">Tendencia</div>
                                    {getTrendBadge(data.buy_analysis.trend_analysis.trend_direction)}
                                </div>
                                <div>
                                    <div className="text-sm text-muted-foreground">Volatilidad</div>
                                    <Badge variant="secondary">{data.buy_analysis.volatility_analysis.volatility_classification.replace('_', ' ')}</Badge>
                                </div>
                            </div>

                            {/* Outliers */}
                            <div className="flex justify-between text-sm">
                                <span>Valores Atípicos Eliminados:</span>
                                <span className="font-semibold text-foreground">
                                    {data.buy_analysis.outlier_analysis.outliers_detected} ({formatPercentage(data.buy_analysis.outlier_analysis.outlier_percentage)})
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Sell Market Analysis */}
                {data.sell_analysis && (
                    <Card className="border-border bg-card">
                        <CardHeader>
                            <CardTitle className="text-foreground flex items-center justify-between">
                                Análisis de Mercado de Venta
                                {getQualityBadge(data.sell_analysis.quality_metrics.quality_score)}
                            </CardTitle>
                            <CardDescription className="text-muted-foreground">Análisis estadístico de órdenes de venta</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {/* Key Statistics */}
                            <div>
                                <h4 className="font-semibold mb-2 text-foreground">Estadísticas de Precio</h4>
                                <div className="grid grid-cols-2 gap-2 text-sm">
                                    <div className="flex justify-between">
                                        <span>Media:</span>
                                        <span className="font-semibold">{formatNumber(data.sell_analysis.cleaned_statistics.mean, 4)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Mediana:</span>
                                        <span className="font-semibold">{formatNumber(data.sell_analysis.cleaned_statistics.median, 4)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Desv. Est.:</span>
                                        <span className="font-semibold">{formatNumber(data.sell_analysis.cleaned_statistics.standard_deviation, 4)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>CV:</span>
                                        <span className="font-semibold">{formatPercentage(data.sell_analysis.cleaned_statistics.coefficient_of_variation)}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Weighted Averages */}
                            <div>
                                <h4 className="font-semibold mb-2 text-foreground">Promedios Ponderados</h4>
                                <div className="grid grid-cols-1 gap-1 text-sm">
                                    <div className="flex justify-between">
                                        <span>Volumen:</span>
                                        <span className="font-semibold">{formatNumber(data.sell_analysis.weighted_averages.volume_weighted, 4)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Confiabilidad:</span>
                                        <span className="font-semibold">{formatNumber(data.sell_analysis.weighted_averages.reliability_weighted, 4)}</span>
                                    </div>
                                </div>
                            </div>

                            {/* Confidence Interval */}
                            <div>
                                <h4 className="font-semibold mb-2">Confidence Interval ({formatPercentage(data.sell_analysis.confidence_intervals.confidence_level * 100)})</h4>
                                <div className="bg-muted/30 border border-border p-2 rounded text-center text-sm">
                                    <div className="font-semibold text-foreground">
                                        {formatNumber(data.sell_analysis.confidence_intervals.lower_bound, 4)} - {formatNumber(data.sell_analysis.confidence_intervals.upper_bound, 4)}
                                    </div>
                                    <div className="text-xs text-foreground">
                                        ± {formatNumber(data.sell_analysis.confidence_intervals.margin_of_error, 4)}
                                    </div>
                                </div>
                            </div>

                            {/* Trend and Volatility */}
                            <div className="flex justify-between items-center">
                                <div>
                                    <div className="text-sm text-muted-foreground">Tendencia</div>
                                    {getTrendBadge(data.sell_analysis.trend_analysis.trend_direction)}
                                </div>
                                <div>
                                    <div className="text-sm text-muted-foreground">Volatilidad</div>
                                    <Badge variant="secondary">{data.sell_analysis.volatility_analysis.volatility_classification.replace('_', ' ')}</Badge>
                                </div>
                            </div>

                            {/* Outliers */}
                            <div className="flex justify-between text-sm">
                                <span>Valores Atípicos Eliminados:</span>
                                <span className="font-semibold text-foreground">
                                    {data.sell_analysis.outlier_analysis.outliers_detected} ({formatPercentage(data.sell_analysis.outlier_analysis.outlier_percentage)})
                                </span>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>

            {/* Percentile Comparison */}
            {data.buy_analysis && data.sell_analysis && (
                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="text-foreground">Comparación de Percentiles</CardTitle>
                        <CardDescription className="text-muted-foreground">Comparación de distribución de precios entre percentiles</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="border-b border-border">
                                        <th className="text-left p-2 text-foreground">Percentil</th>
                                        <th className="text-right p-2 text-muted-foreground">Mercado Compra</th>
                                        <th className="text-right p-2 text-muted-foreground">Mercado Venta</th>
                                        <th className="text-right p-2 text-foreground">Diferencia</th>
                                        <th className="text-right p-2 text-foreground">Diferencia %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {['P25', 'P50', 'P75', 'P90', 'P95'].map((percentile) => {
                                        const buyValue = data.buy_analysis!.percentile_analysis[percentile as keyof PercentileAnalysis];
                                        const sellValue = data.sell_analysis!.percentile_analysis[percentile as keyof PercentileAnalysis];
                                        const diff = sellValue - buyValue;
                                        const diffPercent = buyValue > 0 ? (diff / buyValue) * 100 : 0;
                                        
                                        return (
                                            <tr key={percentile} className="border-b border-border">
                                                <td className="p-2 font-medium text-foreground">{percentile}</td>
                                                <td className="p-2 text-right text-muted-foreground">{formatNumber(buyValue, 4)}</td>
                                                <td className="p-2 text-right text-muted-foreground">{formatNumber(sellValue, 4)}</td>
                                                <td className="p-2 text-right font-semibold text-foreground">{formatNumber(diff, 4)}</td>
                                                <td className="p-2 text-right text-foreground">{formatPercentage(diffPercent)}</td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Historical Price Chart */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="text-foreground">Evolución Histórica de Precios</CardTitle>
                    <CardDescription className="text-muted-foreground">
                        Tendencia temporal de precios de compra/venta y spread de mercado
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <HistoricalPriceChart asset={asset} fiat={fiat} />
                </CardContent>
            </Card>
        </div>
    );
}
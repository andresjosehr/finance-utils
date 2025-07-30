import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Alert, AlertDescription, AlertTitle } from './ui/alert';
import { Skeleton } from './ui/skeleton';
import { cn } from '@/lib/utils';

interface StatisticalAnalysisData {
    asset: string;
    fiat: string;
    trade_type: string;
    timestamp: string;
    analysis: {
        raw_statistics: StatisticalMeasures;
        cleaned_statistics: StatisticalMeasures;
        outlier_analysis: OutlierAnalysis;
        weighted_averages: WeightedAverages;
        time_weighted_averages: TimeWeightedAverages;
        confidence_intervals: ConfidenceInterval;
        percentile_analysis: PercentileAnalysis;
        trend_analysis: TrendAnalysis;
        volatility_analysis: VolatilityAnalysis;
        statistical_tests: StatisticalTests;
        quality_metrics: QualityMetrics;
    };
    metadata: {
        sample_size: number;
        outlier_method: string;
        confidence_level: number;
    };
}

interface StatisticalMeasures {
    count: number;
    mean: number;
    median: number;
    mode: number | null;
    standard_deviation: number;
    variance: number;
    min: number;
    max: number;
    range: number;
    coefficient_of_variation: number;
    skewness: number;
    kurtosis: number;
}

interface OutlierAnalysis {
    method_used: string;
    outliers_detected: number;
    outlier_percentage: number;
    outlier_values: number[];
    outlier_indices: number[];
}

interface WeightedAverages {
    volume_weighted: number;
    trade_count_weighted: number;
    reliability_weighted: number;
    amount_weighted: number;
}

interface TimeWeightedAverages {
    exponential_weighted: number;
    linear_decay_weighted: number;
    recent_emphasis_weighted: number;
}

interface ConfidenceInterval {
    confidence_level: number;
    mean: number;
    margin_of_error: number;
    lower_bound: number;
    upper_bound: number;
    sample_size: number;
    standard_error: number;
}

interface PercentileAnalysis {
    P5: number;
    P10: number;
    P25: number;
    P50: number;
    P75: number;
    P90: number;
    P95: number;
}

interface TrendAnalysis {
    slope: number;
    intercept: number;
    r_squared: number;
    trend_direction: string;
    trend_strength: string;
    price_change_rate: number;
}

interface VolatilityAnalysis {
    absolute_volatility: number;
    relative_volatility: number;
    volatility_classification: string;
    rolling_volatility_5?: RollingVolatility;
    rolling_volatility_10?: RollingVolatility;
    rolling_volatility_20?: RollingVolatility;
    rolling_volatility_50?: RollingVolatility;
}

interface RollingVolatility {
    values: number[];
    average: number;
    min: number;
    max: number;
}

interface StatisticalTests {
    normality_test: {
        skewness: number;
        kurtosis: number;
        normality_score: number;
        assessment: string;
    };
    outlier_impact: {
        raw_mean: number;
        clean_mean: number;
        absolute_difference: number;
        percentage_impact: number;
        impact_level: string;
    };
    data_consistency: {
        coefficient_of_variation: number;
        consistency_level: string;
        data_spread: string;
    };
}

interface QualityMetrics {
    total_data_points: number;
    clean_data_points: number;
    outliers_removed: number;
    data_retention_rate: number;
    outlier_rate: number;
    quality_score: number;
    data_completeness: {
        percentage: number;
        level: string;
    };
}

interface StatisticalAnalysisDashboardProps {
    asset?: string;
    fiat?: string;
    tradeType?: string;
    className?: string;
}

export function StatisticalAnalysisDashboard({
    asset = 'USDT',
    fiat = 'VES',
    tradeType = 'BUY',
    className,
}: StatisticalAnalysisDashboardProps) {
    const [data, setData] = useState<StatisticalAnalysisData | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [outlierMethod, setOutlierMethod] = useState<string>('iqr');
    const [confidenceLevel, setConfidenceLevel] = useState<number>(0.95);
    const [sampleSize, setSampleSize] = useState<number>(50);

    const fetchStatisticalAnalysis = async () => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams({
                asset,
                fiat,
                trade_type: tradeType,
                outlier_method: outlierMethod,
                confidence_level: confidenceLevel.toString(),
                rows: sampleSize.toString(),
            });

            const response = await fetch(`/api/binance-p2p/statistical-analysis?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            setData(result);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error al obtener el análisis estadístico');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchStatisticalAnalysis();
    }, [asset, fiat, tradeType, outlierMethod, confidenceLevel, sampleSize]);

    const formatNumber = (value: number | null, decimals = 2): string => {
        if (value === null || value === undefined) return 'N/A';
        return value.toLocaleString('en-US', { 
            minimumFractionDigits: decimals, 
            maximumFractionDigits: decimals 
        });
    };

    const formatPercentage = (value: number | null, decimals = 2): string => {
        if (value === null || value === undefined) return 'N/A';
        return `${value.toFixed(decimals)}%`;
    };

    const getQualityBadgeVariant = (score: number): "default" | "secondary" | "destructive" | "outline" => {
        if (score >= 0.8) return "default";
        if (score >= 0.6) return "secondary";
        if (score >= 0.4) return "outline";
        return "destructive";
    };

    const getVolatilityBadgeVariant = (classification: string): "default" | "secondary" | "destructive" | "outline" => {
        switch (classification) {
            case 'very_low':
            case 'low':
                return "default";
            case 'moderate':
                return "secondary";
            case 'high':
                return "outline";
            case 'very_high':
                return "destructive";
            default:
                return "secondary";
        }
    };

    const getTrendBadgeVariant = (direction: string): "default" | "secondary" | "destructive" | "outline" => {
        switch (direction) {
            case 'upward':
                return "default";
            case 'stable':
                return "secondary";
            case 'downward':
                return "destructive";
            default:
                return "outline";
        }
    };

    if (loading) {
        return (
            <div className={cn("space-y-6", className)}>
                <div className="flex items-center justify-between">
                    <Skeleton className="h-8 w-64" />
                    <Skeleton className="h-10 w-32" />
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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
                <AlertTitle className="text-foreground">No Data</AlertTitle>
                <AlertDescription className="text-muted-foreground">No statistical analysis data available.</AlertDescription>
            </Alert>
        );
    }

    return (
        <div className={cn("space-y-6", className)}>
            {/* Header with Controls */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold text-foreground">
                        Statistical Analysis: {data.asset}/{data.fiat} ({data.trade_type})
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Last updated: {new Date(data.timestamp).toLocaleString()}
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
                    
                    <Button onClick={fetchStatisticalAnalysis} size="sm">
                        Actualizar
                    </Button>
                </div>
            </div>

            {/* Quality Overview */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="flex items-center justify-between text-foreground">
                        Resumen de Calidad de Datos
                        <Badge variant={getQualityBadgeVariant(data.analysis.quality_metrics.quality_score)}>
                            {formatPercentage(data.analysis.quality_metrics.quality_score * 100)}
                        </Badge>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="text-center">
                            <div className="text-2xl font-bold">{data.analysis.quality_metrics.total_data_points}</div>
                            <div className="text-sm text-muted-foreground">Puntos de Datos Totales</div>
                        </div>
                        <div className="text-center">
                            <div className="text-2xl font-bold">{data.analysis.quality_metrics.clean_data_points}</div>
                            <div className="text-sm text-muted-foreground">Puntos de Datos Limpios</div>
                        </div>
                        <div className="text-center">
                            <div className="text-2xl font-bold">{data.analysis.quality_metrics.outliers_removed}</div>
                            <div className="text-sm text-muted-foreground">Outliers Removed</div>
                        </div>
                        <div className="text-center">
                            <div className="text-2xl font-bold">{formatPercentage(data.analysis.quality_metrics.data_retention_rate)}</div>
                            <div className="text-sm text-muted-foreground">Data Retention</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Statistics Comparison */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="text-foreground">Estadísticas Sin Procesar vs Limpias</CardTitle>
                        <CardDescription className="text-muted-foreground">Impacto de la eliminación de valores atípicos en las medidas estadísticas</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            <div className="flex justify-between">
                                <span>Mean:</span>
                                <div className="text-right">
                                    <div className="text-sm text-muted-foreground/80">Raw: {formatNumber(data.analysis.raw_statistics.mean, 4)}</div>
                                    <div className="font-semibold">Clean: {formatNumber(data.analysis.cleaned_statistics.mean, 4)}</div>
                                </div>
                            </div>
                            <div className="flex justify-between">
                                <span>Std Dev:</span>
                                <div className="text-right">
                                    <div className="text-sm text-muted-foreground/80">Raw: {formatNumber(data.analysis.raw_statistics.standard_deviation, 4)}</div>
                                    <div className="font-semibold">Clean: {formatNumber(data.analysis.cleaned_statistics.standard_deviation, 4)}</div>
                                </div>
                            </div>
                            <div className="flex justify-between">
                                <span>CV:</span>
                                <div className="text-right">
                                    <div className="text-sm text-muted-foreground/80">Raw: {formatPercentage(data.analysis.raw_statistics.coefficient_of_variation)}</div>
                                    <div className="font-semibold">Clean: {formatPercentage(data.analysis.cleaned_statistics.coefficient_of_variation)}</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="text-foreground">Promedios Ponderados</CardTitle>
                        <CardDescription className="text-muted-foreground">Diferentes métodos de ponderación para el promedio de precios</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span>Volume Weighted:</span>
                                <span className="font-semibold">{formatNumber(data.analysis.weighted_averages.volume_weighted, 4)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Trade Count Weighted:</span>
                                <span className="font-semibold">{formatNumber(data.analysis.weighted_averages.trade_count_weighted, 4)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Reliability Weighted:</span>
                                <span className="font-semibold">{formatNumber(data.analysis.weighted_averages.reliability_weighted, 4)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Amount Weighted:</span>
                                <span className="font-semibold">{formatNumber(data.analysis.weighted_averages.amount_weighted, 4)}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Percentiles */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="text-foreground">Análisis de Percentiles</CardTitle>
                    <CardDescription className="text-muted-foreground">Distribución de precios a través de diferentes percentiles</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-7 gap-4 text-center">
                        <div>
                            <div className="text-lg font-bold">{formatNumber(data.analysis.percentile_analysis.P5, 4)}</div>
                            <div className="text-sm text-muted-foreground">P5</div>
                        </div>
                        <div>
                            <div className="text-lg font-bold">{formatNumber(data.analysis.percentile_analysis.P10, 4)}</div>
                            <div className="text-sm text-muted-foreground">P10</div>
                        </div>
                        <div>
                            <div className="text-lg font-bold">{formatNumber(data.analysis.percentile_analysis.P25, 4)}</div>
                            <div className="text-sm text-muted-foreground">P25</div>
                        </div>
                        <div className="bg-muted/30 border border-border p-2 rounded">
                            <div className="text-lg font-bold text-foreground">{formatNumber(data.analysis.percentile_analysis.P50, 4)}</div>
                            <div className="text-sm text-foreground">P50 (Median)</div>
                        </div>
                        <div>
                            <div className="text-lg font-bold">{formatNumber(data.analysis.percentile_analysis.P75, 4)}</div>
                            <div className="text-sm text-muted-foreground">P75</div>
                        </div>
                        <div>
                            <div className="text-lg font-bold">{formatNumber(data.analysis.percentile_analysis.P90, 4)}</div>
                            <div className="text-sm text-muted-foreground">P90</div>
                        </div>
                        <div>
                            <div className="text-lg font-bold">{formatNumber(data.analysis.percentile_analysis.P95, 4)}</div>
                            <div className="text-sm text-muted-foreground">P95</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Confidence Intervals */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="text-foreground">Intervalo de Confianza ({formatPercentage(data.analysis.confidence_intervals.confidence_level * 100)})</CardTitle>
                    <CardDescription className="text-muted-foreground">Rango de confianza estadístico para el precio medio</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-xl font-bold text-foreground">{formatNumber(data.analysis.confidence_intervals.lower_bound, 4)}</div>
                            <div className="text-sm text-foreground">Lower Bound</div>
                        </div>
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-xl font-bold text-foreground">{formatNumber(data.analysis.confidence_intervals.mean, 4)}</div>
                            <div className="text-sm text-foreground">Mean ± {formatNumber(data.analysis.confidence_intervals.margin_of_error, 4)}</div>
                        </div>
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-xl font-bold text-foreground">{formatNumber(data.analysis.confidence_intervals.upper_bound, 4)}</div>
                            <div className="text-sm text-foreground">Upper Bound</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Trend and Volatility Analysis */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            Análisis de Tendencias
                            <Badge variant={getTrendBadgeVariant(data.analysis.trend_analysis.trend_direction)}>
                                {data.analysis.trend_analysis.trend_direction}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span>Slope:</span>
                                <span className="font-semibold">{formatNumber(data.analysis.trend_analysis.slope, 6)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>R²:</span>
                                <span className="font-semibold">{formatNumber(data.analysis.trend_analysis.r_squared, 4)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Trend Strength:</span>
                                <span className="font-semibold capitalize">{data.analysis.trend_analysis.trend_strength.replace('_', ' ')}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            Análisis de Volatilidad
                            <Badge variant={getVolatilityBadgeVariant(data.analysis.volatility_analysis.volatility_classification)}>
                                {data.analysis.volatility_analysis.volatility_classification.replace('_', ' ')}
                            </Badge>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            <div className="flex justify-between">
                                <span>Absolute Volatility:</span>
                                <span className="font-semibold">{formatNumber(data.analysis.volatility_analysis.absolute_volatility, 4)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Relative Volatility:</span>
                                <span className="font-semibold">{formatPercentage(data.analysis.volatility_analysis.relative_volatility)}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Statistical Tests */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="text-foreground">Pruebas Estadísticas</CardTitle>
                    <CardDescription className="text-muted-foreground">Análisis estadístico avanzado y pruebas de calidad de datos</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <h4 className="font-semibold mb-2 text-foreground">Normality Test</h4>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span>Skewness:</span>
                                    <span>{formatNumber(data.analysis.statistical_tests.normality_test.skewness, 4)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Kurtosis:</span>
                                    <span>{formatNumber(data.analysis.statistical_tests.normality_test.kurtosis, 4)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Assessment:</span>
                                    <span className="capitalize">{data.analysis.statistical_tests.normality_test.assessment.replace('_', ' ')}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 className="font-semibold mb-2 text-foreground">Outlier Impact</h4>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span>Mean Difference:</span>
                                    <span>{formatNumber(data.analysis.statistical_tests.outlier_impact.absolute_difference, 4)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Impact:</span>
                                    <span>{formatPercentage(data.analysis.statistical_tests.outlier_impact.percentage_impact)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Level:</span>
                                    <span className="capitalize">{data.analysis.statistical_tests.outlier_impact.impact_level}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 className="font-semibold mb-2 text-foreground">Data Consistency</h4>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span>CV:</span>
                                    <span>{formatPercentage(data.analysis.statistical_tests.data_consistency.coefficient_of_variation)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Consistency:</span>
                                    <span className="capitalize">{data.analysis.statistical_tests.data_consistency.consistency_level}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Spread:</span>
                                    <span className="capitalize">{data.analysis.statistical_tests.data_consistency.data_spread.replace('_', ' ')}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
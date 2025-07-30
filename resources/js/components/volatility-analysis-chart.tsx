import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Alert, AlertDescription, AlertTitle } from './ui/alert';
import { Skeleton } from './ui/skeleton';
import { cn } from '@/lib/utils';

interface VolatilityData {
    asset: string;
    fiat: string;
    trade_type: string;
    timestamp: string;
    volatility_analysis: {
        absolute_volatility: number;
        relative_volatility: number;
        volatility_classification: string;
        rolling_volatility_5?: RollingVolatility;
        rolling_volatility_10?: RollingVolatility;
        rolling_volatility_20?: RollingVolatility;
        rolling_volatility_50?: RollingVolatility;
    };
    statistical_tests: {
        normality_test: {
            skewness: number;
            kurtosis: number;
            normality_score: number;
            assessment: string;
        };
        data_consistency: {
            coefficient_of_variation: number;
            consistency_level: string;
            data_spread: string;
        };
    };
    quality_metrics: {
        quality_score: number;
        data_retention_rate: number;
        outlier_rate: number;
        total_data_points: number;
        clean_data_points: number;
    };
    sample_size: number;
}

interface RollingVolatility {
    values: number[];
    average: number;
    min: number;
    max: number;
}

interface VolatilityAnalysisChartProps {
    asset?: string;
    fiat?: string;
    tradeType?: string;
    className?: string;
}

export function VolatilityAnalysisChart({
    asset = 'USDT',
    fiat = 'VES',
    tradeType = 'BUY',
    className,
}: VolatilityAnalysisChartProps) {
    const [data, setData] = useState<VolatilityData | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [sampleSize, setSampleSize] = useState<number>(100);

    const fetchVolatilityData = async () => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams({
                asset,
                fiat,
                trade_type: tradeType,
                rows: sampleSize.toString(),
            });

            const response = await fetch(`/api/binance-p2p/volatility-analysis?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            setData(result);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error al obtener el análisis de volatilidad');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchVolatilityData();
    }, [asset, fiat, tradeType, sampleSize]);

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

    const getVolatilityBadge = (classification: string): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "secondary";
        
        switch (classification) {
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
                variant = "outline";
                break;
            case 'very_high':
                variant = "destructive";
                break;
        }

        return (
            <Badge variant={variant}>
                {classification.replace('_', ' ').toUpperCase()}
            </Badge>
        );
    };

    const getConsistencyBadge = (level: string): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "secondary";
        
        switch (level) {
            case 'high':
                variant = "default";
                break;
            case 'moderate':
                variant = "secondary";
                break;
            case 'low':
                variant = "destructive";
                break;
        }

        return <Badge variant={variant}>{level}</Badge>;
    };

    const getNormalityBadge = (assessment: string): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "secondary";
        
        switch (assessment) {
            case 'likely_normal':
                variant = "default";
                break;
            case 'moderately_normal':
                variant = "secondary";
                break;
            case 'likely_non_normal':
                variant = "outline";
                break;
        }

        return <Badge variant={variant}>{assessment.replace('_', ' ')}</Badge>;
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
                    {Array.from({ length: 4 }).map((_, i) => (
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
                <AlertDescription className="text-muted-foreground">No volatility analysis data available.</AlertDescription>
            </Alert>
        );
    }

    const rollingPeriods = [
        { key: 'rolling_volatility_5', period: 5, name: '5-Period' },
        { key: 'rolling_volatility_10', period: 10, name: '10-Period' },
        { key: 'rolling_volatility_20', period: 20, name: '20-Period' },
        { key: 'rolling_volatility_50', period: 50, name: '50-Period' },
    ];

    return (
        <div className={cn("space-y-6", className)}>
            {/* Header with Controls */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold text-foreground">
                        Volatility Analysis: {data.asset}/{data.fiat} ({data.trade_type})
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Market volatility and price stability analysis
                    </p>
                </div>
                
                <div className="flex flex-wrap gap-2">
                    <Select value={sampleSize.toString()} onValueChange={(value) => setSampleSize(parseInt(value))}>
                        <SelectTrigger className="w-24">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="50">50</SelectItem>
                            <SelectItem value="100">100</SelectItem>
                            <SelectItem value="200">200</SelectItem>
                        </SelectContent>
                    </Select>
                    
                    <Button onClick={fetchVolatilityData} size="sm">
                        Actualizar
                    </Button>
                </div>
            </div>

            {/* Volatility Overview */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="flex items-center justify-between text-foreground">
                        Volatility Overview
                        {getVolatilityBadge(data.volatility_analysis.volatility_classification)}
                    </CardTitle>
                    <CardDescription className="text-muted-foreground">
                        Current market volatility metrics and classification
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-2xl font-bold text-foreground">
                                {formatNumber(data.volatility_analysis.absolute_volatility, 6)}
                            </div>
                            <div className="text-sm text-foreground">Absolute Volatility</div>
                            <div className="text-xs text-muted-foreground mt-1">Standard deviation of prices</div>
                        </div>
                        
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-2xl font-bold text-foreground">
                                {formatPercentage(data.volatility_analysis.relative_volatility)}
                            </div>
                            <div className="text-sm text-foreground">Relative Volatility</div>
                            <div className="text-xs text-muted-foreground mt-1">Coeficiente de variación</div>
                        </div>
                        
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-2xl font-bold text-foreground">
                                {data.sample_size}
                            </div>
                            <div className="text-sm text-foreground">Sample Size</div>
                            <div className="text-xs text-muted-foreground mt-1">Data points analyzed</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Rolling Volatility Analysis */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="text-foreground">Análisis de Volatilidad Móvil</CardTitle>
                    <CardDescription className="text-muted-foreground">
                        Volatility patterns across different time windows
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        {rollingPeriods.map(({ key, period, name }) => {
                            const rollingData = data.volatility_analysis[key as keyof typeof data.volatility_analysis] as RollingVolatility | undefined;
                            
                            if (!rollingData) {
                                return (
                                    <div key={key} className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                                        <div className="text-lg text-muted-foreground/60">N/A</div>
                                        <div className="text-sm text-muted-foreground/80">{name} Rolling</div>
                                        <div className="text-xs text-muted-foreground/60">Insufficient data</div>
                                    </div>
                                );
                            }

                            return (
                                <div key={key} className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                                    <div className="text-lg font-semibold text-foreground">
                                        {formatNumber(rollingData.average, 6)}
                                    </div>
                                    <div className="text-sm text-muted-foreground">{name} Rolling</div>
                                    <div className="text-xs text-muted-foreground/80 mt-1">
                                        Range: {formatNumber(rollingData.min, 6)} - {formatNumber(rollingData.max, 6)}
                                    </div>
                                    <div className="text-xs text-muted-foreground/80">
                                        {rollingData.values.length} windows
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            {/* Statistical Tests and Quality Metrics */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Statistical Tests */}
                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="text-foreground">Statistical Tests</CardTitle>
                        <CardDescription className="text-muted-foreground">Data distribution and consistency analysis</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {/* Normality Test */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <h4 className="font-semibold text-foreground">Prueba de Normalidad</h4>
                                {getNormalityBadge(data.statistical_tests.normality_test.assessment)}
                            </div>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span>Asimetría:</span>
                                    <span className="font-semibold">{formatNumber(data.statistical_tests.normality_test.skewness, 4)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Curtosis:</span>
                                    <span className="font-semibold">{formatNumber(data.statistical_tests.normality_test.kurtosis, 4)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Puntuación de Normalidad:</span>
                                    <span className="font-semibold">{formatNumber(data.statistical_tests.normality_test.normality_score, 4)}</span>
                                </div>
                            </div>
                        </div>

                        {/* Data Consistency */}
                        <div>
                            <div className="flex items-center justify-between mb-2">
                                <h4 className="font-semibold text-foreground">Consistencia de Datos</h4>
                                {getConsistencyBadge(data.statistical_tests.data_consistency.consistency_level)}
                            </div>
                            <div className="space-y-2 text-sm">
                                <div className="flex justify-between">
                                    <span>Coeficiente de Variación:</span>
                                    <span className="font-semibold">{formatPercentage(data.statistical_tests.data_consistency.coefficient_of_variation)}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Data Spread:</span>
                                    <span className="font-semibold capitalize">{data.statistical_tests.data_consistency.data_spread.replace('_', ' ')}</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Quality Metrics */}
                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between">
                            Métricas de Calidad
                            {getQualityBadge(data.quality_metrics.quality_score)}
                        </CardTitle>
                        <CardDescription className="text-muted-foreground">Data quality and reliability indicators</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="text-center p-3 bg-muted/30 border border-border rounded">
                                <div className="text-lg font-bold text-foreground">{data.quality_metrics.total_data_points}</div>
                                <div className="text-sm text-foreground">Puntos Totales</div>
                            </div>
                            <div className="text-center p-3 bg-muted/30 border border-border rounded">
                                <div className="text-lg font-bold text-foreground">{data.quality_metrics.clean_data_points}</div>
                                <div className="text-sm text-foreground">Puntos Limpios</div>
                            </div>
                        </div>

                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between">
                                <span>Retención de Datos:</span>
                                <span className="font-semibold">{formatPercentage(data.quality_metrics.data_retention_rate)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Tasa de Valores Atípicos:</span>
                                <span className="font-semibold text-foreground">{formatPercentage(data.quality_metrics.outlier_rate)}</span>
                            </div>
                            <div className="flex justify-between">
                                <span>Puntuación de Calidad:</span>
                                <span className="font-semibold">{formatNumber(data.quality_metrics.quality_score, 3)}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Volatility Interpretation */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="text-foreground">Volatility Interpretation</CardTitle>
                    <CardDescription className="text-muted-foreground">What the volatility metrics mean for trading</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {/* Classification Explanation */}
                        <div className="p-4 bg-muted/30 border border-border rounded-lg">
                            <h4 className="font-semibold mb-2">Current Classification: {data.volatility_analysis.volatility_classification.replace('_', ' ').toUpperCase()}</h4>
                            <p className="text-sm text-foreground">
                                {data.volatility_analysis.volatility_classification === 'very_low' && 
                                    "Extremely stable market with minimal price fluctuations. Lower profit potential but also lower risk."}
                                {data.volatility_analysis.volatility_classification === 'low' && 
                                    "Stable market conditions with predictable price movements. Good for conservative trading strategies."}
                                {data.volatility_analysis.volatility_classification === 'moderate' && 
                                    "Balanced market volatility. Offers reasonable profit opportunities with manageable risk levels."}
                                {data.volatility_analysis.volatility_classification === 'high' && 
                                    "Volatile market with significant price swings. Higher profit potential but increased risk of losses."}
                                {data.volatility_analysis.volatility_classification === 'very_high' && 
                                    "Extremely volatile market conditions. High profit potential but very risky. Suitable only for experienced traders."}
                            </p>
                        </div>

                        {/* Trading Implications */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="p-4 border rounded-lg">
                                <h5 className="font-semibold text-foreground mb-2">Opportunities</h5>
                                <ul className="text-sm text-foreground space-y-1">
                                    {data.volatility_analysis.relative_volatility > 20 ? (
                                        <>
                                            <li>• High arbitrage potential</li>
                                            <li>• Good for swing trading</li>
                                            <li>• Profit from price swings</li>
                                        </>
                                    ) : (
                                        <>
                                            <li>• Stable price predictions</li>
                                            <li>• Lower transaction costs</li>
                                            <li>• Good for large volume trades</li>
                                        </>
                                    )}
                                </ul>
                            </div>
                            
                            <div className="p-4 border rounded-lg">
                                <h5 className="font-semibold text-foreground mb-2">Risks</h5>
                                <ul className="text-sm text-foreground space-y-1">
                                    {data.volatility_analysis.relative_volatility > 20 ? (
                                        <>
                                            <li>• High price unpredictability</li>
                                            <li>• Potential for large losses</li>
                                            <li>• Timing becomes critical</li>
                                        </>
                                    ) : (
                                        <>
                                            <li>• Limited profit opportunities</li>
                                            <li>• Market may be stagnant</li>
                                            <li>• Lower return potential</li>
                                        </>
                                    )}
                                </ul>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
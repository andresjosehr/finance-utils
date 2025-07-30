import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Alert, AlertDescription, AlertTitle } from './ui/alert';
import { Skeleton } from './ui/skeleton';
import { cn } from '@/lib/utils';

interface OutlierData {
    asset: string;
    fiat: string;
    trade_type: string;
    outlier_method: string;
    outlier_summary: {
        method_used: string;
        outliers_detected: number;
        outlier_percentage: number;
        outlier_values: number[];
        outlier_indices: number[];
    };
    outlier_details: Array<{
        index: number;
        price: number;
        volume: number;
        merchant: string;
        completion_rate: number;
        trade_count: number;
    }>;
    timestamp: string;
}

interface MethodComparison {
    asset: string;
    fiat: string;
    trade_type: string;
    timestamp: string;
    methods: {
        [key: string]: {
            outlier_analysis: {
                method_used: string;
                outliers_detected: number;
                outlier_percentage: number;
                outlier_values: number[];
                outlier_indices: number[];
            };
            cleaned_mean: number;
            cleaned_std: number;
            data_retention_rate: number;
            quality_score: number;
        };
    };
}

interface OutlierAnalysisChartProps {
    asset?: string;
    fiat?: string;
    tradeType?: string;
    className?: string;
}

export function OutlierAnalysisChart({
    asset = 'USDT',
    fiat = 'VES',
    tradeType = 'BUY',
    className,
}: OutlierAnalysisChartProps) {
    const [outlierData, setOutlierData] = useState<OutlierData | null>(null);
    const [comparisonData, setComparisonData] = useState<MethodComparison | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [outlierMethod, setOutlierMethod] = useState<string>('iqr');
    const [sampleSize, setSampleSize] = useState<number>(50);
    const [showComparison, setShowComparison] = useState(false);

    const fetchOutlierData = async () => {
        setLoading(true);
        setError(null);

        try {
            if (showComparison) {
                const params = new URLSearchParams({
                    asset,
                    fiat,
                    trade_type: tradeType,
                    rows: sampleSize.toString(),
                });

                const response = await fetch(`/api/binance-p2p/compare-outlier-methods?${params}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                setComparisonData(result);
                setOutlierData(null);
            } else {
                const params = new URLSearchParams({
                    asset,
                    fiat,
                    trade_type: tradeType,
                    outlier_method: outlierMethod,
                    rows: sampleSize.toString(),
                });

                const response = await fetch(`/api/binance-p2p/outliers?${params}`);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                setOutlierData(result);
                setComparisonData(null);
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error al obtener el análisis de valores atípicos');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchOutlierData();
    }, [asset, fiat, tradeType, outlierMethod, sampleSize, showComparison]);

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

    const getOutlierSeverityBadge = (percentage: number): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "default";
        let label = "Low";

        if (percentage > 20) {
            variant = "destructive";
            label = "Very High";
        } else if (percentage > 10) {
            variant = "outline";
            label = "High";
        } else if (percentage > 5) {
            variant = "secondary";
            label = "Moderate";
        }

        return <Badge variant={variant}>{label} ({formatPercentage(percentage)})</Badge>;
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
            <Alert className={cn("border-b border-borderorder", className)}>
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
            </Alert>
        );
    }

    return (
        <div className={cn("space-y-6", className)}>
            {/* Header with Controls */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold">
                        Outlier Analysis: {asset}/{fiat} ({tradeType})
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Identify and analyze price outliers that could affect market averages
                    </p>
                </div>
                
                <div className="flex flex-wrap gap-2">
                    <Button
                        variant={showComparison ? "outline" : "default"}
                        onClick={() => setShowComparison(!showComparison)}
                        size="sm"
                    >
                        {showComparison ? "Método Único" : "Comparar Métodos"}
                    </Button>
                    
                    {!showComparison && (
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
                    )}
                    
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
                    
                    <Button onClick={fetchOutlierData} size="sm">
                        Actualizar
                    </Button>
                </div>
            </div>

            {/* Method Comparison View */}
            {showComparison && comparisonData && (
                <div className="space-y-6">
                    <Card className="border-b border-borderorder bg-card">
                        <CardHeader>
                            <CardTitle className="text-foreground">Comparación de Métodos de Detección de Valores Atípicos</CardTitle>
                            <CardDescription className="text-muted-foreground">
                                Compara la efectividad de diferentes algoritmos de detección de valores atípicos
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-border">
                                            <th className="text-left p-2">Method</th>
                                            <th className="text-right p-2">Outliers Detected</th>
                                            <th className="text-right p-2">Outlier %</th>
                                            <th className="text-right p-2">Data Retention</th>
                                            <th className="text-right p-2">Cleaned Mean</th>
                                            <th className="text-right p-2">Quality Score</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {Object.entries(comparisonData.methods).map(([method, data]) => (
                                            <tr key={method} className="border-b border-border">
                                                <td className="p-2 font-medium capitalize">{method.replace('_', ' ')}</td>
                                                <td className="p-2 text-right">{data.outlier_analysis.outliers_detected}</td>
                                                <td className="p-2 text-right">{formatPercentage(data.outlier_analysis.outlier_percentage)}</td>
                                                <td className="p-2 text-right">{formatPercentage(data.data_retention_rate)}</td>
                                                <td className="p-2 text-right">{formatNumber(data.cleaned_mean, 4)}</td>
                                                <td className="p-2 text-right">{getQualityBadge(data.quality_score)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        {Object.entries(comparisonData.methods).map(([method, data]) => (
                            <Card key={method}>
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between text-base">
                                        {method.toUpperCase().replace('_', '-')}
                                        {getOutlierSeverityBadge(data.outlier_analysis.outlier_percentage)}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2 text-sm">
                                        <div className="flex justify-between">
                                            <span>Outliers:</span>
                                            <span className="font-semibold">{data.outlier_analysis.outliers_detected}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Mean:</span>
                                            <span className="font-semibold">{formatNumber(data.cleaned_mean, 4)}</span>
                                        </div>
                                        <div className="flex justify-between">
                                            <span>Std Dev:</span>
                                            <span className="font-semibold">{formatNumber(data.cleaned_std, 4)}</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            )}

            {/* Single Method View */}
            {!showComparison && outlierData && (
                <div className="space-y-6">
                    {/* Outlier Summary */}
                    <Card className="border-b border-borderorder bg-card">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                Outlier Detection Summary ({outlierData.outlier_method.toUpperCase()})
                                {getOutlierSeverityBadge(outlierData.outlier_summary.outlier_percentage)}
                            </CardTitle>
                            <CardDescription className="text-muted-foreground">
                                Using {outlierData.outlier_method.replace('_', ' ')} method for outlier detection
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-foreground">{outlierData.outlier_summary.outliers_detected}</div>
                                    <div className="text-sm text-muted-foreground">Outliers Found</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-foreground">{formatPercentage(outlierData.outlier_summary.outlier_percentage)}</div>
                                    <div className="text-sm text-muted-foreground">Outlier Rate</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-foreground">{outlierData.outlier_summary.outlier_values.length}</div>
                                    <div className="text-sm text-muted-foreground">Flagged Values</div>
                                </div>
                                <div className="text-center">
                                    <div className="text-2xl font-bold text-foreground">
                                        {formatNumber(
                                            outlierData.outlier_summary.outlier_values.length > 0
                                                ? Math.max(...outlierData.outlier_summary.outlier_values) - Math.min(...outlierData.outlier_summary.outlier_values)
                                                : 0,
                                            2
                                        )}
                                    </div>
                                    <div className="text-sm text-muted-foreground">Price Range</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Outlier Values Visualization */}
                    {outlierData.outlier_summary.outlier_values.length > 0 && (
                        <Card className="border-b border-borderorder bg-card">
                            <CardHeader>
                                <CardTitle className="text-foreground">Valores de Precios Atípicos</CardTitle>
                                <CardDescription className="text-muted-foreground">Precios atípicos individuales detectados por el algoritmo</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8 gap-2">
                                    {outlierData.outlier_summary.outlier_values.map((value, index) => (
                                        <div key={index} className="text-center p-2 bg-muted/30 rounded border border-b border-borderorder">
                                            <div className="text-sm font-semibold text-foreground">
                                                {formatNumber(value, 4)}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Detailed Outlier Information */}
                    {outlierData.outlier_details.length > 0 && (
                        <Card className="border-b border-borderorder bg-card">
                            <CardHeader>
                                <CardTitle className="text-foreground">Detalles de Valores Atípicos</CardTitle>
                                <CardDescription className="text-muted-foreground">Información detallada sobre cada valor atípico para revisión manual</CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-border">
                                                <th className="text-left p-2">Index</th>
                                                <th className="text-right p-2">Price</th>
                                                <th className="text-right p-2">Volume</th>
                                                <th className="text-left p-2">Merchant</th>
                                                <th className="text-right p-2">Completion Rate</th>
                                                <th className="text-right p-2">Trade Count</th>
                                                <th className="text-center p-2">Risk Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {outlierData.outlier_details.map((detail, index) => {
                                                const riskLevel = detail.completion_rate < 50 || detail.trade_count < 10 ? 'high' :
                                                                detail.completion_rate < 80 || detail.trade_count < 50 ? 'medium' : 'low';
                                                
                                                return (
                                                    <tr key={index} className="border-b border-border hover:bg-muted/20">
                                                        <td className="p-2 font-mono">{detail.index}</td>
                                                        <td className="p-2 text-right font-semibold">{formatNumber(detail.price, 4)}</td>
                                                        <td className="p-2 text-right">{formatNumber(detail.volume, 2)}</td>
                                                        <td className="p-2 truncate max-w-32" title={detail.merchant}>
                                                            {detail.merchant}
                                                        </td>
                                                        <td className="p-2 text-right">{formatPercentage(detail.completion_rate)}</td>
                                                        <td className="p-2 text-right">{detail.trade_count}</td>
                                                        <td className="p-2 text-center">
                                                            <Badge variant={
                                                                riskLevel === 'high' ? 'destructive' :
                                                                riskLevel === 'medium' ? 'outline' : 'secondary'
                                                            }>
                                                                {riskLevel}
                                                            </Badge>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* No Outliers Found */}
                    {outlierData.outlier_summary.outliers_detected === 0 && (
                        <Card className="border-b border-borderorder bg-card">
                            <CardContent className="text-center py-8">
                                <div className="text-foreground mb-2">
                                    <svg className="mx-auto h-12 w-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <h3 className="text-lg font-semibold text-foreground mb-1">No Outliers Detected</h3>
                                <p className="text-muted-foreground">
                                    The {outlierData.outlier_method.replace('_', ' ')} method found no outliers in the current dataset.
                                    This suggests the market prices are well-distributed and consistent.
                                </p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            )}
        </div>
    );
}
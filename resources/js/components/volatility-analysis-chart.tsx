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

        const translations = {
            'very_low': 'MUY BAJA',
            'low': 'BAJA',
            'moderate': 'MODERADA',
            'high': 'ALTA',
            'very_high': 'MUY ALTA'
        };

        return (
            <Badge variant={variant}>
                {translations[classification as keyof typeof translations] || classification.replace('_', ' ').toUpperCase()}
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

        const translations = {
            'high': 'ALTA',
            'moderate': 'MODERADA',
            'low': 'BAJA'
        };

        return <Badge variant={variant}>{translations[level as keyof typeof translations] || level}</Badge>;
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

        const translations = {
            'likely_normal': 'PROBABLEMENTE NORMAL',
            'moderately_normal': 'MODERADAMENTE NORMAL',
            'likely_non_normal': 'PROBABLEMENTE NO NORMAL'
        };

        return <Badge variant={variant}>{translations[assessment as keyof typeof translations] || assessment.replace('_', ' ')}</Badge>;
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
                <AlertTitle className="text-foreground">Sin Datos</AlertTitle>
                <AlertDescription className="text-muted-foreground">No hay datos de análisis de volatilidad disponibles.</AlertDescription>
            </Alert>
        );
    }

    const rollingPeriods = [
        { key: 'rolling_volatility_5', period: 5, name: '5-Períodos' },
        { key: 'rolling_volatility_10', period: 10, name: '10-Períodos' },
        { key: 'rolling_volatility_20', period: 20, name: '20-Períodos' },
        { key: 'rolling_volatility_50', period: 50, name: '50-Períodos' },
    ];

    return (
        <div className={cn("space-y-6", className)}>
            {/* Header with Controls */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold text-foreground">
                        Análisis de Volatilidad: {data.asset}/{data.fiat} ({data.trade_type})
                    </h2>
                    <p className="text-sm text-muted-foreground">
                        Análisis de volatilidad del mercado y estabilidad de precios
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
                        Resumen de Volatilidad
                        {getVolatilityBadge(data.volatility_analysis.volatility_classification)}
                    </CardTitle>
                    <CardDescription className="text-muted-foreground">
                        Métricas actuales de volatilidad del mercado y clasificación
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-2xl font-bold text-foreground">
                                {formatNumber(data.volatility_analysis.absolute_volatility, 6)}
                            </div>
                            <div className="text-sm text-foreground">Volatilidad Absoluta</div>
                            <div className="text-xs text-muted-foreground mt-1">Desviación estándar de precios</div>
                        </div>
                        
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-2xl font-bold text-foreground">
                                {formatPercentage(data.volatility_analysis.relative_volatility)}
                            </div>
                            <div className="text-sm text-foreground">Volatilidad Relativa</div>
                            <div className="text-xs text-muted-foreground mt-1">Coeficiente de variación</div>
                        </div>
                        
                        <div className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                            <div className="text-2xl font-bold text-foreground">
                                {data.sample_size}
                            </div>
                            <div className="text-sm text-foreground">Tamaño de Muestra</div>
                            <div className="text-xs text-muted-foreground mt-1">Puntos de datos analizados</div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Rolling Volatility Analysis */}
            <Card className="border-border bg-card">
                <CardHeader>
                    <CardTitle className="text-foreground">Análisis de Volatilidad Móvil</CardTitle>
                    <CardDescription className="text-muted-foreground">
                        Patrones de volatilidad en diferentes ventanas de tiempo
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
                                        <div className="text-sm text-muted-foreground/80">{name} Móvil</div>
                                        <div className="text-xs text-muted-foreground/60">Datos insuficientes</div>
                                    </div>
                                );
                            }

                            return (
                                <div key={key} className="text-center p-4 bg-muted/30 border border-border rounded-lg">
                                    <div className="text-lg font-semibold text-foreground">
                                        {formatNumber(rollingData.average, 6)}
                                    </div>
                                    <div className="text-sm text-muted-foreground">{name} Móvil</div>
                                    <div className="text-xs text-muted-foreground/80 mt-1">
                                        Rango: {formatNumber(rollingData.min, 6)} - {formatNumber(rollingData.max, 6)}
                                    </div>
                                    <div className="text-xs text-muted-foreground/80">
                                        {rollingData.values.length} ventanas
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
                        <CardTitle className="text-foreground">Pruebas Estadísticas</CardTitle>
                        <CardDescription className="text-muted-foreground">Análisis de distribución de datos y consistencia</CardDescription>
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
                                    <span>Dispersión de Datos:</span>
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
                        <CardDescription className="text-muted-foreground">Indicadores de calidad y confiabilidad de datos</CardDescription>
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
                    <CardTitle className="text-foreground">Interpretación de Volatilidad</CardTitle>
                    <CardDescription className="text-muted-foreground">Qué significan las métricas de volatilidad para el trading</CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="space-y-4">
                        {/* Classification Explanation */}
                        <div className="p-4 bg-muted/30 border border-border rounded-lg">
                            <h4 className="font-semibold mb-2">Clasificación Actual: {data.volatility_analysis.volatility_classification === 'very_low' ? 'MUY BAJA' : data.volatility_analysis.volatility_classification === 'low' ? 'BAJA' : data.volatility_analysis.volatility_classification === 'moderate' ? 'MODERADA' : data.volatility_analysis.volatility_classification === 'high' ? 'ALTA' : data.volatility_analysis.volatility_classification === 'very_high' ? 'MUY ALTA' : data.volatility_analysis.volatility_classification.replace('_', ' ').toUpperCase()}</h4>
                            <p className="text-sm text-foreground">
                                {data.volatility_analysis.volatility_classification === 'very_low' && 
                                    "Mercado extremadamente estable con fluctuaciones mínimas de precios. Menor potencial de ganancias pero también menor riesgo."}
                                {data.volatility_analysis.volatility_classification === 'low' && 
                                    "Condiciones de mercado estables con movimientos de precios predecibles. Bueno para estrategias de trading conservadoras."}
                                {data.volatility_analysis.volatility_classification === 'moderate' && 
                                    "Volatilidad de mercado equilibrada. Ofrece oportunidades de ganancia razonables con niveles de riesgo manejables."}
                                {data.volatility_analysis.volatility_classification === 'high' && 
                                    "Mercado volátil con oscilaciones significativas de precios. Mayor potencial de ganancias pero mayor riesgo de pérdidas."}
                                {data.volatility_analysis.volatility_classification === 'very_high' && 
                                    "Condiciones de mercado extremadamente volátiles. Alto potencial de ganancias pero muy riesgoso. Adecuado solo para traders experimentados."}
                            </p>
                        </div>

                        {/* Trading Implications */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="p-4 border rounded-lg">
                                <h5 className="font-semibold text-foreground mb-2">Oportunidades</h5>
                                <ul className="text-sm text-foreground space-y-1">
                                    {data.volatility_analysis.relative_volatility > 20 ? (
                                        <>
                                            <li>• Alto potencial de arbitraje</li>
                                            <li>• Bueno para swing trading</li>
                                            <li>• Ganancia por oscilaciones de precio</li>
                                        </>
                                    ) : (
                                        <>
                                            <li>• Predicciones de precio estables</li>
                                            <li>• Menores costos de transacción</li>
                                            <li>• Bueno para operaciones de gran volumen</li>
                                        </>
                                    )}
                                </ul>
                            </div>
                            
                            <div className="p-4 border rounded-lg">
                                <h5 className="font-semibold text-foreground mb-2">Riesgos</h5>
                                <ul className="text-sm text-foreground space-y-1">
                                    {data.volatility_analysis.relative_volatility > 20 ? (
                                        <>
                                            <li>• Alta impredecibilidad de precios</li>
                                            <li>• Potencial de grandes pérdidas</li>
                                            <li>• El timing se vuelve crítico</li>
                                        </>
                                    ) : (
                                        <>
                                            <li>• Oportunidades de ganancia limitadas</li>
                                            <li>• El mercado puede estar estancado</li>
                                            <li>• Menor potencial de retorno</li>
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
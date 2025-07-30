import React, { useState, useEffect } from 'react';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler,
    ScaleOptions,
} from 'chart.js';
import { Line } from 'react-chartjs-2';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from './ui/card';
import { Badge } from './ui/badge';
import { Button } from './ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from './ui/select';
import { Alert, AlertDescription, AlertTitle } from './ui/alert';
import { Skeleton } from './ui/skeleton';
import { cn } from '@/lib/utils';

// Register ChartJS components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend,
    Filler
);

interface HistoricalDataPoint {
    timestamp: string;
    buy_price: number | null;
    sell_price: number | null;
    spread: number | null;
    data_quality: number;
}

interface HistoricalPriceData {
    asset: string;
    fiat: string;
    time_range: {
        start: string;
        end: string;
        hours: number;
    };
    data_points: HistoricalDataPoint[];
    statistics: {
        total_points: number;
        buy_price_avg: number | null;
        sell_price_avg: number | null;
        avg_spread: number | null;
        max_spread: number | null;
        min_spread: number | null;
        volatility: number;
        quality_score: number;
    };
    metadata: {
        collection_frequency_minutes: number;
        data_completeness: number;
        missing_data_points: number;
    };
}

interface HistoricalPriceChartProps {
    asset?: string;
    fiat?: string;
    hours?: number;
    className?: string;
}

export function HistoricalPriceChart({
    asset = 'USDT',
    fiat = 'VES',
    hours = 24,
    className,
}: HistoricalPriceChartProps) {
    const [data, setData] = useState<HistoricalPriceData | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [selectedHours, setSelectedHours] = useState<number>(hours);

    const fetchHistoricalData = async () => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams({
                asset,
                fiat,
                hours: selectedHours.toString(),
            });

            const response = await fetch(`/api/binance-p2p/historical-prices?${params}`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            setData(result);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Error al obtener datos históricos de precios');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchHistoricalData();
    }, [asset, fiat, selectedHours]);

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

    const formatDateTime = (timestamp: string): string => {
        return new Date(timestamp).toLocaleString('es-ES', {
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const getQualityBadge = (score: number): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "default";
        
        if (score >= 0.8) variant = "default";
        else if (score >= 0.6) variant = "secondary";
        else if (score >= 0.4) variant = "outline";
        else variant = "destructive";

        return <Badge variant={variant}>{formatPercentage(score * 100)}</Badge>;
    };

    const getVolatilityBadge = (volatility: number): React.ReactNode => {
        let variant: "default" | "secondary" | "destructive" | "outline" = "secondary";
        let label = "";
        
        if (volatility < 1) {
            variant = "default";
            label = "Baja";
        } else if (volatility < 3) {
            variant = "secondary";
            label = "Moderada";
        } else if (volatility < 5) {
            variant = "outline";
            label = "Alta";
        } else {
            variant = "destructive";
            label = "Muy Alta";
        }

        return <Badge variant={variant}>{label}</Badge>;
    };

    const getChartData = () => {
        if (!data || !data.historical_data || !data.historical_data.length) return null;

        const validDataPoints = data.historical_data.filter(point => 
            point.buy_price !== null || point.sell_price !== null
        );

        const labels = validDataPoints.map(point => formatDateTime(point.timestamp));
        const buyPrices = validDataPoints.map(point => point.buy_price);
        const sellPrices = validDataPoints.map(point => point.sell_price);

        // Create spread data for filled area
        const spreadData = validDataPoints.map((point, index) => {
            if (point.buy_price !== null && point.sell_price !== null) {
                return Math.max(point.buy_price, point.sell_price);
            }
            return null;
        });

        const spreadDataLower = validDataPoints.map((point, index) => {
            if (point.buy_price !== null && point.sell_price !== null) {
                return Math.min(point.buy_price, point.sell_price);
            }
            return null;
        });

        return {
            labels,
            datasets: [
                {
                    label: 'Precio de Compra',
                    data: buyPrices,
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(34, 197, 94)',
                    tension: 0.1,
                    spanGaps: true,
                },
                {
                    label: 'Precio de Venta',
                    data: sellPrices,
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    pointBackgroundColor: 'rgb(239, 68, 68)',
                    tension: 0.1,
                    spanGaps: true,
                },
                {
                    label: 'Área de Spread',
                    data: spreadData,
                    borderColor: 'rgba(168, 85, 247, 0.3)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 0,
                    pointRadius: 0,
                    fill: '+1',
                    tension: 0.1,
                    spanGaps: true,
                },
                {
                    label: '',
                    data: spreadDataLower,
                    borderColor: 'rgba(168, 85, 247, 0.3)',
                    backgroundColor: 'rgba(168, 85, 247, 0.1)',
                    borderWidth: 0,
                    pointRadius: 0,
                    fill: false,
                    tension: 0.1,
                    spanGaps: true,
                    legend: {
                        display: false,
                    },
                },
            ],
        };
    };

    const getChartOptions = () => {
        return {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index' as const,
                intersect: false,
            },
            plugins: {
                title: {
                    display: true,
                    text: `Precios Históricos P2P - ${asset}/${fiat}`,
                    font: {
                        size: 16,
                        weight: 'bold' as const,
                    },
                },
                legend: {
                    position: 'top' as const,
                    labels: {
                        filter: (legendItem: any) => {
                            return legendItem.text !== '';
                        },
                    },
                },
                tooltip: {
                    mode: 'index' as const,
                    intersect: false,
                    callbacks: {
                        title: (context: any) => {
                            const point = data?.data_points[context[0].dataIndex];
                            if (point) {
                                return new Date(point.timestamp).toLocaleString('es-ES');
                            }
                            return '';
                        },
                        label: (context: any) => {
                            const point = data?.data_points[context.dataIndex];
                            if (!point) return '';

                            if (context.datasetIndex === 0 && point.buy_price !== null) {
                                return `Precio de Compra: ${formatNumber(point.buy_price, 4)} ${fiat}`;
                            }
                            if (context.datasetIndex === 1 && point.sell_price !== null) {
                                return `Precio de Venta: ${formatNumber(point.sell_price, 4)} ${fiat}`;
                            }
                            return '';
                        },
                        afterBody: (context: any) => {
                            const point = data?.data_points[context[0].dataIndex];
                            if (point && point.spread !== null) {
                                return [
                                    `Spread: ${formatNumber(point.spread, 4)} ${fiat}`,
                                    `Calidad: ${formatPercentage(point.data_quality * 100)}`,
                                ];
                            }
                            return [];
                        },
                    },
                },
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Tiempo',
                    },
                    ticks: {
                        maxTicksLimit: 8,
                    },
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: `Precio (${fiat})`,
                    },
                    ticks: {
                        callback: function(value: any) {
                            return formatNumber(value, 2);
                        },
                    },
                },
            },
        };
    };

    if (loading) {
        return (
            <div className={cn("space-y-6", className)}>
                <div className="flex items-center justify-between">
                    <Skeleton className="h-8 w-64" />
                    <Skeleton className="h-10 w-32" />
                </div>
                <Card>
                    <CardHeader>
                        <Skeleton className="h-6 w-48" />
                        <Skeleton className="h-4 w-32" />
                    </CardHeader>
                    <CardContent>
                        <Skeleton className="h-80 w-full" />
                    </CardContent>
                </Card>
            </div>
        );
    }

    if (error) {
        return (
            <Alert className={cn("border-red-200", className)}>
                <AlertTitle>Error</AlertTitle>
                <AlertDescription>{error}</AlertDescription>
            </Alert>
        );
    }

    if (!data || !data.historical_data || !data.historical_data.length) {
        return (
            <Alert className={className}>
                <AlertTitle>Sin Datos</AlertTitle>
                <AlertDescription>No hay datos históricos de precios disponibles para el período seleccionado.</AlertDescription>
            </Alert>
        );
    }

    const chartData = getChartData();

    return (
        <div className={cn("space-y-6", className)}>
            {/* Header with Controls */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div>
                    <h2 className="text-2xl font-bold">
                        Gráfico Histórico de Precios: {data.asset}/{data.fiat}
                    </h2>
                    <p className="text-sm text-gray-600">
                        Período: {data.summary?.time_range ? `${new Date(data.summary.time_range.start).toLocaleString('es-ES')} - ${new Date(data.summary.time_range.end).toLocaleString('es-ES')}` : 'Datos recientes'}
                    </p>
                </div>
                
                <div className="flex flex-wrap gap-2">
                    <Select value={selectedHours.toString()} onValueChange={(value) => setSelectedHours(parseInt(value))}>
                        <SelectTrigger className="w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="6">6 horas</SelectItem>
                            <SelectItem value="12">12 horas</SelectItem>
                            <SelectItem value="24">24 horas</SelectItem>
                            <SelectItem value="48">48 horas</SelectItem>
                            <SelectItem value="72">72 horas</SelectItem>
                            <SelectItem value="168">7 días</SelectItem>
                        </SelectContent>
                    </Select>
                    
                    <Button onClick={fetchHistoricalData} size="sm">
                        Actualizar
                    </Button>
                </div>
            </div>

            {/* Statistics Overview */}
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center justify-between">
                        Resumen Estadístico
                        {data.summary?.data_quality ? getQualityBadge(data.summary.data_quality.average_score) : <Badge variant="secondary">N/A</Badge>}
                    </CardTitle>
                    <CardDescription>
                        Métricas principales para el período de {data.hours || selectedHours} horas
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="text-center p-4 bg-green-50 rounded-lg">
                            <div className="text-xl font-bold text-green-600">
                                {data.summary?.price_summary ? formatNumber(data.summary.price_summary.buy_price_avg, 4) : 'N/A'}
                            </div>
                            <div className="text-sm text-green-600">Precio Promedio de Compra</div>
                        </div>
                        
                        <div className="text-center p-4 bg-red-50 rounded-lg">
                            <div className="text-xl font-bold text-red-600">
                                {data.summary?.price_summary ? formatNumber(data.summary.price_summary.sell_price_avg, 4) : 'N/A'}
                            </div>
                            <div className="text-sm text-red-600">Precio Promedio de Venta</div>
                        </div>
                        
                        <div className="text-center p-4 bg-purple-50 rounded-lg">
                            <div className="text-xl font-bold text-purple-600">
                                {data.summary?.price_summary ? formatNumber(data.summary.price_summary.avg_spread, 4) : 'N/A'}
                            </div>
                            <div className="text-sm text-purple-600">Spread Promedio</div>
                            <div className="text-xs text-gray-600 mt-1">
                                Max: {data.summary?.price_summary ? formatNumber(data.summary.price_summary.max_spread, 4) : 'N/A'}
                            </div>
                        </div>
                        
                        <div className="text-center p-4 bg-blue-50 rounded-lg">
                            <div className="text-xl font-bold text-blue-600">
                                {data.summary?.price_summary ? getVolatilityBadge(data.summary.price_summary.volatility) : <Badge variant="secondary">N/A</Badge>}
                            </div>
                            <div className="text-sm text-blue-600">Volatilidad</div>
                            <div className="text-xs text-gray-600 mt-1">
                                {data.summary?.price_summary ? formatNumber(data.summary.price_summary.volatility, 2) + '%' : 'N/A'}
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>

            {/* Historical Price Chart */}
            <Card>
                <CardHeader>
                    <CardTitle>Evolución de Precios</CardTitle>
                    <CardDescription>
                        Gráfico temporal con líneas de precios BUY/SELL y área de spread
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <div className="h-80 w-full">
                        {chartData ? (
                            <Line data={chartData} options={getChartOptions()} />
                        ) : (
                            <div className="flex items-center justify-center h-full text-gray-500">
                                No hay datos válidos para mostrar el gráfico
                            </div>
                        )}
                    </div>
                </CardContent>
            </Card>

            {/* Data Quality and Metadata */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Calidad de los Datos</CardTitle>
                        <CardDescription>Métricas de integridad y completitud de datos</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex justify-between">
                            <span>Puntos de datos totales:</span>
                            <span className="font-semibold">{data.summary?.total_data_points || 0}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Completitud de datos:</span>
                            <span className="font-semibold">{data.summary?.data_quality ? formatPercentage(data.summary.data_quality.completeness_rate) : 'N/A'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Datos faltantes:</span>
                            <span className="font-semibold text-red-600">{data.summary?.data_quality ? data.summary.data_quality.missing_points : 'N/A'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Puntuación de calidad:</span>
                            <span className="font-semibold">{data.summary?.data_quality ? formatNumber(data.summary.data_quality.average_score, 3) : 'N/A'}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Información de Recolección</CardTitle>
                        <CardDescription>Metadatos sobre la recolección de datos</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex justify-between">
                            <span>Frecuencia de recolección:</span>
                            <span className="font-semibold">5 min</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Rango temporal:</span>
                            <span className="font-semibold">{data.hours || selectedHours} horas</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Período de inicio:</span>
                            <span className="font-semibold text-sm">{data.summary?.time_range ? new Date(data.summary.time_range.start).toLocaleString('es-ES') : 'N/A'}</span>
                        </div>
                        <div className="flex justify-between">
                            <span>Período de fin:</span>
                            <span className="font-semibold text-sm">{data.summary?.time_range ? new Date(data.summary.time_range.end).toLocaleString('es-ES') : 'N/A'}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
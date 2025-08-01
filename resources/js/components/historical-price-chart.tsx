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
    trade_type: string;
    best_price: number;
    avg_price: number;
    worst_price: number;
    median_price: number | null;
    volume_weighted_price: number | null;
    total_volume: number;
    order_count: number;
    price_spread: number;
    data_quality_score: number;
}

interface SpreadDataPoint {
    timestamp: string;
    buy_price: number;
    sell_price: number;
    spread_absolute: number;
    spread_percentage: number;
}

interface HistoricalPriceData {
    asset: string;
    fiat: string;
    hours: number;
    summary: {
        total_data_points: number;
        time_range: {
            start: string;
            end: string;
            duration_hours: number;
        };
        price_summary: {
            min_price: number;
            max_price: number;
            avg_price: number;
            price_volatility: number;
        } | null;
        data_quality: {
            avg_quality_score: number;
            min_quality_score: number;
            max_quality_score: number;
        };
        spread_opportunities: number;
    };
    historical_data: HistoricalDataPoint[];
    spread_data: SpreadDataPoint[];
}

interface HistoricalPriceChartProps {
    asset?: string;
    fiat?: string;
    hours?: number;
    interval?: number;
    className?: string;
}

export function HistoricalPriceChart({
    asset = 'USDT',
    fiat = 'VES',
    hours = 24,
    interval = 10,
    className,
}: HistoricalPriceChartProps) {
    const [data, setData] = useState<HistoricalPriceData | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [selectedHours, setSelectedHours] = useState<number>(hours);
    const [selectedInterval, setSelectedInterval] = useState<number>(interval);

    const fetchHistoricalData = async () => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams({
                asset,
                fiat,
                hours: selectedHours.toString(),
                interval: selectedInterval.toString(),
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
    }, [asset, fiat, selectedHours, selectedInterval]);

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

        // Separate buy and sell data points
        const buyData = data.historical_data.filter(point => point.trade_type === 'BUY');
        const sellData = data.historical_data.filter(point => point.trade_type === 'SELL');

        // Create time-based mapping for better alignment
        const timeMap = new Map();
        
        buyData.forEach(point => {
            const timeKey = new Date(point.timestamp).toISOString();
            if (!timeMap.has(timeKey)) {
                timeMap.set(timeKey, { timestamp: timeKey });
            }
            timeMap.get(timeKey).buy_price = point.avg_price;
            timeMap.get(timeKey).buy_quality = point.data_quality_score;
        });

        sellData.forEach(point => {
            const timeKey = new Date(point.timestamp).toISOString();
            if (!timeMap.has(timeKey)) {
                timeMap.set(timeKey, { timestamp: timeKey });
            }
            timeMap.get(timeKey).sell_price = point.avg_price;
            timeMap.get(timeKey).sell_quality = point.data_quality_score;
        });

        // Convert to sorted array
        const validDataPoints = Array.from(timeMap.values())
            .filter(point => point.buy_price || point.sell_price)
            .sort((a, b) => new Date(a.timestamp).getTime() - new Date(b.timestamp).getTime());

        const labels = validDataPoints.map(point => formatDateTime(point.timestamp));
        const buyPrices = validDataPoints.map(point => point.buy_price || null);
        const sellPrices = validDataPoints.map(point => point.sell_price || null);

        // Create spread data for filled area using spread_data if available
        let spreadDataUpper = null;
        let spreadDataLower = null;

        if (data.spread_data && data.spread_data.length > 0) {
            // Use actual spread data from API
            const spreadMap = new Map();
            data.spread_data.forEach(spread => {
                spreadMap.set(spread.timestamp, spread);
            });

            spreadDataUpper = validDataPoints.map(point => {
                const spread = spreadMap.get(point.timestamp);
                return spread ? Math.max(spread.buy_price, spread.sell_price) : null;
            });

            spreadDataLower = validDataPoints.map(point => {
                const spread = spreadMap.get(point.timestamp);
                return spread ? Math.min(spread.buy_price, spread.sell_price) : null;
            });
        } else {
            // Fallback: calculate from available data
            spreadDataUpper = validDataPoints.map(point => {
                if (point.buy_price && point.sell_price) {
                    return Math.max(point.buy_price, point.sell_price);
                }
                return null;
            });

            spreadDataLower = validDataPoints.map(point => {
                if (point.buy_price && point.sell_price) {
                    return Math.min(point.buy_price, point.sell_price);
                }
                return null;
            });
        }

        return {
            labels,
            datasets: [
                {
                    label: 'Precio de Compra',
                    data: buyPrices,
                    borderColor: 'rgb(16, 185, 129)',
                    backgroundColor: 'rgba(16, 185, 129, 0.15)',
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: 'rgb(16, 185, 129)',
                    pointBorderColor: 'rgb(255, 255, 255)',
                    pointBorderWidth: 2,
                    tension: 0.1,
                    spanGaps: true,
                },
                {
                    label: 'Precio de Venta',
                    data: sellPrices,
                    borderColor: 'rgb(220, 38, 127)',
                    backgroundColor: 'rgba(220, 38, 127, 0.15)',
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 7,
                    pointBackgroundColor: 'rgb(220, 38, 127)',
                    pointBorderColor: 'rgb(255, 255, 255)',
                    pointBorderWidth: 2,
                    tension: 0.1,
                    spanGaps: true,
                },
                {
                    label: 'Área de Spread',
                    data: spreadDataUpper,
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
                    display: false,
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
                            if (context.length > 0) {
                                const chartData = getChartData();
                                if (chartData && chartData.labels && context[0].dataIndex < chartData.labels.length) {
                                    return chartData.labels[context[0].dataIndex];
                                }
                            }
                            return '';
                        },
                        label: (context: any) => {
                            const chartData = getChartData();
                            if (!chartData) return '';

                            const dataIndex = context.dataIndex;
                            
                            if (context.datasetIndex === 0) {
                                const buyPrice = chartData.datasets[0].data[dataIndex];
                                if (buyPrice !== null && buyPrice !== undefined) {
                                    return `Precio de Compra: ${formatNumber(buyPrice, 4)} ${fiat}`;
                                }
                            }
                            if (context.datasetIndex === 1) {
                                const sellPrice = chartData.datasets[1].data[dataIndex];
                                if (sellPrice !== null && sellPrice !== undefined) {
                                    return `Precio de Venta: ${formatNumber(sellPrice, 4)} ${fiat}`;
                                }
                            }
                            return '';
                        },
                        afterBody: (context: any) => {
                            if (context.length === 0) return [];
                            
                            const dataIndex = context[0].dataIndex;
                            const chartData = getChartData();
                            
                            if (chartData && data?.spread_data) {
                                const spread = data.spread_data.find(s => {
                                    const spreadTime = formatDateTime(s.timestamp);
                                    const labelTime = chartData.labels[dataIndex];
                                    return spreadTime === labelTime;
                                });
                                
                                if (spread) {
                                    return [
                                        `Spread: ${formatNumber(spread.spread_absolute, 4)} ${fiat}`,
                                        `Spread %: ${formatNumber(spread.spread_percentage, 2)}%`,
                                    ];
                                }
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

                    <Select value={selectedInterval.toString()} onValueChange={(value) => setSelectedInterval(parseInt(value))}>
                        <SelectTrigger className="w-36">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="5">5 minutos</SelectItem>
                            <SelectItem value="10">10 minutos</SelectItem>
                            <SelectItem value="15">15 minutos</SelectItem>
                            <SelectItem value="30">30 minutos</SelectItem>
                            <SelectItem value="60">1 hora</SelectItem>
                        </SelectContent>
                    </Select>
                    
                    <Button onClick={fetchHistoricalData} size="sm">
                        Actualizar
                    </Button>
                </div>
            </div>

            {/* Historical Price Chart with Integrated Statistics */}
            <div className="space-y-6">
                {/* Compact Statistics for BUY and SELL */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 max-w-4xl mx-auto">
                    {/* BUY Statistics */}
                    <div className="bg-gray-50 dark:bg-gray-800 border-2 border-green-200 dark:border-green-700 rounded-lg p-4">
                        <div className="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center">
                            <div className="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                            COMPRA (BUY)
                        </div>
                        <div className="grid grid-cols-4 gap-3">
                            <div className="text-center">
                                <div className="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {(() => {
                                        const buyData = data.historical_data?.filter(point => point.trade_type === 'BUY') || [];
                                        const minPrice = buyData.length > 0 ? Math.min(...buyData.map(p => p.avg_price)) : null;
                                        return minPrice ? formatNumber(minPrice, 4) : 'N/A';
                                    })()}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">Mín</div>
                            </div>
                            <div className="text-center">
                                <div className="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {(() => {
                                        const buyData = data.historical_data?.filter(point => point.trade_type === 'BUY') || [];
                                        const maxPrice = buyData.length > 0 ? Math.max(...buyData.map(p => p.avg_price)) : null;
                                        return maxPrice ? formatNumber(maxPrice, 4) : 'N/A';
                                    })()}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">Máx</div>
                            </div>
                            <div className="text-center">
                                <div className="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {(() => {
                                        const buyData = data.historical_data?.filter(point => point.trade_type === 'BUY') || [];
                                        const avgPrice = buyData.length > 0 ? buyData.reduce((sum, p) => sum + p.avg_price, 0) / buyData.length : null;
                                        return avgPrice ? formatNumber(avgPrice, 4) : 'N/A';
                                    })()}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">Prom</div>
                            </div>
                            <div className="text-center">
                                <div className="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {data.historical_data?.filter(point => point.trade_type === 'BUY').length || 0}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">Pts</div>
                            </div>
                        </div>
                    </div>

                    {/* SELL Statistics */}
                    <div className="bg-gray-50 dark:bg-gray-800 border-2 border-red-200 dark:border-red-700 rounded-lg p-4">
                        <div className="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-3 flex items-center">
                            <div className="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                            VENTA (SELL)
                        </div>
                        <div className="grid grid-cols-4 gap-3">
                            <div className="text-center">
                                <div className="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {(() => {
                                        const sellData = data.historical_data?.filter(point => point.trade_type === 'SELL') || [];
                                        const minPrice = sellData.length > 0 ? Math.min(...sellData.map(p => p.avg_price)) : null;
                                        return minPrice ? formatNumber(minPrice, 4) : 'N/A';
                                    })()}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">Mín</div>
                            </div>
                            <div className="text-center">
                                <div className="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {(() => {
                                        const sellData = data.historical_data?.filter(point => point.trade_type === 'SELL') || [];
                                        const maxPrice = sellData.length > 0 ? Math.max(...sellData.map(p => p.avg_price)) : null;
                                        return maxPrice ? formatNumber(maxPrice, 4) : 'N/A';
                                    })()}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">Máx</div>
                            </div>
                            <div className="text-center">
                                <div className="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {(() => {
                                        const sellData = data.historical_data?.filter(point => point.trade_type === 'SELL') || [];
                                        const avgPrice = sellData.length > 0 ? sellData.reduce((sum, p) => sum + p.avg_price, 0) / sellData.length : null;
                                        return avgPrice ? formatNumber(avgPrice, 4) : 'N/A';
                                    })()}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">Prom</div>
                            </div>
                            <div className="text-center">
                                <div className="text-base font-bold text-gray-900 dark:text-gray-100">
                                    {data.historical_data?.filter(point => point.trade_type === 'SELL').length || 0}
                                </div>
                                <div className="text-sm text-gray-600 dark:text-gray-400">Pts</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {/* Chart */}
                <Card>
                    <CardContent className="p-6">
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
            </div>

            {/* Data Quality and Metadata */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Calidad de los Datos</CardTitle>
                        <CardDescription>Métricas de integridad y completitud de datos</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-0">
                        <div className="flex justify-between py-3">
                            <span>Puntos de datos totales:</span>
                            <span className="font-semibold">{data.summary?.total_data_points || 0}</span>
                        </div>
                        <div className="border-t border-gray-200 dark:border-gray-700"></div>
                        <div className="flex justify-between py-3">
                            <span>Calidad promedio:</span>
                            <span className="font-semibold">{data.summary?.data_quality ? formatNumber(data.summary.data_quality.avg_quality_score, 3) : 'N/A'}</span>
                        </div>
                        <div className="border-t border-gray-200 dark:border-gray-700"></div>
                        <div className="flex justify-between py-3">
                            <span>Calidad mínima:</span>
                            <span className="font-semibold text-red-600">{data.summary?.data_quality ? formatNumber(data.summary.data_quality.min_quality_score, 3) : 'N/A'}</span>
                        </div>
                        <div className="border-t border-gray-200 dark:border-gray-700"></div>
                        <div className="flex justify-between py-3">
                            <span>Calidad máxima:</span>
                            <span className="font-semibold text-green-600">{data.summary?.data_quality ? formatNumber(data.summary.data_quality.max_quality_score, 3) : 'N/A'}</span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Información de Recolección</CardTitle>
                        <CardDescription>Metadatos sobre la recolección de datos</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-0">
                        <div className="flex justify-between py-3">
                            <span>Frecuencia de recolección:</span>
                            <span className="font-semibold">5 min</span>
                        </div>
                        <div className="border-t border-gray-200 dark:border-gray-700"></div>
                        <div className="flex justify-between py-3">
                            <span>Rango temporal:</span>
                            <span className="font-semibold">{data.hours || selectedHours} horas</span>
                        </div>
                        <div className="border-t border-gray-200 dark:border-gray-700"></div>
                        <div className="flex justify-between py-3">
                            <span>Período de inicio:</span>
                            <span className="font-semibold text-sm">{data.summary?.time_range ? new Date(data.summary.time_range.start).toLocaleString('es-ES') : 'N/A'}</span>
                        </div>
                        <div className="border-t border-gray-200 dark:border-gray-700"></div>
                        <div className="flex justify-between py-3">
                            <span>Período de fin:</span>
                            <span className="font-semibold text-sm">{data.summary?.time_range ? new Date(data.summary.time_range.end).toLocaleString('es-ES') : 'N/A'}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
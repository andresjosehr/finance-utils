import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { StatisticalAnalysisDashboard } from '@/components/statistical-analysis-dashboard';
import { OutlierAnalysisChart } from '@/components/outlier-analysis-chart';
import { ComprehensiveMarketAnalysis } from '@/components/comprehensive-market-analysis';
import { VolatilityAnalysisChart } from '@/components/volatility-analysis-chart';

interface StatisticalAnalysisPageProps {
    auth: {
        user: {
            name: string;
            email: string;
        };
    };
}

export default function StatisticalAnalysisPage({ auth }: StatisticalAnalysisPageProps) {
    const [selectedAsset, setSelectedAsset] = useState('USDT');
    const [selectedFiat, setSelectedFiat] = useState('VES');
    const [selectedTradeType, setSelectedTradeType] = useState('BUY');

    const assets = [
        { value: 'USDT', label: 'USDT (Tether)' },
        { value: 'BTC', label: 'BTC (Bitcoin)' },
        { value: 'ETH', label: 'ETH (Ethereum)' },
        { value: 'BNB', label: 'BNB (Binance Coin)' },
        { value: 'BUSD', label: 'BUSD (Binance USD)' },
    ];

    const fiats = [
        { value: 'VES', label: 'VES (Venezuelan Bolívar)' },
        { value: 'USD', label: 'USD (US Dollar)' },
        { value: 'EUR', label: 'EUR (Euro)' },
        { value: 'GBP', label: 'GBP (British Pound)' },
        { value: 'CNY', label: 'CNY (Chinese Yuan)' },
        { value: 'ARS', label: 'ARS (Argentine Peso)' },
        { value: 'COP', label: 'COP (Colombian Peso)' },
        { value: 'PEN', label: 'PEN (Peruvian Sol)' },
    ];

    return (
        <AppLayout>
            <Head title="Análisis Estadístico" />
            
            <div className="space-y-4 p-3 max-w-none">
                {/* Page Header */}
                <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-3">
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold text-foreground">Análisis Estadístico</h1>
                        <p className="text-muted-foreground text-sm mt-1">
                            Análisis estadístico avanzado para mercados P2P de criptomonedas con detección de valores atípicos y análisis de tendencias
                        </p>
                    </div>
                    
                    <div className="flex flex-wrap gap-2 lg:flex-nowrap">
                        <Select value={selectedAsset} onValueChange={setSelectedAsset}>
                            <SelectTrigger className="w-36">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {assets.map((asset) => (
                                    <SelectItem key={asset.value} value={asset.value}>
                                        {asset.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        
                        <Select value={selectedFiat} onValueChange={setSelectedFiat}>
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {fiats.map((fiat) => (
                                    <SelectItem key={fiat.value} value={fiat.value}>
                                        {fiat.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        
                        <Select value={selectedTradeType} onValueChange={setSelectedTradeType}>
                            <SelectTrigger className="w-20">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="BUY">Comprar</SelectItem>
                                <SelectItem value="SELL">Vender</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {/* Feature Overview Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
                    <Card className="border-border bg-card">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-foreground">Detección de Valores Atípicos</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="flex flex-wrap gap-1 mb-2">
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">IQR</Badge>
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Z-Score</Badge>
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Z Modificada</Badge>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Identifica y filtra valores atípicos de precios que podrían sesgar los promedios del mercado
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border-border bg-card">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-foreground">Promedios Ponderados</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="flex flex-wrap gap-1 mb-2">
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Por Volumen</Badge>
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Por Tiempo</Badge>
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Por Confiabilidad</Badge>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Calcula promedios sofisticados basados en múltiples factores
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border-border bg-card">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-foreground">Pruebas Estadísticas</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="flex flex-wrap gap-1 mb-2">
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Intervalos</Badge>
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Normalidad</Badge>
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Tendencias</Badge>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Medidas estadísticas avanzadas y pruebas de hipótesis
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="border-border bg-card">
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm text-foreground">Análisis de Mercado</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="flex flex-wrap gap-1 mb-2">
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Volatilidad</Badge>
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Percentiles</Badge>
                                <Badge variant="outline" className="text-xs border-muted-foreground/20 text-muted-foreground">Comparación</Badge>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                Análisis integral de condiciones del mercado y comparación
                            </p>
                        </CardContent>
                    </Card>
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

                {/* Statistical Methods Information */}
                <Card className="mt-6 border-border bg-card">
                    <CardHeader>
                        <CardTitle className="text-foreground">Acerca de los Métodos Estadísticos</CardTitle>
                        <CardDescription className="text-muted-foreground">
                            Comprendiendo los algoritmos y técnicas utilizados en este análisis
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <h4 className="font-semibold text-foreground mb-2 text-sm">Métodos de Detección de Valores Atípicos</h4>
                                <div className="space-y-1 text-xs text-muted-foreground">
                                    <div>
                                        <strong className="text-foreground">Método IQR:</strong> Usa cuartiles para identificar valores fuera de 1.5 × IQR desde Q1/Q3
                                    </div>
                                    <div>
                                        <strong className="text-foreground">Puntuación Z:</strong> Identifica valores a más de 2.5 desviaciones estándar de la media
                                    </div>
                                    <div>
                                        <strong className="text-foreground">Puntuación Z Modificada:</strong> Usa la desviación absoluta mediana para una detección más robusta
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 className="font-semibold text-foreground mb-2 text-sm">Promediado Ponderado</h4>
                                <div className="space-y-1 text-xs text-muted-foreground">
                                    <div>
                                        <strong className="text-foreground">Ponderado por Volumen:</strong> Pondera precios por volumen de negociación (VWAP)
                                    </div>
                                    <div>
                                        <strong className="text-foreground">Ponderado por Tiempo:</strong> Da más peso a los puntos de datos recientes
                                    </div>
                                    <div>
                                        <strong className="text-foreground">Ponderado por Confiabilidad:</strong> Pondera por tasas de finalización de comerciantes
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 className="font-semibold text-foreground mb-2 text-sm">Análisis Estadístico</h4>
                                <div className="space-y-1 text-xs text-muted-foreground">
                                    <div>
                                        <strong className="text-foreground">Intervalos de Confianza:</strong> Rango estadístico para el precio medio real
                                    </div>
                                    <div>
                                        <strong className="text-foreground">Análisis de Percentiles:</strong> Distribución de precios a través de diferentes percentiles
                                    </div>
                                    <div>
                                        <strong className="text-foreground">Análisis de Tendencias:</strong> Regresión lineal para identificar tendencias de precios
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
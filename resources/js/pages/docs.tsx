import React from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

export default function DocsPage() {
    return (
        <AppLayout>
            <Head title="Documentación" />
            
            <div className="space-y-6 p-6 max-w-none">
                {/* Page Header */}
                <div className="flex flex-col justify-between items-start gap-3">
                    <div className="flex-1">
                        <h1 className="text-3xl font-bold text-foreground">Documentación</h1>
                        <p className="text-muted-foreground text-base mt-2">
                            Comprendiendo los algoritmos y técnicas utilizados en el análisis estadístico
                        </p>
                    </div>
                </div>

                {/* Statistical Methods Information */}
                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="text-foreground">Acerca de los Métodos Estadísticos</CardTitle>
                        <CardDescription className="text-muted-foreground">
                            Algoritmos y técnicas utilizados para el análisis avanzado de mercados P2P
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="pt-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <div>
                                <h4 className="font-semibold text-foreground mb-3 text-base">Métodos de Detección de Valores Atípicos</h4>
                                <div className="space-y-3 text-sm text-muted-foreground">
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
                                <h4 className="font-semibold text-foreground mb-3 text-base">Promediado Ponderado</h4>
                                <div className="space-y-3 text-sm text-muted-foreground">
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
                                <h4 className="font-semibold text-foreground mb-3 text-base">Análisis Estadístico</h4>
                                <div className="space-y-3 text-sm text-muted-foreground">
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

                {/* Outlier Detection Methods Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="border-border bg-card">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base text-foreground flex items-center gap-2">
                                Método IQR
                                <Badge variant="outline" className="text-xs">Cuartiles</Badge>
                            </CardTitle>
                            <CardDescription className="text-sm">
                                Detección de Valores Atípicos basada en Rango Intercuartílico
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="space-y-3 text-sm text-muted-foreground">
                                <div>
                                    <strong className="text-foreground">Cálculo:</strong> Q1 - 1.5×IQR y Q3 + 1.5×IQR
                                </div>
                                <div>
                                    <strong className="text-foreground">Ventajas:</strong> Robusto ante distribuciones asimétricas
                                </div>
                                <div>
                                    <strong className="text-foreground">Uso:</strong> Ideal para datos con distribución no normal
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border bg-card">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base text-foreground flex items-center gap-2">
                                Puntuación Z
                                <Badge variant="outline" className="text-xs">Desviación</Badge>
                            </CardTitle>
                            <CardDescription className="text-sm">
                                Detección basada en desviaciones estándar de la media
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="space-y-3 text-sm text-muted-foreground">
                                <div>
                                    <strong className="text-foreground">Cálculo:</strong> |x - μ| / σ &gt; 2.5
                                </div>
                                <div>
                                    <strong className="text-foreground">Ventajas:</strong> Simple y ampliamente utilizado
                                </div>
                                <div>
                                    <strong className="text-foreground">Uso:</strong> Efectivo con distribuciones normales
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border bg-card">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base text-foreground flex items-center gap-2">
                                Z Modificada
                                <Badge variant="outline" className="text-xs">Mediana</Badge>
                            </CardTitle>
                            <CardDescription className="text-sm">
                                Versión robusta usando desviación absoluta mediana
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="space-y-3 text-sm text-muted-foreground">
                                <div>
                                    <strong className="text-foreground">Cálculo:</strong> 0.6745 × |x - mediana| / MAD
                                </div>
                                <div>
                                    <strong className="text-foreground">Ventajas:</strong> Muy resistente a valores atípicos
                                </div>
                                <div>
                                    <strong className="text-foreground">Uso:</strong> Mejor para datos con muchos outliers
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border bg-card">
                        <CardHeader className="pb-3">
                            <CardTitle className="text-base text-foreground flex items-center gap-2">
                                Combinado
                                <Badge variant="outline" className="text-xs">Híbrido</Badge>
                            </CardTitle>
                            <CardDescription className="text-sm">
                                Consenso entre múltiples métodos de detección
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="pt-0">
                            <div className="space-y-3 text-sm text-muted-foreground">
                                <div>
                                    <strong className="text-foreground">Método:</strong> Intersección de todos los métodos
                                </div>
                                <div>
                                    <strong className="text-foreground">Ventajas:</strong> Reduce falsos positivos
                                </div>
                                <div>
                                    <strong className="text-foreground">Uso:</strong> Máxima confiabilidad en detección
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Advanced Analysis Features */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <Card className="border-border bg-card">
                        <CardHeader>
                            <CardTitle className="text-foreground">Promedios Ponderados Avanzados</CardTitle>
                            <CardDescription className="text-muted-foreground">
                                Técnicas sofisticadas de promediado para mayor precisión
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-3">
                                <div>
                                    <h5 className="font-semibold text-foreground mb-2">Ponderado por Volumen (VWAP)</h5>
                                    <p className="text-sm text-muted-foreground">
                                        Calcula el precio promedio ponderado por el volumen de cada transacción. 
                                        Fórmula: VWAP = Σ(Precio × Volumen) / Σ(Volumen)
                                    </p>
                                </div>
                                <div>
                                    <h5 className="font-semibold text-foreground mb-2">Ponderado por Tiempo</h5>
                                    <p className="text-sm text-muted-foreground">
                                        Asigna mayor peso a los datos más recientes usando decaimiento exponencial. 
                                        Los datos antiguos tienen menos influencia en el promedio final.
                                    </p>
                                </div>
                                <div>
                                    <h5 className="font-semibold text-foreground mb-2">Ponderado por Confiabilidad</h5>
                                    <p className="text-sm text-muted-foreground">
                                        Utiliza las tasas de finalización y el historial de los comerciantes 
                                        para ponderar la confiabilidad de cada oferta de precio.
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="border-border bg-card">
                        <CardHeader>
                            <CardTitle className="text-foreground">Análisis Estadístico Avanzado</CardTitle>
                            <CardDescription className="text-muted-foreground">
                                Herramientas estadísticas para evaluación completa del mercado
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-3">
                                <div>
                                    <h5 className="font-semibold text-foreground mb-2">Intervalos de Confianza</h5>
                                    <p className="text-sm text-muted-foreground">
                                        Proporciona rangos de confianza del 90%, 95% y 99% para estimar 
                                        el verdadero precio medio del mercado con incertidumbre estadística.
                                    </p>
                                </div>
                                <div>
                                    <h5 className="font-semibold text-foreground mb-2">Análisis de Percentiles</h5>
                                    <p className="text-sm text-muted-foreground">
                                        Calcula P5, P10, P25, P50 (mediana), P75, P90, P95 para entender 
                                        la distribución completa de precios en el mercado.
                                    </p>
                                </div>
                                <div>
                                    <h5 className="font-semibold text-foreground mb-2">Análisis de Tendencias</h5>
                                    <p className="text-sm text-muted-foreground">
                                        Utiliza regresión lineal para identificar tendencias de precios 
                                        y calcular la fuerza de la tendencia con coeficiente R².
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Market Analysis Features */}
                <Card className="border-border bg-card">
                    <CardHeader>
                        <CardTitle className="text-foreground">Características del Análisis de Mercado</CardTitle>
                        <CardDescription className="text-muted-foreground">
                            Funcionalidades avanzadas para el análisis integral de mercados P2P
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <h5 className="font-semibold text-foreground mb-3">Análisis de Volatilidad</h5>
                                <div className="space-y-2 text-sm text-muted-foreground">
                                    <div>• Volatilidad rodante para múltiples períodos</div>
                                    <div>• Comparación con promedios históricos</div>
                                    <div>• Identificación de períodos de alta/baja volatilidad</div>
                                    <div>• Análisis de estabilidad del mercado</div>
                                </div>
                            </div>
                            <div>
                                <h5 className="font-semibold text-foreground mb-3">Calidad de Datos</h5>
                                <div className="space-y-2 text-sm text-muted-foreground">
                                    <div>• Puntuación automática de calidad</div>
                                    <div>• Validación de consistencia de precios</div>
                                    <div>• Seguimiento de confiabilidad de comerciantes</div>
                                    <div>• Filtrado de datos sospechosos</div>
                                </div>
                            </div>
                            <div>
                                <h5 className="font-semibold text-foreground mb-3">Visualización Avanzada</h5>
                                <div className="space-y-2 text-sm text-muted-foreground">
                                    <div>• Gráficos interactivos en tiempo real</div>
                                    <div>• Mapas de calor de distribución</div>
                                    <div>• Comparación entre múltiples pares</div>
                                    <div>• Exportación de datos y análisis</div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
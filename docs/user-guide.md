# Guía de Usuario - Sistema de Análisis P2P

## Introducción

Bienvenido al Sistema de Análisis P2P, una herramienta avanzada para analizar datos de mercado de criptomonedas peer-to-peer. Este sistema recolecta automáticamente datos del mercado P2P de Binance cada 5 minutos y proporciona análisis estadísticos sofisticados para ayudarte a tomar decisiones de trading informadas.

## Acceso al Sistema

### Requisitos Previos
- Cuenta registrada en la aplicación
- Navegador web moderno (Chrome, Firefox, Safari, Edge)
- Conexión a internet estable

### Iniciar Sesión
1. Visita la página principal de la aplicación
2. Haz clic en "Iniciar Sesión" 
3. Ingresa tu email y contraseña
4. Haz clic en "Entrar"

## Navegación Principal

### Menú Lateral
Una vez autenticado, verás el menú lateral con las siguientes opciones:

- **📊 Dashboard** - Panel principal con resumen general
- **📈 Statistical Analysis** - Análisis estadístico P2P (nuestro módulo principal)
- **⚙️ Settings** - Configuración de cuenta y apariencia

### Acceder al Análisis P2P
Haz clic en **"Statistical Analysis"** en el menú lateral para acceder al módulo de análisis P2P.

---

## Interfaz Principal de Análisis P2P

### Panel de Control Superior

Al ingresar al análisis estadístico, encontrarás los siguientes controles:

#### 1. Selector de Par de Trading
```
Asset: [USDT ▼]  Fiat: [VES ▼]
```
- **Asset**: Criptomoneda base (ej: USDT, BTC, ETH)
- **Fiat**: Moneda fiduciaria (ej: VES, USD, EUR)
- *Por defecto*: USDT/VES (actualmente el único par completamente funcional)

#### 2. Selector de Tipo de Operación
```
Trade Type: [BUY ▼]
```
- **BUY**: Analizar órdenes de compra
- **SELL**: Analizar órdenes de venta

---

## Pestañas de Análisis

La interfaz está organizada en 5 pestañas principales:

### 1. 📊 **Comprehensive Analysis** (Análisis Completo)

Esta pestaña muestra una comparación lado a lado entre mercados de compra y venta.

#### Secciones Principales:

**A. Comparación de Mercados**
- **Mercado de Compra (BUY)**: Estadísticas del lado comprador
- **Mercado de Venta (SELL)**: Estadísticas del lado vendedor
- **Spread del Mercado**: Diferencia entre precios de compra y venta

**B. Métricas Clave Mostradas:**
- **Precio Promedio**: Precio medio ponderado
- **Precio Mediano**: Valor central de los precios
- **Rango de Precios**: Diferencia entre precio más alto y más bajo
- **Número de Órdenes**: Cantidad total de anuncios activos
- **Volumen Total**: Suma de todos los montos disponibles

**C. Indicadores de Calidad:**
- **Quality Score**: Puntuación de 0 a 1 sobre la calidad de los datos
- **Data Retention Rate**: Porcentaje de datos válidos después de filtrar outliers
- **Outliers Removed**: Cantidad de órdenes anómalas detectadas

**D. Análisis de Oportunidades:**
- **Spread Assessment**: Evaluación del diferencial (tight/normal/wide)
- **Arbitrage Opportunity**: Indica si existe oportunidad de arbitraje
- **Risk Assessment**: Nivel de riesgo de la operación

### 2. 📈 **Detailed Analysis** (Análisis Detallado)

Análisis estadístico profundo de un mercado específico (compra o venta).

#### Configuración:
```
Trade Type: [BUY ▼]  Sample Size: [50]  
Outlier Method: [IQR ▼]  Confidence: [95% ▼]
```

#### Parámetros Configurables:

**A. Outlier Method (Método de Detección de Anomalías):**
- **IQR**: Rango Intercuartil - Recomendado para datos financieros
- **Z-Score**: Desviación estándar - Para distribuciones normales
- **Modified Z-Score**: Z-Score modificado - Para datos con ruido

**B. Confidence Level (Nivel de Confianza):**
- **90%**: Intervalos de confianza más amplios
- **95%**: Balance estándar (recomendado)
- **99%**: Intervalos más estrechos y conservadores

#### Datos Mostrados:

**A. Estadísticas Básicas:**
- Count, Mean, Median, Mode
- Standard Deviation, Variance
- Min, Max, Range
- Coefficient of Variation

**B. Estadísticas Limpias:**
- Las mismas métricas después de remover outliers
- Comparación con datos sin procesar

**C. Promedios Ponderados:**
- **Volume Weighted**: Ponderado por volumen disponible
- **Reliability Weighted**: Ponderado por confiabilidad del merchant
- **Time Weighted**: Ponderado por tiempo (datos más recientes)

**D. Análisis de Intervalos:**
- Intervalos de confianza con límites superior e inferior
- Margen de error estadístico
- Percentiles (P5, P10, P25, P50, P75, P90, P95)

### 3. 🔍 **Outlier Analysis** (Análisis de Anomalías)

Visualización detallada de órdenes anómalas en el mercado.

#### Funcionalidades:

**A. Detección Visual:**
- Gráfico de dispersión mostrando todos los precios
- Puntos rojos indican outliers detectados
- Líneas horizontales muestran umbrales de detección

**B. Información de Outliers:**
Para cada anomalía detectada se muestra:
- **Precio**: Valor de la orden anómala
- **Desviación**: Qué tan lejos está del valor normal
- **Merchant**: Información del comerciante
- **Risk Level**: Nivel de riesgo de la orden

**C. Comparación de Métodos:**
- IQR vs Z-Score vs Modified Z-Score
- Cantidad de outliers detectados por cada método
- Recomendación del mejor método para los datos actuales

**D. Impacto en el Análisis:**
- Diferencia en el precio promedio antes/después de filtrar
- Mejora en la desviación estándar
- Porcentaje de mejora en la calidad de datos

### 4. 📊 **Volatility Analysis** (Análisis de Volatilidad)

Evaluación de la estabilidad y riesgo del mercado.

#### Métricas de Volatilidad:

**A. Volatilidad Actual:**
- **Absolute Volatility**: Medida absoluta de fluctuación
- **Relative Volatility**: Volatilidad como porcentaje del precio
- **Classification**: Muy baja / Baja / Moderada / Alta / Muy alta

**B. Volatilidad Móvil:**
- Gráficos de volatilidad para períodos de 5, 10, 20 datos
- Tendencia de volatilidad (aumentando/disminuyendo/estable)
- Promedio de volatilidad en cada período

**C. Implicaciones para Trading:**
- **Estabilidad del Mercado**: Qué tan predecible es el precio
- **Nivel de Riesgo**: Evaluación del riesgo de fluctuación
- **Recomendaciones**: Sugerencias basadas en la volatilidad actual

### 5. 📈 **Integral** (Visualización de Gráficos Históricos)

Esta pestaña proporciona una visualización completa de los datos históricos del mercado P2P, permitiendo observar tendencias y patrones a lo largo del tiempo.

#### Acceso al Gráfico Histórico:
1. Navega a la pestaña **"Integral"** en la interfaz de análisis estadístico
2. El gráfico se carga automáticamente con los datos más recientes
3. Por defecto muestra las últimas 6 horas de datos

#### Controles de Período:
```
Período: [6h] [12h] [24h] [48h] [7d] [30d]
```

**Períodos Disponibles:**
- **6h**: Últimas 6 horas - Ideal para trading intradia y movimientos rápidos
- **12h**: Últimas 12 horas - Para análisis de tendencias de medio día
- **24h**: Últimas 24 horas - Vista diaria completa de la actividad
- **48h**: Últimos 2 días - Patrones de fin de semana vs días laborables
- **7d**: Última semana - Tendencias semanales y ciclos regulares
- **30d**: Último mes - Análisis de tendencias a largo plazo

#### Elementos del Gráfico:

**A. Líneas de Precio:**
- **Línea Azul (BUY)**: Representa los precios promedio de compra a lo largo del tiempo
- **Línea Verde (SELL)**: Representa los precios promedio de venta a lo largo del tiempo
- **Grosor de línea**: Las líneas más gruesas indican mayor volumen de órdenes

**B. Área de Spread:**
- **Área Sombreada**: El espacio entre las líneas BUY y SELL representa el spread del mercado
- **Color del Área**: 
  - Verde claro: Spread normal (oportunidad estándar)
  - Amarillo: Spread amplio (mayor oportunidad, pero menos liquidez)
  - Rojo: Spread muy amplio (posible problema de liquidez)

**C. Elementos Interactivos:**
- **Hover/Desplazamiento**: Coloca el cursor sobre cualquier punto para ver:
  - Precio exacto de BUY y SELL en ese momento
  - Fecha y hora específica
  - Valor del spread en ese punto
  - Número de órdenes activas
- **Zoom**: Usa la rueda del ratón para hacer zoom en períodos específicos
- **Navegación**: Arrastra horizontalmente para moverte por el historial

#### Métricas Estadísticas Mostradas:

**A. Panel de Información Superior:**
- **Spread Promedio**: Diferencia promedio entre BUY y SELL durante el período
- **Volatilidad del Período**: Medida de la variabilidad de precios
- **Tendencia General**: Indicador de si los precios están subiendo, bajando o estables
- **Puntos de Datos**: Cantidad total de snapshots incluidos en el gráfico

**B. Estadísticas en Tiempo Real:**
- **Precio BUY Actual**: Último precio promedio de compra registrado
- **Precio SELL Actual**: Último precio promedio de venta registrado
- **Spread Actual**: Diferencia actual entre BUY y SELL
- **Última Actualización**: Timestamp de la última recolección de datos

#### Interpretación del Gráfico:

**A. Patrones de Tendencia:**
- **Líneas Paralelas**: Mercado estable con spread consistente
- **Convergencia**: Las líneas se acercan, indica mejora en la liquidez
- **Divergencia**: Las líneas se separan, posible problema de liquidez o alta volatilidad
- **Oscilaciones Regulares**: Patrones cíclicos normales del mercado P2P

**B. Señales de Trading:**
- **Spread Estrecho**: Mejor momento para operaciones grandes
- **Spread Amplio**: Oportunidad de arbitraje, pero con menor liquidez
- **Tendencia Ascendente**: Presión compradora en el mercado
- **Tendencia Descendente**: Presión vendedora en el mercado

**C. Indicadores de Calidad:**
- **Líneas Suaves**: Datos de buena calidad, mercado activo
- **Líneas Irregulares**: Posible baja actividad o datos de menor calidad
- **Gaps en el Gráfico**: Períodos sin datos (mantenimiento de Binance o problemas técnicos)

#### Casos de Uso Prácticos:

**Para Trading Intradia (6h-12h):**
- Identifica patrones de alta y baja actividad durante el día
- Observa cómo el spread varía en diferentes horarios
- Detecta momentos óptimos para ejecutar órdenes grandes

**Para Análisis Semanal (7d):**
- Compara patrones de días laborables vs fines de semana
- Identifica tendencias de mediano plazo
- Evalúa la consistencia del mercado P2P

**Para Planificación a Largo Plazo (30d):**
- Observa tendencias macro del tipo de cambio
- Identifica patrones estacionales o cíclicos
- Evalúa la evolución de la liquidez del mercado

#### Consejos de Interpretación:

1. **Combina con Otras Pestañas**: Usa el gráfico histórico junto con el análisis detallado para confirmar tendencias
2. **Observa el Contexto**: Considera eventos económicos o noticias que puedan explicar cambios abruptos
3. **Valida con Volumen**: Tendencias con mayor volumen son más confiables
4. **Considera la Hora**: Los patrones pueden variar según las horas de mayor actividad en Venezuela

---

## Interpretación de Resultados

### Códigos de Color

**🟢 Verde**: Datos de buena calidad, mercado estable
**🟡 Amarillo**: Advertencias menores, proceder con precaución  
**🔴 Rojo**: Problemas significativos, alta volatilidad o mala calidad de datos

### Quality Scores (Puntuaciones de Calidad)

- **0.8 - 1.0**: Excelente calidad de datos
- **0.6 - 0.8**: Buena calidad de datos
- **0.4 - 0.6**: Calidad moderada, usar con precaución
- **0.0 - 0.4**: Calidad pobre, no recomendado para decisiones importantes

### Niveles de Volatilidad

- **Very Low** (< 1%): Mercado muy estable, ideal para órdenes grandes
- **Low** (1-2%): Mercado estable con fluctuación mínima
- **Moderate** (2-5%): Fluctuación normal del mercado
- **High** (5-10%): Alta volatilidad, mayor riesgo
- **Very High** (> 10%): Mercado muy volátil, alto riesgo

---

## Consejos de Uso

### Para Traders Principiantes

1. **Empieza con Comprehensive Analysis**: Obtén una visión general del mercado
2. **Revisa el Quality Score**: Solo usa datos con score > 0.6
3. **Observa el Spread**: Spreads amplios indican menor liquidez
4. **Evita períodos de alta volatilidad**: Especialmente si eres nuevo

### Para Traders Experimentados

1. **Usa Detailed Analysis**: Configura parámetros según tu estrategia
2. **Analiza Outliers**: Identifica oportunidades o manipulación
3. **Monitorea Volatility**: Ajusta el tamaño de posición según volatilidad
4. **Revisa el Gráfico Histórico**: Usa la pestaña "Integral" para identificar patrones temporales
5. **Compara métodos de detección**: Usa el más apropiado para cada situación

### Para Análisis Técnico

1. **Percentile Analysis**: Identifica niveles de soporte y resistencia
2. **Trend Analysis**: Observa la dirección del mercado
3. **Confidence Intervals**: Evalúa la confiabilidad de las predicciones
4. **Weighted Averages**: Usa VWAP para órdenes grandes
5. **Análisis de Patrones Temporales**: Usa los gráficos históricos para identificar:
   - Patrones de repetición en horarios específicos
   - Convergencias y divergencias de spread
   - Niveles de soporte y resistencia dinámicos
   - Ciclos de liquidez del mercado P2P

---

## Actualización de Datos

### Frecuencia de Actualización
- **Datos del Mercado**: Se actualizan automáticamente cada 5 minutos
- **Interfaz**: Haz clic en el botón "Refresh" o recarga la página para obtener datos más recientes
- **Última Actualización**: Se muestra la fecha/hora de la última actualización en cada análisis

### Indicadores de Estado
- **🟢 Datos Actuales**: Recolectados en los últimos 5 minutos
- **🟡 Datos Recientes**: Entre 5-15 minutos de antigüedad
- **🔴 Datos Antiguos**: Más de 15 minutos, considera refrescar

---

## Resolución de Problemas

### Problemas Comunes

**1. "No hay datos disponibles"**
- Verifica que el par USDT/VES esté seleccionado
- Espera unos minutos para la próxima recolección de datos
- Recarga la página

**2. "Quality Score muy bajo"**
- Los datos pueden estar afectados por baja actividad del mercado
- Prueba en diferentes horarios (horarios de mayor actividad en Venezuela)
- Usa un método de outlier detection diferente

**3. "Error 500 en la API"**
- Problema temporal del servidor
- Espera unos minutos e intenta nuevamente
- Si persiste, contacta al soporte técnico

**4. "Componentes no se cargan"**
- Limpia el caché del navegador
- Verifica tu conexión a internet
- Prueba en modo incógnito

### Mejores Prácticas

1. **Usa múltiples métodos de análisis**: No dependas de una sola métrica
2. **Combina con análisis fundamental**: Los datos técnicos son solo parte del panorama
3. **Mantén registros**: Documenta tus análisis para aprender de patrones
4. **Considera el contexto del mercado**: Eventos externos pueden afectar los datos

---

## Glosario de Términos

**API**: Interfaz de programación que permite obtener datos de Binance
**Arbitraje**: Oportunidad de ganar dinero aprovechando diferencias de precio
**Confidence Interval**: Rango estadístico donde probablemente se encuentra el valor real
**IQR**: Rango Intercuartil, método robusto para detectar outliers
**Merchant**: Comerciante P2P que publica órdenes en Binance
**Outlier**: Valor anómalo que se desvía significativamente del resto
**P2P**: Peer-to-Peer, comercio directo entre usuarios
**Percentile**: Valor que divide los datos en porcentajes específicos
**Quality Score**: Puntuación automática de la calidad de los datos
**Spread**: Diferencia entre precio de compra y venta
**Standard Deviation**: Medida de dispersión de los datos
**VWAP**: Volume Weighted Average Price, precio promedio ponderado por volumen
**Volatility**: Medida de la variabilidad de los precios

---

## Soporte y Contacto

Para obtener ayuda adicional:
- Revisa los logs de error en el navegador (F12 → Console)
- Consulta la documentación técnica en `/docs/`
- Reporta problemas a través del sistema de issues del proyecto

Esta guía te proporciona todo lo necesario para usar efectivamente el Sistema de Análisis P2P. ¡Feliz trading! 📈
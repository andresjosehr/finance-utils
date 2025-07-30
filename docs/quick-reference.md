# Referencia Rápida - Sistema de Análisis P2P

## 🎯 Acceso Rápido

| Acción | Ubicación | Resultado |
|--------|-----------|-----------|
| **Acceder al sistema** | Menú lateral → "Statistical Analysis" | Interfaz principal |
| **Cambiar par de trading** | Selectores superiores | USDT/VES (único funcional) |
| **Análisis general** | Pestaña "Comprehensive Analysis" | Comparación BUY vs SELL |
| **Análisis específico** | Pestaña "Detailed Analysis" | Estadísticas detalladas |
| **Detectar anomalías** | Pestaña "Outlier Analysis" | Visualización de outliers |
| **Evaluar riesgo** | Pestaña "Volatility Analysis" | Análisis de volatilidad |

---

## 📊 Interpretación de Métricas

### Quality Score (Puntuación de Calidad)
```
🟢 0.8 - 1.0  = Excelente (operar con confianza)
🟡 0.6 - 0.8  = Buena (operar normalmente)
🟠 0.4 - 0.6  = Regular (reducir posición)
🔴 0.0 - 0.4  = Pobre (evitar operar)
```

### Spread Assessment (Evaluación del Diferencial)
```
🟢 Tight     = Diferencial estrecho (<2%)
🟡 Normal    = Diferencial normal (2-5%)
🔴 Wide      = Diferencial amplio (>5%)
```

### Volatility Classification (Clasificación de Volatilidad)
```
🟢 Very Low  = <1% (ideal para órdenes grandes)
🟢 Low       = 1-2% (mercado estable)
🟡 Moderate  = 2-5% (fluctuación normal)
🟠 High      = 5-10% (alta volatilidad)
🔴 Very High = >10% (mercado muy volátil)
```

### Trend Direction (Dirección de Tendencia)
```
📈 Upward    = Precios en tendencia alcista
📉 Downward  = Precios en tendencia bajista
➡️ Flat      = Sin tendencia clara
```

### Trend Strength (Fuerza de Tendencia)
```
🟢 Very Strong = R² ≥ 0.8 (tendencia muy confiable)
🟢 Strong      = R² ≥ 0.6 (tendencia confiable)
🟡 Moderate    = R² ≥ 0.4 (tendencia moderada)
🟠 Weak        = R² ≥ 0.2 (tendencia débil)
🔴 Very Weak   = R² < 0.2 (sin tendencia)
```

---

## ⚙️ Configuración de Parámetros

### Outlier Detection Methods (Métodos de Detección)
| Método | Cuándo Usar | Pros | Contras |
|--------|-------------|------|---------|
| **IQR** | Datos financieros | Robusto, interpretable | Menos sensible |
| **Z-Score** | Distribución normal | Simple, estándar | Asume normalidad |
| **Modified Z-Score** | Datos con ruido | Muy robusto | Más complejo |

### Confidence Levels (Niveles de Confianza)
| Nivel | Uso Recomendado | Interpretación |
|-------|-----------------|----------------|
| **90%** | Trading agresivo | Intervalos más amplios |
| **95%** | Trading estándar | Balance recomendado |
| **99%** | Trading conservador | Intervalos más estrechos |

### Sample Size (Tamaño de Muestra)
| Tamaño | Situación | Resultado |
|--------|-----------|-----------|
| **20-30** | Mercado rápido | Análisis ágil |
| **50** | Uso estándar | Balance óptimo |
| **100+** | Análisis profundo | Mayor precisión |

---

## 🚨 Señales de Alerta

### ❌ NO Operar Cuando:
```
🚨 Quality Score < 0.4
🚨 Volatilidad "Very High"
🚨 Outliers > 25%
🚨 Datos > 30 minutos antiguos
🚨 < 3 órdenes activas
🚨 Spread > 8%
🚨 Merchant reliability promedio < 0.6
```

### ⚠️ Proceder with Precaución:
```
⚠️ Quality Score 0.4-0.6
⚠️ Volatilidad "High"
⚠️ Outliers 15-25%
⚠️ Datos 15-30 minutos antiguos
⚠️ 3-5 órdenes activas
⚠️ Spread 5-8%
```

### ✅ Condiciones Óptimas:
```
✅ Quality Score > 0.7
✅ Volatilidad "Low" o "Very Low"
✅ Outliers < 15%
✅ Datos < 10 minutos
✅ > 8 órdenes activas
✅ Spread < 4%
✅ Merchant reliability > 0.8
```

---

## 📈 Estrategias por Perfil

### 👨‍💼 Trader Conservador
```
📋 Configuración:
   • Confidence Level: 99%
   • Outlier Method: IQR
   • Sample Size: 100
   
🎯 Criterios:
   • Quality Score > 0.8
   • Volatilidad ≤ "Low"
   • Spread ≤ 3%
   • > 10 órdenes activas
```

### ⚡ Trader Agresivo
```
📋 Configuración:
   • Confidence Level: 90%
   • Outlier Method: Modified Z-Score
   • Sample Size: 30
   
🎯 Criterios:
   • Quality Score > 0.6
   • Volatilidad ≤ "High"
   • Spread ≤ 6%
   • > 5 órdenes activas
```

### 🔍 Analista Técnico
```
📋 Configuración:
   • Confidence Level: 95%
   • Outlier Method: Comparar todos
   • Sample Size: 50-100
   
🎯 Enfoque:
   • Múltiples métodos de análisis
   • Percentiles para soporte/resistencia
   • Trend analysis para dirección
   • Weighted averages para precision
```

---

## 🔧 Soluciones Rápidas

### Problema: Datos no se cargan
```
1. ✅ Verificar USDT/VES seleccionado
2. 🔄 Refrescar página (F5)
3. ⏱️ Esperar 5 minutos
4. 🌐 Verificar conexión internet
```

### Problema: Quality Score bajo
```
1. 🔄 Cambiar método outlier detection
2. ⏰ Probar en horario pico (7-10am, 2-6pm VET)
3. ⏱️ Esperar próxima recolección
4. 📊 Verificar actividad del mercado
```

### Problema: Resultados inconsistentes
```
1. 📊 Comparar múltiples métodos
2. 🕒 Revisar timestamp de datos
3. 📈 Analizar volatilidad del período
4. 📰 Considerar eventos externos
```

---

## 💡 Tips Rápidos

### ⏰ Mejores Horarios (Venezuela)
```
🌅 Mañana: 7:00 AM - 10:00 AM (alta actividad)
🌞 Tarde: 2:00 PM - 6:00 PM (pico comercial)
🌙 Noche: 8:00 PM - 11:00 PM (actividad nocturna)
```

### 📊 Análisis Rápido en 2 Minutos
```
1. 👀 Comprehensive Analysis → Quality Scores
2. 📈 Verificar spread assessment
3. ⚠️ Outlier Analysis → % de anomalías
4. 📊 Volatility → Classification
5. ✅ Decidir basado en criterios
```

### 🎯 Usar Percentiles como Niveles
```
P10-P25: Zona de compra agresiva
P25-P50: Zona de compra normal
P50-P75: Zona de venta normal
P75-P90: Zona de venta agresiva
```

---

## 📱 Atajos de Teclado

| Acción | Atajo | Resultado |
|--------|-------|-----------|
| **Refrescar página** | F5 | Nuevos datos |
| **Abrir herramientas dev** | F12 | Console para errores |
| **Zoom +** | Ctrl + | Aumentar tamaño |
| **Zoom -** | Ctrl - | Reducir tamaño |
| **Zoom reset** | Ctrl 0 | Tamaño normal |

---

## 📊 Fórmulas Clave

### Spread Calculation
```
Absolute Spread = Buy Price - Sell Price
Percentage Spread = (Absolute Spread / Sell Price) × 100
```

### Quality Score Factors
```
Quality Score = (Completeness × 0.4) + (Timing × 0.2) + 
                (Response × 0.2) + (Consistency × 0.2)
```

### Volatility Calculation
```
Volatility = Standard Deviation of Returns
Relative Volatility = (Volatility / Mean Price) × 100
```

---

## 🆘 Contacto Rápido

| Tipo | Recurso | Ubicación |
|------|---------|-----------|
| **Documentación completa** | user-guide.md | `/docs/user-guide.md` |
| **Tutorial paso a paso** | quick-start-tutorial.md | `/docs/quick-start-tutorial.md` |
| **Referencia API** | api-reference.md | `/docs/03-api-reference.md` |
| **Errores técnicos** | Console del navegador | F12 → Console |

---

## 📋 Checklist de Trading

```
PRE-OPERACIÓN:
□ Quality Score > 0.6
□ Datos < 15 minutos
□ Volatilidad aceptable
□ Spread razonable
□ Suficientes órdenes
□ Sin outliers extremos

DURANTE ANÁLISIS:
□ Comparar múltiples métricas
□ Verificar consistencia
□ Considerar contexto de mercado
□ Documentar decisiones

POST-ANÁLISIS:
□ Confirmar criterios cumplidos
□ Evaluar nivel de confianza
□ Determinar tamaño de posición
□ Ejecutar con plan definido
```

---

**🎯 Recuerda**: Esta es una herramienta de análisis. Las decisiones de trading son tu responsabilidad. Siempre considera múltiples factores y tu tolerancia al riesgo.
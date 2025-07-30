# API Reference - P2P Market Data Analysis Module

## Overview

The P2P Market Data Analysis API provides comprehensive access to cryptocurrency market data, statistical analysis, and trading insights. All endpoints return JSON responses and require authentication through Laravel's built-in session system.

## Base URL

```
Production: https://your-domain.com/api/binance-p2p
Development: http://localhost:8000/api/binance-p2p
```

## Authentication

All API endpoints require user authentication. The application uses Laravel Sanctum for API authentication with session-based authentication for web requests.

```http
GET /api/binance-p2p/endpoint
Authorization: Bearer {token}
Content-Type: application/json
```

## Endpoints Overview

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/market-summary` | GET | Current market summary |
| `/buy-prices` | GET | Buy market data |
| `/sell-prices` | GET | Sell market data |
| `/both-prices` | GET | Combined buy/sell data |
| `/comprehensive-analysis` | GET | Complete statistical analysis |
| `/statistical-analysis` | GET | Detailed statistical metrics |
| `/outliers` | GET | Outlier detection analysis |
| `/volatility-analysis` | GET | Market volatility analysis |
| `/historical-prices` | GET | Historical price data with trends |

---

## 1. Market Summary

Get current market summary with basic metrics.

### Request

```http
GET /api/binance-p2p/market-summary?asset=USDT&fiat=VES
```

### Parameters

| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `asset` | string | Yes | Base cryptocurrency (e.g., "USDT") |
| `fiat` | string | Yes | Fiat currency (e.g., "VES") |

### Response

```json
{
  "pair": "USDT/VES",
  "timestamp": "2025-01-30T10:30:00.000Z",
  "buy_market": {
    "best_price": 169.78,
    "average_price": 169.52,
    "worst_price": 167.50,
    "total_ads": 10,
    "total_volume": 125000.50
  },
  "sell_market": {
    "best_price": 163.90,
    "average_price": 163.35,
    "worst_price": 158.59,
    "total_ads": 11,
    "total_volume": 89500.25
  },
  "spread": {
    "absolute": 5.87,
    "percentage": 3.52
  },
  "data_quality": {
    "buy_quality_score": 0.85,
    "sell_quality_score": 0.78,
    "overall_quality": 0.82
  }
}
```

---

## 2. Buy Market Data

Get detailed buy market information.

### Request

```http
GET /api/binance-p2p/buy-prices?asset=USDT&fiat=VES&limit=20
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `asset` | string | Yes | - | Base cryptocurrency |
| `fiat` | string | Yes | - | Fiat currency |
| `limit` | integer | No | 50 | Maximum number of orders |

### Response

```json
{
  "pair": "USDT/VES",
  "trade_type": "BUY",
  "timestamp": "2025-01-30T10:30:00.000Z",
  "orders": [
    {
      "price": 169.78,
      "available_amount": 5000.00,
      "min_order": 100.00,
      "max_order": 5000.00,
      "merchant": {
        "name": "CryptoTrader123",
        "completion_rate": 98.5,
        "avg_pay_time": 15,
        "orders_count": 1250
      },
      "payment_methods": ["Banco de Venezuela", "Mercantil"]
    }
  ],
  "statistics": {
    "count": 10,
    "average_price": 169.52,
    "median_price": 169.78,
    "min_price": 167.50,
    "max_price": 170.02,
    "total_volume": 125000.50,
    "quality_score": 0.85
  }
}
```

---

## 3. Sell Market Data

Get detailed sell market information.

### Request

```http
GET /api/binance-p2p/sell-prices?asset=USDT&fiat=VES&limit=20
```

### Parameters

Same as buy market data endpoint.

### Response

Similar structure to buy market data with `trade_type: "SELL"`.

---

## 4. Combined Market Data

Get both buy and sell market data in a single request.

### Request

```http
GET /api/binance-p2p/both-prices?asset=USDT&fiat=VES&limit=20
```

### Response

```json
{
  "pair": "USDT/VES",
  "timestamp": "2025-01-30T10:30:00.000Z",
  "buy_market": {
    // Buy market data structure
  },
  "sell_market": {
    // Sell market data structure  
  },
  "comparison": {
    "spread_absolute": 5.87,
    "spread_percentage": 3.52,
    "liquidity_ratio": 1.4,
    "arbitrage_opportunity": false
  }
}
```

---

## 5. Comprehensive Analysis

Get complete statistical analysis with outlier detection and market comparison.

### Request

```http
GET /api/binance-p2p/comprehensive-analysis?asset=USDT&fiat=VES&outlier_method=iqr&confidence_level=0.95
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `asset` | string | Yes | - | Base cryptocurrency |
| `fiat` | string | Yes | - | Fiat currency |
| `outlier_method` | string | No | "iqr" | Outlier detection method: "iqr", "zscore", "modified_zscore" |
| `confidence_level` | float | No | 0.95 | Confidence level for intervals (0.90, 0.95, 0.99) |

### Response

```json
{
  "asset": "USDT",
  "fiat": "VES",
  "timestamp": "2025-01-30T10:30:00.000Z",
  "buy_analysis": {
    "raw_statistics": {
      "count": 10,
      "mean": 169.50,
      "median": 169.78,
      "mode": 169.78,
      "standard_deviation": 0.74,
      "variance": 0.55,
      "min": 167.50,
      "max": 170.02,
      "range": 2.52,
      "coefficient_of_variation": 0.436,
      "skewness": -2.63,
      "kurtosis": 7.69
    },
    "cleaned_statistics": {
      "count": 9,
      "mean": 169.73,
      "median": 169.78,
      "mode": 169.78,
      "standard_deviation": 0.33,
      "variance": 0.11,
      "min": 168.98,
      "max": 170.02,
      "range": 1.04,
      "coefficient_of_variation": 0.20,
      "skewness": -1.63,
      "kurtosis": 3.00
    },
    "outlier_analysis": {
      "method_used": "iqr",
      "outliers_detected": 1,
      "outlier_percentage": 10.0,
      "outlier_values": [167.50],
      "outlier_indices": [0]
    },
    "weighted_averages": {
      "volume_weighted": 168.92,
      "trade_count_weighted": 169.42,
      "reliability_weighted": 169.50,
      "amount_weighted": 169.05
    },
    "time_weighted_averages": {
      "exponential_weighted": 169.50,
      "linear_decay_weighted": 169.50,
      "recent_emphasis_weighted": 168.84
    },
    "confidence_intervals": {
      "confidence_level": 0.95,
      "mean": 169.73,
      "margin_of_error": 0.30,
      "lower_bound": 169.42,
      "upper_bound": 170.03,
      "sample_size": 9,
      "standard_error": 0.12
    },
    "percentile_analysis": {
      "P5": 169.14,
      "P10": 169.30,
      "P25": 169.61,
      "P50": 169.78,
      "P75": 170.00,
      "P90": 170.00,
      "P95": 170.01
    },
    "trend_analysis": {
      "slope": 0.12,
      "intercept": 169.26,
      "r_squared": 0.82,
      "trend_direction": "upward",
      "trend_strength": "strong",
      "price_change_rate": 0.12
    },
    "volatility_analysis": {
      "absolute_volatility": 0.35,
      "relative_volatility": 0.21,
      "volatility_classification": "very_low",
      "rolling_volatility_5": {
        "values": [0.34, 0.23, 0.16, 0.12, 0.10],
        "average": 0.19,
        "min": 0.10,
        "max": 0.34
      }
    },
    "statistical_tests": {
      "normality_test": {
        "skewness": -1.37,
        "kurtosis": 1.41,
        "normality_score": 0.51,
        "assessment": "moderately_normal"
      },
      "outlier_impact": {
        "raw_mean": 169.50,
        "clean_mean": 169.73,
        "absolute_difference": 0.22,
        "percentage_impact": 0.13,
        "impact_level": "minimal"
      },
      "data_consistency": {
        "coefficient_of_variation": 0.21,
        "consistency_level": "high",
        "data_spread": "very_low"
      }
    },
    "quality_metrics": {
      "total_data_points": 10,
      "clean_data_points": 9,
      "outliers_removed": 1,
      "data_retention_rate": 90.0,
      "outlier_rate": 10.0,
      "quality_score": 0.72,
      "data_completeness": {
        "percentage": 100,
        "level": "excellent"
      }
    }
  },
  "sell_analysis": {
    // Similar structure for sell market
  },
  "market_comparison": {
    "price_spread": {
      "absolute": -5.90,
      "percentage": -3.48,
      "assessment": "tight"
    },
    "volatility_comparison": {
      "buy_volatility": 0.21,
      "sell_volatility": 0.38,
      "volatility_difference": 0.17
    },
    "liquidity_comparison": {
      "buy_sample_size": 10,
      "sell_sample_size": 11,
      "liquidity_balance": 1.0
    },
    "quality_comparison": {
      "buy_quality_score": 0.72,
      "sell_quality_score": 0.74,
      "quality_difference": 0.02
    },
    "arbitrage_opportunity": {
      "exists": false,
      "potential_profit_percentage": 0,
      "risk_assessment": "very_low"
    }
  }
}
```

---

## 6. Statistical Analysis

Get detailed statistical analysis without market comparison.

### Request

```http
GET /api/binance-p2p/statistical-analysis?asset=USDT&fiat=VES&trade_type=BUY&sample_size=50
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `asset` | string | Yes | - | Base cryptocurrency |
| `fiat` | string | Yes | - | Fiat currency |
| `trade_type` | string | Yes | - | "BUY" or "SELL" |
| `sample_size` | integer | No | 50 | Number of recent orders to analyze |
| `outlier_method` | string | No | "iqr" | Outlier detection method |
| `confidence_level` | float | No | 0.95 | Confidence level for intervals |

### Response

Returns the analysis section from comprehensive analysis for the specified trade type.

---

## 7. Outlier Detection

Get detailed outlier analysis and comparison of detection methods.

### Request

```http
GET /api/binance-p2p/outliers?asset=USDT&fiat=VES&trade_type=BUY&outlier_method=iqr
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `asset` | string | Yes | - | Base cryptocurrency |
| `fiat` | string | Yes | - | Fiat currency |
| `trade_type` | string | Yes | - | "BUY" or "SELL" |
| `outlier_method` | string | No | "iqr" | Primary outlier detection method |

### Response

```json
{
  "asset": "USDT",
  "fiat": "VES",
  "trade_type": "BUY",
  "timestamp": "2025-01-30T10:30:00.000Z",
  "primary_method": {
    "method": "iqr",
    "outliers_detected": 1,
    "outlier_percentage": 10.0,
    "outliers": [
      {
        "value": 167.50,
        "index": 0,
        "deviation_from_median": -2.28,
        "z_score": -2.74,
        "merchant_reliability": 0.85,
        "risk_assessment": "medium"
      }
    ]
  },
  "method_comparison": {
    "iqr": {
      "outliers_count": 1,
      "outlier_values": [167.50],
      "threshold_lower": 168.36,
      "threshold_upper": 170.43
    },
    "zscore": {
      "outliers_count": 1,
      "outlier_values": [167.50],
      "threshold": 2.5
    },
    "modified_zscore": {
      "outliers_count": 1,
      "outlier_values": [167.50],
      "threshold": 3.5
    }
  },
  "impact_analysis": {
    "raw_statistics": {
      "mean": 169.50,
      "median": 169.78,
      "std_dev": 0.74
    },
    "cleaned_statistics": {
      "mean": 169.73,
      "median": 169.78,
      "std_dev": 0.33
    },
    "improvement_metrics": {
      "mean_change": 0.22,
      "std_dev_reduction": 55.4,
      "outlier_impact_level": "minimal"
    }
  }
}
```

---

## 8. Volatility Analysis

Get comprehensive market volatility analysis.

### Request

```http
GET /api/binance-p2p/volatility-analysis?asset=USDT&fiat=VES&periods=5,10,20&timeframe=1h
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `asset` | string | Yes | - | Base cryptocurrency |
| `fiat` | string | Yes | - | Fiat currency |
| `periods` | string | No | "5,10,20" | Rolling volatility periods (comma-separated) |
| `timeframe` | string | No | "1h" | Analysis timeframe |

### Response

```json
{
  "asset": "USDT",
  "fiat": "VES",
  "timestamp": "2025-01-30T10:30:00.000Z",
  "timeframe": "1h",
  "buy_market_volatility": {
    "current_volatility": {
      "absolute": 0.35,
      "relative": 0.21,
      "classification": "very_low"
    },
    "rolling_volatility": {
      "period_5": {
        "values": [0.34, 0.23, 0.16, 0.12, 0.10],
        "average": 0.19,
        "trend": "decreasing",
        "stability": "improving"
      },
      "period_10": {
        "values": [0.45, 0.38, 0.34, 0.28, 0.23, 0.19, 0.16, 0.14, 0.12, 0.10],
        "average": 0.24,
        "trend": "decreasing",
        "stability": "improving"
      }
    },
    "volatility_analysis": {
      "volatility_trend": "decreasing",
      "market_stability": "high",
      "risk_level": "very_low",
      "trading_implications": [
        "Low price fluctuation risk",
        "Stable trading environment",
        "Good for large orders"
      ]
    }
  },
  "sell_market_volatility": {
    // Similar structure for sell market
  },
  "comparative_analysis": {
    "buy_vs_sell_volatility": {
      "buy_volatility": 0.21,
      "sell_volatility": 0.38,
      "difference": 0.17,
      "more_volatile_side": "sell"
    },
    "market_assessment": {
      "overall_volatility": "low",
      "market_efficiency": "high",
      "arbitrage_potential": "low"
    }
  }
}
```

---

## 9. Historical Price Data

Get time-series historical price data from stored market snapshots for trend analysis and charting.

### Request

```http
GET /api/binance-p2p/historical-prices?asset=USDT&fiat=VES&hours=48
```

### Parameters

| Parameter | Type | Required | Default | Description |
|-----------|------|----------|---------|-------------|
| `asset` | string | No | "USDT" | Base cryptocurrency symbol |
| `fiat` | string | No | "VES" | Fiat currency symbol |
| `hours` | integer | No | 24 | Hours to look back (max: 168 for 1 week) |

### Response

```json
{
  "asset": "USDT",
  "fiat": "VES",
  "hours": 48,
  "timestamp": "2025-01-30T10:30:00.000Z",
  "summary": {
    "total_data_points": 192,
    "unique_timestamps": 96,
    "time_range": {
      "start": "2025-01-28T10:30:00.000Z",
      "end": "2025-01-30T10:30:00.000Z",
      "duration_hours": 48
    },
    "price_summary": {
      "min_price": 163.45,
      "max_price": 170.15,
      "avg_price": 166.82,
      "price_volatility": 1.24
    },
    "data_quality": {
      "avg_quality_score": 0.847,
      "min_quality_score": 0.6,
      "max_quality_score": 0.95
    }
  },
  "historical_data": [
    {
      "timestamp": "2025-01-28T10:30:00.000Z",
      "collected_at_unix": 1738053000,
      "trade_type": "BUY",
      "best_price": 169.78,
      "avg_price": 169.52,
      "worst_price": 167.50,
      "median_price": 169.65,
      "volume_weighted_price": 168.92,
      "total_volume": 125000.50,
      "order_count": 10,
      "price_spread": 2.28,
      "data_quality_score": 0.85
    },
    {
      "timestamp": "2025-01-28T10:35:00.000Z",
      "collected_at_unix": 1738053300,
      "trade_type": "SELL",
      "best_price": 163.90,
      "avg_price": 163.35,
      "worst_price": 158.59,
      "median_price": 163.12,
      "volume_weighted_price": 162.88,
      "total_volume": 89500.25,
      "order_count": 11,
      "price_spread": 5.31,
      "data_quality_score": 0.78
    }
  ],
  "spread_data": [
    {
      "timestamp": "2025-01-28T10:30:00.000Z",
      "buy_price": 169.52,
      "sell_price": 163.35,
      "spread_absolute": -6.17,
      "spread_percentage": -3.64
    }
  ]
}
```

### Data Structure Details

#### historical_data Array
Each data point contains:
- **timestamp**: ISO 8601 formatted timestamp
- **collected_at_unix**: Unix timestamp for easier processing
- **trade_type**: "BUY" or "SELL" market type
- **best_price**: Most favorable price (highest for buy, lowest for sell)
- **avg_price**: Simple average of all order prices
- **worst_price**: Least favorable price (lowest for buy, highest for sell)
- **median_price**: Median price from all orders
- **volume_weighted_price**: Average weighted by order volumes
- **total_volume**: Sum of all available amounts
- **order_count**: Number of orders in the snapshot
- **price_spread**: Range between best and worst prices
- **data_quality_score**: Quality assessment score (0.0-1.0)

#### spread_data Array
Calculated only when both buy and sell data exist for the same timestamp:
- **timestamp**: Matching timestamp for both markets
- **buy_price**: Average buy price at this timestamp
- **sell_price**: Average sell price at this timestamp
- **spread_absolute**: Difference (sell_price - buy_price)
- **spread_percentage**: Percentage spread relative to buy price

### Use Cases

1. **Price Trend Analysis**: Track price movements over time
2. **Volatility Assessment**: Calculate historical volatility
3. **Chart Generation**: Power time-series charts and graphs
4. **Market Efficiency**: Analyze spread patterns over time
5. **Data Quality Monitoring**: Track collection reliability

### Performance Notes

- Data is filtered to include only snapshots with quality_score ≥ 0.5
- Maximum lookback period is 168 hours (1 week) to ensure performance
- Results are ordered chronologically for easy chart rendering
- Database queries are optimized with proper time-series indexes

### Error Responses

#### 400 Bad Request - Invalid Hours Parameter
```json
{
  "error": "Hours parameter must be positive"
}
```

#### 400 Bad Request - Missing Required Parameters
```json
{
  "error": "Asset and fiat parameters are required"
}
```

#### 404 Not Found - Trading Pair Not Available
```json
{
  "error": "Trading pair USDT/VES not found or inactive"
}
```

#### 500 Internal Server Error - Data Retrieval Failure
```json
{
  "error": "Failed to retrieve historical price data",
  "message": "Internal server error"
}
```

### Empty Data Response

When no historical data is available for the specified period:

```json
{
  "asset": "USDT",
  "fiat": "VES",
  "hours": 24,
  "message": "No historical data available for the specified time period",
  "historical_data": []
}
```

### SDK Examples

#### JavaScript/React
```javascript
// Fetch 24 hours of historical data
const response = await fetch('/api/binance-p2p/historical-prices?asset=USDT&fiat=VES&hours=24');
const data = await response.json();

// Process for chart display
const chartData = data.historical_data.map(point => ({
  time: new Date(point.timestamp),
  price: point.avg_price,
  volume: point.total_volume,
  quality: point.data_quality_score
}));
```

#### Python
```python
import requests
from datetime import datetime

# Get 48 hours of data
response = requests.get(
    'http://localhost:8000/api/binance-p2p/historical-prices',
    params={'asset': 'USDT', 'fiat': 'VES', 'hours': 48}
)

data = response.json()

# Extract price trends
buy_prices = [p for p in data['historical_data'] if p['trade_type'] == 'BUY']
sell_prices = [p for p in data['historical_data'] if p['trade_type'] == 'SELL']
```

---

## Error Responses

All endpoints return standardized error responses:

### 400 Bad Request

```json
{
  "error": "Bad Request",
  "message": "Invalid asset or fiat currency",
  "code": "INVALID_PARAMETERS",
  "details": {
    "asset": ["The asset field must be a valid cryptocurrency symbol"],
    "fiat": ["The fiat field must be a valid fiat currency code"]
  }
}
```

### 404 Not Found

```json
{
  "error": "Not Found",
  "message": "No data found for the specified trading pair",
  "code": "NO_DATA_FOUND",
  "pair": "BTC/USD"
}
```

### 500 Internal Server Error

```json
{
  "error": "Internal Server Error",
  "message": "An error occurred while processing the request",
  "code": "PROCESSING_ERROR",
  "request_id": "req_1234567890"
}
```

## Rate Limiting

The API implements rate limiting to ensure fair usage:

- **General endpoints**: 60 requests per minute per user
- **Analysis endpoints**: 30 requests per minute per user
- **Bulk data endpoints**: 10 requests per minute per user

Rate limit headers are included in all responses:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1643544000
```

## SDK Examples

### JavaScript/Node.js

```javascript
const api = axios.create({
  baseURL: 'http://localhost:8000/api/binance-p2p',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  }
});

// Get comprehensive analysis
const analysis = await api.get('/comprehensive-analysis', {
  params: {
    asset: 'USDT',
    fiat: 'VES',
    outlier_method: 'iqr',
    confidence_level: 0.95
  }
});

console.log(analysis.data);
```

### Python

```python
import requests

headers = {
    'Authorization': f'Bearer {token}',
    'Content-Type': 'application/json'
}

response = requests.get(
    'http://localhost:8000/api/binance-p2p/comprehensive-analysis',
    headers=headers,
    params={
        'asset': 'USDT',
        'fiat': 'VES',
        'outlier_method': 'iqr',
        'confidence_level': 0.95
    }
)

data = response.json()
print(data)
```

### PHP

```php
$client = new GuzzleHttp\Client([
    'base_uri' => 'http://localhost:8000/api/binance-p2p/',
    'headers' => [
        'Authorization' => 'Bearer ' . $token,
        'Content-Type' => 'application/json'
    ]
]);

$response = $client->get('comprehensive-analysis', [
    'query' => [
        'asset' => 'USDT',
        'fiat' => 'VES',
        'outlier_method' => 'iqr',
        'confidence_level' => 0.95
    ]
]);

$data = json_decode($response->getBody(), true);
```

This API reference provides comprehensive documentation for integrating with the P2P Market Data Analysis system, enabling developers to build sophisticated financial analysis applications.
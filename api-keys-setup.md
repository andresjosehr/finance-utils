# API Keys Setup Guide

## 🔑 **Required API Keys for MCP Servers**

### 💰 **Financial Data APIs**

#### 1. **Alpha Vantage** (Free tier available)
- **URL:** https://www.alphavantage.co/support/#api-key
- **Free tier:** 25 requests/day, 5 requests/minute
- **Use:** Stock prices, forex, crypto data
- **ENV:** `ALPHA_VANTAGE_API_KEY`

#### 2. **CoinMarketCap** (Free tier available)
- **URL:** https://coinmarketcap.com/api/
- **Free tier:** 10,000 calls/month
- **Use:** Crypto prices, market data
- **ENV:** `COINMARKET_API_KEY`

#### 3. **Polygon.io** (Paid service)
- **URL:** https://polygon.io/pricing
- **Cost:** Starts at $99/month for stocks
- **Use:** Real-time stock, forex, crypto data
- **ENV:** `POLYGON_API_KEY`

### 🏪 **Exchange APIs (Optional - for trading)**

#### 4. **Binance** (Free for market data)
- **URL:** https://www.binance.com/en/binance-api
- **Free:** Market data, paid for trading
- **Use:** Crypto exchange data via CCXT
- **ENV:** `BINANCE_API_KEY`, `BINANCE_SECRET`

#### 5. **Coinbase Pro** (Free for market data)
- **URL:** https://docs.cloud.coinbase.com/
- **Free:** Market data, paid for trading
- **Use:** Crypto exchange data via CCXT
- **ENV:** `COINBASE_API_KEY`, `COINBASE_SECRET`

### 🌐 **Browser Automation**

#### 6. **Browserbase** (Paid service)
- **URL:** https://www.browserbase.com/
- **Cost:** Starts at $50/month
- **Use:** Cloud browser automation for scraping
- **ENV:** `BROWSERBASE_API_KEY`

## 🚀 **Quick Start Priority**

### **Free Tier Setup (Start Here):**
1. ✅ **Alpha Vantage** - Free 25 requests/day
2. ✅ **CoinMarketCap** - Free 10k calls/month
3. ✅ **CCXT Exchange APIs** - Free market data

### **Paid Tier (When Scaling):**
4. **Polygon.io** - Professional data
5. **Browserbase** - Cloud scraping

## 📝 **Configuration Steps**

1. **Get API Keys:**
   ```bash
   # Copy the template
   cp .env.mcp .env
   
   # Edit with your keys
   nano .env
   ```

2. **Test API Keys:**
   ```bash
   # Test Alpha Vantage
   curl "https://www.alphavantage.co/query?function=TIME_SERIES_INTRADAY&symbol=IBM&interval=5min&apikey=YOUR_API_KEY"
   
   # Test CoinMarketCap
   curl -H "X-CMC_PRO_API_KEY: YOUR_API_KEY" "https://pro-api.coinmarketcap.com/v1/cryptocurrency/listings/latest"
   ```

3. **Update MCP Config:**
   - Edit `claude-desktop-config.json`
   - Add API keys to `env` sections

## 🔒 **Security Best Practices**

- ✅ Never commit `.env` files to git
- ✅ Use read-only API keys when possible
- ✅ Set IP restrictions on API keys
- ✅ Monitor API usage regularly
- ✅ Rotate keys periodically

## 🆓 **Free Alternatives**

If you don't want to pay for APIs initially:

1. **CoinCap API** - Free crypto data (already integrated)
2. **Public exchange APIs** - Most provide free market data
3. **Yahoo Finance** - Free stock data (not officially supported)
4. **Local scraping** - Use Playwright MCP without Browserbase

## 📊 **API Usage Monitoring**

Most APIs provide usage dashboards:
- Track remaining quota
- Monitor rate limits
- Set up alerts for high usage
- Plan upgrades based on needs

## ❗ **Important Notes**

- Start with free tiers to test functionality
- Scale up to paid tiers based on actual usage
- Some MCP servers work without API keys (limited functionality)
- CCXT server can use multiple exchanges simultaneously
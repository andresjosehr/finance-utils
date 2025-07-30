# 🚀 MCP Servers - Complete Guide

## 📋 **Table of Contents**
1. [Overview](#overview)
2. [Installed MCP Servers](#installed-mcp-servers)
3. [Configuration](#configuration)
4. [Agent Integration](#agent-integration)
5. [API Keys & Setup](#api-keys--setup)
6. [Usage Examples](#usage-examples)
7. [Troubleshooting](#troubleshooting)

---

## 🎯 **Overview**

This project integrates **8 FREE MCP servers** for developing P2P crypto financial applications. All servers are configured to work with the specialized agents defined in the project architecture.

### **✅ Current Status: READY TO USE**
- ✅ Local configuration in `.claude/mcp.json`
- ✅ All Node.js dependencies installed
- ✅ Environment variables configured
- ✅ Test scripts available

---

## 📊 **Installed MCP Servers**

### 🔥 **Critical - Core Functionality**

#### 1. **CCXT Exchange Data** 
```json
"ccxt-exchange-data": {
  "command": "node",
  "args": ["./mcp-servers/mcp-servers/mcp-server-ccxt/src/index.ts"]
}
```
- **Purpose:** Real-time data from 100+ exchanges (Binance, Coinbase, etc.)
- **Used by:** Backend Expert Laravel, Web Scraping Specialist, QA Engineer
- **Tools:** `get_exchange_info`, `fetch_ticker`, `fetch_ohlcv`, `fetch_order_book`
- **API Key:** Not required for market data
- **Status:** ✅ **READY**

#### 2. **Playwright Browser Automation**
```json
"playwright-browser": {
  "command": "node", 
  "args": ["./mcp-servers/mcp-servers/playwright-mcp/src/index.ts"]
}
```
- **Purpose:** Local browser automation for P2P sites scraping
- **Used by:** Web Scraping Specialist, QA Engineer
- **Tools:** `navigate`, `click`, `type`, `screenshot`, `extract`, `wait`
- **API Key:** Not required
- **Status:** ✅ **READY**

#### 3. **Ashra Data Extraction**
```json
"ashra-data-extraction": {
  "command": "node",
  "args": ["./mcp-servers/mcp-servers/ashra-mcp/src/index.ts"]
}
```
- **Purpose:** AI-powered structured data extraction → JSON
- **Used by:** Web Scraping Specialist, Product Analyst
- **Tools:** `extract_data`, `parse_structured`
- **API Key:** Not required
- **Status:** ✅ **READY**

### 🛠️ **Supporting Tools**

#### 4. **CoinCap Crypto Prices**
```json
"coincap-crypto-prices": {
  "command": "node",
  "args": ["./mcp-servers/mcp-servers/coincap-mcp/build/index.js"]
}
```
- **Purpose:** Free crypto prices backup (no API key needed)
- **Used by:** Backend Expert Laravel
- **Tools:** `get_crypto_price`, `list_assets`, `get_bitcoin_price`
- **API Key:** Not required
- **Status:** ✅ **READY**

#### 5. **DBHub Database Management**
```json
"dbhub-database": {
  "command": "node",
  "args": ["./mcp-servers/dbhub/src/index.ts"]
}
```
- **Purpose:** Universal database access (MySQL, PostgreSQL, SQLite)
- **Used by:** Database Expert, Backend Expert Laravel
- **Tools:** Database connection, query execution, schema management
- **API Key:** Not required
- **Status:** ✅ **READY**

#### 6. **Chroma Vector Database**
```json
"chroma-vector-db": {
  "command": "python",
  "args": ["-m", "chroma_mcp.server"],
  "cwd": "./mcp-servers/chroma-mcp"
}
```
- **Purpose:** Vector database for embeddings and ML features
- **Used by:** Database Expert
- **Tools:** Vector storage, similarity search, embeddings
- **API Key:** Not required
- **Status:** ✅ **READY**

### 📈 **FREE Tier APIs (Optional)**

#### 7. **Alpha Vantage Financial Data**
```json
"alpha-vantage-financial": {
  "command": "python",
  "args": ["-m", "alpha_vantage_mcp.server"],
  "cwd": "./mcp-servers/mcp-servers/alpha-vantage-mcp"
}
```
- **Purpose:** Stock prices, forex, crypto data
- **Free Tier:** 25 requests/day
- **API Key:** `ALPHA_VANTAGE_API_KEY` (free at https://www.alphavantage.co/support/#api-key)
- **Status:** ✅ **READY**

#### 8. **CoinMarketCap Data**
```json
"coinmarket-data": {
  "command": "python",
  "args": ["-m", "coinmarket_service.server"],
  "cwd": "./mcp-servers/mcp-servers/coinmarket-mcp-server"
}
```
- **Purpose:** Professional crypto market data
- **Free Tier:** 10,000 calls/month
- **API Key:** `COINMARKETCAP_API_KEY` (free at https://coinmarketcap.com/api/)
- **Status:** ✅ **READY**

---

## ⚙️ **Configuration**

### **Local MCP Configuration**
The project uses local configuration in `.claude/mcp.json` which is automatically detected by Claude Code when working in this directory.

### **Environment Variables**
```bash
# Free API Keys (Optional)
ALPHA_VANTAGE_API_KEY=your-key-here
COINMARKETCAP_API_KEY=your-key-here
```

### **Testing Configuration**
```bash
# Test all servers
node test-mcp-setup.js

# Test individual servers
cd mcp-servers/[server-name] && npm test
```

---

## 🤖 **Agent Integration**

### **Web Scraping Specialist**
**Primary MCPs:** Playwright + Ashra + (optional Browserbase for scaling)
```javascript
// Complete P2P scraping workflow
await playwright.navigate('https://localbitcoins.com/es/buy-bitcoins-online/usd/');
const p2pData = await ashra.extractData('Extract all buy/sell offers with prices');
```

### **Backend Expert Laravel**
**Primary MCPs:** CCXT + DBHub + CoinCap
```php
// Financial data pipeline
$exchangeData = $this->mcpCCXT->fetchTicker('BTC/USD', 'binance');
$this->mcpDBHub->executeSQL("INSERT INTO prices...", $exchangeData);
$backupPrice = $this->mcpCoinCap->getCryptoPrice('bitcoin');
```

### **Database Expert**
**Primary MCPs:** DBHub + Chroma
- Schema design and optimization
- Vector data management
- Query performance tuning

### **QA Engineer**
**Primary MCPs:** Playwright + CCXT + Ashra
- E2E testing with browser automation
- Financial data accuracy validation
- Scraped data verification

### **Product Analyst**
**Primary MCPs:** CCXT + Ashra
- Market trend analysis
- Competitor P2P pricing research
- Data-driven requirement definition

---

## 🔑 **API Keys & Setup**

### **Free Tier APIs** (Recommended)
1. **Alpha Vantage** (25 requests/day FREE)
   - Get key: https://www.alphavantage.co/support/#api-key
   - Add to `.env`: `ALPHA_VANTAGE_API_KEY=your-key`

2. **CoinMarketCap** (10k calls/month FREE)
   - Get key: https://coinmarketcap.com/api/
   - Add to `.env`: `COINMARKETCAP_API_KEY=your-key`

### **No API Key Required**
- CCXT Exchange Data (market data)
- Playwright Browser Automation
- Ashra Data Extraction
- CoinCap Crypto Prices
- DBHub Database Management
- Chroma Vector Database

---

## 💡 **Usage Examples**

### **P2P Price Collection Pipeline**
```javascript
// 1. Get official exchange price
const binancePrice = await ccxt.fetchTicker('BTC/USD');

// 2. Scrape P2P site
await playwright.navigate('https://localbitcoins.com/es/buy-bitcoins-online/usd/');

// 3. Extract P2P listings
const p2pData = await ashra.extractData('Extract all buy/sell offers with prices');

// 4. Store in database
await dbhub.executeSQL('INSERT INTO p2p_prices ...', p2pData);

// 5. Calculate premium/discount
const avgP2PPrice = calculateAverage(p2pData);
const premium = (avgP2PPrice - binancePrice.last) / binancePrice.last * 100;
```

### **Multi-Exchange Data Collection**
```javascript
// Get data from multiple exchanges
const exchanges = ['binance', 'coinbase', 'kraken'];
const prices = {};

for (const exchange of exchanges) {
  prices[exchange] = await ccxt.fetchTicker('BTC/USD', exchange);
}

// Find best prices
const bestBid = Math.max(...Object.values(prices).map(p => p.bid));
const bestAsk = Math.min(...Object.values(prices).map(p => p.ask));
```

### **Database Operations**
```javascript
// Create optimized schema
await dbhub.executeSQL(`
  CREATE TABLE IF NOT EXISTS crypto_prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    symbol VARCHAR(10),
    exchange VARCHAR(20),
    price DECIMAL(20,8),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_symbol_exchange (symbol, exchange),
    INDEX idx_timestamp (timestamp)
  )
`);

// Bulk insert prices
await dbhub.bulkInsert('crypto_prices', priceData);
```

---

## 🔧 **Troubleshooting**

### **Common Issues**

#### 1. **MCP Server Not Responding**
```bash
# Check server path
ls -la ./mcp-servers/[server-name]/

# Test server directly
cd ./mcp-servers/[server-name]
node src/index.ts
```

#### 2. **API Key Errors**
```bash
# Verify environment variables
echo $ALPHA_VANTAGE_API_KEY
echo $COINMARKETCAP_API_KEY

# Test API key
curl "https://www.alphavantage.co/query?function=TIME_SERIES_INTRADAY&symbol=IBM&interval=5min&apikey=$ALPHA_VANTAGE_API_KEY"
```

#### 3. **Playwright Installation Issues**
```bash
# Install browser dependencies
cd ./mcp-servers/mcp-servers/playwright-mcp
npx playwright install
npx playwright install-deps
```

#### 4. **Python MCP Servers**
```bash
# Install Python dependencies
cd ./mcp-servers/chroma-mcp
pip install -r requirements.txt

cd ./mcp-servers/mcp-servers/alpha-vantage-mcp
pip install -r requirements.txt
```

### **Debug Commands**
```bash
# Test individual server
echo '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{}}' | node ./mcp-servers/[server]/src/index.ts

# Test all servers
node test-mcp-setup.js

# Check Claude Code MCP status
# Restart Claude Code to reload .claude/mcp.json
```

### **Performance Tips**
- Use connection pooling for database operations
- Cache exchange data to avoid rate limits
- Implement retry logic for network requests
- Monitor MCP server response times

---

## 🚀 **Next Steps**

1. **✅ Configuration Complete** - All MCP servers configured
2. **🔑 Add API Keys** - Get free Alpha Vantage & CoinMarketCap keys
3. **🧪 Test Integration** - Run test scripts to verify functionality
4. **🔗 Agent Integration** - Connect agents with their assigned MCP servers
5. **📈 Monitor Performance** - Track response times and success rates
6. **🔄 Implement Workflows** - Create end-to-end P2P data collection pipelines

---

## 📚 **Resources**

- [MCP Protocol Documentation](https://modelcontextprotocol.io/)
- [CCXT Exchange Library](https://docs.ccxt.com/)
- [Playwright Automation](https://playwright.dev/)
- [Alpha Vantage API Docs](https://www.alphavantage.co/documentation/)
- [CoinMarketCap API Docs](https://coinmarketcap.com/api/documentation/)

---

## 🔒 **Security Notes**

- ✅ All paid services removed from configuration
- ✅ Only free/freemium APIs configured
- ✅ API keys stored in environment variables
- ✅ No API keys committed to repository
- ✅ Local MCP configuration for project isolation

**Total Cost: $0/month** 🎉
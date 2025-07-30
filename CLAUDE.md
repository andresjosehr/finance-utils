# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel React finance application built with Inertia.js, featuring a comprehensive P2P cryptocurrency market data analysis system. The project includes a modern full-stack architecture with PHP backend, React TypeScript frontend, advanced statistical analysis capabilities, and automated data collection from Binance P2P markets.

## Key Features

### P2P Market Data Analysis Module
- **Automated Data Collection**: Collects Binance P2P market data every 5 minutes via queue jobs
- **Advanced Statistical Analysis**: Multiple outlier detection methods (IQR, Z-Score, Modified Z-Score)
- **Sophisticated Averaging**: Volume-weighted, reliability-weighted, and time-weighted averages
- **Real-time Dashboard**: Interactive React components for market analysis and visualization
- **Historical Data Tracking**: Time-series optimized database schema with 5 comprehensive models
- **RESTful API**: 8 endpoints providing complete market analysis and statistical data
- **Data Quality Management**: Automated quality scoring and validation with merchant reliability tracking

## Development Commands

### Frontend (Node.js/npm)
- `npm run dev` - Start Vite development server for frontend assets
- `npm run build` - Build production assets
- `npm run build:ssr` - Build with server-side rendering support
- `npm run lint` - Run ESLint with auto-fix
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check code formatting
- `npm run types` - Run TypeScript type checking

### Backend (PHP/Composer)
- `composer dev` - Start full development environment (server, queue, logs, vite)
- `composer dev:ssr` - Start development environment with SSR
- `composer test` - Run PHPUnit tests
- `php artisan serve` - Start Laravel development server
- `php artisan test` - Run Laravel tests
- `php artisan migrate` - Run database migrations
- `vendor/bin/pint` - Run Laravel Pint (PHP code formatter)

### Queue & Data Collection
- `php artisan queue:work` - Process queue jobs (including P2P data collection)
- `php artisan queue:work --queue=p2p-data-collection` - Process P2P data collection jobs specifically
- `php artisan collect:p2p-data` - Manual P2P data collection command
- `php artisan db:seed --class=TradingPairsSeeder` - Seed trading pairs with historical data

### Testing
- Backend: `composer test` or `php artisan test` (PHPUnit)
- Frontend: Type checking with `npm run types`
- Code quality: `npm run lint` and `vendor/bin/pint`

## Architecture

### Backend Structure
- **Framework**: Laravel 12 with PHP 8.2+
- **Database**: MySQL (production) / SQLite (development), migrations in `database/migrations/`
- **Authentication**: Laravel Breeze with Inertia.js integration
- **Models**: Located in `app/Models/` (User + 5 financial models: TradingPair, P2PMarketSnapshot, OrderBookEntry, PriceHistory, MarketStatistics)
- **Controllers**: Organized in `app/Http/Controllers/` with Auth, Settings, and BinanceP2PController
- **Services**: 4 specialized services (BinanceP2PService, P2PDataCollectionService, StatisticalAnalysisService, P2PDataValidationService)
- **Jobs**: Queue-based automated data collection (CollectP2PMarketDataJob)
- **Routes**: Separated into `routes/web.php`, `routes/auth.php`, `routes/settings.php`, `routes/api.php`

### Frontend Structure
- **Framework**: React 19 with TypeScript and Inertia.js
- **Styling**: Tailwind CSS 4.0 with custom UI components
- **Components**: 
  - UI components in `resources/js/components/ui/` (Radix UI + custom)
  - App-specific components in `resources/js/components/`
  - Layout components in `resources/js/layouts/`
- **Pages**: Organized in `resources/js/pages/` (auth/, settings/, dashboard.tsx, welcome.tsx, statistical-analysis.tsx)
- **Financial Components**: 4 specialized React components (StatisticalAnalysisDashboard, ComprehensiveMarketAnalysis, OutlierAnalysisChart, VolatilityAnalysisChart)
- **State Management**: Inertia.js shared data, custom hooks for appearance/theme
- **Build Tool**: Vite with Laravel integration

### Key Architectural Patterns
- **Inertia.js SSR**: Full-stack reactivity without API endpoints
- **Component Architecture**: Atomic design with reusable UI components
- **Layout System**: Flexible sidebar/header layouts with mobile responsiveness
- **Theme System**: Dark/light mode with persistent storage
- **Authentication Flow**: Complete auth system with email verification, password reset

### Database
- Development uses SQLite (`database/database.sqlite`) / Production uses MySQL
- Standard Laravel auth tables (users, cache, jobs)
- **Financial Tables**: 5 specialized tables with time-series optimization
  - `trading_pairs` - Cryptocurrency pair configuration
  - `p2p_market_snapshots` - Raw market data with quality scoring
  - `order_book_entries` - Individual orders with merchant performance data
  - `price_history` - OHLC-style aggregated price data
  - `market_statistics` - Pre-calculated statistical analysis
- **Performance Indexes**: Time-series optimized indexes for efficient querying
- Migrations follow Laravel conventions with financial data extensions

### Asset Pipeline
- Vite handles frontend compilation
- CSS: Single `resources/css/app.css` entry point
- JS: `resources/js/app.tsx` main entry with automatic page resolution
- SSR support via `resources/js/ssr.tsx`

## File Organization Conventions
- PHP classes follow PSR-4 autoloading
- React components use PascalCase filenames
- Pages mirror route structure
- UI components are generic, app components are specific
- Hooks prefixed with `use-`
- Types defined in `resources/js/types/`

## P2P Market Data Analysis System

### Core Components
- **Data Collection**: Automated collection every 5 minutes via Laravel Scheduler and Queue Jobs
- **Statistical Analysis**: Advanced algorithms with multiple outlier detection methods
- **API Endpoints**: 8 RESTful endpoints providing comprehensive market analysis
- **Interactive Dashboard**: Real-time React components accessible at `/statistical-analysis`
- **Data Quality Management**: Automated scoring and validation with merchant reliability tracking

### API Endpoints
- `/api/binance-p2p/market-summary` - Current market overview
- `/api/binance-p2p/comprehensive-analysis` - Complete statistical analysis
- `/api/binance-p2p/statistical-analysis` - Detailed statistical metrics
- `/api/binance-p2p/outliers` - Outlier detection analysis
- `/api/binance-p2p/volatility-analysis` - Market volatility assessment
- `/api/binance-p2p/buy-prices` - Buy market data
- `/api/binance-p2p/sell-prices` - Sell market data
- `/api/binance-p2p/both-prices` - Combined market data

### Statistical Features
- **Outlier Detection**: IQR, Z-Score, Modified Z-Score methods
- **Advanced Averaging**: Volume-weighted, reliability-weighted, time-weighted averages
- **Confidence Intervals**: 90%, 95%, 99% confidence levels
- **Trend Analysis**: Linear regression with R-squared and trend strength
- **Volatility Analysis**: Rolling volatility for multiple periods
- **Percentile Analysis**: P5, P10, P25, P50, P75, P90, P95 calculations

### Queue System
- **Default Queue**: `php artisan queue:work` (processes all jobs)
- **Specific Queue**: `php artisan queue:work --queue=p2p-data-collection`
- **Job Scheduling**: Laravel Scheduler runs collection jobs every 5 minutes
- **Error Handling**: Comprehensive error handling with retry mechanisms

## MCP Configuration
- MCP servers configured in `.claude/mcp.json` for local development
- 8 FREE MCP servers ready to use (no paid services)
- **CCXT Exchange Data**: Used for Binance P2P API integration
- Complete documentation in `MCP-COMPLETE-GUIDE.md`
- Test with: `node test-mcp-setup.js`

## Documentation
Comprehensive documentation available in `/docs/` directory:
- **System Overview** - Complete module architecture and functionality
- **Database Architecture** - Detailed schema and relationships
- **API Reference** - Complete endpoint documentation with examples
- **Statistical Analysis** - Mathematical algorithms and implementation details
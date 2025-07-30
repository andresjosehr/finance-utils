# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel React starter kit built with Inertia.js, featuring a modern full-stack architecture with PHP backend and React TypeScript frontend. The project includes authentication, user settings, and a responsive sidebar/header layout system.

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

### Testing
- Backend: `composer test` or `php artisan test` (PHPUnit)
- Frontend: Type checking with `npm run types`
- Code quality: `npm run lint` and `vendor/bin/pint`

## Architecture

### Backend Structure
- **Framework**: Laravel 12 with PHP 8.2+
- **Database**: SQLite (development), migrations in `database/migrations/`
- **Authentication**: Laravel Breeze with Inertia.js integration
- **Models**: Located in `app/Models/` (User model with standard auth features)
- **Controllers**: Organized in `app/Http/Controllers/` with Auth and Settings subdirectories
- **Routes**: Separated into `routes/web.php`, `routes/auth.php`, `routes/settings.php`

### Frontend Structure
- **Framework**: React 19 with TypeScript and Inertia.js
- **Styling**: Tailwind CSS 4.0 with custom UI components
- **Components**: 
  - UI components in `resources/js/components/ui/` (Radix UI + custom)
  - App-specific components in `resources/js/components/`
  - Layout components in `resources/js/layouts/`
- **Pages**: Organized in `resources/js/pages/` (auth/, settings/, dashboard.tsx, welcome.tsx)
- **State Management**: Inertia.js shared data, custom hooks for appearance/theme
- **Build Tool**: Vite with Laravel integration

### Key Architectural Patterns
- **Inertia.js SSR**: Full-stack reactivity without API endpoints
- **Component Architecture**: Atomic design with reusable UI components
- **Layout System**: Flexible sidebar/header layouts with mobile responsiveness
- **Theme System**: Dark/light mode with persistent storage
- **Authentication Flow**: Complete auth system with email verification, password reset

### Database
- Development uses SQLite (`database/database.sqlite`)
- Standard Laravel auth tables (users, cache, jobs)
- Migrations follow Laravel conventions

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

## MCP Configuration
- MCP servers configured in `.claude/mcp.json` for local development
- 8 FREE MCP servers ready to use (no paid services)
- Complete documentation in `MCP-COMPLETE-GUIDE.md`
- Test with: `node test-mcp-setup.js`
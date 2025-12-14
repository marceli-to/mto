# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Technology Stack

This is a Laravel 8 invoice management application with a Vue.js 2 frontend. Key technologies:

- **Backend**: Laravel 8 (PHP 8.2+)
- **Frontend**: Vue.js 2.5+ with Vuex for state management
- **Database**: MySQL/MariaDB
- **Asset Building**: Laravel Mix (Webpack)
- **Authentication**: JWT tokens via tymon/jwt-auth
- **PDF Generation**: DomPDF (barryvdh/laravel-dompdf)
- **Swiss QR Bills**: sprain/swiss-qr-bill

## Development Commands

### PHP/Laravel Commands
```bash
# Install PHP dependencies
composer install

# Run database migrations
php artisan migrate

# Seed database (if needed)
php artisan db:seed

# Generate application key
php artisan key:generate

# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Run tests
./vendor/bin/phpunit
# or
php artisan test

# Start development server
php artisan serve

# Run custom artisan commands
php artisan invoice:report   # Interactive invoice reports by date range and state
php artisan expense:report   # Interactive expense reports with CSV export
php artisan images:clear     # Remove unused uploaded images
php artisan positions:create # Create invoice line items
```

### Frontend Commands
```bash
# Install Node dependencies
npm install

# Development build with file watching
npm run dev
npm run watch

# Production build
npm run prod

# Hot reloading development server
npm run hot
```

## Application Architecture

### Core Business Logic
The application manages:
- **Clients**: Business entities that receive invoices
- **Contacts**: Individuals associated with clients
- **Projects**: Work items billable to clients
- **Rates**: Hourly billing rates
- **Time Tracking**: Time entries for projects
- **Invoices**: Generated bills with line items
- **Expenses**: Business expense tracking

### Key Directories

#### Backend Structure
- `app/Models/` - Eloquent models (Invoice, Client, Project, etc.)
- `app/Http/Controllers/Api/` - RESTful API controllers
- `app/Http/Requests/` - Form validation classes
- `app/Http/Resources/` - API response formatters
- `app/Console/Commands/` - Custom artisan commands
- `app/Observers/` - Model event observers
- `database/migrations/` - Database schema definitions
- `routes/api.php` - API route definitions
- `config/` - Application configuration files

#### Frontend Structure
- `resources/js/spa/` - Vue.js application root
- `resources/js/spa/components/` - Vue components organized by feature
- `resources/js/spa/store.js` - Vuex state management
- `resources/js/spa/routes.js` - Vue Router configuration
- `resources/sass/admin/` - SCSS stylesheets

### API Authentication
All API routes except authentication endpoints require JWT token authentication via the `auth:api` middleware.

### PDF Generation
The application generates:
- Invoice PDFs with Swiss QR codes
- Expense reports
- Invoice journals

Templates are in `resources/views/pdf/`.

### File Uploads
- Expense receipts stored in `storage/app/public/media/expenses/`
- Images processed via Intervention Image library
- Upload handling in `app/Http/Controllers/MediaController.php`

### Database Relationships
- Clients have many Contacts and Projects
- Projects belong to Clients and have Rates
- Time entries belong to Projects
- Invoices belong to Clients and have many InvoicePositions
- Invoices have InvoiceStates (draft, sent, paid, etc.)

### Vue.js Frontend Architecture
- Single-page application with Vue Router
- Vuex for centralized state management (auth state in `resources/js/spa/store.js`)
- Component structure mirrors backend entities (client, contact, project, timer, invoice, expense)
- Authentication state managed globally with JWT token persistence
- API communication via axios with Vue-axios
- Laravel Mix builds assets to `public/assets/admin/` with alias `@` pointing to `resources/js/spa/`

### Testing
- PHPUnit configuration in `phpunit.xml` with Unit and Feature test suites
- Run single test: `./vendor/bin/phpunit --filter=TestName`
- Test environment uses array drivers for cache, mail, queue, and sessions
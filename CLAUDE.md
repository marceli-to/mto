# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Technology Stack

This is a Laravel 12 invoice/quote management application with a Vue 3 SPA frontend. Key technologies:

- **Backend**: Laravel 12 (PHP 8.2+)
- **Frontend**: Vue 3 (Composition API) SPA with Vue Router 4
- **Database**: MySQL/MariaDB
- **Asset Building**: Vite (with `laravel-vite-plugin` and `@vitejs/plugin-vue`)
- **Styling**: Tailwind CSS 4 (`resources/css/app.css`)
- **Authentication**: Laravel Sanctum (`auth:sanctum` guard)
- **PDF Generation**: DomPDF (barryvdh/laravel-dompdf)
- **Swiss QR Bills**: sprain/swiss-qr-bill
- **Image Processing**: Intervention Image
- **AI**: `laravel/ai` SDK (Anthropic Claude — used for receipt scanning)

> Note: `vuex` is listed as a dependency but is **not currently used** — component state is managed with Vue composables (see `resources/js/spa/composables/`).

## Development Commands

### PHP/Laravel Commands
```bash
# Install PHP dependencies
composer install

# Run database migrations
php artisan migrate

# Generate application key
php artisan key:generate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Run tests
php artisan test
# or a single test
./vendor/bin/phpunit --filter=TestName

# Start development server
php artisan serve

# Custom artisan commands (see app/Console/Commands/)
php artisan invoice:report   # Interactive invoice reports by date range and state
php artisan expense:report   # Interactive expense reports with CSV export
# Plus a GeneratePdf command
```

### Frontend Commands
```bash
# Install Node dependencies
npm install

# Vite dev server (HMR)
npm run dev

# Production build (outputs to public/build/)
npm run build

# Preview a production build
npm run preview
```

## Application Architecture

### Core Business Logic
The application manages:
- **Clients**: Business entities that receive invoices
- **Contacts**: Individuals associated with clients
- **Projects**: Work items billable to clients (soft-deletable; reference a Rate)
- **Rates**: Hourly billing rates (`description` + `amount`)
- **Invoices**: Generated bills with line items (InvoicePositions) and InvoiceStates
- **Quotes**: Quotes with sections and positions (Tiptap-edited)
- **Expenses**: Business expense tracking with AI receipt scanning

### Backend patterns

**Action pattern.** Controllers in `app/Http/Controllers/Api/` are thin and delegate to single-purpose Action classes in `app/Actions/<Entity>/` (e.g. `App\Actions\Expense\Store`, `App\Actions\Invoice\Get`). When adding features, follow this pattern rather than putting logic in controllers.

### Key Directories

#### Backend Structure
- `app/Models/` - Eloquent models (Invoice, Client, Project, Quote, etc.)
- `app/Actions/<Entity>/` - Single-purpose action classes (the primary place for business logic)
- `app/Http/Controllers/Api/` - Thin API controllers that delegate to Actions
- `app/Http/Requests/` - Form validation classes
- `app/Http/Resources/` - API response formatters (Collections)
- `app/Observers/` - Model observers (InvoiceObserver, QuoteObserver)
- `app/Console/Commands/` - Custom artisan commands
- `app/Ai/Agents/` - `laravel/ai` agents (e.g. ReceiptScanner)
- `database/migrations/` - Database schema definitions
- `routes/api.php` - API route definitions
- `config/` - Application configuration files

#### Frontend Structure
- `resources/js/spa/app.js` - SPA entry point
- `resources/js/spa/App.vue` - Root component
- `resources/js/spa/router/index.js` - Vue Router configuration
- `resources/js/spa/components/` - Components organized by feature (clients, contacts, projects, invoices, quotes, expenses, dashboard, ui)
- `resources/js/spa/composables/` - Shared composables (`useApi`, `useCurrency`, `useToast`)
- `resources/css/app.css` - Tailwind CSS entry
- Vite alias `@` → `resources/js/spa`

### API Authentication
API routes are protected by the `auth:sanctum` middleware group in `routes/api.php`. The `api` guard uses the Sanctum driver (see `config/auth.php`).

### PDF Generation
Generated via DomPDF. Invoice PDFs embed Swiss QR bills. Blade templates live in `resources/views/pdf/` (`invoice.blade.php`, `expense.blade.php`, `quote.blade.php`, plus `partials/`).

### File Uploads
- Uploads handled via `UploadController` (temp) and finalized in the relevant entity's Actions.
- Expense receipts are stored under `storage/app/public/media/expenses/` (named by expense number).
- Images processed via Intervention Image.

### AI Receipt Scanning
`POST /api/expense/scan` sends an uploaded receipt (JPG/PNG/PDF) to Claude via the `App\Ai\Agents\ReceiptScanner` agent (`laravel/ai`) and returns extracted fields to pre-fill the expense form.

### Database Relationships
- Clients have many Contacts and Projects
- Projects belong to a Client and reference a Rate (`rate_id`)
- Invoices belong to a Client and have many InvoicePositions
- Invoices have InvoiceStates (draft, sent, paid, etc.)
- Quotes have QuoteSections and QuotePositions

### Frontend Architecture
- Single-page application with Vue Router 4
- State/shared logic via composables (not Vuex)
- Forms are presented as flyouts (see existing feature components for the pattern)
- API communication via axios; auth token persisted client-side
- Vite builds assets to `public/build/`

### Testing
- PHPUnit configuration in `phpunit.xml` with Unit and Feature test suites
- Run single test: `./vendor/bin/phpunit --filter=TestName`
- Test environment uses array drivers for cache, mail, queue, and sessions

# StockFlow

StockFlow is a web-based inventory and stock management application for small businesses. It centralizes product and supplier records, protects stock changes with validated transactions, and provides dashboards, alerts, and reports for day-to-day inventory work.

## Objectives

- Keep a reliable view of products, categories, suppliers, and stock quantities.
- Record stock entries and exits with an auditable movement history.
- Prevent invalid stock changes and restrict operations by user role.
- Highlight low and empty stock and make inventory reporting available to authorized staff.

## Features

- Authenticated StockFlow panel at `/admin`, branded for StockFlow.
- Product and category catalog, including SKU, price, minimum stock, and current quantity.
- Supplier management and supplier association on stock entries.
- Stock entry and exit operations with validation, database transactions, and quantity locking.
- Read-only stock movement history.
- Dashboard statistics, recent movements, stock activity, and low-stock products.
- Database notifications for administrators and stock managers when a product crosses from above its minimum stock threshold to at or below it.
- Current inventory and stock movement reports with filters and pagination.
- Administrator, Stock Manager, and Employee roles with policy and gate authorization.

## Technology

- PHP 8.5 (Composer constraint: `^8.3`)
- Laravel 13
- Filament 4
- Livewire 3
- MySQL or MariaDB for a development installation
- Vite and npm for frontend assets
- Laravel Boost for local development tooling
- Pest for automated tests
- Larastan/PHPStan and Laravel Pint for static analysis and formatting

Use the dependency versions in `composer.lock` and `package-lock.json`; do not change framework versions as part of setup.

## Architecture

The application uses Laravel's Eloquent models and relationships for users, categories, products, suppliers, and stock movements. Filament provides the authenticated admin panel, resources, tables, forms, dashboard, reports, and database notifications. Livewire powers Filament's interactive UI.

`StockMovementService` is the entry point for stock changes. It authorizes the operation, validates input, locks the product row, checks available or supported quantity, writes the movement, and changes the product quantity within a database transaction. `InventoryDashboardService` and `InventoryReportService` keep aggregate/report queries out of presentation code. Laravel policies and gates control access to resources, stock operations, reports, and user management.

There is no separate API required for the standard application workflow. Filament routes are registered by the panel provider.

## Requirements

- PHP 8.5, with the extensions required by Laravel and the locked Composer dependencies.
- Composer.
- Node.js and npm.
- MySQL or MariaDB.
- A local web server, such as Laravel Herd, or Laravel's development server.

## Installation

The following instructions are for a **new development machine and a new, empty development database**. Do not point setup or seed commands at a database containing another project's data.

1. Clone the project and enter its directory.
2. Install the locked PHP dependencies:

   ```powershell
   composer install
   ```

3. Create a local environment file only if one does not already exist. Review `.env.example`, then configure `.env` locally with the correct application and database settings. Never commit `.env` or share its secrets.

   ```powershell
   Copy-Item .env.example .env
   ```

   Set the database connection to MySQL and provide the host, port, database name, username, and password for the database you created. Do not assume a blank password; use the credentials configured for your own MySQL/MariaDB installation. If `.env` already exists, preserve it and edit it yourself rather than replacing it.

4. Generate an application key for a new local environment, then clear cached configuration after setting environment values:

   ```powershell
   php artisan key:generate
   php artisan config:clear
   ```

5. Create a new empty database, for example `stockflow_dev`, using phpMyAdmin or your database administration tool. Use `utf8mb4` and grant the local database user access to that database. On phpMyAdmin, select **New**, enter the database name, choose a `utf8mb4` collation, and select **Create**. Do not use an existing database belonging to another application.

6. Confirm that `.env` points to that newly created database. Apply migrations only after confirming the connection target:

   ```powershell
   php artisan migrate
   ```

7. Install the locked frontend dependencies and build the assets:

   ```powershell
   npm ci
   npm run build
   ```

8. Create the first Administrator in the local environment. The command prompts for the password without echoing it and enforces password validation:

   ```powershell
   php artisan stockflow:bootstrap-administrator --confirm --name="Demo Administrator" --email=admin@example.test
   ```

9. Start the application:

   ```powershell
   php artisan serve
   ```

   Open the local application URL and sign in at `/admin`. For frontend development with hot reload, use `npm run dev` in a separate terminal.

Do not use `migrate:fresh`, `db:wipe`, or a setup script that implicitly migrates a database unless you have verified that the configured database is the intended new development database.

## Roles

| Role | Access |
| --- | --- |
| Administrator | Full administrative access, including user and role management, inventory, stock operations, and reports. |
| Stock Manager | Manage the catalog and stock operations and view reports; cannot manage users or assign roles. |
| Employee | Read-only inventory and movement access; cannot perform stock operations or access reports and user management. |

The first Administrator can be provisioned by the local-only bootstrap command above. Administrators manage subsequent user accounts and roles in the panel. The bootstrap command does not create Stock Manager or Employee accounts.

## Main modules

- **Dashboard:** inventory statistics, stock activity period selection, low-stock products, and recent movements.
- **Categories and Products:** maintain catalog information and review each product's stock status.
- **Suppliers:** maintain supplier records and associate a supplier with stock receipts.
- **Stock Movements:** record entries and exits, then inspect the read-only movement history.
- **Reports:** current inventory (including calculated inventory value and category/status filters) and stock movements (date range, product, category, supplier, and movement-type filters).
- **User Management:** administrator-only account and role administration.

## Business rules

- A stock movement must be an entry or exit with a positive integer quantity.
- A stock exit cannot exceed available quantity.
- A stock entry cannot exceed the maximum quantity supported by the product quantity column.
- Supplier association is accepted for entries, not exits.
- The product is locked while a movement is validated and applied inside a database transaction.
- A stock movement is historical audit data and cannot be updated, deleted, restored, or force-deleted through the application.
- Low stock means a positive quantity at or below the product's `min_stock`; zero quantity is out of stock.
- A low-stock notification is sent to Administrators and Stock Managers when a movement crosses the minimum threshold from above it. Employees do not receive these notifications.

## Demo data

`DemoSeeder` is separate from the default `DatabaseSeeder`. It adds three demo categories, two suppliers, three products, and six stock movements through the normal stock service. On an empty development database, the products demonstrate normal (26 units), low (4 units), and out-of-stock (0 units) states.

To load it, first follow the installation instructions on a **fresh development database**, create the local Administrator, and then run:

```powershell
php artisan db:seed --class=DemoSeeder
```

The seeder uses stable demo identifiers and skips movements it has already recorded, but it is intended for demonstration data, not for seeding production or the existing `stockflow` database. It does not create user accounts. Do not run any seeder against a database until you have verified the selected connection and obtained the database owner's approval.

## Tests and quality checks

The test configuration uses SQLite `:memory:` so tests do not need or modify the MySQL/MariaDB database:

```powershell
php artisan test
```

Other useful project checks:

```powershell
vendor/bin/pint --dirty --format agent
vendor/bin/phpstan analyse --memory-limit=1G
composer validate --no-check-publish
php artisan route:list --except-vendor
npm run build
```

## Project structure

```text
app/
  Console/Commands/       Local administrator bootstrap command
  Enums/                  User roles and domain values
  Filament/               Panel resources, pages, reports, and widgets
  Models/                 Eloquent models and relationships
  Notifications/           Database notifications
  Policies/                Resource authorization
  Providers/               Application and Filament panel configuration
  Services/                Stock operations, reports, dashboard, and user management
database/
  factories/               Test data factories
  migrations/              Application schema
  seeders/                 Standard and opt-in demonstration data
resources/
  css/                     Filament theme
  images/                  StockFlow brand assets
tests/
  Feature/                 Application and authorization behavior tests
  Unit/                    Isolated unit tests
```

## Presentation guide

See [docs/PROJECT_PRESENTATION.md](docs/PROJECT_PRESENTATION.md) for a presentation flow, technical overview, business rules, and likely project questions.

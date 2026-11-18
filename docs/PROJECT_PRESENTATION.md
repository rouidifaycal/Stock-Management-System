# StockFlow — Project Presentation Guide

## 1. Project introduction

StockFlow is an inventory management application for a small business. It provides one place to maintain a product catalog and supplier information, record stock receipts and issues, monitor stock levels, and review inventory and movement reports.

The application is intended for three user groups:

- **Administrators**, who configure and manage the application and its users.
- **Stock Managers**, who operate the catalog and stock workflows and review reports.
- **Employees**, who need to consult inventory and movement records without changing stock.

The business problem it addresses is fragmented stock information and unreliable quantity changes. StockFlow links each quantity change to a dated movement and the user who recorded it, while enforcing authorization and quantity rules.

## 2. Main features

| Module | Purpose |
| --- | --- |
| Dashboard | Summarizes inventory, stock activity, low-stock items, and recent movements. Authorized users can select a 7-, 30-, or 90-day activity period. |
| Categories | Organizes products for catalog navigation and reporting. |
| Products | Maintains SKU, category, price, quantity, and minimum stock threshold. Stock status is shown as normal, low, or out of stock. |
| Suppliers | Maintains supplier details and allows a supplier to be associated with an incoming stock entry. |
| Stock operations | Records an entry or exit through the stock movement service, which validates the operation and updates quantity transactionally. |
| Movement history | Shows the recorded movements and associated product, supplier (where relevant), user, date, and quantity. Historical movements are not editable through application actions. |
| Reports | Provides a current inventory report and a stock movement report, both with pagination. Inventory can be filtered by category and stock status. Movements can be filtered by date range, product, category, supplier, and movement type. |
| Notifications | Stores a low-stock notification for Administrators and Stock Managers when stock crosses its configured minimum threshold from above. |
| User management | Allows Administrators to manage user accounts and roles. Stock Managers and Employees cannot access it. |

## 3. Technical architecture

### Laravel and Eloquent

Laravel provides the application framework, routing, authentication, validation, database access, and service container. Eloquent models represent users, categories, products, suppliers, and stock movements, with relationships used to connect catalog and audit data.

### Business services

- `StockMovementService` authorizes and validates a requested movement, obtains a row lock on the product, checks stock bounds, creates the movement, and updates the product quantity within a database transaction. The transaction is configured for retries.
- `InventoryDashboardService` provides dashboard aggregates and activity data.
- `InventoryReportService` provides inventory and movement report queries, including eager loading for related records.
- `UserManagementService` applies authorization and validation around user and role management.

### Authorization

Laravel policies protect resource operations, and named gates control cross-cutting abilities such as report access and stock operations. Filament's strict authorization mode is enabled. Access is enforced by server-side policies and gates, rather than relying only on whether a navigation link is visible.

### Filament and Livewire

Filament 4 provides the authenticated admin panel, catalog resources, forms, tables, dashboard widgets, report pages, login, and database notification interface. Livewire 3 powers the panel's interactive behavior, including dashboard filters and table interactions.

### Database

The schema includes users and roles, categories, suppliers, products, stock movements, and database notifications, plus Laravel support tables for cache and queued jobs. Product and movement relationships preserve the association between current inventory and the history of recorded changes. The test suite is configured to use an in-memory SQLite database rather than the development MySQL/MariaDB database.

## 4. Business rules

1. A movement is either a stock entry or a stock exit.
2. Movement quantities must be positive integers.
3. An exit cannot be larger than the product's available quantity.
4. An entry cannot exceed the maximum quantity supported by the product quantity column.
5. A supplier can be associated with an entry, but not an exit.
6. The product row is locked during the operation, and movement creation plus quantity adjustment occur in a database transaction. This helps prevent concurrent operations from producing inconsistent quantities.
7. Stock movements are historical records. Application authorization denies update, delete, restore, and force-delete operations.
8. A product with quantity zero is out of stock. A positive quantity less than or equal to `min_stock` is low stock; a quantity above `min_stock` is normal.
9. A notification is generated when a stock operation moves a product from above its minimum threshold to at or below it. The notification is stored in the database and addressed to Administrators and Stock Managers.

## 5. Demo scenario

Use a prepared, isolated development database. Do not seed or demonstrate against a production database or another project's database. The opt-in `DemoSeeder` creates demonstration categories, suppliers, products, and movements using the normal stock operation service. Its expected product levels on a fresh setup are 26 (normal), 4 (low), and 0 (out of stock).

Suggested walkthrough:

1. **Log in as Administrator** at `/admin`. Explain that the first Administrator is created locally by the protected `stockflow:bootstrap-administrator` command, and that later users are managed in the application.
2. **Show the dashboard.** Point out inventory statistics, the selected stock activity period, low-stock products, recent movements, and the StockFlow branding.
3. **Open Categories and Products.** Show how products are identified by SKU, assigned a category, and given a minimum stock level.
4. **Open Suppliers.** Explain that supplier details can be attached to a stock entry.
5. **Perform a stock entry** for a product and explain the positive quantity validation and supplier field.
6. **Perform a stock exit** for the same product and explain that exits cannot exceed available stock.
7. **Return to the product or dashboard** and show the updated stock quantity/status.
8. **Open Stock Movements** and identify the entry and exit, their timestamps, quantities, and recorded user.
9. **Show the notification center** with a low-stock alert, if the walkthrough has caused a product to cross its threshold. Alerts are triggered on crossing from above the threshold, not merely by viewing an already-low item.
10. **Open Reports.** Demonstrate category/status filtering in Current Inventory and date, product, category, supplier, and movement-type filters in Stock Movements.
11. **Compare role access** using prepared accounts: Administrators have user management and full administration; Stock Managers can operate inventory and see reports but cannot manage users; Employees can consult inventory and movements but cannot operate stock or access reports. Do not imply that hiding navigation alone is the security boundary; server-side authorization also protects actions and report queries.

For a fresh local demo database, provision the Administrator first and then run `php artisan db:seed --class=DemoSeeder`. The seeder intentionally does not create credentials or user accounts.

## 6. Likely presentation questions

### Why Laravel?

Laravel provides a mature application foundation for routing, authentication, validation, database transactions, Eloquent relationships, and authorization. These built-in patterns let StockFlow keep its inventory rules in server-side application code.

### Why Filament and Livewire?

Filament provides the admin-oriented resources, forms, tables, dashboard widgets, reports, and notification UI needed by an inventory application. Livewire supports interactive server-rendered workflows without introducing a separate frontend application.

### Why Eloquent relationships?

The inventory domain is relational: products belong to categories, stock movements belong to products and users, and entries can reference suppliers. Eloquent makes these associations explicit and supports eager loading for report tables.

### Why use services?

Stock operations have multiple related responsibilities—authorization, validation, concurrency control, movement history, quantity adjustment, and notification triggering. Keeping this orchestration in a service allows the same business rules to be used independently of a particular form or table.

### How are policies and gates used?

Policies govern access to resource records and actions. Gates express shared abilities such as viewing inventory reports or creating stock operations. They are checked server-side by application services and Filament pages/widgets, and the panel enables strict authorization.

### How are stock quantities protected?

The operation validates positive quantities, locks the product row, checks for insufficient stock on exits and overflow on entries, then records the movement and adjusts stock in a database transaction. Invalid input is rejected before it can create a successful stock change.

### Why use database transactions?

A movement record and its corresponding product quantity update are one business operation. A transaction ensures they are committed or rolled back together; row locking helps coordinate concurrent operations on the same product.

### How are roles managed?

Users have one of three roles: Administrator, Stock Manager, or Employee. Role-aware policies and gates enforce their different capabilities. The first Administrator is provisioned only in a local environment by the explicit bootstrap command; Administrators manage subsequent user accounts and roles.

### How are low-stock alerts generated?

When a stock operation changes a product from above its `min_stock` to at or below that threshold, StockFlow sends a database notification to Administrators and Stock Managers. It is not sent to Employees.

### How is the application tested?

The Pest suite covers application behavior such as catalog access, authorization, stock operations, dashboard and notifications, reports, and demo seeding. Tests use the SQLite in-memory database configured in `phpunit.xml`, so running the suite does not require the presentation database.

## Presentation setup checklist

- Use an isolated development database populated with the opt-in demo seeder.
- Prepare an Administrator, Stock Manager, and Employee account through the application's local user-management workflow.
- Confirm each account can sign in before presenting.
- Verify dashboard, reports, notification, and stock operation flows on the local setup.
- Avoid displaying `.env`, passwords, or other credentials during the presentation.

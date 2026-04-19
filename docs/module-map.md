# Module Map

## Core Runtime Modules
- `app/bootstrap.php`: loads env, starts session, wires core includes
- `app/config.php`: environment-driven config resolution
- `app/db.php`: PDO connection + `dbConnected()`
- `app/auth.php`: login, session helpers, RBAC guard, password/profile updates
- `app/repository.php`: repository include aggregator

## Repository Modules
- `common.php`: shared fallback/date helpers
- `dashboard.php`: KPI metrics, vehicle status, upcoming bookings
- `customers.php`: customer listing/profile/create/update
- `bookings.php`: booking create/update/actions/cancel
- `vehicles.php`: vehicle CRUD/listing/maintenance options
- `payments.php`: invoice retrieval and payment updates
- `support.php`: contact/reset queue operations and admin response actions
- `tracking.php`: simulated live tracking and role-scoped map payloads
- `analytics.php`: reporting aggregates and recommendations

## UI/Pages by Audience
- Public auth/support pages: login/register/forgot-password/contact-admin
- Customer pages: dashboard/bookings/vehicles/tracking/payments
- Staff pages: dashboard/bookings/customers/vehicles/maintenance/tracking/payments/reports
- Admin pages: settings/support inbox

## Shared Frontend
- `public/assets/styles.css`: global styling
- `public/assets/app.js`: insight refresh, modal system, form confirmations, profile panel utilities

## Data Layer Files
- `mobilis_sql.sql`: canonical schema + seed baseline
- `database/migrations/*.sql`: incremental schema transformations

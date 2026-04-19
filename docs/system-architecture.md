# System Architecture

## Architecture Style
- Monolithic, server-rendered PHP web application
- Layered by responsibility:
  - Entry/page controllers (`public/`)
  - Domain/data repositories (`app/repositories/`)
  - Cross-cutting services (`app/auth.php`, `app/db.php`, `app/config.php`)
  - View/layout rendering (`app/view.php`, `app/views/layouts/`, `app/view_helpers.php`)

## High-Level Component Diagram
```text
Browser
  -> PHP Page Controllers (public/*.php, public/Role/*.php)
    -> Auth/session guard (app/auth.php)
    -> Repository functions (app/repositories/*.php)
      -> PDO adapter (app/db.php)
        -> MySQL schema (mobilis_db)
    -> Layout renderer (app/view.php + app/views/layouts/*.php)

Browser JS (public/assets/app.js)
  -> /api/dashboard.php
  -> /api/tracking.php
```

## Request Flow
1. Request enters a PHP file in `public/`.
2. File includes `app/bootstrap.php`.
3. Bootstrap loads environment, starts session, and requires core modules.
4. `requireAuth()` validates user + role where needed.
5. Page/controller calls repository functions.
6. Repositories execute SQL via PDO or fallback data.
7. Page renders with shared layout and static assets.

## Role-Based Routing
- Admin home: `/Admin/settings.php`
- Staff home: `/Staff/dashboard.php`
- Customer home: `/Customer/dashboard.php`

Role gating is centralized in `requireAuth()`.

## API Surface
- `GET /api/dashboard.php`
  - Auth required for admin/staff/customer
  - Returns metrics, vehicles, bookings, maintenance, and insights payload
- `GET /api/tracking.php`
  - Auth required for admin/staff/customer
  - Returns role-aware live tracking snapshot (simulated movement)

## Tracking Subsystem Architecture
Tracking in `app/repositories/tracking.php` includes:
- Route anchor derivation from vehicle coordinates
- Optional route enrichment via OSRM endpoint
- Local cache in temp directory (`mobilis_tracking_cache`)
- Haversine metrics + interpolation
- Role-scoped snapshot generation (`customer` sees active booked vehicles only)

## Reporting/Analytics Architecture
- Reports page (`public/Staff/reports.php`) composes analytics from repository functions in `app/repositories/analytics.php`.
- Aggregations are SQL-driven and returned as chart-ready arrays.
- `public/assets/app.js` also attempts to fetch dashboard insights from API.

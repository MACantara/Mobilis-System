# Technology Stack

## Overview
Mobilis is a monolithic PHP web application with MySQL persistence and browser-side JavaScript enhancements.

## Runtime and Languages
- Backend language: PHP 8.x
- Frontend languages: HTML5, CSS3, JavaScript (vanilla)
- Database: MySQL 8.x (InnoDB)

## Backend Stack
- PHP built-in server for local development (`php -S`)
- Native PHP sessions for authentication state
- PDO for MySQL access with prepared statements
- Function-based repository layer in `app/repositories/`
- Lightweight custom view/layout system in `app/view.php` + `app/views/layouts/`

## Frontend Stack
- Server-rendered PHP pages under `public/`
- Shared stylesheet: `public/assets/styles.css`
- Shared interactive logic: `public/assets/app.js`
- Chart rendering in reports pages via client-side script

## Database Stack
- Single schema SQL bootstrap: `mobilis_sql.sql`
- Additional migration scripts: `database/migrations/`
- Core engine: InnoDB with foreign keys and indexes
- Views used for reporting/support summary:
  - `vw_active_rentals`
  - `vw_monthly_revenue`
  - `vw_support_inbox_summary`

## Environment and Configuration
Configuration is loaded in this order:
1. Process environment variables
2. Optional project `.env` file (loaded by `loadProjectEnv()`)
3. App defaults in `app/config.php`

Primary variables:
- `MOBILIS_DB_HOST`, `MOBILIS_DB_PORT`, `MOBILIS_DB_NAME`, `MOBILIS_DB_USER`, `MOBILIS_DB_PASS`
- Railway-compatible fallbacks: `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`

## Security/Access Model
- Session-based auth in `app/auth.php`
- Roles: `admin`, `staff`, `customer`
- Route gating via `requireAuth([...])`
- Password hashing with bcrypt (`password_hash`, `password_verify`)

## Deployment Surface
- Local: PHP built-in server (`php -S localhost:8000 -t public`)
- Containerized: `Dockerfile`
- Cloud target: Railway

## Notes for Developers
- The codebase is mostly function-oriented (not class-based MVC).
- Many repositories include fallback demo arrays when DB is unavailable.
- Dynamic schema guards exist for some columns (for example payment method and support response columns), enabling forward compatibility when older schemas are present.

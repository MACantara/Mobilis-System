# Technology Stack

## Overview
Mobilis is a web application with PHP backend, MySQL persistence, and standalone Python scripts for data export capabilities.

## Runtime and Languages
- Backend language (primary): PHP 8.x
- Backend language (exports): Python 3.11+
- Frontend languages: HTML5, CSS3, JavaScript (vanilla)
- Database: MySQL 8.x (InnoDB)

## Backend Stack (PHP)
- PHP built-in server for local development (`php -S`)
- Native PHP sessions for authentication state
- PDO for MySQL access with prepared statements
- Function-based repository layer in `app/repositories/`
- Lightweight custom view/layout system in `app/view.php` + `app/views/layouts/`

## Backend Stack (Python)
- Standalone scripts for data export in `python-scripts/`
- pandas for data processing
- openpyxl for Excel (.xlsx) export generation
- reportlab for PDF export generation
- Direct database connection via pymysql

## Frontend Stack
- Server-rendered PHP pages under `public/`
- Shared stylesheet: `public/assets/styles.css`
- Shared interactive logic: `public/assets/app.js`
- Chart rendering in reports pages via client-side script (Chart.js)
- Python scripts for multi-format data export (CSV, Excel, PDF)

## Database Stack
- Single schema SQL bootstrap: `mobilis_sql.sql`
- Additional migration scripts: `database/migrations/`
- Core engine: InnoDB with foreign keys and indexes
- Views used for reporting/support summary:
  - `vw_active_rentals`
  - `vw_monthly_revenue`
  - `vw_support_inbox_summary`

## Environment and Configuration

### PHP Configuration
Configuration is loaded in this order:
1. Process environment variables
2. Optional project `.env` file (loaded by `loadProjectEnv()`)
3. App defaults in `app/config.php`

Primary variables:
- `MOBILIS_DB_HOST`, `MOBILIS_DB_PORT`, `MOBILIS_DB_NAME`, `MOBILIS_DB_USER`, `MOBILIS_DB_PASS`
- Railway-compatible fallbacks: `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`

### Python Scripts Configuration
Configuration is loaded from `python-scripts/config.py`:
- Database connection settings (host, port, name, user, password)

## Security/Access Model
- Session-based auth in `app/auth.php`
- Roles: `admin`, `staff`, `customer`
- Route gating via `requireAuth([...])`
- Password hashing with bcrypt (`password_hash`, `password_verify`)

## Deployment Surface
- Local (PHP): PHP built-in server (`php -S localhost:8000 -t public`)
- Containerized: `Dockerfile` (PHP)
- Cloud target: Railway (PHP service)

## Notes for Developers
- The codebase is mostly function-oriented (not class-based MVC).
- Many repositories include fallback demo arrays when DB is unavailable.
- Dynamic schema guards exist for some columns (for example payment method and support response columns), enabling forward compatibility when older schemas are present.
- Python export scripts in `python-scripts/` are called by PHP export files to generate CSV, Excel, and PDF files.
- Python scripts connect directly to the database using configuration from `python-scripts/config.py`.

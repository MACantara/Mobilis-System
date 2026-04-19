# Mobilis Prototype (PHP + MySQL + Python)

This is a functional prototype for the Mobilis vehicle rental and fleet management system using the requested stack:

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Advanced processing: Python (analytics and maintenance insights)

## Features Included

- Session-based login with role simulation (Admin, Staff, Customer)
- Dashboard with key metrics, vehicle status, and upcoming bookings
- Vehicles module with card-based inventory view
- Bookings module with status chips and totals
- Customers module with spending and booking summaries
- Reports module with Python-generated analytics output
- Contact Admin form with database persistence
- Forgot Password request form with database persistence
- Admin Support Inbox page to review stored support requests
- Fallback demo data when MySQL is not yet connected

## Project Structure

- `app/` PHP backend logic (auth, DB, repository, layout, Python bridge)
- `public/` web root and pages
- `public/api/` JSON endpoint used by dashboard and reports
- `public/forgot-password.php` password reset request form (writes to DB)
- `public/contact-admin.php` admin contact form (writes to DB)
- `public/support-requests.php` admin/staff inbox for submitted requests
- `python/` analytics processor scripts
- `mobilis_sql.sql` database schema and seed data

## Quick Start

1. Create and seed the database:

```bash
mysql -u root -p < mobilis_sql.sql
```

The seed script is idempotent: rerunning it will skip existing seed rows instead of failing on duplicate keys.

2. Export environment variables (or set in your shell profile):

```bash
export MOBILIS_DB_HOST=127.0.0.1
export MOBILIS_DB_PORT=3306
export MOBILIS_DB_NAME=mobilis_db
export MOBILIS_DB_USER=root
export MOBILIS_DB_PASS=your_password
export MOBILIS_PYTHON_BIN=python3
```

3. Run the PHP development server from the repository root:

```bash
php -S localhost:8000 -t public
```

4. Open the app:

- http://localhost:8000

Demo credentials:

- `admin@mobilis.ph / admin123`
- `staff@mobilis.ph / staff123`
- `customer@mobilis.ph / customer123`

## How Python Is Integrated

- Endpoint `public/api/dashboard.php` pulls metrics and records via PHP.
- PHP calls `python/analyze.py` through `proc_open` and sends JSON payload.
- Python computes insights such as utilization interpretation, demand trends, and maintenance alerts.
- The dashboard/reports pages display the Python response directly.

## Notes

- This is a prototype intended for coursework and iterative expansion.
- For production, replace demo-auth with database-backed users and hashed passwords.
- Add input validation, CSRF protection, and stricter RBAC before deployment.

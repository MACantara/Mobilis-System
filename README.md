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

## User Accounts & Authentication

The system uses a role-based access control (RBAC) with three user types: Admin, Staff, and Customer. All user accounts are stored in the `User` table with password hashing using bcrypt.

### User Roles and Permissions

**Admin Role:**
- Full system access and configuration
- Can manage all vehicles, bookings, and customers
- Can view and manage support requests
- Can access all system settings
- Can manage staff accounts

**Staff Role:**
- Can view and manage vehicles
- Can create and manage bookings
- Can view customer information
- Can access reports and analytics
- Can process support requests

**Customer Role:**
- Can only view their own bookings and tracked vehicles
- Can track vehicles they have active/confirmed bookings for
- Can view personal booking history and spending summaries
- Can update personal profile information
- Can submit support requests

### Demo Accounts

The following demo accounts are pre-configured in the database:

| Role | Email | Password | Description |
|------|-------|----------|-------------|
| Admin | admin@mobilis.ph | password | Full system administrator access |
| Staff | staff@mobilis.ph | password | Staff member with operational access |
| Customer | customer@mobilis.ph | password | Sample customer account |

**Note:** All demo accounts use the default password "password" for demonstration purposes. In production, passwords should be changed and proper security measures implemented.

### Sample Customer Accounts

The database includes sample customer accounts with booking history:

- Maria Reyes (maria@email.com)
- Juan dela Cruz (jdc@email.com)
- Ana Lim (ana.lim@email.com)
- Ramon Santos (ramon.s@email.com)
- Pedro Cruz (pedz@email.com)
- Lisa Garcia (lisag@email.com)
- Bea Torres (bea.t@email.com)

All sample customer accounts use the default password "password".

### User Data Structure

User data is stored in the `User` table with the following key fields:
- `user_id`: Unique identifier
- `first_name`: First name
- `last_name`: Last name
- `email`: Email address (used for login, must be unique)
- `phone`: Contact phone number
- `license_number`: Driver's license number (nullable for admin/staff)
- `license_expiry`: License expiration date (nullable for admin/staff)
- `address`: Physical address
- `role`: User role (admin, staff, customer)
- `password_hash`: Bcrypt hash of user password
- `created_at`: Account creation timestamp

## Customers Module

The Customers module provides comprehensive customer management capabilities for staff and admin users:

### Customer Information Tracked
- **Personal Details**: Full name, email address, phone number
- **Account Status**: Active/inactive status, registration date
- **Booking History**: Total bookings, active bookings, completed bookings
- **Financial Summary**: Total spending, average spending per booking
- **Contact Information**: Address, city, zip code

### Customer Features
- **Customer Dashboard**: View personal booking history and spending summaries
- **Live Tracking**: Track rented vehicles in real-time with GPS coordinates
- **Booking Management**: View current and past bookings with status updates
- **Profile Management**: Update personal information and contact details

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

1. Create and seed the database.

Linux:

```bash
sudo mysql -e "CREATE DATABASE IF NOT EXISTS mobilis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'mobilis_app'@'127.0.0.1' IDENTIFIED BY 'mobilis_app_pass';"
sudo mysql -e "GRANT ALL PRIVILEGES ON mobilis_db.* TO 'mobilis_app'@'127.0.0.1'; FLUSH PRIVILEGES;"
mysql -h 127.0.0.1 -P 3306 -u mobilis_app -p mobilis_db < mobilis_sql.sql
```

Windows (Command Prompt):

```cmd
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mobilis_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE USER IF NOT EXISTS 'mobilis_app'@'127.0.0.1' IDENTIFIED BY 'mobilis_app_pass';"
mysql -u root -p -e "GRANT ALL PRIVILEGES ON mobilis_db.* TO 'mobilis_app'@'127.0.0.1'; FLUSH PRIVILEGES;"
mysql -h 127.0.0.1 -P 3306 -u mobilis_app -p mobilis_db < mobilis_sql.sql
```

The seed script now performs a full reseed: each run drops the app tables/views and recreates them with fresh demo data.
Rerunning it is safe, but any existing records in those tables will be replaced.
Booking and invoice timestamps are generated relative to the current date, so dashboard "upcoming" and "today" figures stay relevant over time.
On many Linux installs, `root` uses socket authentication and cannot be used by PHP over TCP without sudo.
On Windows, run the commands in Command Prompt as Administrator if your MySQL setup requires elevated permissions.

2. Export environment variables (or set in your shell profile).

Linux:

```bash
export MOBILIS_DB_HOST=127.0.0.1
export MOBILIS_DB_PORT=3306
export MOBILIS_DB_NAME=mobilis_db
export MOBILIS_DB_USER=mobilis_app
export MOBILIS_DB_PASS=mobilis_app_pass
export MOBILIS_PYTHON_BIN=python3
```

Windows PowerShell:

```powershell
$env:MOBILIS_DB_HOST="127.0.0.1"
$env:MOBILIS_DB_PORT="3306"
$env:MOBILIS_DB_NAME="mobilis_db"
$env:MOBILIS_DB_USER="mobilis_app"
$env:MOBILIS_DB_PASS="mobilis_app_pass"
$env:MOBILIS_PYTHON_BIN="python"
```

3. Run the PHP development server from the repository root:

```bash
php -S localhost:8000 -t public
```

4. Open the app:

- http://localhost:8000

Demo credentials:

- `admin@mobilis.ph / password`
- `staff@mobilis.ph / password`
- `customer@mobilis.ph / password`

Note: All customer accounts use the same default password "password" for demo purposes.

## How Python Is Integrated

- Endpoint `public/api/dashboard.php` pulls metrics and records via PHP.
- PHP calls `python/analyze.py` through `proc_open` and sends JSON payload.
- Python computes insights such as utilization interpretation, demand trends, and maintenance alerts.
- The dashboard/reports pages display the Python response directly.

## Deploy To Railway

This repository is ready for Railway using the included Docker runtime.

1. Push this codebase to GitHub.
2. In Railway, create a new project and deploy from that GitHub repository.
3. Add a MySQL service in the same Railway project.
4. Set environment variables in the app service: use Railway MySQL defaults (`MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`) which are auto-detected; optional override is `MOBILIS_DB_HOST`, `MOBILIS_DB_PORT`, `MOBILIS_DB_NAME`, `MOBILIS_DB_USER`, `MOBILIS_DB_PASS`; keep `MOBILIS_PYTHON_BIN=python3`.

5. Open the MySQL service shell (or connect from your local machine using Railway connection info) and import schema + seed data:

```bash
mysql -h <host> -P <port> -u <user> -p <database> < mobilis_sql.sql
```

6. Redeploy (or trigger a new deployment) and open the generated Railway URL.

The app starts with:

```bash
php -S 0.0.0.0:$PORT -t public
```

## Notes

- This is a prototype intended for coursework and iterative expansion.
- For production, replace demo-auth with database-backed users and hashed passwords.
- Add input validation, CSRF protection, and stricter RBAC before deployment.

# Proof of Connectivity: PHP and MySQL

## Connection Implementation
The application uses PDO in `app/db.php`:
- DSN format: `mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4`
- Error mode: exceptions
- Fetch mode: associative arrays
- Emulated prepares: disabled

Config resolution occurs in `app/config.php` and supports both explicit MOBILIS env vars and Railway MySQL env vars.

## Runtime Proof Command
Executed from project root:
```bash
php -r 'require "app/bootstrap.php"; echo "dbConnected=".(dbConnected()?"yes":"no").PHP_EOL; if (dbConnected()) { $pdo = db(); echo "users=".$pdo->query("SELECT COUNT(*) FROM User")->fetchColumn().PHP_EOL; echo "vehicles=".$pdo->query("SELECT COUNT(*) FROM Vehicle")->fetchColumn().PHP_EOL; echo "rentals=".$pdo->query("SELECT COUNT(*) FROM Rental")->fetchColumn().PHP_EOL; }'
```

Observed output:
```text
dbConnected=yes
users=11
vehicles=48
rentals=106
```

This confirms:
- PHP can instantiate PDO with current configuration.
- Queries execute successfully against MySQL.
- Seeded data is reachable from application runtime.

## Request-Level Proof Points
The following routes depend on successful DB connectivity and were exercised in local runs:
- `POST /register.php` -> inserts into `User`
- `POST /forgot-password.php` -> inserts into `PasswordResetRequest`
- `POST /contact-admin.php` -> inserts into `AdminContactMessage`
- `POST /Customer/booking-create.php` -> transaction across `Rental`, `Invoice`, `Vehicle`
- `POST /Customer/payments.php` -> updates `Invoice.payment_status` and `Invoice.payment_method`

## Connectivity Failure Behavior
If DB is unavailable:
- Many repositories return fallback demo arrays.
- Write flows (register/support/reset/booking/payment) return user-facing errors.

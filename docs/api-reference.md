# API and Endpoint Reference

## PHP JSON APIs

### GET /api/dashboard.php
- Auth: required (`admin|staff|customer`)
- Response:
  - `ok`: boolean
  - `source`: `mysql` or `fallback`
  - `payload`:
    - `generated_at`
    - `metrics` (fleet, rentals, revenue, utilization)
    - `vehicles` list
    - `bookings` list
    - `maintenance` list
  - `insights`: analytics insights output

### GET /api/tracking.php
- Auth: required (`admin|staff|customer`)
- Response:
  - `ok`, `source`, `role`
  - `generated_at`, `step_seconds`
  - `center` (`lat`, `lng`)
  - `vehicles[]`: `vehicle_id`, `name`, `plate`, `status`, `lat`, `lng`, `updated_at`

## Python Export Scripts

The system uses standalone Python scripts for data export functionality. These scripts are called directly by PHP export files using `exec()` and are not HTTP endpoints.

See [python-integration.md](python-integration.md) for details on the export scripts.

## Public Auth/Support Pages
- `GET/POST /login.php`
- `GET/POST /register.php`
- `GET/POST /forgot-password.php`
- `GET/POST /contact-admin.php`
- `GET /logout.php`

## Admin Pages
- `GET/POST /Admin/support-requests.php`
  - Handles contact responses (`respond_contact`)
  - Handles password reset completion/rejection (`reset_password`, `reject_request`)
- `GET /Admin/settings.php`

## Staff Pages
- Fleet operations: vehicles, maintenance, tracking
- Customer operations: customers, bookings, payments
- Reporting: `/Staff/reports.php`

## Customer Pages
- `/Customer/dashboard.php`
- `/Customer/bookings.php`, `/Customer/booking-create.php`, `/Customer/booking-view.php`
- `/Customer/payments.php`
- `/Customer/vehicles.php`
- `/Customer/tracking.php`

## Common Response/Status Patterns
- Write actions often return redirect + notice.
- Repository write functions return structures like:
  - `{ ok: true, ... }`
  - `{ ok: false, error: "..." }`

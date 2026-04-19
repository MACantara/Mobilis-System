# Database Design and Schema

## Schema Strategy
- Database name: `mobilis_db`
- Charset/collation: `utf8mb4` / `utf8mb4_unicode_ci`
- Engine: InnoDB
- Seed approach: full reseed on each run of `mobilis_sql.sql`

## Initialization Behavior
`mobilis_sql.sql` does the following in order:
1. Creates schema if missing
2. Disables FK checks temporarily
3. Drops views and app tables
4. Recreates all tables with constraints
5. Inserts baseline and trend-oriented seed data
6. Recreates analytical/support views

This makes environments reproducible for coursework/demo use.

## Core Tables
- `VehicleCategory`
- `Vehicle`
- `User`
- `Rental`
- `MaintenanceLog`
- `Invoice`
- `AdminContactMessage`
- `PasswordResetRequest`

## Design Highlights
- Strong referential links for fleet and booking paths (`VehicleCategory -> Vehicle -> Rental -> Invoice`).
- Role-based user model unified in single `User` table (`admin|staff|customer`).
- Support workflow modeled as two independent queues:
  - Contact admin queue
  - Password reset queue
- GPS fields (`latitude`, `longitude`) embedded in `Vehicle` for live tracking simulations.

## Indexing and Uniqueness
- Uniqueness:
  - `VehicleCategory.category_name`
  - `Vehicle.plate_number`
  - `User.email`
  - `User.license_number` (nullable, unique when present)
  - `Invoice.rental_id` (enforces 1-to-1 rental-invoice)
- Operational indexes:
  - Support status/date indexes in contact and reset tables

## Enumerated Domains
- `Vehicle.status`: `available`, `rented`, `maintenance`
- `User.role`: `admin`, `staff`, `customer`
- `Rental.status`: `pending`, `active`, `completed`, `cancelled`
- `Invoice.payment_status`: `unpaid`, `paid`, `partial`
- `Invoice.payment_method`: `pending`, `cash`, `gcash`, `card`, `bank_transfer`
- `AdminContactMessage.status`: `new`, `read`, `resolved`
- `PasswordResetRequest.status`: `pending`, `processing`, `completed`, `rejected`

## Migration Files
- `database/migrations/2024_04_19_convert_customer_to_user.sql`
- `database/migrations/2024_04_19_add_vehicle_gps.sql`

These are idempotent migration scripts for incremental evolution scenarios; the primary bootstrap remains `mobilis_sql.sql`.

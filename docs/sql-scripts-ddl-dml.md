# SQL Scripts (DDL/DML)

## Primary Script
- File: `mobilis_sql.sql`
- Purpose: end-to-end bootstrap (drop/recreate + seed + views)

## DDL Coverage
`mobilis_sql.sql` includes:
- `CREATE DATABASE` and `USE`
- `DROP VIEW` / `DROP TABLE` reset section
- `CREATE TABLE` definitions for all app entities
- PK, unique keys, FK constraints, and indexes
- `CREATE OR REPLACE VIEW` for reporting/support

## DML Coverage
`mobilis_sql.sql` includes:
- Seed inserts for categories, vehicles, users
- Relative-date rental and invoice seeds (`CURDATE()`, date arithmetic)
- Supplemental inserts for trend density
- Status/method update statements
- Maintenance and support sample data

## Script Execution
Linux example:
```bash
mysql -h 127.0.0.1 -P 3306 -u mobilis_app -p mobilis_db < mobilis_sql.sql
```

## Included Migration Scripts
- `database/migrations/2024_04_19_convert_customer_to_user.sql`
- `database/migrations/2024_04_19_add_vehicle_gps.sql`

These scripts are useful for incremental upgrades on older databases and include idempotency guards via `INFORMATION_SCHEMA` checks.

## DDL Object Inventory
- Tables: 8
- Views: 3
- Foreign keys: 6 enforced FK constraints
- Enumerated domains: 7 enum columns

## Important Operational Note
The bootstrap script is destructive for app tables/views by design (drops and recreates). Use with care in non-demo environments.

## Quick Validation Queries
```sql
SHOW TABLES;
SHOW FULL TABLES WHERE Table_type = 'VIEW';
SELECT COUNT(*) AS users FROM User;
SELECT COUNT(*) AS vehicles FROM Vehicle;
SELECT COUNT(*) AS rentals FROM Rental;
SELECT COUNT(*) AS invoices FROM Invoice;
```

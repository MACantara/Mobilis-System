# Database Cardinality Rules

## Cardinality Matrix
| Parent Entity | Child Entity | Cardinality | Enforced By |
|---|---|---|---|
| VehicleCategory | Vehicle | 1:N | `Vehicle.category_id` FK |
| User | Rental | 1:N | `Rental.user_id` FK |
| Vehicle | Rental | 1:N | `Rental.vehicle_id` FK |
| Rental | Invoice | 1:1 | `Invoice.rental_id` FK + unique key |
| Vehicle | MaintenanceLog | 1:N | `MaintenanceLog.vehicle_id` FK |
| User | PasswordResetRequest | 0..1 to N | `PasswordResetRequest.user_id` nullable FK |

## Optional/Standalone Structures
- `AdminContactMessage` has no required parent entity (standalone queue table).
- `AdminContactMessage.responded_by` is nullable and currently unconstrained by FK.

## Deletion/Update Rules (FK Actions)
- `VehicleCategory -> Vehicle`: `ON UPDATE CASCADE`, `ON DELETE RESTRICT`
- `User -> Rental`: `ON UPDATE CASCADE`, `ON DELETE RESTRICT`
- `Vehicle -> Rental`: `ON UPDATE CASCADE`, `ON DELETE RESTRICT`
- `Rental -> Invoice`: `ON UPDATE CASCADE`, `ON DELETE RESTRICT`
- `Vehicle -> MaintenanceLog`: `ON UPDATE CASCADE`, `ON DELETE RESTRICT`
- `User -> PasswordResetRequest`: `ON UPDATE CASCADE`, `ON DELETE SET NULL`

## Application-Level Cardinality Enforcement
Beyond FK constraints, the PHP layer enforces:
- Booking creation only when target vehicle status is `available`.
- One invoice created per booking transaction.
- Customer tracking view limited to vehicles from customer active/confirmed bookings.

## Practical Implications
- Historical rentals/invoices are preserved by restrictive deletes.
- A password reset request remains auditable even if a user record is removed (`SET NULL`).
- Contact messages can exist independent of authenticated users, supporting pre-login support flows.

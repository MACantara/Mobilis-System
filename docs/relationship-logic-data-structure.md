# Relationship Logic and Data Structure

## Business-Critical Relationship Chains

## 1) Fleet and Catalog Chain
`VehicleCategory (1) -> (N) Vehicle`
- Every vehicle must belong to one category.
- Category-level pricing (`daily_rate`) is reused by booking/invoice logic.

## 2) Customer Rental Chain
`User (customer) (1) -> (N) Rental`
- A customer can create multiple rentals.
- Rental status drives both operational state and UI chips.

## 3) Vehicle Utilization Chain
`Vehicle (1) -> (N) Rental`
- A vehicle can appear in many rentals over time.
- Runtime rules in booking repository prevent booking vehicles that are not `available`.

## 4) Billing Chain
`Rental (1) -> (1) Invoice`
- Enforced by unique key on `Invoice.rental_id`.
- Payment updates are invoice-scoped and role-scoped in customer flows.

## 5) Maintenance Chain
`Vehicle (1) -> (N) MaintenanceLog`
- Captures service history and supports maintenance alert reporting.

## 6) Support and Recovery Chains
- `AdminContactMessage`: standalone ticket queue from public contact form.
- `User (0..1) -> (N) PasswordResetRequest`: optional user link for account recovery requests.

## Runtime Data Structure Patterns in PHP
- Repository functions return plain associative arrays.
- Fallback behavior returns static arrays when DB is unavailable.
- Page controllers (`public/...`) compose repository outputs, then render through layout helpers.

## Derived/Computed Structures
- Dashboard metrics: counts, daily revenue, utilization percentage.
- Tracking snapshot: time-step simulation over route metrics, with role-aware visibility.
- Reports aggregates: revenue series, booking trends, payment/booking breakdowns, customer and vehicle rankings.

## Integrity and Consistency Rules in Code
- Booking creation uses DB transaction:
  - lock vehicle row
  - create rental + invoice
  - update vehicle status
- Booking updates maintain vehicle status consistency.
- Customer cancellation moves rented vehicle back to available when appropriate.
- Support responses enforce non-empty admin response text and valid status transitions (`read`/`resolved`).

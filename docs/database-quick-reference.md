# Database Quick Reference

## Table Purposes at a Glance
- `VehicleCategory`: fleet class and pricing metadata
- `Vehicle`: individual fleet units and live coordinates
- `User`: all account identities and roles
- `Rental`: booking records and lifecycle status
- `Invoice`: billing record tied 1:1 with rental
- `MaintenanceLog`: service history
- `AdminContactMessage`: contact/support queue
- `PasswordResetRequest`: account recovery queue

## Common Join Paths
- User bookings:
```sql
SELECT u.email, r.rental_id, v.plate_number
FROM User u
JOIN Rental r ON r.user_id = u.user_id
JOIN Vehicle v ON v.vehicle_id = r.vehicle_id;
```

- Revenue by vehicle category:
```sql
SELECT vc.category_name, SUM(i.total_amount) AS revenue
FROM Invoice i
JOIN Rental r ON r.rental_id = i.rental_id
JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
JOIN VehicleCategory vc ON vc.category_id = v.category_id
GROUP BY vc.category_name;
```

- Open support workload:
```sql
SELECT * FROM vw_support_inbox_summary;
```

## Status Value Cheatsheet
- Rental: `pending | active | completed | cancelled`
- Vehicle: `available | rented | maintenance`
- Invoice payment: `unpaid | paid | partial`
- Contact ticket: `new | read | resolved`
- Reset request: `pending | processing | completed | rejected`

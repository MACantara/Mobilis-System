# Data Dictionary

## VehicleCategory
| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| category_id | INT UNSIGNED | No | PK | AUTO_INCREMENT | Category identifier |
| category_name | VARCHAR(50) | No | UK | - | Category label (Sedan, SUV, etc.) |
| daily_rate | DECIMAL(8,2) | No | - | 0.00 | Base daily rental rate |
| description | TEXT | Yes | - | NULL | Category notes |

## Vehicle
| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| vehicle_id | INT UNSIGNED | No | PK | AUTO_INCREMENT | Vehicle identifier |
| category_id | INT UNSIGNED | No | FK | - | References `VehicleCategory.category_id` |
| plate_number | VARCHAR(20) | No | UK | - | Plate number |
| brand | VARCHAR(50) | No | - | - | Manufacturer |
| model | VARCHAR(50) | No | - | - | Model |
| year | YEAR | No | - | - | Model year |
| color | VARCHAR(30) | No | - | - | Vehicle color |
| mileage_km | INT UNSIGNED | No | - | 0 | Current odometer |
| latitude | DECIMAL(10,8) | Yes | - | NULL | GPS latitude |
| longitude | DECIMAL(11,8) | Yes | - | NULL | GPS longitude |
| status | ENUM('available','rented','maintenance') | No | - | available | Operational status |

## User
| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| user_id | INT UNSIGNED | No | PK | AUTO_INCREMENT | User identifier |
| first_name | VARCHAR(60) | No | - | - | First name |
| last_name | VARCHAR(60) | No | - | - | Last name |
| email | VARCHAR(100) | No | UK | - | Login/email identity |
| phone | VARCHAR(20) | No | - | - | Contact number |
| license_number | VARCHAR(30) | Yes | UK | NULL | Driver license (mainly customer) |
| license_expiry | DATE | Yes | - | NULL | License expiry date |
| address | TEXT | Yes | - | NULL | Address |
| role | ENUM('admin','staff','customer') | No | - | customer | RBAC role |
| password_hash | VARCHAR(255) | No | - | - | Bcrypt hash |
| created_at | TIMESTAMP | Yes | - | CURRENT_TIMESTAMP | Record creation time |

## Rental
| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| rental_id | INT UNSIGNED | No | PK | AUTO_INCREMENT | Booking identifier |
| user_id | INT UNSIGNED | No | FK | - | References `User.user_id` |
| vehicle_id | INT UNSIGNED | No | FK | - | References `Vehicle.vehicle_id` |
| pickup_date | DATE | No | - | - | Start date |
| return_date | DATE | No | - | - | Planned return date |
| actual_return | DATE | Yes | - | NULL | Actual completion date |
| status | ENUM('pending','active','completed','cancelled') | No | - | active | Booking state |
| notes | TEXT | Yes | - | NULL | Notes/context |
| created_at | TIMESTAMP | Yes | - | CURRENT_TIMESTAMP | Created timestamp |

## MaintenanceLog
| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| log_id | INT UNSIGNED | No | PK | AUTO_INCREMENT | Maintenance record ID |
| vehicle_id | INT UNSIGNED | No | FK | - | References `Vehicle.vehicle_id` |
| service_date | DATE | No | - | - | Date serviced |
| service_type | VARCHAR(100) | No | - | - | Service category |
| cost | DECIMAL(10,2) | No | - | 0.00 | Service cost |
| performed_by | VARCHAR(100) | Yes | - | NULL | Provider/technician |
| odometer_km | INT UNSIGNED | No | - | - | Odometer at service |
| remarks | TEXT | Yes | - | NULL | Additional notes |

## Invoice
| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| invoice_id | INT UNSIGNED | No | PK | AUTO_INCREMENT | Invoice identifier |
| rental_id | INT UNSIGNED | No | FK + UK | - | References `Rental.rental_id`; unique |
| base_amount | DECIMAL(10,2) | No | - | - | Base computed charge |
| late_fee | DECIMAL(10,2) | No | - | 0.00 | Late fee |
| damage_fee | DECIMAL(10,2) | No | - | 0.00 | Damage fee |
| total_amount | DECIMAL(10,2) | No | - | - | Total invoice amount |
| payment_status | ENUM('unpaid','paid','partial') | No | - | unpaid | Payment state |
| payment_method | ENUM('pending','cash','gcash','card','bank_transfer') | No | - | pending | Method used |
| issued_at | TIMESTAMP | Yes | - | CURRENT_TIMESTAMP | Invoice issue time |

## AdminContactMessage
| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| message_id | INT UNSIGNED | No | PK | AUTO_INCREMENT | Contact ticket ID |
| full_name | VARCHAR(120) | No | - | - | Sender full name |
| email | VARCHAR(120) | No | - | - | Sender email |
| phone | VARCHAR(30) | Yes | - | NULL | Sender phone |
| subject | VARCHAR(180) | No | - | - | Subject line |
| message | TEXT | No | - | - | Message body |
| admin_response | TEXT | Yes | - | NULL | Admin reply |
| status | ENUM('new','read','resolved') | No | IDX | new | Ticket status |
| created_at | TIMESTAMP | Yes | IDX | CURRENT_TIMESTAMP | Created timestamp |
| responded_at | TIMESTAMP | Yes | IDX | NULL | Response timestamp |
| responded_by | INT UNSIGNED | Yes | - | NULL | Admin user id (logical reference) |

## PasswordResetRequest
| Column | Type | Null | Key | Default | Description |
|---|---|---|---|---|---|
| request_id | INT UNSIGNED | No | PK | AUTO_INCREMENT | Reset request ID |
| user_id | INT UNSIGNED | Yes | FK | NULL | Optional `User.user_id` link |
| email | VARCHAR(120) | No | - | - | Request email |
| license_number | VARCHAR(30) | Yes | - | NULL | Optional license verifier |
| reason | VARCHAR(500) | No | - | - | Request reason |
| status | ENUM('pending','processing','completed','rejected') | No | IDX | pending | Workflow status |
| requested_ip | VARCHAR(45) | Yes | - | NULL | Source IP |
| user_agent | VARCHAR(255) | Yes | - | NULL | Request agent |
| created_at | TIMESTAMP | Yes | IDX | CURRENT_TIMESTAMP | Request timestamp |
| handled_at | TIMESTAMP | Yes | - | NULL | Handling timestamp |

## Database Views
### vw_active_rentals
Active rental records with joined customer and vehicle labels.

### vw_monthly_revenue
Paid-invoice aggregate by pickup year/month.

### vw_support_inbox_summary
Unioned queue summary (`contact_messages` and `password_reset_requests`) grouped by status.

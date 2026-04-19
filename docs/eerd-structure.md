# Entity-Relationship Diagram (EERD) Structure

## Conceptual ER Scope
The system centers on users renting vehicles, with billing, maintenance, and support workflows.

## Mermaid EERD
```mermaid
erDiagram
    VEHICLECATEGORY ||--o{ VEHICLE : categorizes
    USER ||--o{ RENTAL : books
    VEHICLE ||--o{ RENTAL : assigned_to
    RENTAL ||--|| INVOICE : billed_by
    VEHICLE ||--o{ MAINTENANCELOG : has
    USER o|--o{ PASSWORDRESETREQUEST : requests

    VEHICLECATEGORY {
      int category_id PK
      varchar category_name UK
      decimal daily_rate
      text description
    }

    VEHICLE {
      int vehicle_id PK
      int category_id FK
      varchar plate_number UK
      varchar brand
      varchar model
      year year
      varchar color
      int mileage_km
      decimal latitude
      decimal longitude
      enum status
    }

    USER {
      int user_id PK
      varchar first_name
      varchar last_name
      varchar email UK
      varchar phone
      varchar license_number UK nullable
      date license_expiry nullable
      text address
      enum role
      varchar password_hash
      timestamp created_at
    }

    RENTAL {
      int rental_id PK
      int user_id FK
      int vehicle_id FK
      date pickup_date
      date return_date
      date actual_return nullable
      enum status
      text notes
      timestamp created_at
    }

    INVOICE {
      int invoice_id PK
      int rental_id FK UK
      decimal base_amount
      decimal late_fee
      decimal damage_fee
      decimal total_amount
      enum payment_status
      enum payment_method
      timestamp issued_at
    }

    MAINTENANCELOG {
      int log_id PK
      int vehicle_id FK
      date service_date
      varchar service_type
      decimal cost
      varchar performed_by nullable
      int odometer_km
      text remarks nullable
    }

    ADMINCONTACTMESSAGE {
      int message_id PK
      varchar full_name
      varchar email
      varchar phone nullable
      varchar subject
      text message
      text admin_response nullable
      enum status
      timestamp created_at
      timestamp responded_at nullable
      int responded_by nullable
    }

    PASSWORDRESETREQUEST {
      int request_id PK
      int user_id FK nullable
      varchar email
      varchar license_number nullable
      varchar reason
      enum status
      varchar requested_ip nullable
      varchar user_agent nullable
      timestamp created_at
      timestamp handled_at nullable
    }
```

## Support Workflow Entity Notes
- `AdminContactMessage.responded_by` is currently modeled as an integer reference to admin user ID but has no enforced foreign key constraint.
- `PasswordResetRequest.user_id` is nullable to support requests from users whose account link cannot be resolved at submission time.

## Reporting Views
- `vw_active_rentals`: active rental join across user and vehicle.
- `vw_monthly_revenue`: paid invoice aggregation by pickup month/year.
- `vw_support_inbox_summary`: status counts for admin contacts and reset queues.

-- ============================================================
-- Mobilis — Vehicle Rental & Fleet Management System
-- BSCSIT 2207L Database System 1 9312-AY2245 Final Project 
-- ============================================================

-- Step 1: Create and select the database
CREATE DATABASE IF NOT EXISTS mobilis_db
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mobilis_db;

-- ── 1. VehicleCategory ──────────────────────────────────────
CREATE TABLE IF NOT EXISTS VehicleCategory (
  category_id   INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  category_name VARCHAR(50)     NOT NULL,
  daily_rate    DECIMAL(8,2)    NOT NULL DEFAULT 0.00,
  description   TEXT,
  PRIMARY KEY (category_id),
  UNIQUE KEY uq_category_name (category_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO VehicleCategory (category_name, daily_rate, description)
SELECT seed.category_name, seed.daily_rate, seed.description
FROM (
  SELECT 'Sedan' AS category_name, 1500.00 AS daily_rate, 'Standard 4-door passenger car' AS description
  UNION ALL SELECT 'SUV', 2500.00, 'Sport Utility Vehicle, 7-seater'
  UNION ALL SELECT 'Van', 3000.00, 'Passenger or cargo van'
  UNION ALL SELECT 'Motorcycle', 600.00, 'Motorbikes and scooters'
  UNION ALL SELECT 'Pickup Truck', 2000.00, '4x4 and utility pickup trucks'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM VehicleCategory vc
  WHERE vc.category_name = seed.category_name
);

-- ── 2. Vehicle ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Vehicle (
  vehicle_id   INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  category_id  INT UNSIGNED  NOT NULL,
  plate_number VARCHAR(20)   NOT NULL,
  brand        VARCHAR(50)   NOT NULL,
  model        VARCHAR(50)   NOT NULL,
  year         YEAR          NOT NULL,
  color        VARCHAR(30)   NOT NULL,
  mileage_km   INT UNSIGNED  NOT NULL DEFAULT 0,
  status       ENUM('available','rented','maintenance') NOT NULL DEFAULT 'available',
  PRIMARY KEY (vehicle_id),
  UNIQUE KEY uq_plate (plate_number),
  CONSTRAINT fk_veh_cat FOREIGN KEY (category_id)
    REFERENCES VehicleCategory(category_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Vehicle (category_id, plate_number, brand, model, year, color, mileage_km, status)
SELECT seed.category_id, seed.plate_number, seed.brand, seed.model, seed.year, seed.color, seed.mileage_km, seed.status
FROM (
  SELECT 1 AS category_id, 'ABC 1234' AS plate_number, 'Toyota' AS brand, 'Vios' AS model, 2022 AS year, 'White' AS color, 45000 AS mileage_km, 'available' AS status
  UNION ALL SELECT 1, 'DEF 5678', 'Honda', 'City', 2023, 'Silver', 12000, 'rented'
  UNION ALL SELECT 2, 'GHI 9012', 'Ford', 'Everest', 2021, 'Black', 78000, 'available'
  UNION ALL SELECT 2, 'JKL 3456', 'Mitsubishi', 'Montero', 2020, 'Gray', 95000, 'maintenance'
  UNION ALL SELECT 3, 'MNO 7890', 'Toyota', 'HiAce', 2019, 'White', 130000, 'available'
  UNION ALL SELECT 4, 'PQR 1122', 'Honda', 'Click 125', 2023, 'Red', 8000, 'available'
  UNION ALL SELECT 5, 'STU 3344', 'Mitsubishi', 'Strada', 2022, 'Blue', 55000, 'available'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Vehicle v
  WHERE v.plate_number = seed.plate_number
);

-- ── 3. Customer ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Customer (
  customer_id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  first_name     VARCHAR(60)   NOT NULL,
  last_name      VARCHAR(60)   NOT NULL,
  email          VARCHAR(100)  NOT NULL,
  phone          VARCHAR(20)   NOT NULL,
  license_number VARCHAR(30)   NOT NULL,
  license_expiry DATE          NOT NULL,
  address        TEXT,
  created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (customer_id),
  UNIQUE KEY uq_email (email),
  UNIQUE KEY uq_license (license_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Customer (first_name, last_name, email, phone, license_number, license_expiry, address)
SELECT seed.first_name, seed.last_name, seed.email, seed.phone, seed.license_number, seed.license_expiry, seed.address
FROM (
  SELECT 'Juan' AS first_name, 'dela Cruz' AS last_name, 'juan@email.com' AS email, '09171234567' AS phone, 'N01-23-456789' AS license_number, '2027-05-12' AS license_expiry, 'Quezon City' AS address
  UNION ALL SELECT 'Maria', 'Santos', 'maria@email.com', '09189876543', 'N02-34-567890', '2026-08-30', 'Makati City'
  UNION ALL SELECT 'Jose', 'Reyes', 'jose@email.com', '09201122334', 'N03-45-678901', '2028-01-15', 'Pasig City'
  UNION ALL SELECT 'Ana', 'Garcia', 'ana@email.com', '09331234567', 'N04-56-789012', '2025-12-01', 'Marikina City'
  UNION ALL SELECT 'Pedro', 'Lim', 'pedro@email.com', '09558765432', 'N05-67-890123', '2029-03-20', 'Taguig City'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Customer c
  WHERE c.email = seed.email OR c.license_number = seed.license_number
);

-- ── 4. Rental ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Rental (
  rental_id     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  customer_id   INT UNSIGNED  NOT NULL,
  vehicle_id    INT UNSIGNED  NOT NULL,
  pickup_date   DATE          NOT NULL,
  return_date   DATE          NOT NULL,
  actual_return DATE          DEFAULT NULL,
  status        ENUM('active','completed','cancelled') NOT NULL DEFAULT 'active',
  notes         TEXT,
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rental_id),
  CONSTRAINT fk_rent_cust FOREIGN KEY (customer_id)
    REFERENCES Customer(customer_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_rent_veh  FOREIGN KEY (vehicle_id)
    REFERENCES Vehicle(vehicle_id)   ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Rental (customer_id, vehicle_id, pickup_date, return_date, actual_return, status)
SELECT seed.customer_id, seed.vehicle_id, seed.pickup_date, seed.return_date, seed.actual_return, seed.status
FROM (
  SELECT 1 AS customer_id, 2 AS vehicle_id, '2026-04-01' AS pickup_date, '2026-04-05' AS return_date, '2026-04-05' AS actual_return, 'completed' AS status
  UNION ALL SELECT 2, 1, '2026-04-06', '2026-04-10', NULL, 'active'
  UNION ALL SELECT 3, 3, '2026-03-15', '2026-03-20', '2026-03-21', 'completed'
  UNION ALL SELECT 4, 6, '2026-04-08', '2026-04-11', NULL, 'active'
  UNION ALL SELECT 5, 7, '2026-03-01', '2026-03-07', '2026-03-07', 'completed'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Rental r
  WHERE r.customer_id = seed.customer_id
    AND r.vehicle_id = seed.vehicle_id
    AND r.pickup_date = seed.pickup_date
    AND r.return_date = seed.return_date
);

-- ── 5. MaintenanceLog ────────────────────────────────────────
CREATE TABLE IF NOT EXISTS MaintenanceLog (
  log_id       INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  vehicle_id   INT UNSIGNED   NOT NULL,
  service_date DATE           NOT NULL,
  service_type VARCHAR(100)   NOT NULL,
  cost         DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  performed_by VARCHAR(100)   DEFAULT NULL,
  odometer_km  INT UNSIGNED   NOT NULL,
  remarks      TEXT,
  PRIMARY KEY (log_id),
  CONSTRAINT fk_maint_veh FOREIGN KEY (vehicle_id)
    REFERENCES Vehicle(vehicle_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO MaintenanceLog (vehicle_id, service_date, service_type, cost, performed_by, odometer_km)
SELECT seed.vehicle_id, seed.service_date, seed.service_type, seed.cost, seed.performed_by, seed.odometer_km
FROM (
  SELECT 4 AS vehicle_id, '2026-04-01' AS service_date, 'Engine overhaul' AS service_type, 8500.00 AS cost, 'AMS Auto Shop' AS performed_by, 94800 AS odometer_km
  UNION ALL SELECT 1, '2026-03-10', 'Oil change', 600.00, 'Petron Lube Center', 44500
  UNION ALL SELECT 5, '2026-02-20', 'Tire rotation', 350.00, 'FastFit Tires', 129000
  UNION ALL SELECT 3, '2026-01-15', 'Brake pad replacement', 1200.00, 'Ford Service Center', 77500
  UNION ALL SELECT 2, '2025-12-05', 'Air filter replacement', 400.00, 'Honda Casa', 11500
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM MaintenanceLog m
  WHERE m.vehicle_id = seed.vehicle_id
    AND m.service_date = seed.service_date
    AND m.service_type = seed.service_type
);

-- ── 6. Invoice ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Invoice (
  invoice_id     INT UNSIGNED   NOT NULL AUTO_INCREMENT,
  rental_id      INT UNSIGNED   NOT NULL,
  base_amount    DECIMAL(10,2)  NOT NULL,
  late_fee       DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  damage_fee     DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  total_amount   DECIMAL(10,2)  NOT NULL,
  payment_status ENUM('unpaid','paid','partial') NOT NULL DEFAULT 'unpaid',
  issued_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (invoice_id),
  UNIQUE KEY uq_rental (rental_id),
  CONSTRAINT fk_inv_rent FOREIGN KEY (rental_id)
    REFERENCES Rental(rental_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Invoice (rental_id, base_amount, late_fee, damage_fee, total_amount, payment_status)
SELECT seed.rental_id, seed.base_amount, seed.late_fee, seed.damage_fee, seed.total_amount, seed.payment_status
FROM (
  SELECT 1 AS rental_id, 6000.00 AS base_amount, 0.00 AS late_fee, 0.00 AS damage_fee, 6000.00 AS total_amount, 'paid' AS payment_status
  UNION ALL SELECT 3, 12500.00, 2500.00, 0.00, 15000.00, 'paid'
  UNION ALL SELECT 5, 12000.00, 0.00, 500.00, 12500.00, 'paid'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Invoice i
  WHERE i.rental_id = seed.rental_id
);

-- ── 7. AdminContactMessage ───────────────────────────────────
CREATE TABLE IF NOT EXISTS AdminContactMessage (
  message_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name    VARCHAR(120) NOT NULL,
  email        VARCHAR(120) NOT NULL,
  phone        VARCHAR(30)  DEFAULT NULL,
  subject      VARCHAR(180) NOT NULL,
  message      TEXT         NOT NULL,
  status       ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id),
  KEY idx_admin_contact_status (status),
  KEY idx_admin_contact_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO AdminContactMessage (full_name, email, phone, subject, message, status)
SELECT seed.full_name, seed.email, seed.phone, seed.subject, seed.message, seed.status
FROM (
  SELECT 'Maria Reyes' AS full_name, 'maria@email.com' AS email, '+63 917 123 4567' AS phone, 'Request for account creation' AS subject, 'Please create an account for branch staff operations.' AS message, 'new' AS status
  UNION ALL SELECT 'Juan dela Cruz', 'juan@email.com', '+63 918 234 5678', 'Billing clarification', 'Need a copy of receipt for last completed booking.', 'read'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM AdminContactMessage acm
  WHERE acm.email = seed.email
    AND acm.subject = seed.subject
    AND acm.message = seed.message
);

-- ── 8. PasswordResetRequest ──────────────────────────────────
CREATE TABLE IF NOT EXISTS PasswordResetRequest (
  request_id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  customer_id     INT UNSIGNED DEFAULT NULL,
  email           VARCHAR(120) NOT NULL,
  license_number  VARCHAR(30)  DEFAULT NULL,
  reason          VARCHAR(500) NOT NULL,
  status          ENUM('pending','processing','completed','rejected') NOT NULL DEFAULT 'pending',
  requested_ip    VARCHAR(45)  DEFAULT NULL,
  user_agent      VARCHAR(255) DEFAULT NULL,
  created_at      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  handled_at      TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (request_id),
  KEY idx_pwd_reset_status (status),
  KEY idx_pwd_reset_created (created_at),
  CONSTRAINT fk_pwdreset_customer FOREIGN KEY (customer_id)
    REFERENCES Customer(customer_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO PasswordResetRequest (customer_id, email, license_number, reason, status, requested_ip)
SELECT seed.customer_id, seed.email, seed.license_number, seed.reason, seed.status, seed.requested_ip
FROM (
  SELECT 1 AS customer_id, 'juan@email.com' AS email, 'N01-23-456789' AS license_number, 'I forgot my password after changing devices.' AS reason, 'pending' AS status, '127.0.0.1' AS requested_ip
  UNION ALL SELECT 2, 'maria@email.com', 'N02-34-567890', 'Unable to sign in with previous credentials.', 'processing', '127.0.0.1'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM PasswordResetRequest prr
  WHERE prr.email = seed.email
    AND IFNULL(prr.license_number, '') = IFNULL(seed.license_number, '')
    AND prr.reason = seed.reason
);

-- ── Views ─────────────────────────────────────────────────────
CREATE OR REPLACE VIEW vw_active_rentals AS
  SELECT r.rental_id,
         CONCAT(c.first_name,' ',c.last_name) AS customer_name,
         v.plate_number, v.brand, v.model,
         r.pickup_date, r.return_date
  FROM Rental r
  JOIN Customer c ON r.customer_id = c.customer_id
  JOIN Vehicle  v ON r.vehicle_id  = v.vehicle_id
  WHERE r.status = 'active';

CREATE OR REPLACE VIEW vw_monthly_revenue AS
  SELECT YEAR(r.pickup_date)  AS yr,
         MONTH(r.pickup_date) AS mo,
         SUM(i.total_amount)  AS total_revenue,
         COUNT(r.rental_id)   AS total_rentals
  FROM Rental r
  JOIN Invoice i ON r.rental_id = i.rental_id
  WHERE i.payment_status = 'paid'
  GROUP BY yr, mo
  ORDER BY yr DESC, mo DESC;

CREATE OR REPLACE VIEW vw_support_inbox_summary AS
  SELECT 'contact_messages' AS queue, status, COUNT(*) AS total
  FROM AdminContactMessage
  GROUP BY status
  UNION ALL
  SELECT 'password_reset_requests' AS queue, status, COUNT(*) AS total
  FROM PasswordResetRequest
  GROUP BY status;

-- ── Done! ─────────────────────────────────────────────────────
SELECT 'Mobilis DB setup complete!' AS Status;
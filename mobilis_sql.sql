-- ============================================================
-- Mobilis — Vehicle Rental & Fleet Management System
-- BSCSIT 2207L Database System 1 9312-AY2245 Final Project 
-- ============================================================

-- Step 1: Create and select the database
CREATE DATABASE IF NOT EXISTS mobilis_db
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE mobilis_db;

-- Step 2: Reset schema objects so every run reseeds from a clean slate
SET FOREIGN_KEY_CHECKS = 0;

DROP VIEW IF EXISTS vw_active_rentals;
DROP VIEW IF EXISTS vw_monthly_revenue;
DROP VIEW IF EXISTS vw_support_inbox_summary;

DROP TABLE IF EXISTS PasswordResetRequest;
DROP TABLE IF EXISTS AdminContactMessage;
DROP TABLE IF EXISTS Invoice;
DROP TABLE IF EXISTS MaintenanceLog;
DROP TABLE IF EXISTS Rental;
DROP TABLE IF EXISTS Vehicle;
DROP TABLE IF EXISTS User;
DROP TABLE IF EXISTS VehicleCategory;

SET FOREIGN_KEY_CHECKS = 1;

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
  latitude     DECIMAL(10,8) DEFAULT NULL,
  longitude    DECIMAL(11,8) DEFAULT NULL,
  status       ENUM('available','rented','maintenance') NOT NULL DEFAULT 'available',
  PRIMARY KEY (vehicle_id),
  UNIQUE KEY uq_plate (plate_number),
  CONSTRAINT fk_veh_cat FOREIGN KEY (category_id)
    REFERENCES VehicleCategory(category_id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Vehicle (category_id, plate_number, brand, model, year, color, mileage_km, latitude, longitude, status)
SELECT seed.category_id, seed.plate_number, seed.brand, seed.model, seed.year, seed.color, seed.mileage_km, seed.latitude, seed.longitude, seed.status
FROM (
  SELECT 2 AS category_id, 'ABC-1234' AS plate_number, 'Toyota' AS brand, 'Fortuner' AS model, 2022 AS year, 'Blue' AS color, 38200 AS mileage_km, 14.6091 AS latitude, 121.0223 AS longitude, 'rented' AS status
  UNION ALL SELECT 1, 'XYZ-5678', 'Honda', 'Civic', 2023, 'Gray', 12500, 14.5764, 121.0851, 'available'
  UNION ALL SELECT 3, 'DEF-9012', 'Toyota', 'HiAce', 2021, 'White', 49800, 14.6349, 121.0330, 'maintenance'
  UNION ALL SELECT 2, 'GHI-3456', 'Mitsubishi', 'Xpander', 2023, 'Silver', 21000, 14.5547, 121.0241, 'rented'
  UNION ALL SELECT 5, 'JKL-7890', 'Ford', 'Ranger', 2022, 'Black', 30100, 14.5995, 121.0586, 'available'
  UNION ALL SELECT 2, 'MNO-2345', 'Hyundai', 'Tucson', 2024, 'White', 8400, 14.6359, 121.0119, 'rented'
  UNION ALL SELECT 1, 'QRS-1007', 'Toyota', 'Vios', 2022, 'White', 44500, 14.5794, 121.0358, 'rented'
  UNION ALL SELECT 1, 'TUV-1008', 'Honda', 'City', 2023, 'Silver', 16000, 14.6042, 120.9842, 'rented'
  UNION ALL SELECT 1, 'WXY-1009', 'Nissan', 'Almera', 2021, 'Red', 55200, 14.5869, 121.0637, 'rented'
  UNION ALL SELECT 1, 'ZAB-1010', 'Mazda', '3', 2022, 'Machine Gray', 22000, 14.5532, 121.0465, 'rented'
  UNION ALL SELECT 2, 'CDE-1011', 'Ford', 'Everest', 2021, 'Black', 77800, 14.6188, 121.0097, 'rented'
  UNION ALL SELECT 2, 'FGH-1012', 'Isuzu', 'mu-X', 2020, 'Brown', 88000, 14.5485, 121.0682, 'rented'
  UNION ALL SELECT 2, 'IJK-1013', 'Mitsubishi', 'Montero', 2020, 'Gray', 95400, 14.5679, 120.9924, 'rented'
  UNION ALL SELECT 3, 'LMN-1014', 'Toyota', 'Innova', 2022, 'White', 36000, 14.5917, 121.0726, 'rented'
  UNION ALL SELECT 3, 'OPQ-1015', 'Nissan', 'Urvan', 2019, 'Pearl White', 116000, 14.6214, 121.0438, 'rented'
  UNION ALL SELECT 3, 'RST-1016', 'Kia', 'Carnival', 2023, 'Blue', 28000, 14.5418, 121.0158, 'rented'
  UNION ALL SELECT 5, 'UVW-1017', 'Ford', 'F-150', 2022, 'Red', 42000, 14.5883, 121.0532, 'rented'
  UNION ALL SELECT 5, 'XYA-1018', 'Toyota', 'Hilux', 2021, 'Black', 61000, 14.6087, 121.0289, 'rented'
  UNION ALL SELECT 5, 'BCD-1019', 'Mitsubishi', 'Strada', 2022, 'Blue', 54800, 14.5724, 121.0065, 'rented'
  UNION ALL SELECT 3, 'EFG-1020', 'Suzuki', 'Ertiga', 2023, 'Gray', 14000, 14.5956, 121.0409, 'rented'
  UNION ALL SELECT 2, 'HIJ-1021', 'Geely', 'Coolray', 2023, 'White', 17000, 14.5635, 121.0779, 'rented'
  UNION ALL SELECT 2, 'KLM-1022', 'Honda', 'BR-V', 2022, 'Silver', 25000, 14.6314, 121.0610, 'rented'
  UNION ALL SELECT 2, 'NOP-1023', 'Chery', 'Tiggo', 2024, 'Black', 9000, 14.5821, 120.9976, 'rented'
  UNION ALL SELECT 2, 'QRT-1024', 'Toyota', 'Raize', 2024, 'Yellow', 7800, 14.5586, 121.0302, 'rented'
  UNION ALL SELECT 3, 'STU-1025', 'Hyundai', 'Staria', 2024, 'White', 11000, 14.6159, 121.0193, 'rented'
  UNION ALL SELECT 2, 'VWX-1026', 'Peugeot', '3008', 2021, 'Blue', 31200, 14.5492, 121.0665, 'rented'
  UNION ALL SELECT 2, 'YZA-1027', 'Subaru', 'Forester', 2022, 'Green', 27600, 14.6026, 121.0458, 'rented'
  UNION ALL SELECT 2, 'ABC-1028', 'Chevrolet', 'Trailblazer', 2020, 'Black', 68000, 14.5751, 121.0128, 'rented'
  UNION ALL SELECT 5, 'DEF-1029', 'Nissan', 'Navara', 2021, 'Orange', 50000, 14.5901, 121.0676, 'rented'
  UNION ALL SELECT 5, 'GHI-1030', 'Mazda', 'BT-50', 2022, 'Gray', 34500, 14.5523, 121.0499, 'rented'
  UNION ALL SELECT 1, 'JKL-1031', 'Toyota', 'Corolla', 2023, 'White', 14300, 14.6268, 121.0375, 'rented'
  UNION ALL SELECT 1, 'MNP-1032', 'Honda', 'Accord', 2020, 'Blue', 62800, 14.5457, 121.0593, 'rented'
  UNION ALL SELECT 1, 'QWE-1033', 'Kia', 'Soluto', 2024, 'Red', 6200, 14.6193, 121.0267, 'rented'
  UNION ALL SELECT 2, 'RTY-1034', 'MG', 'ZS', 2023, 'Gray', 12300, 14.5664, 121.0805, 'rented'
  UNION ALL SELECT 3, 'UIO-1035', 'Toyota', 'Avanza', 2021, 'Silver', 43000, 14.5879, 120.9944, 'available'
  UNION ALL SELECT 3, 'PAS-1036', 'Honda', 'Mobilio', 2020, 'Gray', 49000, 14.6075, 121.0702, 'available'
  UNION ALL SELECT 1, 'DFG-1037', 'Suzuki', 'Dzire', 2022, 'Blue', 23000, 14.5569, 121.0227, 'available'
  UNION ALL SELECT 2, 'HJK-1038', 'Nissan', 'Terra', 2021, 'Black', 57500, 14.6242, 121.0548, 'available'
  UNION ALL SELECT 2, 'LZX-1039', 'Ford', 'Explorer', 2020, 'White', 70000, 14.5938, 121.0134, 'available'
  UNION ALL SELECT 2, 'CVB-1040', 'Toyota', 'Rush', 2023, 'Maroon', 11000, 14.5716, 121.0441, 'available'
  UNION ALL SELECT 5, 'NMK-1041', 'Isuzu', 'D-Max', 2022, 'Brown', 32000, 14.6175, 121.0043, 'available'
  UNION ALL SELECT 3, 'POI-1042', 'Mitsubishi', 'L300', 2021, 'White', 51000, 14.5401, 121.0609, 'available'
  UNION ALL SELECT 1, 'TRE-1043', 'Hyundai', 'Accent', 2024, 'Silver', 5500, 14.6008, 121.0321, 'available'
  UNION ALL SELECT 2, 'WQA-1044', 'Kia', 'Sportage', 2022, 'Green', 26900, 14.5847, 121.0075, 'available'
  UNION ALL SELECT 2, 'SED-1045', 'Toyota', 'Land Cruiser', 2019, 'White', 99000, 14.6482, 121.0485, 'maintenance'
  UNION ALL SELECT 3, 'RFV-1046', 'Ford', 'Transit', 2020, 'Blue', 88000, 14.5598, 121.0753, 'maintenance'
  UNION ALL SELECT 2, 'TGB-1047', 'Mazda', 'CX-9', 2021, 'Red', 54000, 14.6129, 121.0221, 'maintenance'
  UNION ALL SELECT 2, 'YHN-1048', 'Nissan', 'Patrol', 2018, 'Black', 120000, 14.5367, 121.0402, 'maintenance'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Vehicle v
  WHERE v.plate_number = seed.plate_number
);

-- ── 3. User ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS User (
  user_id        INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  first_name     VARCHAR(60)   NOT NULL,
  last_name      VARCHAR(60)   NOT NULL,
  email          VARCHAR(100)  NOT NULL,
  phone          VARCHAR(20)   NOT NULL,
  license_number VARCHAR(30)   NULL,
  license_expiry DATE          NULL,
  address        TEXT,
  role           ENUM('admin','staff','customer') NOT NULL DEFAULT 'customer',
  password_hash  VARCHAR(255)  NOT NULL,
  created_at     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_email (email),
  UNIQUE KEY uq_license (license_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO User (first_name, last_name, email, phone, license_number, license_expiry, address, role, password_hash, created_at)
SELECT seed.first_name, seed.last_name, seed.email, seed.phone, seed.license_number, seed.license_expiry, seed.address, seed.role, seed.password_hash, seed.created_at
FROM (
  -- Admin account
  SELECT 'Admin' AS first_name, 'User' AS last_name, 'admin@mobilis.ph' AS email, '+63 900 000 0001' AS phone, NULL AS license_number, NULL AS license_expiry, 'Mobilis HQ, Metro Manila' AS address, 'admin' AS role, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' AS password_hash, '2024-01-01 00:00:00' AS created_at
  UNION ALL
  -- Staff account
  SELECT 'Staff' AS first_name, 'User' AS last_name, 'staff@mobilis.ph' AS email, '+63 900 000 0002' AS phone, NULL AS license_number, NULL AS license_expiry, 'Mobilis HQ, Metro Manila' AS address, 'staff' AS role, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' AS password_hash, '2024-01-01 00:00:00' AS created_at
  UNION ALL
  -- Customer account
  SELECT 'Customer' AS first_name, 'User' AS last_name, 'customer@mobilis.ph' AS email, '+63 900 000 0003' AS phone, NULL AS license_number, NULL AS license_expiry, 'Metro Manila, Philippines' AS address, 'customer' AS role, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' AS password_hash, '2024-01-01 00:00:00' AS created_at
  UNION ALL
  -- Sample customers
  SELECT 'Maria' AS first_name, 'Reyes' AS last_name, 'maria@email.com' AS email, '+63 917 123 4567' AS phone, 'N01-23-456789' AS license_number, '2028-03-20' AS license_expiry, 'Makati City, Metro Manila' AS address, 'customer' AS role, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' AS password_hash, '2023-01-12 09:30:00' AS created_at
  UNION ALL SELECT 'Juan', 'dela Cruz', 'jdc@email.com', '+63 918 234 5678', 'N02-34-567890', '2027-08-30', 'Quezon City, Metro Manila', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2024-04-03 11:00:00'
  UNION ALL SELECT 'Ana', 'Lim', 'ana.lim@email.com', '+63 919 345 6789', 'N03-45-678901', '2028-03-15', 'Pasig City, Metro Manila', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2023-03-18 10:15:00'
  UNION ALL SELECT 'Ramon', 'Santos', 'ramon.s@email.com', '+63 920 456 7890', 'N04-56-789012', '2027-11-21', 'Manila City, Metro Manila', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2024-01-09 13:25:00'
  UNION ALL SELECT 'Pedro', 'Cruz', 'pedz@email.com', '+63 921 567 8901', 'N05-67-890123', '2029-06-12', 'Taguig City, Metro Manila', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2023-06-25 08:20:00'
  UNION ALL SELECT 'Lisa', 'Garcia', 'lisag@email.com', '+63 922 678 9012', 'N06-78-901234', '2028-10-08', 'Mandaluyong City, Metro Manila', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2026-03-30 16:10:00'
  UNION ALL SELECT 'Bea', 'Torres', 'bea.t@email.com', '+63 923 789 0123', 'N07-89-012345', '2029-02-14', 'Caloocan City, Metro Manila', 'customer', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '2022-02-05 09:05:00'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM User u
  WHERE u.email = seed.email OR (u.license_number IS NOT NULL AND u.license_number = seed.license_number)
);

-- ── 4. Rental ────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Rental (
  rental_id     INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id       INT UNSIGNED  NOT NULL,
  vehicle_id    INT UNSIGNED  NOT NULL,
  pickup_date   DATE          NOT NULL,
  return_date   DATE          NOT NULL,
  actual_return DATE          DEFAULT NULL,
  status        ENUM('pending','active','completed','cancelled') NOT NULL DEFAULT 'active',
  notes         TEXT,
  created_at    TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (rental_id),
  CONSTRAINT fk_rent_user FOREIGN KEY (user_id)
    REFERENCES User(user_id) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_rent_veh  FOREIGN KEY (vehicle_id)
    REFERENCES Vehicle(vehicle_id)   ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Rental (rental_id, user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
SELECT seed.rental_id, seed.user_id, seed.vehicle_id, seed.pickup_date, seed.return_date, seed.actual_return, seed.status, seed.notes
FROM (
  SELECT 412 AS rental_id, 4 AS user_id, 1 AS vehicle_id, DATE_ADD(CURDATE(), INTERVAL 1 DAY) AS pickup_date, DATE_ADD(CURDATE(), INTERVAL 4 DAY) AS return_date, NULL AS actual_return, 'active' AS status, 'Priority corporate booking' AS notes
  UNION ALL SELECT 411, 5, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'pending', 'Awaiting approval'
  UNION ALL SELECT 410, 6, 3, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), NULL, 'active', 'Family vacation trip'
  UNION ALL SELECT 409, 7, 5, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'active', 'Weekend out-of-town use'
  UNION ALL SELECT 408, 8, 4, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'completed', 'Completed with receipt issued'
  UNION ALL SELECT 407, 9, 6, DATE_SUB(CURDATE(), INTERVAL 11 DAY), DATE_SUB(CURDATE(), INTERVAL 11 DAY), NULL, 'cancelled', 'Customer cancelled same day'
  UNION ALL SELECT 406, 4, 5, DATE_SUB(CURDATE(), INTERVAL 22 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'completed', 'Recent booking history'
  UNION ALL SELECT 405, 4, 3, DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 36 DAY), DATE_SUB(CURDATE(), INTERVAL 36 DAY), 'completed', 'Recent booking history'
  UNION ALL SELECT 404, 10, 11, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'active', 'VIP corporate booking'
  UNION ALL SELECT 403, 10, 12, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL, 'active', 'VIP corporate booking'
  UNION ALL SELECT 402, 5, 7, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'active', 'Regional travel'
  UNION ALL SELECT 401, 7, 8, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'active', 'Branch operations'
  UNION ALL SELECT 400, 6, 9, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'active', 'Intercity transfer'
  UNION ALL SELECT 399, 8, 10, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'active', 'Event support fleet'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Rental r
  WHERE r.rental_id = seed.rental_id
);

INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
SELECT
  ((v.vehicle_id - 1) % 7) + 4 AS user_id,
  v.vehicle_id,
  DATE_SUB(CURDATE(), INTERVAL ((v.vehicle_id - 1) % 5 + 1) DAY) AS pickup_date,
  DATE_ADD(CURDATE(), INTERVAL ((v.vehicle_id - 1) % 4 + 1) DAY) AS return_date,
  NULL AS actual_return,
  'active' AS status,
  'Baseline seeded active rental' AS notes
FROM Vehicle v
WHERE v.status = 'rented'
  AND NOT EXISTS (
    SELECT 1
    FROM Rental r
    WHERE r.vehicle_id = v.vehicle_id
      AND r.status = 'active'
  );

-- Add richer time-series rentals so reports have denser, date-relative data.
INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
WITH RECURSIVE rental_seed AS (
  SELECT 1 AS n
  UNION ALL
  SELECT n + 1
  FROM rental_seed
  WHERE n < 54
)
SELECT
  4 + MOD(n, 7) AS user_id,
  1 + MOD(n, 48) AS vehicle_id,
  DATE_SUB(CURDATE(), INTERVAL (n + 24) DAY) AS pickup_date,
  DATE_ADD(DATE_SUB(CURDATE(), INTERVAL (n + 24) DAY), INTERVAL (2 + MOD(n, 4)) DAY) AS return_date,
  CASE
    WHEN MOD(n, 9) = 0 THEN NULL
    WHEN MOD(n, 7) = 0 THEN DATE_ADD(DATE_ADD(DATE_SUB(CURDATE(), INTERVAL (n + 24) DAY), INTERVAL (2 + MOD(n, 4)) DAY), INTERVAL 1 DAY)
    ELSE DATE_ADD(DATE_SUB(CURDATE(), INTERVAL (n + 24) DAY), INTERVAL (2 + MOD(n, 4)) DAY)
  END AS actual_return,
  CASE
    WHEN MOD(n, 9) = 0 THEN 'cancelled'
    ELSE 'completed'
  END AS status,
  CONCAT('Trend seed rental #', LPAD(n, 2, '0')) AS notes
FROM rental_seed;

-- Seed a few additional near-term records to diversify booking status charts.
INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
SELECT seed.user_id, seed.vehicle_id, seed.pickup_date, seed.return_date, seed.actual_return, seed.status, seed.notes
FROM (
  SELECT 4 AS user_id, 18 AS vehicle_id, DATE_ADD(CURDATE(), INTERVAL 2 DAY) AS pickup_date, DATE_ADD(CURDATE(), INTERVAL 5 DAY) AS return_date, NULL AS actual_return, 'pending' AS status, 'Trend seed pending future booking' AS notes
  UNION ALL SELECT 5, 21, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'active', 'Trend seed active short-haul booking'
  UNION ALL SELECT 6, 24, DATE_ADD(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL, 'pending', 'Trend seed pending long-haul booking'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Rental r
  WHERE r.user_id = seed.user_id
    AND r.vehicle_id = seed.vehicle_id
    AND r.pickup_date = seed.pickup_date
    AND r.return_date = seed.return_date
    AND r.notes = seed.notes
);

-- ── Additional Booking Sample Data for All Customers ───────────
-- 10+ Past bookings (completed/cancelled, pickup_date < CURDATE())
INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
SELECT seed.user_id, seed.vehicle_id, seed.pickup_date, seed.return_date, seed.actual_return, seed.status, seed.notes
FROM (
  SELECT 4 AS user_id, 1 AS vehicle_id, DATE_SUB(CURDATE(), INTERVAL 60 DAY) AS pickup_date, DATE_SUB(CURDATE(), INTERVAL 55 DAY) AS return_date, DATE_SUB(CURDATE(), INTERVAL 55 DAY) AS actual_return, 'completed' AS status, 'Past booking - Maria Reyes' AS notes
  UNION ALL SELECT 4, 2, DATE_SUB(CURDATE(), INTERVAL 55 DAY), DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_SUB(CURDATE(), INTERVAL 50 DAY), 'completed', 'Past booking - Maria Reyes'
  UNION ALL SELECT 5, 3, DATE_SUB(CURDATE(), INTERVAL 50 DAY), DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 45 DAY), 'completed', 'Past booking - Juan dela Cruz'
  UNION ALL SELECT 5, 4, DATE_SUB(CURDATE(), INTERVAL 45 DAY), DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'completed', 'Past booking - Juan dela Cruz'
  UNION ALL SELECT 6, 5, DATE_SUB(CURDATE(), INTERVAL 40 DAY), DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 35 DAY), 'completed', 'Past booking - Ana Lim'
  UNION ALL SELECT 6, 6, DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'completed', 'Past booking - Ana Lim'
  UNION ALL SELECT 7, 7, DATE_SUB(CURDATE(), INTERVAL 30 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'completed', 'Past booking - Ramon Santos'
  UNION ALL SELECT 7, 8, DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'completed', 'Past booking - Ramon Santos'
  UNION ALL SELECT 8, 9, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'completed', 'Past booking - Pedro Cruz'
  UNION ALL SELECT 8, 10, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'completed', 'Past booking - Pedro Cruz'
  UNION ALL SELECT 9, 11, DATE_SUB(CURDATE(), INTERVAL 80 DAY), DATE_SUB(CURDATE(), INTERVAL 75 DAY), DATE_SUB(CURDATE(), INTERVAL 75 DAY), 'completed', 'Past booking - Lisa Garcia'
  UNION ALL SELECT 9, 12, DATE_SUB(CURDATE(), INTERVAL 70 DAY), DATE_SUB(CURDATE(), INTERVAL 65 DAY), DATE_SUB(CURDATE(), INTERVAL 65 DAY), 'completed', 'Past booking - Lisa Garcia'
  UNION ALL SELECT 10, 13, DATE_SUB(CURDATE(), INTERVAL 65 DAY), DATE_SUB(CURDATE(), INTERVAL 60 DAY), DATE_SUB(CURDATE(), INTERVAL 60 DAY), 'completed', 'Past booking - Bea Torres'
  UNION ALL SELECT 10, 14, DATE_SUB(CURDATE(), INTERVAL 58 DAY), DATE_SUB(CURDATE(), INTERVAL 53 DAY), DATE_SUB(CURDATE(), INTERVAL 53 DAY), 'completed', 'Past booking - Bea Torres'
  UNION ALL SELECT 4, 15, DATE_SUB(CURDATE(), INTERVAL 48 DAY), DATE_SUB(CURDATE(), INTERVAL 43 DAY), NULL, 'cancelled', 'Cancelled booking - Maria Reyes'
  UNION ALL SELECT 5, 16, DATE_SUB(CURDATE(), INTERVAL 42 DAY), DATE_SUB(CURDATE(), INTERVAL 37 DAY), NULL, 'cancelled', 'Cancelled booking - Juan dela Cruz'
  UNION ALL SELECT 6, 17, DATE_SUB(CURDATE(), INTERVAL 38 DAY), DATE_SUB(CURDATE(), INTERVAL 33 DAY), NULL, 'cancelled', 'Cancelled booking - Ana Lim'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Rental r
  WHERE r.user_id = seed.user_id
    AND r.vehicle_id = seed.vehicle_id
    AND r.pickup_date = seed.pickup_date
    AND r.return_date = seed.return_date
    AND r.notes = seed.notes
);

-- 10+ Present bookings (active, pickup_date <= CURDATE() and return_date >= CURDATE())
INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
SELECT seed.user_id, seed.vehicle_id, seed.pickup_date, seed.return_date, seed.actual_return, seed.status, seed.notes
FROM (
  SELECT 4 AS user_id, 1 AS vehicle_id, DATE_SUB(CURDATE(), INTERVAL 2 DAY) AS pickup_date, DATE_ADD(CURDATE(), INTERVAL 3 DAY) AS return_date, NULL AS actual_return, 'active' AS status, 'Present active booking - Maria Reyes' AS notes
  UNION ALL SELECT 5, 2, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL, 'active', 'Present active booking - Juan dela Cruz'
  UNION ALL SELECT 6, 3, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL, 'active', 'Present active booking - Ana Lim'
  UNION ALL SELECT 7, 4, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'active', 'Present active booking - Ramon Santos'
  UNION ALL SELECT 8, 5, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'active', 'Present active booking - Pedro Cruz'
  UNION ALL SELECT 9, 6, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'active', 'Present active booking - Lisa Garcia'
  UNION ALL SELECT 10, 7, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL, 'active', 'Present active booking - Bea Torres'
  UNION ALL SELECT 4, 8, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'active', 'Present active booking - Maria Reyes'
  UNION ALL SELECT 5, 9, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL, 'active', 'Present active booking - Juan dela Cruz'
  UNION ALL SELECT 6, 10, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL, 'active', 'Present active booking - Ana Lim'
  UNION ALL SELECT 7, 11, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL, 'active', 'Present active booking - Ramon Santos'
  UNION ALL SELECT 8, 12, DATE_SUB(CURDATE(), INTERVAL 1 DAY), DATE_ADD(CURDATE(), INTERVAL 2 DAY), NULL, 'active', 'Present active booking - Pedro Cruz'
  UNION ALL SELECT 9, 13, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL, 'active', 'Present active booking - Lisa Garcia'
  UNION ALL SELECT 10, 14, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL, 'active', 'Present active booking - Bea Torres'
  UNION ALL SELECT 4, 15, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 1 DAY), NULL, 'active', 'Present active booking - Maria Reyes'
  UNION ALL SELECT 5, 16, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL, 'active', 'Present active booking - Juan dela Cruz'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Rental r
  WHERE r.user_id = seed.user_id
    AND r.vehicle_id = seed.vehicle_id
    AND r.pickup_date = seed.pickup_date
    AND r.return_date = seed.return_date
    AND r.notes = seed.notes
);

-- 10+ Future bookings (pending/active, pickup_date > CURDATE())
INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
SELECT seed.user_id, seed.vehicle_id, seed.pickup_date, seed.return_date, seed.actual_return, seed.status, seed.notes
FROM (
  SELECT 4 AS user_id, 1 AS vehicle_id, DATE_ADD(CURDATE(), INTERVAL 5 DAY) AS pickup_date, DATE_ADD(CURDATE(), INTERVAL 10 DAY) AS return_date, NULL AS actual_return, 'pending' AS status, 'Future pending booking - Maria Reyes' AS notes
  UNION ALL SELECT 5, 2, DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), NULL, 'pending', 'Future pending booking - Juan dela Cruz'
  UNION ALL SELECT 6, 3, DATE_ADD(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), NULL, 'pending', 'Future pending booking - Ana Lim'
  UNION ALL SELECT 7, 4, DATE_ADD(CURDATE(), INTERVAL 8 DAY), DATE_ADD(CURDATE(), INTERVAL 13 DAY), NULL, 'pending', 'Future pending booking - Ramon Santos'
  UNION ALL SELECT 8, 5, DATE_ADD(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 17 DAY), NULL, 'pending', 'Future pending booking - Pedro Cruz'
  UNION ALL SELECT 9, 6, DATE_ADD(CURDATE(), INTERVAL 6 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), NULL, 'pending', 'Future pending booking - Lisa Garcia'
  UNION ALL SELECT 10, 7, DATE_ADD(CURDATE(), INTERVAL 9 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), NULL, 'pending', 'Future pending booking - Bea Torres'
  UNION ALL SELECT 4, 8, DATE_ADD(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), NULL, 'pending', 'Future pending booking - Maria Reyes'
  UNION ALL SELECT 5, 9, DATE_ADD(CURDATE(), INTERVAL 18 DAY), DATE_ADD(CURDATE(), INTERVAL 23 DAY), NULL, 'pending', 'Future pending booking - Juan dela Cruz'
  UNION ALL SELECT 6, 10, DATE_ADD(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), NULL, 'pending', 'Future pending booking - Ana Lim'
  UNION ALL SELECT 7, 11, DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 19 DAY), NULL, 'pending', 'Future pending booking - Ramon Santos'
  UNION ALL SELECT 8, 12, DATE_ADD(CURDATE(), INTERVAL 22 DAY), DATE_ADD(CURDATE(), INTERVAL 27 DAY), NULL, 'pending', 'Future pending booking - Pedro Cruz'
  UNION ALL SELECT 9, 13, DATE_ADD(CURDATE(), INTERVAL 16 DAY), DATE_ADD(CURDATE(), INTERVAL 21 DAY), NULL, 'pending', 'Future pending booking - Lisa Garcia'
  UNION ALL SELECT 10, 14, DATE_ADD(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NULL, 'pending', 'Future pending booking - Bea Torres'
  UNION ALL SELECT 4, 15, DATE_ADD(CURDATE(), INTERVAL 30 DAY), DATE_ADD(CURDATE(), INTERVAL 35 DAY), NULL, 'pending', 'Future pending booking - Maria Reyes'
  UNION ALL SELECT 5, 16, DATE_ADD(CURDATE(), INTERVAL 35 DAY), DATE_ADD(CURDATE(), INTERVAL 40 DAY), NULL, 'pending', 'Future pending booking - Juan dela Cruz'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Rental r
  WHERE r.user_id = seed.user_id
    AND r.vehicle_id = seed.vehicle_id
    AND r.pickup_date = seed.pickup_date
    AND r.return_date = seed.return_date
    AND r.notes = seed.notes
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
  SELECT 4 AS vehicle_id, DATE_SUB(CURDATE(), INTERVAL 18 DAY) AS service_date, 'Engine overhaul' AS service_type, 8500.00 AS cost, 'AMS Auto Shop' AS performed_by, 94800 AS odometer_km
  UNION ALL SELECT 1, DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'Oil change', 600.00, 'Petron Lube Center', 44500
  UNION ALL SELECT 5, DATE_SUB(CURDATE(), INTERVAL 55 DAY), 'Tire rotation', 350.00, 'FastFit Tires', 129000
  UNION ALL SELECT 3, DATE_SUB(CURDATE(), INTERVAL 70 DAY), 'Brake pad replacement', 1200.00, 'Ford Service Center', 77500
  UNION ALL SELECT 2, DATE_SUB(CURDATE(), INTERVAL 120 DAY), 'Air filter replacement', 400.00, 'Honda Casa', 11500
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
  payment_method ENUM('pending','cash','gcash','card','bank_transfer') NOT NULL DEFAULT 'pending',
  issued_at      TIMESTAMP      DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (invoice_id),
  UNIQUE KEY uq_rental (rental_id),
  CONSTRAINT fk_inv_rent FOREIGN KEY (rental_id)
    REFERENCES Rental(rental_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO Invoice (rental_id, base_amount, late_fee, damage_fee, total_amount, payment_status, issued_at)
SELECT seed.rental_id, seed.base_amount, seed.late_fee, seed.damage_fee, seed.total_amount, seed.payment_status, seed.issued_at
FROM (
  SELECT 412 AS rental_id, 10500.00 AS base_amount, 0.00 AS late_fee, 0.00 AS damage_fee, 10500.00 AS total_amount, 'paid' AS payment_status, TIMESTAMP(CURDATE(), '08:00:00') AS issued_at
  UNION ALL SELECT 411, 2200.00, 0.00, 0.00, 2200.00, 'unpaid', TIMESTAMP(CURDATE(), '09:00:00')
  UNION ALL SELECT 410, 20000.00, 0.00, 0.00, 20000.00, 'paid', TIMESTAMP(CURDATE(), '10:00:00')
  UNION ALL SELECT 409, 6400.00, 0.00, 0.00, 6400.00, 'unpaid', TIMESTAMP(CURDATE(), '11:00:00')
  UNION ALL SELECT 408, 5600.00, 0.00, 0.00, 5600.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 7 DAY), '14:30:00')
  UNION ALL SELECT 407, 3800.00, 0.00, 0.00, 3800.00, 'unpaid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 11 DAY), '15:20:00')
  UNION ALL SELECT 406, 6400.00, 0.00, 0.00, 6400.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 20 DAY), '16:00:00')
  UNION ALL SELECT 405, 125100.00, 0.00, 0.00, 125100.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 36 DAY), '13:10:00')
  UNION ALL SELECT 404, 150000.00, 0.00, 0.00, 150000.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 2 DAY), '12:00:00')
  UNION ALL SELECT 403, 126300.00, 0.00, 0.00, 126300.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 1 DAY), '12:15:00')
  UNION ALL SELECT 402, 36200.00, 0.00, 0.00, 36200.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 4 DAY), '12:45:00')
  UNION ALL SELECT 401, 14800.00, 0.00, 0.00, 14800.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 3 DAY), '18:25:00')
  UNION ALL SELECT 400, 178500.00, 0.00, 0.00, 178500.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 5 DAY), '18:45:00')
  UNION ALL SELECT 399, 49200.00, 0.00, 0.00, 49200.00, 'paid', TIMESTAMP(DATE_SUB(CURDATE(), INTERVAL 6 DAY), '19:00:00')
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Invoice i
  WHERE i.rental_id = seed.rental_id
);

-- Add additional invoices for trend-seeded rentals with mixed payment statuses.
INSERT INTO Invoice (rental_id, base_amount, late_fee, damage_fee, total_amount, payment_status, issued_at)
SELECT
  r.rental_id,
  ROUND(vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1), 2) AS base_amount,
  CASE WHEN MOD(r.rental_id, 11) = 0 THEN 450.00 ELSE 0.00 END AS late_fee,
  CASE WHEN MOD(r.rental_id, 17) = 0 THEN 700.00 ELSE 0.00 END AS damage_fee,
  ROUND(
    vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1)
    + CASE WHEN MOD(r.rental_id, 11) = 0 THEN 450.00 ELSE 0.00 END
    + CASE WHEN MOD(r.rental_id, 17) = 0 THEN 700.00 ELSE 0.00 END,
    2
  ) AS total_amount,
  CASE
    WHEN MOD(r.rental_id, 8) = 0 THEN 'partial'
    WHEN MOD(r.rental_id, 6) = 0 THEN 'unpaid'
    ELSE 'paid'
  END AS payment_status,
  TIMESTAMP(
    DATE_ADD(r.return_date, INTERVAL 1 DAY),
    SEC_TO_TIME(28800 + MOD(r.rental_id, 36000))
  ) AS issued_at
FROM Rental r
JOIN Vehicle v
  ON v.vehicle_id = r.vehicle_id
JOIN VehicleCategory vc
  ON vc.category_id = v.category_id
WHERE r.notes LIKE 'Trend seed rental #%'
  AND r.status = 'completed'
  AND NOT EXISTS (
    SELECT 1
    FROM Invoice i
    WHERE i.rental_id = r.rental_id
  );

UPDATE Invoice
SET payment_method = CASE
  WHEN payment_status = 'paid' AND MOD(invoice_id, 4) = 0 THEN 'card'
  WHEN payment_status = 'paid' AND MOD(invoice_id, 4) = 1 THEN 'gcash'
  WHEN payment_status = 'paid' AND MOD(invoice_id, 4) = 2 THEN 'bank_transfer'
  WHEN payment_status = 'paid' THEN 'cash'
  WHEN payment_status = 'partial' THEN 'bank_transfer'
  ELSE 'pending'
END;

-- Add additional maintenance log entries for diverse vehicle service history and alerts
INSERT INTO MaintenanceLog (vehicle_id, service_date, service_type, cost, performed_by, odometer_km, remarks)
SELECT seed.vehicle_id, seed.service_date, seed.service_type, seed.cost, seed.performed_by, seed.odometer_km, seed.remarks
FROM (
  SELECT 6 AS vehicle_id, DATE_SUB(CURDATE(), INTERVAL 5 DAY) AS service_date, 'Regular oil change' AS service_type, 450.00 AS cost, 'QuickLube Express' AS performed_by, 38200 AS odometer_km, 'Routine maintenance' AS remarks
  UNION ALL SELECT 7, DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'Brake inspection', 800.00, 'Brake Masters', 21000, 'Brake pads at 60%'
  UNION ALL SELECT 8, DATE_SUB(CURDATE(), INTERVAL 25 DAY), 'Transmission fluid check', 550.00, 'AutoTrans Service', 16000, 'Fluid level low'
  UNION ALL SELECT 9, DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'Air conditioning service', 1200.00, 'CoolAir Solutions', 55200, 'Refrigerant recharge'
  UNION ALL SELECT 10, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'Suspension check', 950.00, 'Suspension Pro', 30100, 'Shock absorbers showing wear'
  UNION ALL SELECT 11, DATE_SUB(CURDATE(), INTERVAL 15 DAY), 'Battery replacement', 3500.00, 'Battery World', 8400, 'Old battery failed load test'
  UNION ALL SELECT 12, DATE_SUB(CURDATE(), INTERVAL 22 DAY), 'Wheel alignment', 400.00, 'Tire Kingdom', 44500, 'Pulling to the right'
  UNION ALL SELECT 13, DATE_SUB(CURDATE(), INTERVAL 35 DAY), 'Coolant flush', 650.00, 'Radiator Doctors', 36000, 'Coolant discolored'
  UNION ALL SELECT 14, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'Timing belt replacement', 4500.00, 'Timing Belt Specialists', 116000, 'Preventive maintenance at 100k km'
  UNION ALL SELECT 15, DATE_SUB(CURDATE(), INTERVAL 28 DAY), 'Fuel system cleaning', 750.00, 'Fuel Injection Pros', 28000, 'Poor fuel economy reported'
  UNION ALL SELECT 16, DATE_SUB(CURDATE(), INTERVAL 18 DAY), 'Exhaust system repair', 2200.00, 'Muffler Man', 42000, 'Exhaust leak detected'
  UNION ALL SELECT 17, DATE_SUB(CURDATE(), INTERVAL 40 DAY), 'Power steering service', 850.00, 'Steering Solutions', 54800, 'Stiff steering at low speeds'
  UNION ALL SELECT 18, DATE_SUB(CURDATE(), INTERVAL 7 DAY), 'Engine diagnostic', 500.00, 'Engine Experts', 14300, 'Check engine light on'
  UNION ALL SELECT 19, DATE_SUB(CURDATE(), INTERVAL 20 DAY), 'Clutch replacement', 6800.00, 'Clutch Masters', 62800, 'Slipping clutch reported'
  UNION ALL SELECT 20, DATE_SUB(CURDATE(), INTERVAL 33 DAY), 'Differential service', 1100.00, 'Gearbox Garage', 12300, 'Whining noise from rear'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM MaintenanceLog m
  WHERE m.vehicle_id = seed.vehicle_id
    AND m.service_date = seed.service_date
    AND m.service_type = seed.service_type
);

-- Add more rental records with diverse statuses for booking status breakdown
INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
SELECT seed.user_id, seed.vehicle_id, seed.pickup_date, seed.return_date, seed.actual_return, seed.status, seed.notes
FROM (
  SELECT 4 AS user_id, 2 AS vehicle_id, DATE_SUB(CURDATE(), INTERVAL 3 DAY) AS pickup_date, DATE_ADD(CURDATE(), INTERVAL 2 DAY) AS return_date, NULL AS actual_return, 'active' AS status, 'Customer extended booking' AS notes
  UNION ALL SELECT 5, 3, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'completed', 'Short weekend trip'
  UNION ALL SELECT 6, 4, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_SUB(CURDATE(), INTERVAL 4 DAY), 'completed', 'Business trip'
  UNION ALL SELECT 7, 5, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_SUB(CURDATE(), INTERVAL 8 DAY), 'completed', 'Family vacation'
  UNION ALL SELECT 8, 6, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 10 DAY), NULL, 'cancelled', 'Customer changed plans'
  UNION ALL SELECT 4, 7, DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 12 DAY), 'completed', 'Airport transfer'
  UNION ALL SELECT 9, 8, DATE_SUB(CURDATE(), INTERVAL 16 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), 'completed', 'City tour'
  UNION ALL SELECT 10, 9, DATE_SUB(CURDATE(), INTERVAL 18 DAY), DATE_SUB(CURDATE(), INTERVAL 16 DAY), NULL, 'cancelled', 'Payment issue'
  UNION ALL SELECT 5, 10, DATE_ADD(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'pending', 'Future booking confirmed'
  UNION ALL SELECT 6, 11, DATE_ADD(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), NULL, 'pending', 'Corporate event booking'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Rental r
  WHERE r.user_id = seed.user_id
    AND r.vehicle_id = seed.vehicle_id
    AND r.pickup_date = seed.pickup_date
    AND r.return_date = seed.return_date
    AND r.notes = seed.notes
);

-- Add invoices for the new rental records
INSERT INTO Invoice (rental_id, base_amount, late_fee, damage_fee, total_amount, payment_status, issued_at)
SELECT
  r.rental_id,
  ROUND(vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1), 2) AS base_amount,
  0.00 AS late_fee,
  0.00 AS damage_fee,
  ROUND(vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1), 2) AS total_amount,
  CASE
    WHEN r.status = 'cancelled' THEN 'unpaid'
    WHEN r.status = 'pending' THEN 'unpaid'
    WHEN MOD(r.rental_id, 3) = 0 THEN 'partial'
    ELSE 'paid'
  END AS payment_status,
  CASE
    WHEN r.status = 'pending' THEN NULL
    ELSE TIMESTAMP(DATE_ADD(COALESCE(r.actual_return, r.return_date), INTERVAL 1 DAY), SEC_TO_TIME(28800))
  END AS issued_at
FROM Rental r
JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
JOIN VehicleCategory vc ON vc.category_id = v.category_id
WHERE r.notes IN ('Customer extended booking', 'Short weekend trip', 'Business trip', 'Family vacation', 'Airport transfer', 'City tour')
  AND NOT EXISTS (
    SELECT 1
    FROM Invoice i
    WHERE i.rental_id = r.rental_id
  );

-- Add today's revenue - update existing invoices to today's date with paid status
INSERT INTO Invoice (rental_id, base_amount, late_fee, damage_fee, total_amount, payment_status, payment_method, issued_at)
SELECT seed.rental_id, seed.base_amount, seed.late_fee, seed.damage_fee, seed.total_amount, seed.payment_status, seed.payment_method, seed.issued_at
FROM (
  SELECT 412 AS rental_id, 10500.00 AS base_amount, 0.00 AS late_fee, 0.00 AS damage_fee, 10500.00 AS total_amount, 'paid' AS payment_status, 'cash' AS payment_method, TIMESTAMP(CURDATE(), '09:30:00') AS issued_at
  UNION ALL SELECT 410, 20000.00, 0.00, 0.00, 20000.00, 'paid', 'gcash', TIMESTAMP(CURDATE(), '10:15:00')
  UNION ALL SELECT 408, 5600.00, 0.00, 0.00, 5600.00, 'paid', 'card', TIMESTAMP(CURDATE(), '11:45:00')
  UNION ALL SELECT 406, 6400.00, 0.00, 0.00, 6400.00, 'paid', 'bank_transfer', TIMESTAMP(CURDATE(), '14:20:00')
  UNION ALL SELECT 405, 125100.00, 0.00, 0.00, 125100.00, 'paid', 'cash', TIMESTAMP(CURDATE(), '15:30:00')
) AS seed
ON DUPLICATE KEY UPDATE
  issued_at = VALUES(issued_at),
  payment_status = VALUES(payment_status),
  payment_method = VALUES(payment_method);

-- Add more recent rentals from last 30 days for better analytics
INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, actual_return, status, notes)
SELECT seed.user_id, seed.vehicle_id, seed.pickup_date, seed.return_date, seed.actual_return, seed.status, seed.notes
FROM (
  SELECT 4 AS user_id, 1 AS vehicle_id, DATE_SUB(CURDATE(), INTERVAL 1 DAY) AS pickup_date, DATE_ADD(CURDATE(), INTERVAL 2 DAY) AS return_date, NULL AS actual_return, 'active' AS status, 'Recent booking - last 30 days' AS notes
  UNION ALL SELECT 5, 2, DATE_SUB(CURDATE(), INTERVAL 2 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 6, 3, DATE_SUB(CURDATE(), INTERVAL 3 DAY), DATE_ADD(CURDATE(), INTERVAL 4 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 7, 4, DATE_SUB(CURDATE(), INTERVAL 4 DAY), DATE_ADD(CURDATE(), INTERVAL 5 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 8, 5, DATE_SUB(CURDATE(), INTERVAL 5 DAY), DATE_ADD(CURDATE(), INTERVAL 6 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 9, 6, DATE_SUB(CURDATE(), INTERVAL 6 DAY), DATE_ADD(CURDATE(), INTERVAL 7 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 10, 7, DATE_SUB(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 8 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 4, 8, DATE_SUB(CURDATE(), INTERVAL 8 DAY), DATE_ADD(CURDATE(), INTERVAL 9 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 5, 9, DATE_SUB(CURDATE(), INTERVAL 9 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 6, 10, DATE_SUB(CURDATE(), INTERVAL 10 DAY), DATE_ADD(CURDATE(), INTERVAL 11 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 7, 11, DATE_SUB(CURDATE(), INTERVAL 11 DAY), DATE_ADD(CURDATE(), INTERVAL 12 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 8, 12, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_ADD(CURDATE(), INTERVAL 13 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 9, 13, DATE_SUB(CURDATE(), INTERVAL 13 DAY), DATE_ADD(CURDATE(), INTERVAL 14 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 10, 14, DATE_SUB(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 15 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 4, 15, DATE_SUB(CURDATE(), INTERVAL 15 DAY), DATE_ADD(CURDATE(), INTERVAL 16 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 5, 16, DATE_SUB(CURDATE(), INTERVAL 16 DAY), DATE_ADD(CURDATE(), INTERVAL 17 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 6, 17, DATE_SUB(CURDATE(), INTERVAL 17 DAY), DATE_ADD(CURDATE(), INTERVAL 18 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 7, 18, DATE_SUB(CURDATE(), INTERVAL 18 DAY), DATE_ADD(CURDATE(), INTERVAL 19 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 8, 19, DATE_SUB(CURDATE(), INTERVAL 19 DAY), DATE_ADD(CURDATE(), INTERVAL 20 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 9, 20, DATE_SUB(CURDATE(), INTERVAL 20 DAY), DATE_ADD(CURDATE(), INTERVAL 21 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 10, 21, DATE_SUB(CURDATE(), INTERVAL 21 DAY), DATE_ADD(CURDATE(), INTERVAL 22 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 4, 22, DATE_SUB(CURDATE(), INTERVAL 22 DAY), DATE_ADD(CURDATE(), INTERVAL 23 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 5, 23, DATE_SUB(CURDATE(), INTERVAL 23 DAY), DATE_ADD(CURDATE(), INTERVAL 24 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 6, 24, DATE_SUB(CURDATE(), INTERVAL 24 DAY), DATE_ADD(CURDATE(), INTERVAL 25 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 7, 25, DATE_SUB(CURDATE(), INTERVAL 25 DAY), DATE_ADD(CURDATE(), INTERVAL 26 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 8, 26, DATE_SUB(CURDATE(), INTERVAL 26 DAY), DATE_ADD(CURDATE(), INTERVAL 27 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 9, 27, DATE_SUB(CURDATE(), INTERVAL 27 DAY), DATE_ADD(CURDATE(), INTERVAL 28 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 10, 28, DATE_SUB(CURDATE(), INTERVAL 28 DAY), DATE_ADD(CURDATE(), INTERVAL 29 DAY), NULL, 'active', 'Recent booking - last 30 days'
  UNION ALL SELECT 4, 29, DATE_SUB(CURDATE(), INTERVAL 29 DAY), DATE_ADD(CURDATE(), INTERVAL 30 DAY), NULL, 'active', 'Recent booking - last 30 days'
) AS seed
WHERE NOT EXISTS (
  SELECT 1
  FROM Rental r
  WHERE r.user_id = seed.user_id
    AND r.vehicle_id = seed.vehicle_id
    AND r.pickup_date = seed.pickup_date
    AND r.return_date = seed.return_date
    AND r.notes = seed.notes
);

-- Add invoices for recent rentals with paid status
INSERT INTO Invoice (rental_id, base_amount, late_fee, damage_fee, total_amount, payment_status, payment_method, issued_at)
SELECT
  r.rental_id,
  ROUND(vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1), 2) AS base_amount,
  0.00 AS late_fee,
  0.00 AS damage_fee,
  ROUND(vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1), 2) AS total_amount,
  'paid' AS payment_status,
  CASE MOD(r.rental_id, 4)
    WHEN 0 THEN 'card'
    WHEN 1 THEN 'gcash'
    WHEN 2 THEN 'bank_transfer'
    ELSE 'cash'
  END AS payment_method,
  TIMESTAMP(DATE_ADD(r.pickup_date, INTERVAL 1 DAY), SEC_TO_TIME(28800 + MOD(r.rental_id, 3600))) AS issued_at
FROM Rental r
JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
JOIN VehicleCategory vc ON vc.category_id = v.category_id
WHERE r.notes = 'Recent booking - last 30 days'
  AND NOT EXISTS (
    SELECT 1
    FROM Invoice i
    WHERE i.rental_id = r.rental_id
  );

-- ── 7. AdminContactMessage ───────────────────────────────────
CREATE TABLE IF NOT EXISTS AdminContactMessage (
  message_id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  full_name    VARCHAR(120) NOT NULL,
  email        VARCHAR(120) NOT NULL,
  phone        VARCHAR(30)  DEFAULT NULL,
  subject      VARCHAR(180) NOT NULL,
  message      TEXT         NOT NULL,
  admin_response TEXT       DEFAULT NULL,
  status       ENUM('new','read','resolved') NOT NULL DEFAULT 'new',
  created_at   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  responded_at TIMESTAMP NULL DEFAULT NULL,
  responded_by INT UNSIGNED DEFAULT NULL,
  PRIMARY KEY (message_id),
  KEY idx_admin_contact_status (status),
  KEY idx_admin_contact_created (created_at),
  KEY idx_admin_contact_responded_at (responded_at)
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
  user_id         INT UNSIGNED DEFAULT NULL,
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
  CONSTRAINT fk_pwdreset_user FOREIGN KEY (user_id)
    REFERENCES User(user_id) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO PasswordResetRequest (user_id, email, license_number, reason, status, requested_ip)
SELECT seed.user_id, seed.email, seed.license_number, seed.reason, seed.status, seed.requested_ip
FROM (
  SELECT 4 AS user_id, 'maria@email.com' AS email, 'N01-23-456789' AS license_number, 'I forgot my password after changing devices.' AS reason, 'pending' AS status, '127.0.0.1' AS requested_ip
  UNION ALL SELECT 5, 'jdc@email.com', 'N02-34-567890', 'Unable to sign in with previous credentials.', 'processing', '127.0.0.1'
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
         CONCAT(u.first_name,' ',u.last_name) AS customer_name,
         v.plate_number, v.brand, v.model,
         r.pickup_date, r.return_date
  FROM Rental r
  JOIN User u ON r.user_id = u.user_id
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
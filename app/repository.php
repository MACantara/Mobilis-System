<?php
declare(strict_types=1);

if (!function_exists('dbNow')) {
    function dbNow(): string
    {
        return date('Y-m-d');
    }
}

if (!function_exists('fallbackMetrics')) {
    function fallbackMetrics(): array
    {
        return [
            'total_fleet' => 48,
            'active_rentals' => 31,
            'revenue_today' => 28450.00,
            'utilization_rate' => 64,
        ];
    }
}

if (!function_exists('getDashboardMetrics')) {
    function getDashboardMetrics(): array
    {
        if (!dbConnected()) {
            return fallbackMetrics();
        }

        try {
            $pdo = db();
            $totalFleet = (int) $pdo->query('SELECT COUNT(*) FROM Vehicle')->fetchColumn();
            $activeRentals = (int) $pdo->query("SELECT COUNT(*) FROM Rental WHERE status = 'active'")->fetchColumn();
            $revenueTodayStmt = $pdo->prepare('SELECT COALESCE(SUM(total_amount), 0) FROM Invoice WHERE DATE(issued_at) = :today');
            $revenueTodayStmt->execute(['today' => dbNow()]);
            $revenueToday = (float) $revenueTodayStmt->fetchColumn();
            $utilization = $totalFleet > 0 ? (int) round(($activeRentals / $totalFleet) * 100) : 0;

            return [
                'total_fleet' => $totalFleet,
                'active_rentals' => $activeRentals,
                'revenue_today' => $revenueToday,
                'utilization_rate' => $utilization,
            ];
        } catch (Throwable $e) {
            return fallbackMetrics();
        }
    }
}

if (!function_exists('getVehicleStatusList')) {
    function getVehicleStatusList(int $limit = 6): array
    {
        if (!dbConnected()) {
            return [
                ['icon' => '🚙', 'name' => 'Toyota Fortuner', 'plate' => 'ABC-1234', 'status' => 'rented'],
                ['icon' => '🚗', 'name' => 'Honda Civic', 'plate' => 'XYZ-5678', 'status' => 'available'],
                ['icon' => '🚐', 'name' => 'Toyota HiAce', 'plate' => 'DEF-9012', 'status' => 'maintenance'],
                ['icon' => '🚗', 'name' => 'Mitsubishi Xpander', 'plate' => 'GHI-3456', 'status' => 'reserved'],
                ['icon' => '🛻', 'name' => 'Ford Ranger', 'plate' => 'JKL-7890', 'status' => 'available'],
            ];
        }

        try {
            $sql = "
                SELECT
                    CASE
                        WHEN LOWER(vc.category_name) LIKE '%motorcycle%' THEN '🏍️'
                        WHEN LOWER(vc.category_name) LIKE '%pickup%' THEN '🛻'
                        WHEN LOWER(vc.category_name) LIKE '%van%' THEN '🚐'
                        WHEN LOWER(vc.category_name) LIKE '%suv%' THEN '🚙'
                        ELSE '🚗'
                    END AS icon,
                    CONCAT(v.brand, ' ', v.model) AS name,
                    REPLACE(v.plate_number, ' ', '-') AS plate,
                    v.status
                FROM Vehicle v
                INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                ORDER BY FIELD(v.status, 'rented', 'available', 'maintenance'), v.vehicle_id ASC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getUpcomingBookings')) {
    function getUpcomingBookings(int $limit = 6): array
    {
        if (!dbConnected()) {
            return [
                ['customer_name' => 'Maria Reyes', 'vehicle_name' => 'Toyota Fortuner', 'pickup_date' => '2026-04-13', 'return_date' => '2026-04-16', 'status' => 'confirmed'],
                ['customer_name' => 'Juan dela Cruz', 'vehicle_name' => 'Honda Civic', 'pickup_date' => '2026-04-14', 'return_date' => '2026-04-14', 'status' => 'pending'],
                ['customer_name' => 'Ana Lim', 'vehicle_name' => 'Toyota HiAce', 'pickup_date' => '2026-04-15', 'return_date' => '2026-04-20', 'status' => 'confirmed'],
            ];
        }

        try {
            $sql = "
                SELECT
                    CONCAT(c.first_name, ' ', c.last_name) AS customer_name,
                    CONCAT(v.brand, ' ', v.model) AS vehicle_name,
                    r.pickup_date,
                    r.return_date,
                    CASE
                        WHEN r.status = 'active' THEN 'confirmed'
                        WHEN r.status = 'completed' THEN 'completed'
                        WHEN r.status = 'cancelled' THEN 'cancelled'
                        ELSE 'pending'
                    END AS status
                FROM Rental r
                INNER JOIN Customer c ON c.customer_id = r.customer_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                WHERE r.pickup_date >= :today
                ORDER BY r.pickup_date ASC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':today', dbNow());
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getCustomers')) {
    function getCustomers(int $limit = 10): array
    {
        if (!dbConnected()) {
            return [
                [
                    'customer_id' => 1,
                    'name' => 'Maria Reyes',
                    'email' => 'maria@email.com',
                    'phone' => '+63 917 123 4567',
                    'license_number' => 'N01-23-456789',
                    'license_expiry' => '2028-03-20',
                    'address' => 'Makati City, Metro Manila',
                    'created_at' => '2023-01-12 09:30:00',
                    'bookings' => 18,
                    'spent' => 142000,
                    'avg_rental_days' => 3.2,
                    'no_shows' => 0,
                    'tier' => 'VIP',
                ],
                [
                    'customer_id' => 2,
                    'name' => 'Juan dela Cruz',
                    'email' => 'jdc@email.com',
                    'phone' => '+63 918 234 5678',
                    'license_number' => 'N02-34-567890',
                    'license_expiry' => '2027-08-30',
                    'address' => 'Quezon City, Metro Manila',
                    'created_at' => '2024-04-03 11:00:00',
                    'bookings' => 7,
                    'spent' => 38400,
                    'avg_rental_days' => 2.1,
                    'no_shows' => 1,
                    'tier' => 'Regular',
                ],
            ];
        }

        try {
            $sql = "
                SELECT
                    c.customer_id,
                    CONCAT(c.first_name, ' ', c.last_name) AS name,
                    c.email,
                    c.phone,
                    c.license_number,
                    c.license_expiry,
                    c.address,
                    c.created_at,
                    COUNT(DISTINCT r.rental_id) AS bookings,
                    COALESCE(SUM(i.total_amount), 0) AS spent,
                    COALESCE(AVG(GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1)), 0) AS avg_rental_days,
                    SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) AS no_shows
                FROM Customer c
                LEFT JOIN Rental r ON r.customer_id = c.customer_id
                LEFT JOIN Invoice i ON i.rental_id = r.rental_id
                GROUP BY c.customer_id, c.first_name, c.last_name, c.email, c.phone, c.license_number, c.license_expiry, c.address, c.created_at
                ORDER BY spent DESC, bookings DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll();

            foreach ($rows as &$row) {
                $spent = (float) ($row['spent'] ?? 0);
                $bookings = (int) ($row['bookings'] ?? 0);

                if ($spent >= 100000 || $bookings >= 20) {
                    $row['tier'] = 'VIP';
                } elseif ($bookings <= 2) {
                    $row['tier'] = 'New';
                } else {
                    $row['tier'] = 'Regular';
                }
            }

            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getCustomerRecentBookings')) {
    function getCustomerRecentBookings(int $customerId, int $limit = 3): array
    {
        if (!dbConnected()) {
            return [
                ['label' => 'Toyota Fortuner · Apr 13-16', 'status' => 'confirmed'],
                ['label' => 'Ford Ranger · Mar 28-30', 'status' => 'completed'],
                ['label' => 'HiAce Van · Mar 10-14', 'status' => 'completed'],
            ];
        }

        try {
            $sql = "
                SELECT
                    CONCAT(
                        v.brand, ' ', v.model,
                        ' · ',
                        DATE_FORMAT(r.pickup_date, '%b %d'),
                        '-',
                        DATE_FORMAT(r.return_date, '%d')
                    ) AS label,
                    CASE
                        WHEN r.status = 'active' THEN 'confirmed'
                        WHEN r.status = 'completed' THEN 'completed'
                        WHEN r.status = 'cancelled' THEN 'cancelled'
                        ELSE 'pending'
                    END AS status
                FROM Rental r
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                WHERE r.customer_id = :customer_id
                ORDER BY r.pickup_date DESC
                LIMIT :limit
            ";

            $stmt = db()->prepare($sql);
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getCustomerById')) {
    function getCustomerById(int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }

        if (!dbConnected()) {
            foreach (getCustomers(50) as $customer) {
                if ((int) ($customer['customer_id'] ?? 0) === $customerId) {
                    return $customer;
                }
            }
            return null;
        }

        try {
            $sql = "
                SELECT
                    c.customer_id,
                    c.first_name,
                    c.last_name,
                    CONCAT(c.first_name, ' ', c.last_name) AS name,
                    c.email,
                    c.phone,
                    c.license_number,
                    c.license_expiry,
                    c.address,
                    c.created_at
                FROM Customer c
                WHERE c.customer_id = :customer_id
                LIMIT 1
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute(['customer_id' => $customerId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('createCustomer')) {
    function createCustomer(array $payload): array
    {
        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        try {
            $sql = "
                INSERT INTO Customer (
                    first_name,
                    last_name,
                    email,
                    phone,
                    license_number,
                    license_expiry,
                    address
                ) VALUES (
                    :first_name,
                    :last_name,
                    :email,
                    :phone,
                    :license_number,
                    :license_expiry,
                    :address
                )
            ";

            $stmt = db()->prepare($sql);
            $stmt->execute([
                'first_name' => trim((string) ($payload['first_name'] ?? '')),
                'last_name' => trim((string) ($payload['last_name'] ?? '')),
                'email' => strtolower(trim((string) ($payload['email'] ?? ''))),
                'phone' => trim((string) ($payload['phone'] ?? '')),
                'license_number' => trim((string) ($payload['license_number'] ?? '')),
                'license_expiry' => trim((string) ($payload['license_expiry'] ?? '')),
                'address' => trim((string) ($payload['address'] ?? '')),
            ]);

            return ['ok' => true, 'customer_id' => (int) db()->lastInsertId()];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not create customer. Email or license number might already exist.'];
        }
    }
}

if (!function_exists('updateCustomer')) {
    function updateCustomer(int $customerId, array $payload): array
    {
        if ($customerId <= 0) {
            return ['ok' => false, 'error' => 'Invalid customer ID.'];
        }

        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        try {
            $sql = "
                UPDATE Customer
                SET
                    first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone,
                    license_number = :license_number,
                    license_expiry = :license_expiry,
                    address = :address
                WHERE customer_id = :customer_id
            ";

            $stmt = db()->prepare($sql);
            $stmt->execute([
                'customer_id' => $customerId,
                'first_name' => trim((string) ($payload['first_name'] ?? '')),
                'last_name' => trim((string) ($payload['last_name'] ?? '')),
                'email' => strtolower(trim((string) ($payload['email'] ?? ''))),
                'phone' => trim((string) ($payload['phone'] ?? '')),
                'license_number' => trim((string) ($payload['license_number'] ?? '')),
                'license_expiry' => trim((string) ($payload['license_expiry'] ?? '')),
                'address' => trim((string) ($payload['address'] ?? '')),
            ]);

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not update customer. Email or license number might already exist.'];
        }
    }
}

if (!function_exists('createRentalBooking')) {
    function createRentalBooking(int $customerId, int $vehicleId, string $pickupDate, string $returnDate, string $notes = ''): array
    {
        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        if ($customerId <= 0 || $vehicleId <= 0) {
            return ['ok' => false, 'error' => 'Please select valid customer and vehicle.'];
        }

        if ($pickupDate === '' || $returnDate === '' || $returnDate < $pickupDate) {
            return ['ok' => false, 'error' => 'Please provide a valid pickup and return date.'];
        }

        try {
            $pdo = db();
            $pdo->beginTransaction();

            $vehicleStmt = $pdo->prepare('SELECT status FROM Vehicle WHERE vehicle_id = :vehicle_id FOR UPDATE');
            $vehicleStmt->execute(['vehicle_id' => $vehicleId]);
            $vehicle = $vehicleStmt->fetch();

            if (!$vehicle) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Vehicle not found.'];
            }

            if (($vehicle['status'] ?? '') === 'maintenance') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Selected vehicle is under maintenance.'];
            }

            $insertRental = $pdo->prepare(
                "INSERT INTO Rental (customer_id, vehicle_id, pickup_date, return_date, status, notes)
                 VALUES (:customer_id, :vehicle_id, :pickup_date, :return_date, 'active', :notes)"
            );
            $insertRental->execute([
                'customer_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
                'notes' => trim($notes),
            ]);

            $rentalId = (int) $pdo->lastInsertId();

            $updateVehicle = $pdo->prepare("UPDATE Vehicle SET status = 'rented' WHERE vehicle_id = :vehicle_id");
            $updateVehicle->execute(['vehicle_id' => $vehicleId]);

            $pdo->commit();
            return ['ok' => true, 'rental_id' => $rentalId];
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'Could not create booking right now.'];
        }
    }
}

if (!function_exists('getBookings')) {
    function getBookings(int $limit = 10): array
    {
        if (!dbConnected()) {
            return [
                ['rental_id' => 412, 'customer_id' => 1, 'customer' => 'Maria Reyes', 'customer_email' => 'maria@email.com', 'vehicle_id' => 1, 'vehicle' => 'Toyota Fortuner', 'pickup_date' => '2026-04-13', 'return_date' => '2026-04-16', 'status' => 'confirmed', 'payment_status' => 'paid', 'days' => 3, 'total' => 10500],
                ['rental_id' => 411, 'customer_id' => 2, 'customer' => 'Juan dela Cruz', 'customer_email' => 'jdc@email.com', 'vehicle_id' => 2, 'vehicle' => 'Honda Civic', 'pickup_date' => '2026-04-14', 'return_date' => '2026-04-14', 'status' => 'pending', 'payment_status' => 'unpaid', 'days' => 1, 'total' => 2200],
                ['rental_id' => 410, 'customer_id' => 3, 'customer' => 'Ana Lim', 'customer_email' => 'ana@email.com', 'vehicle_id' => 3, 'vehicle' => 'Toyota HiAce', 'pickup_date' => '2026-04-15', 'return_date' => '2026-04-20', 'status' => 'confirmed', 'payment_status' => 'paid', 'days' => 5, 'total' => 20000],
                ['rental_id' => 409, 'customer_id' => 4, 'customer' => 'Ramon Santos', 'customer_email' => 'ramon@email.com', 'vehicle_id' => 4, 'vehicle' => 'Ford Ranger', 'pickup_date' => '2026-04-17', 'return_date' => '2026-04-19', 'status' => 'confirmed', 'payment_status' => 'unpaid', 'days' => 2, 'total' => 6400],
                ['rental_id' => 408, 'customer_id' => 5, 'customer' => 'Pedro Cruz', 'customer_email' => 'pedz@email.com', 'vehicle_id' => 5, 'vehicle' => 'Mitsubishi Xpander', 'pickup_date' => '2026-04-10', 'return_date' => '2026-04-12', 'status' => 'completed', 'payment_status' => 'paid', 'days' => 2, 'total' => 5600],
                ['rental_id' => 407, 'customer_id' => 6, 'customer' => 'Lisa Garcia', 'customer_email' => 'lisag@email.com', 'vehicle_id' => 6, 'vehicle' => 'Hyundai Tucson', 'pickup_date' => '2026-04-08', 'return_date' => '2026-04-08', 'status' => 'cancelled', 'payment_status' => 'unpaid', 'days' => 1, 'total' => 3800],
            ];
        }

        try {
            $sql = "
                SELECT
                    r.rental_id,
                    r.customer_id,
                    CONCAT(c.first_name, ' ', c.last_name) AS customer,
                    c.email AS customer_email,
                    r.vehicle_id,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    r.pickup_date,
                    r.return_date,
                    r.status,
                    i.payment_status,
                    GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1) AS days,
                    COALESCE(i.total_amount, vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1)) AS total
                FROM Rental r
                INNER JOIN Customer c ON c.customer_id = r.customer_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                LEFT JOIN Invoice i ON i.rental_id = r.rental_id
                ORDER BY r.pickup_date DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                if ($row['status'] === 'active') {
                    $row['status'] = 'confirmed';
                }
                if (!isset($row['payment_status']) || $row['payment_status'] === null) {
                    $row['payment_status'] = 'unpaid';
                }
            }
            return $rows;
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getBookingById')) {
    function getBookingById(int $rentalId): ?array
    {
        if ($rentalId <= 0) {
            return null;
        }

        foreach (getBookings(500) as $booking) {
            if ((int) ($booking['rental_id'] ?? 0) === $rentalId) {
                return $booking;
            }
        }

        return null;
    }
}

if (!function_exists('updateBookingRecord')) {
    function updateBookingRecord(int $rentalId, string $pickupDate, string $returnDate, string $status, string $notes = ''): array
    {
        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        if ($rentalId <= 0) {
            return ['ok' => false, 'error' => 'Invalid booking ID.'];
        }

        $statusMap = [
            'pending' => 'active',
            'confirmed' => 'active',
            'active' => 'active',
            'completed' => 'completed',
            'cancelled' => 'cancelled',
        ];

        $normalizedStatus = strtolower(trim($status));
        if (!isset($statusMap[$normalizedStatus])) {
            return ['ok' => false, 'error' => 'Invalid booking status.'];
        }

        if ($pickupDate === '' || $returnDate === '' || $returnDate < $pickupDate) {
            return ['ok' => false, 'error' => 'Invalid date range.'];
        }

        try {
            $pdo = db();
            $pdo->beginTransaction();

            $rentalStmt = $pdo->prepare('SELECT rental_id, vehicle_id FROM Rental WHERE rental_id = :rental_id FOR UPDATE');
            $rentalStmt->execute(['rental_id' => $rentalId]);
            $rental = $rentalStmt->fetch();

            if (!$rental) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Booking not found.'];
            }

            $dbStatus = $statusMap[$normalizedStatus];

            $updateRental = $pdo->prepare(
                "UPDATE Rental
                 SET pickup_date = :pickup_date,
                     return_date = :return_date,
                     status = :status,
                     notes = :notes,
                     actual_return = CASE WHEN :status = 'completed' THEN CURRENT_DATE ELSE NULL END
                 WHERE rental_id = :rental_id"
            );

            $updateRental->execute([
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
                'status' => $dbStatus,
                'notes' => $notes,
                'rental_id' => $rentalId,
            ]);

            $vehicleStatus = $dbStatus === 'active' ? 'rented' : 'available';
            $updateVehicle = $pdo->prepare('UPDATE Vehicle SET status = :status WHERE vehicle_id = :vehicle_id');
            $updateVehicle->execute([
                'status' => $vehicleStatus,
                'vehicle_id' => (int) $rental['vehicle_id'],
            ]);

            $pdo->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'Could not update booking.'];
        }
    }
}

if (!function_exists('applyBookingAction')) {
    function applyBookingAction(int $rentalId, string $action): array
    {
        $action = strtolower(trim($action));

        if ($action === 'remind') {
            return ['ok' => true, 'notice' => 'Payment reminder queued.'];
        }

        if ($action === 'approve') {
            $booking = getBookingById($rentalId);
            if ($booking === null) {
                return ['ok' => false, 'error' => 'Booking not found.'];
            }

            return updateBookingRecord(
                $rentalId,
                (string) ($booking['pickup_date'] ?? ''),
                (string) ($booking['return_date'] ?? ''),
                'confirmed',
                ''
            );
        }

        if ($action === 'cancel' || $action === 'complete') {
            $booking = getBookingById($rentalId);
            if ($booking === null) {
                return ['ok' => false, 'error' => 'Booking not found.'];
            }

            $status = $action === 'cancel' ? 'cancelled' : 'completed';
            return updateBookingRecord(
                $rentalId,
                (string) ($booking['pickup_date'] ?? ''),
                (string) ($booking['return_date'] ?? ''),
                $status,
                ''
            );
        }

        return ['ok' => false, 'error' => 'Unsupported action.'];
    }
}

if (!function_exists('getVehicles')) {
    function getVehicles(int $limit = 12): array
    {
        if (!dbConnected()) {
            return [
                ['vehicle_id' => 1, 'name' => 'Toyota Fortuner', 'plate' => 'ABC-1234', 'category_name' => 'SUV', 'year' => 2022, 'mileage_km' => 38200, 'status' => 'rented', 'daily_rate' => 3500],
                ['vehicle_id' => 2, 'name' => 'Honda Civic', 'plate' => 'XYZ-5678', 'category_name' => 'Sedan', 'year' => 2023, 'mileage_km' => 12500, 'status' => 'available', 'daily_rate' => 2200],
            ];
        }

        try {
            $sql = "
                SELECT
                    v.vehicle_id,
                    CONCAT(v.brand, ' ', v.model) AS name,
                    REPLACE(v.plate_number, ' ', '-') AS plate,
                    vc.category_name,
                    v.year,
                    v.mileage_km,
                    v.status,
                    vc.daily_rate
                FROM Vehicle v
                INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                ORDER BY v.vehicle_id DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getFleetByCategory')) {
    function getFleetByCategory(): array
    {
        if (!dbConnected()) {
            return [
                ['category_name' => 'Sedans', 'utilization' => 70],
                ['category_name' => 'SUVs', 'utilization' => 55],
                ['category_name' => 'Vans', 'utilization' => 40],
                ['category_name' => 'Pick-ups', 'utilization' => 80],
            ];
        }

        try {
            $sql = "
                SELECT
                    vc.category_name,
                    ROUND(SUM(CASE WHEN v.status = 'rented' THEN 1 ELSE 0 END) * 100 / NULLIF(COUNT(v.vehicle_id), 0), 0) AS utilization
                FROM VehicleCategory vc
                LEFT JOIN Vehicle v ON v.category_id = vc.category_id
                GROUP BY vc.category_id, vc.category_name
                ORDER BY vc.category_name ASC
            ";
            return db()->query($sql)->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getMaintenanceBacklog')) {
    function getMaintenanceBacklog(): array
    {
        if (!dbConnected()) {
            return [
                ['vehicle' => 'Mitsubishi Montero', 'mileage_km' => 95000, 'last_service' => '2026-04-01', 'service_type' => 'Engine overhaul'],
                ['vehicle' => 'Toyota HiAce', 'mileage_km' => 130000, 'last_service' => '2026-02-20', 'service_type' => 'Tire rotation'],
            ];
        }

        try {
            $threshold = (int) appConfig()['mileage_alert_threshold'];
            $sql = "
                SELECT
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    v.mileage_km,
                    MAX(m.service_date) AS last_service,
                    SUBSTRING_INDEX(GROUP_CONCAT(m.service_type ORDER BY m.service_date DESC SEPARATOR ','), ',', 1) AS service_type
                FROM Vehicle v
                LEFT JOIN MaintenanceLog m ON m.vehicle_id = v.vehicle_id
                WHERE v.mileage_km >= :threshold OR v.status = 'maintenance'
                GROUP BY v.vehicle_id, v.brand, v.model, v.mileage_km
                ORDER BY v.mileage_km DESC
                LIMIT 8
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute(['threshold' => $threshold]);
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getPayments')) {
    function getPayments(int $limit = 20): array
    {
        if (!dbConnected()) {
            return [
                ['invoice_id' => 1001, 'customer' => 'Maria Reyes', 'vehicle' => 'Toyota Fortuner', 'total_amount' => 10500, 'payment_status' => 'paid', 'issued_at' => '2026-04-13'],
                ['invoice_id' => 1002, 'customer' => 'Juan dela Cruz', 'vehicle' => 'Honda Civic', 'total_amount' => 2200, 'payment_status' => 'unpaid', 'issued_at' => '2026-04-14'],
                ['invoice_id' => 1003, 'customer' => 'Ana Lim', 'vehicle' => 'Toyota HiAce', 'total_amount' => 20000, 'payment_status' => 'partial', 'issued_at' => '2026-04-15'],
            ];
        }

        try {
            $sql = "
                SELECT
                    i.invoice_id,
                    CONCAT(c.first_name, ' ', c.last_name) AS customer,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    i.total_amount,
                    i.payment_status,
                    DATE(i.issued_at) AS issued_at
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN Customer c ON c.customer_id = r.customer_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                ORDER BY i.issued_at DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('submitAdminContactMessage')) {
    function submitAdminContactMessage(string $fullName, string $email, string $phone, string $subject, string $message): bool
    {
        if (!dbConnected()) {
            return false;
        }

        try {
            $sql = "
                INSERT INTO AdminContactMessage (full_name, email, phone, subject, message, status)
                VALUES (:full_name, :email, :phone, :subject, :message, 'new')
            ";
            $stmt = db()->prepare($sql);
            return $stmt->execute([
                'full_name' => $fullName,
                'email' => $email,
                'phone' => $phone,
                'subject' => $subject,
                'message' => $message,
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('submitPasswordResetRequest')) {
    function submitPasswordResetRequest(string $email, ?string $licenseNumber, string $reason, ?int $customerId = null): bool
    {
        if (!dbConnected()) {
            return false;
        }

        try {
            $sql = "
                INSERT INTO PasswordResetRequest (
                    customer_id,
                    email,
                    license_number,
                    reason,
                    status,
                    requested_ip,
                    user_agent
                ) VALUES (
                    :customer_id,
                    :email,
                    :license_number,
                    :reason,
                    'pending',
                    :requested_ip,
                    :user_agent
                )
            ";
            $stmt = db()->prepare($sql);
            $customerValue = $customerId !== null ? $customerId : null;
            $licenseValue = $licenseNumber !== null && trim($licenseNumber) !== '' ? trim($licenseNumber) : null;

            return $stmt->execute([
                'customer_id' => $customerValue,
                'email' => $email,
                'license_number' => $licenseValue,
                'reason' => $reason,
                'requested_ip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
                'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
            ]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('getAdminContactMessages')) {
    function getAdminContactMessages(int $limit = 25): array
    {
        if (!dbConnected()) {
            return [
                [
                    'message_id' => 1,
                    'full_name' => 'Ana Lim',
                    'email' => 'ana@email.com',
                    'phone' => '+63 919 345 6789',
                    'subject' => 'Need account activation',
                    'status' => 'new',
                    'created_at' => '2026-04-19 09:30:00',
                ],
            ];
        }

        try {
            $sql = "
                SELECT
                    message_id,
                    full_name,
                    email,
                    phone,
                    subject,
                    status,
                    created_at
                FROM AdminContactMessage
                ORDER BY created_at DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getPasswordResetRequests')) {
    function getPasswordResetRequests(int $limit = 25): array
    {
        if (!dbConnected()) {
            return [
                [
                    'request_id' => 1,
                    'email' => 'juan@email.com',
                    'license_number' => 'N01-23-456789',
                    'status' => 'pending',
                    'created_at' => '2026-04-19 10:00:00',
                ],
            ];
        }

        try {
            $sql = "
                SELECT
                    request_id,
                    email,
                    license_number,
                    status,
                    created_at
                FROM PasswordResetRequest
                ORDER BY created_at DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

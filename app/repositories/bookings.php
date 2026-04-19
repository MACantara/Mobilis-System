<?php
declare(strict_types=1);

if (!function_exists('createRentalBooking')) {
    function createRentalBooking(int $customerId, int $vehicleId, string $pickupDate, string $returnDate, string $notes = ''): array
    {
        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        if ($customerId <= 0 || $vehicleId <= 0) {
            return ['ok' => false, 'error' => 'Please select valid customer and vehicle.'];
        }

        $pickupTs = strtotime($pickupDate);
        $returnTs = strtotime($returnDate);

        if ($pickupDate === '' || $returnDate === '' || $pickupTs === false || $returnTs === false || $returnTs < $pickupTs) {
            return ['ok' => false, 'error' => 'Please provide a valid pickup and return date.'];
        }

        $days = max(1, (int) ceil(($returnTs - $pickupTs) / 86400));

        try {
            $pdo = db();
            $pdo->beginTransaction();

            $vehicleStmt = $pdo->prepare(
                'SELECT v.status, vc.daily_rate
                 FROM Vehicle v
                 INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                 WHERE v.vehicle_id = :vehicle_id
                 FOR UPDATE'
            );
            $vehicleStmt->execute(['vehicle_id' => $vehicleId]);
            $vehicle = $vehicleStmt->fetch();

            if (!$vehicle) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Vehicle not found.'];
            }

            if (($vehicle['status'] ?? '') !== 'available') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Selected vehicle is not available for booking.'];
            }

            $insertRental = $pdo->prepare(
                "INSERT INTO Rental (user_id, vehicle_id, pickup_date, return_date, status, notes)
                 VALUES (:user_id, :vehicle_id, :pickup_date, :return_date, 'active', :notes)"
            );
            $insertRental->execute([
                'user_id' => $customerId,
                'vehicle_id' => $vehicleId,
                'pickup_date' => $pickupDate,
                'return_date' => $returnDate,
                'notes' => trim($notes),
            ]);

            $rentalId = (int) $pdo->lastInsertId();

            $baseAmount = (float) ($vehicle['daily_rate'] ?? 0) * $days;
            $insertInvoice = $pdo->prepare(
                'INSERT INTO Invoice (rental_id, base_amount, late_fee, damage_fee, total_amount, payment_status)
                 VALUES (:rental_id, :base_amount, 0, 0, :total_amount, :payment_status)'
            );
            $insertInvoice->execute([
                'rental_id' => $rentalId,
                'base_amount' => $baseAmount,
                'total_amount' => $baseAmount,
                'payment_status' => 'unpaid',
            ]);

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
                ['rental_id' => 412, 'user_id' => 4, 'customer' => 'Maria Reyes', 'customer_email' => 'maria@email.com', 'vehicle_id' => 1, 'vehicle' => 'Toyota Fortuner', 'pickup_date' => '2026-04-13', 'return_date' => '2026-04-16', 'status' => 'confirmed', 'payment_status' => 'paid', 'days' => 3, 'total' => 10500],
                ['rental_id' => 411, 'user_id' => 5, 'customer' => 'Juan dela Cruz', 'customer_email' => 'jdc@email.com', 'vehicle_id' => 2, 'vehicle' => 'Honda Civic', 'pickup_date' => '2026-04-14', 'return_date' => '2026-04-14', 'status' => 'pending', 'payment_status' => 'unpaid', 'days' => 1, 'total' => 2200],
                ['rental_id' => 410, 'user_id' => 6, 'customer' => 'Ana Lim', 'customer_email' => 'ana@email.com', 'vehicle_id' => 3, 'vehicle' => 'Toyota HiAce', 'pickup_date' => '2026-04-15', 'return_date' => '2026-04-20', 'status' => 'confirmed', 'payment_status' => 'paid', 'days' => 5, 'total' => 20000],
                ['rental_id' => 409, 'user_id' => 7, 'customer' => 'Ramon Santos', 'customer_email' => 'ramon@email.com', 'vehicle_id' => 4, 'vehicle' => 'Ford Ranger', 'pickup_date' => '2026-04-17', 'return_date' => '2026-04-19', 'status' => 'confirmed', 'payment_status' => 'unpaid', 'days' => 2, 'total' => 6400],
                ['rental_id' => 408, 'user_id' => 8, 'customer' => 'Pedro Cruz', 'customer_email' => 'pedz@email.com', 'vehicle_id' => 5, 'vehicle' => 'Mitsubishi Xpander', 'pickup_date' => '2026-04-10', 'return_date' => '2026-04-12', 'status' => 'completed', 'payment_status' => 'paid', 'days' => 2, 'total' => 5600],
                ['rental_id' => 407, 'user_id' => 9, 'customer' => 'Lisa Garcia', 'customer_email' => 'lisag@email.com', 'vehicle_id' => 6, 'vehicle' => 'Hyundai Tucson', 'pickup_date' => '2026-04-08', 'return_date' => '2026-04-08', 'status' => 'cancelled', 'payment_status' => 'unpaid', 'days' => 1, 'total' => 3800],
            ];
        }

        try {
            $sql = "
                SELECT
                    r.rental_id,
                    r.user_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer,
                    u.email AS customer_email,
                    r.vehicle_id,
                    i.invoice_id,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    r.pickup_date,
                    r.return_date,
                    r.status,
                    i.payment_status,
                    GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1) AS days,
                    COALESCE(i.total_amount, vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1)) AS total
                FROM Rental r
                INNER JOIN User u ON u.user_id = r.user_id
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

if (!function_exists('getCustomerBookings')) {
    function getCustomerBookings(string $customerEmail, int $limit = 10): array
    {
        if (!dbConnected()) {
            return [
                ['rental_id' => 412, 'vehicle_id' => 1, 'vehicle' => 'Toyota Fortuner', 'pickup_date' => '2026-04-13', 'return_date' => '2026-04-16', 'status' => 'confirmed', 'payment_status' => 'paid', 'days' => 3, 'total' => 10500],
                ['rental_id' => 408, 'vehicle_id' => 5, 'vehicle' => 'Mitsubishi Xpander', 'pickup_date' => '2026-04-10', 'return_date' => '2026-04-12', 'status' => 'completed', 'payment_status' => 'paid', 'days' => 2, 'total' => 5600],
            ];
        }

        try {
            $sql = "
                SELECT
                    r.rental_id,
                    r.user_id,
                    r.vehicle_id,
                    i.invoice_id,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    r.pickup_date,
                    r.return_date,
                    r.status,
                    i.payment_status,
                    GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1) AS days,
                    COALESCE(i.total_amount, vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1)) AS total
                FROM Rental r
                INNER JOIN User u ON u.user_id = r.user_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                LEFT JOIN Invoice i ON i.rental_id = r.rental_id
                WHERE u.email = :email
                ORDER BY r.pickup_date DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':email', $customerEmail);
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

if (!function_exists('getCustomerBookingsByCustomerId')) {
    function getCustomerBookingsByCustomerId(int $customerId, int $limit = 10): array
    {
        if ($customerId <= 0) {
            return [];
        }

        if (!dbConnected()) {
            return array_values(array_filter(
                getBookings(max($limit, 500)),
                static fn(array $booking): bool => (int) ($booking['user_id'] ?? 0) === $customerId
            ));
        }

        try {
            $sql = "
                SELECT
                    r.rental_id,
                    r.user_id,
                    r.vehicle_id,
                    i.invoice_id,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    r.pickup_date,
                    r.return_date,
                    r.status,
                    i.payment_status,
                    GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1) AS days,
                    COALESCE(i.total_amount, vc.daily_rate * GREATEST(DATEDIFF(r.return_date, r.pickup_date), 1)) AS total
                FROM Rental r
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                INNER JOIN VehicleCategory vc ON vc.category_id = v.category_id
                LEFT JOIN Invoice i ON i.rental_id = r.rental_id
                WHERE r.user_id = :user_id
                ORDER BY r.pickup_date DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':user_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                if (($row['status'] ?? '') === 'active') {
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

if (!function_exists('getCustomerBookingById')) {
    function getCustomerBookingById(int $customerId, int $rentalId): ?array
    {
        if ($customerId <= 0 || $rentalId <= 0) {
            return null;
        }

        foreach (getCustomerBookingsByCustomerId($customerId, 500) as $booking) {
            if ((int) ($booking['rental_id'] ?? 0) === $rentalId) {
                return $booking;
            }
        }

        return null;
    }
}

if (!function_exists('cancelCustomerBooking')) {
    function cancelCustomerBooking(int $customerId, int $rentalId, string $notes = ''): array
    {
        if ($customerId <= 0 || $rentalId <= 0) {
            return ['ok' => false, 'error' => 'Invalid booking reference.'];
        }

        if (!dbConnected()) {
            $booking = getCustomerBookingById($customerId, $rentalId);
            if ($booking === null) {
                return ['ok' => false, 'error' => 'Booking not found.'];
            }

            $status = strtolower((string) ($booking['status'] ?? 'pending'));
            if ($status === 'completed' || $status === 'cancelled') {
                return ['ok' => false, 'error' => 'This booking can no longer be cancelled.'];
            }

            return ['ok' => true];
        }

        try {
            $pdo = db();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT rental_id, vehicle_id, status, notes
                 FROM Rental
                 WHERE rental_id = :rental_id AND user_id = :user_id
                 FOR UPDATE'
            );
            $stmt->execute([
                'rental_id' => $rentalId,
                'user_id' => $customerId,
            ]);
            $booking = $stmt->fetch();

            if (!$booking) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Booking not found.'];
            }

            $status = strtolower((string) ($booking['status'] ?? 'pending'));
            if ($status === 'completed' || $status === 'cancelled') {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'This booking can no longer be cancelled.'];
            }

            $existingNotes = trim((string) ($booking['notes'] ?? ''));
            $cancelNotes = trim($notes);
            $mergedNotes = $existingNotes;
            if ($cancelNotes !== '') {
                $mergedNotes = $existingNotes === ''
                    ? 'Customer cancellation: ' . $cancelNotes
                    : $existingNotes . "\nCustomer cancellation: " . $cancelNotes;
            }

            $updateRental = $pdo->prepare(
                'UPDATE Rental
                 SET status = :status, notes = :notes
                 WHERE rental_id = :rental_id'
            );
            $updateRental->execute([
                'status' => 'cancelled',
                'notes' => $mergedNotes,
                'rental_id' => $rentalId,
            ]);

            $updateVehicle = $pdo->prepare(
                "UPDATE Vehicle
                 SET status = 'available'
                 WHERE vehicle_id = :vehicle_id AND status = 'rented'"
            );
            $updateVehicle->execute([
                'vehicle_id' => (int) ($booking['vehicle_id'] ?? 0),
            ]);

            $pdo->commit();
            return ['ok' => true];
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            return ['ok' => false, 'error' => 'Could not cancel booking right now.'];
        }
    }
}

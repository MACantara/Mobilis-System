<?php
declare(strict_types=1);

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

if (!function_exists('getCustomerByEmail')) {
    function getCustomerByEmail(string $email): ?array
    {
        if (!dbConnected()) {
            foreach (getCustomers(50) as $customer) {
                if (($customer['email'] ?? '') === strtolower($email)) {
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
                    c.address
                FROM Customer c
                WHERE c.email = :email
                LIMIT 1
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute(['email' => strtolower($email)]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('getCustomerByName')) {
    function getCustomerByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        if (!dbConnected()) {
            foreach (getCustomers(200) as $customer) {
                $candidate = strtolower(trim((string) ($customer['name'] ?? '')));
                if ($candidate === strtolower($name)) {
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
                    c.address
                FROM Customer c
                WHERE LOWER(CONCAT(c.first_name, ' ', c.last_name)) = LOWER(:name)
                LIMIT 1
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute(['name' => $name]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('resolveCustomerForUser')) {
    function resolveCustomerForUser(?array $user): ?array
    {
        if (!is_array($user)) {
            return null;
        }

        $email = strtolower(trim((string) ($user['email'] ?? '')));
        if ($email !== '') {
            $byEmail = getCustomerByEmail($email);
            if ($byEmail !== null) {
                return $byEmail;
            }
        }

        $name = trim((string) ($user['name'] ?? ''));
        if ($name !== '') {
            $byName = getCustomerByName($name);
            if ($byName !== null) {
                return $byName;
            }
        }

        return null;
    }
}

<?php
declare(strict_types=1);

if (!function_exists('ensureInvoicePaymentMethodColumn')) {
    function ensureInvoicePaymentMethodColumn(): void
    {
        static $ensured = false;
        if ($ensured || !dbConnected()) {
            return;
        }

        $ensured = true;

        try {
            db()->exec("ALTER TABLE Invoice ADD COLUMN payment_method ENUM('pending','cash','gcash','card','bank_transfer') NOT NULL DEFAULT 'pending' AFTER payment_status");
        } catch (Throwable $e) {
            // Ignore if column already exists.
        }
    }
}

if (!function_exists('normalizePaymentMethod')) {
    function normalizePaymentMethod(?string $value, bool $allowPending = true): string
    {
        $normalized = strtolower(trim((string) $value));
        $allowed = $allowPending
            ? ['pending', 'cash', 'gcash', 'card', 'bank_transfer']
            : ['cash', 'gcash', 'card', 'bank_transfer'];

        if (!in_array($normalized, $allowed, true)) {
            return $allowPending ? 'pending' : 'cash';
        }

        return $normalized;
    }
}

if (!function_exists('getInvoices')) {
    function getInvoices(int $limit = 20): array
    {
        if (!dbConnected()) {
            return [
                ['invoice_id' => 1001, 'rental_id' => 412, 'customer' => 'Maria Reyes', 'vehicle' => 'Toyota Fortuner', 'base_amount' => 10500, 'late_fee' => 0, 'damage_fee' => 0, 'total_amount' => 10500, 'payment_status' => 'paid', 'payment_method' => 'gcash', 'issued_at' => '2026-04-13'],
                ['invoice_id' => 1002, 'rental_id' => 411, 'customer' => 'Juan dela Cruz', 'vehicle' => 'Honda Civic', 'base_amount' => 2200, 'late_fee' => 0, 'damage_fee' => 0, 'total_amount' => 2200, 'payment_status' => 'unpaid', 'payment_method' => 'pending', 'issued_at' => '2026-04-14'],
                ['invoice_id' => 1003, 'rental_id' => 410, 'customer' => 'Ana Lim', 'vehicle' => 'Toyota HiAce', 'base_amount' => 20000, 'late_fee' => 0, 'damage_fee' => 0, 'total_amount' => 20000, 'payment_status' => 'partial', 'payment_method' => 'bank_transfer', 'issued_at' => '2026-04-15'],
            ];
        }

        try {
            ensureInvoicePaymentMethodColumn();

            $sql = "
                SELECT
                    i.invoice_id,
                    i.rental_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    i.base_amount,
                    i.late_fee,
                    i.damage_fee,
                    i.total_amount,
                    i.payment_status,
                    i.payment_method,
                    i.issued_at
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN User u ON u.user_id = r.user_id
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

if (!function_exists('getPayments')) {
    function getPayments(int $limit = 20): array
    {
        if (!dbConnected()) {
            return [
                ['invoice_id' => 1001, 'customer' => 'Maria Reyes', 'vehicle' => 'Toyota Fortuner', 'total_amount' => 10500, 'payment_status' => 'paid', 'payment_method' => 'gcash', 'issued_at' => '2026-04-13'],
                ['invoice_id' => 1002, 'customer' => 'Juan dela Cruz', 'vehicle' => 'Honda Civic', 'total_amount' => 2200, 'payment_status' => 'unpaid', 'payment_method' => 'pending', 'issued_at' => '2026-04-14'],
                ['invoice_id' => 1003, 'customer' => 'Ana Lim', 'vehicle' => 'Toyota HiAce', 'total_amount' => 20000, 'payment_status' => 'partial', 'payment_method' => 'bank_transfer', 'issued_at' => '2026-04-15'],
            ];
        }

        try {
            ensureInvoicePaymentMethodColumn();

            $sql = "
                SELECT
                    i.invoice_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS customer,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    i.total_amount,
                    i.payment_status,
                    i.payment_method,
                    DATE(i.issued_at) AS issued_at
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN User u ON u.user_id = r.user_id
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

if (!function_exists('getCustomerPayments')) {
    function getCustomerPayments(string $customerEmail, int $limit = 10): array
    {
        if (!dbConnected()) {
            return [
                ['invoice_id' => 1001, 'vehicle' => 'Toyota Fortuner', 'total_amount' => 10500, 'payment_status' => 'paid', 'payment_method' => 'gcash', 'issued_at' => '2026-04-13'],
                ['invoice_id' => 1002, 'vehicle' => 'Mitsubishi Xpander', 'total_amount' => 5600, 'payment_status' => 'paid', 'payment_method' => 'cash', 'issued_at' => '2026-04-10'],
            ];
        }

        try {
            ensureInvoicePaymentMethodColumn();

            $sql = "
                SELECT
                    i.invoice_id,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    i.total_amount,
                    i.payment_status,
                    i.payment_method,
                    DATE(i.issued_at) AS issued_at
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN User u ON u.user_id = r.user_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                WHERE u.email = :email
                ORDER BY i.issued_at DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':email', $customerEmail);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getCustomerPaymentsByCustomerId')) {
    function getCustomerPaymentsByCustomerId(int $customerId, int $limit = 10): array
    {
        if ($customerId <= 0) {
            return [];
        }

        if (!dbConnected()) {
            return array_slice(getCustomerPayments('', max($limit, 20)), 0, $limit);
        }

        try {
            ensureInvoicePaymentMethodColumn();

            $sql = "
                SELECT
                    i.invoice_id,
                    i.rental_id,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    i.total_amount,
                    i.payment_status,
                    i.payment_method,
                    DATE(i.issued_at) AS issued_at
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                WHERE r.user_id = :user_id
                ORDER BY i.issued_at DESC
                LIMIT :limit
            ";
            $stmt = db()->prepare($sql);
            $stmt->bindValue(':user_id', $customerId, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('getCustomerInvoiceById')) {
    function getCustomerInvoiceById(int $customerId, int $invoiceId): ?array
    {
        if ($customerId <= 0 || $invoiceId <= 0) {
            return null;
        }

        if (!dbConnected()) {
            foreach (getCustomerPaymentsByCustomerId($customerId, 200) as $invoice) {
                if ((int) ($invoice['invoice_id'] ?? 0) === $invoiceId) {
                    return $invoice;
                }
            }
            return null;
        }

        try {
            ensureInvoicePaymentMethodColumn();

            $sql = "
                SELECT
                    i.invoice_id,
                    i.rental_id,
                    i.total_amount,
                    i.payment_status,
                    i.payment_method,
                    DATE(i.issued_at) AS issued_at,
                    CONCAT(v.brand, ' ', v.model) AS vehicle
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                WHERE i.invoice_id = :invoice_id
                  AND r.user_id = :user_id
                LIMIT 1
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute([
                'invoice_id' => $invoiceId,
                'user_id' => $customerId,
            ]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('markCustomerInvoicePaid')) {
    function markCustomerInvoicePaid(int $customerId, int $invoiceId, string $paymentMethod = 'cash'): array
    {
        $invoice = getCustomerInvoiceById($customerId, $invoiceId);
        if ($invoice === null) {
            return ['ok' => false, 'error' => 'Invoice not found.'];
        }

        $currentStatus = strtolower((string) ($invoice['payment_status'] ?? 'unpaid'));
        if ($currentStatus === 'paid') {
            return ['ok' => true, 'notice' => 'Invoice is already marked as paid.'];
        }

        if (!dbConnected()) {
            return ['ok' => true, 'notice' => 'Payment simulated successfully.'];
        }

        try {
            ensureInvoicePaymentMethodColumn();

            $sql = "
                UPDATE Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                SET i.payment_status = 'paid',
                    i.payment_method = :payment_method
                WHERE i.invoice_id = :invoice_id
                  AND r.user_id = :user_id
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute([
                'payment_method' => normalizePaymentMethod($paymentMethod, false),
                'invoice_id' => $invoiceId,
                'user_id' => $customerId,
            ]);

            if ((int) $stmt->rowCount() <= 0) {
                return ['ok' => false, 'error' => 'Could not update invoice payment status.'];
            }

            return ['ok' => true, 'notice' => 'Payment recorded successfully.'];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Payment could not be processed right now.'];
        }
    }
}

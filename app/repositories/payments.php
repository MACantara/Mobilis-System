<?php
declare(strict_types=1);

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

if (!function_exists('getCustomerPayments')) {
    function getCustomerPayments(string $customerEmail, int $limit = 10): array
    {
        if (!dbConnected()) {
            return [
                ['invoice_id' => 1001, 'vehicle' => 'Toyota Fortuner', 'total_amount' => 10500, 'payment_status' => 'paid', 'issued_at' => '2026-04-13'],
                ['invoice_id' => 1002, 'vehicle' => 'Mitsubishi Xpander', 'total_amount' => 5600, 'payment_status' => 'paid', 'issued_at' => '2026-04-10'],
            ];
        }

        try {
            $sql = "
                SELECT
                    i.invoice_id,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    i.total_amount,
                    i.payment_status,
                    DATE(i.issued_at) AS issued_at
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN Customer c ON c.customer_id = r.customer_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                WHERE c.email = :email
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
            $sql = "
                SELECT
                    i.invoice_id,
                    i.rental_id,
                    CONCAT(v.brand, ' ', v.model) AS vehicle,
                    i.total_amount,
                    i.payment_status,
                    DATE(i.issued_at) AS issued_at
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                WHERE r.customer_id = :customer_id
                ORDER BY i.issued_at DESC
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
            $sql = "
                SELECT
                    i.invoice_id,
                    i.rental_id,
                    i.total_amount,
                    i.payment_status,
                    DATE(i.issued_at) AS issued_at,
                    CONCAT(v.brand, ' ', v.model) AS vehicle
                FROM Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                INNER JOIN Vehicle v ON v.vehicle_id = r.vehicle_id
                WHERE i.invoice_id = :invoice_id
                  AND r.customer_id = :customer_id
                LIMIT 1
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute([
                'invoice_id' => $invoiceId,
                'customer_id' => $customerId,
            ]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('markCustomerInvoicePaid')) {
    function markCustomerInvoicePaid(int $customerId, int $invoiceId): array
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
            $sql = "
                UPDATE Invoice i
                INNER JOIN Rental r ON r.rental_id = i.rental_id
                SET i.payment_status = 'paid'
                WHERE i.invoice_id = :invoice_id
                  AND r.customer_id = :customer_id
            ";
            $stmt = db()->prepare($sql);
            $stmt->execute([
                'invoice_id' => $invoiceId,
                'customer_id' => $customerId,
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

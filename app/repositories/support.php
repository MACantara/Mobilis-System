<?php
declare(strict_types=1);

if (!function_exists('ensureAdminContactMessageResponseColumns')) {
    function ensureAdminContactMessageResponseColumns(): void
    {
        static $ensured = false;
        if ($ensured || !dbConnected()) {
            return;
        }

        $ensured = true;

        $ddl = [
            "ALTER TABLE AdminContactMessage ADD COLUMN admin_response TEXT NULL AFTER message",
            "ALTER TABLE AdminContactMessage ADD COLUMN responded_at TIMESTAMP NULL DEFAULT NULL AFTER created_at",
            "ALTER TABLE AdminContactMessage ADD COLUMN responded_by INT UNSIGNED NULL AFTER responded_at",
            "ALTER TABLE AdminContactMessage ADD KEY idx_admin_contact_responded_at (responded_at)",
        ];

        foreach ($ddl as $sql) {
            try {
                db()->exec($sql);
            } catch (Throwable $e) {
                // Ignore when columns/indexes already exist.
            }
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
    function submitPasswordResetRequest(string $email, ?string $licenseNumber, string $reason, ?int $userId = null): bool
    {
        if (!dbConnected()) {
            return false;
        }

        try {
            $sql = "
                INSERT INTO PasswordResetRequest (
                    user_id,
                    email,
                    license_number,
                    reason,
                    status,
                    requested_ip,
                    user_agent
                ) VALUES (
                    :user_id,
                    :email,
                    :license_number,
                    :reason,
                    'pending',
                    :requested_ip,
                    :user_agent
                )
            ";
            $stmt = db()->prepare($sql);
            $userValue = $userId !== null ? $userId : null;
            $licenseValue = $licenseNumber !== null && trim($licenseNumber) !== '' ? trim($licenseNumber) : null;

            return $stmt->execute([
                'user_id' => $userValue,
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
                    'message' => 'Please activate my account.',
                    'admin_response' => null,
                    'status' => 'new',
                    'created_at' => '2026-04-19 09:30:00',
                    'responded_at' => null,
                ],
            ];
        }

        try {
            ensureAdminContactMessageResponseColumns();

            $sql = "
                SELECT
                    message_id,
                    full_name,
                    email,
                    phone,
                    subject,
                    message,
                    admin_response,
                    status,
                    created_at,
                    responded_at
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

if (!function_exists('getAdminContactMessagesByEmail')) {
    function getAdminContactMessagesByEmail(string $email, int $limit = 5): array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return [];
        }

        if (!dbConnected()) {
            return [];
        }

        try {
            ensureAdminContactMessageResponseColumns();

            $sql = "
                SELECT
                    message_id,
                    subject,
                    message,
                    status,
                    admin_response,
                    created_at,
                    responded_at
                FROM AdminContactMessage
                WHERE email = :email
                ORDER BY created_at DESC
                LIMIT :limit
            ";

            $stmt = db()->prepare($sql);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('respondToAdminContactMessage')) {
    function respondToAdminContactMessage(int $messageId, int $adminUserId, string $status, string $response): array
    {
        if ($messageId <= 0) {
            return ['ok' => false, 'error' => 'Invalid message ID.'];
        }

        $normalizedStatus = strtolower(trim($status));
        if (!in_array($normalizedStatus, ['read', 'resolved'], true)) {
            return ['ok' => false, 'error' => 'Invalid status selected.'];
        }

        if (trim($response) === '') {
            return ['ok' => false, 'error' => 'Response message is required.'];
        }

        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        try {
            ensureAdminContactMessageResponseColumns();

            $sql = "
                UPDATE AdminContactMessage
                SET
                    status = :status,
                    admin_response = :admin_response,
                    responded_at = NOW(),
                    responded_by = :responded_by
                WHERE message_id = :message_id
            ";

            $stmt = db()->prepare($sql);
            $stmt->execute([
                'status' => $normalizedStatus,
                'admin_response' => trim($response),
                'responded_by' => $adminUserId > 0 ? $adminUserId : null,
                'message_id' => $messageId,
            ]);

            if ((int) $stmt->rowCount() <= 0) {
                return ['ok' => false, 'error' => 'Message not found or unchanged.'];
            }

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not save response right now.'];
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
                    'user_id' => 4,
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
                    user_id,
                    email,
                    license_number,
                    reason,
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

if (!function_exists('updatePasswordResetRequestStatus')) {
    function updatePasswordResetRequestStatus(int $requestId, string $status): bool
    {
        if (!dbConnected()) {
            return false;
        }

        try {
            $sql = "UPDATE PasswordResetRequest SET status = ? WHERE request_id = ?";
            $stmt = db()->prepare($sql);
            return $stmt->execute([$status, $requestId]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('findUserByEmail')) {
    function findUserByEmail(string $email): ?array
    {
        if (!dbConnected()) {
            return null;
        }

        try {
            $sql = "SELECT user_id, first_name, last_name, email FROM User WHERE email = ?";
            $stmt = db()->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            return $user ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

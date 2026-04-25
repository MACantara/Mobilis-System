<?php
declare(strict_types=1);

if (!function_exists('demoUsers')) {
    function demoUsers(): array
    {
        return [
            'admin@mobilis.ph' => [
                'name' => 'Alex Jose',
                'role' => 'admin',
            ],
            'staff@mobilis.ph' => [
                'name' => 'Sofia Cruz',
                'role' => 'staff',
            ],
            'customer@mobilis.ph' => [
                'name' => 'Maria Reyes',
                'role' => 'customer',
            ],
        ];
    }
}

if (!function_exists('attemptLogin')) {
    function attemptLogin(string $email, string $password): bool
    {
        $email = strtolower(trim($email));

        if (!dbConnected()) {
            // Fallback to demo users if database is not connected
            $users = demoUsers();

            if (!isset($users[$email])) {
                return false;
            }

            $_SESSION['user'] = [
                'email' => $email,
                'name' => $users[$email]['name'],
                'role' => $users[$email]['role'],
            ];

            return true;
        }

        try {
            $sql = "SELECT user_id, first_name, last_name, email, role, password_hash FROM User WHERE email = ? LIMIT 1";
            $stmt = db()->prepare($sql);
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                return false;
            }

            if (!password_verify($password, $user['password_hash'])) {
                return false;
            }

            $_SESSION['user'] = [
                'user_id' => (int) $user['user_id'],
                'email' => $user['email'],
                'name' => $user['first_name'] . ' ' . $user['last_name'],
                'role' => $user['role'],
            ];

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('currentUser')) {
    function currentUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }
}

if (!function_exists('isAuthenticated')) {
    function isAuthenticated(): bool
    {
        return currentUser() !== null;
    }
}

if (!function_exists('requireAuth')) {
    function requireAuth(array $roles = []): void
    {
        $user = currentUser();

        if ($user === null) {
            header('Location: ' . baseUrl() . '/login.php');
            exit;
        }

        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            $homePath = currentUserHomePath();
            $currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

            if ($homePath !== '' && $homePath !== $currentPath) {
                header('Location: ' . $homePath);
            } else {
                header('Location: ' . baseUrl() . '/errors/403.php');
            }
            exit;
        }
    }
}

if (!function_exists('roleHomePath')) {
    function roleHomePath(?string $role): string
    {
        if ($role === 'customer') {
            return '/Customer/dashboard.php';
        }

        if ($role === 'admin') {
            return '/Admin/settings.php';
        }

        return '/Staff/dashboard.php';
    }
}

if (!function_exists('currentUserHomePath')) {
    function currentUserHomePath(): string
    {
        $user = currentUser();
        $role = is_array($user) ? (string) ($user['role'] ?? '') : '';

        return roleHomePath($role);
    }
}

if (!function_exists('logoutUser')) {
    function logoutUser(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

if (!function_exists('resetUserPassword')) {
    function resetUserPassword(int $userId, string $newPassword): bool
    {
        if (!dbConnected()) {
            return false;
        }

        try {
            $passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $sql = "UPDATE User SET password_hash = ? WHERE user_id = ?";
            $stmt = db()->prepare($sql);
            return $stmt->execute([$passwordHash, $userId]);
        } catch (Throwable $e) {
            return false;
        }
    }
}

if (!function_exists('getUserById')) {
    function getUserById(int $userId): ?array
    {
        if ($userId <= 0 || !dbConnected()) {
            return null;
        }

        try {
            $stmt = db()->prepare('SELECT user_id, first_name, last_name, email, phone, role, password_hash FROM User WHERE user_id = :user_id LIMIT 1');
            $stmt->execute(['user_id' => $userId]);
            $row = $stmt->fetch();
            return $row ?: null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('updateUserProfile')) {
    function updateUserProfile(int $userId, array $payload): array
    {
        if ($userId <= 0) {
            return ['ok' => false, 'error' => 'Invalid user.'];
        }

        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $phone = trim((string) ($payload['phone'] ?? ''));

        if ($firstName === '' || $lastName === '' || $email === '') {
            return ['ok' => false, 'error' => 'First name, last name, and email are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'error' => 'Please provide a valid email address.'];
        }

        try {
            $sql = '
                UPDATE User
                SET first_name = :first_name,
                    last_name = :last_name,
                    email = :email,
                    phone = :phone
                WHERE user_id = :user_id
            ';
            $stmt = db()->prepare($sql);
            $stmt->execute([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'user_id' => $userId,
            ]);

            if (isset($_SESSION['user']) && (int) ($_SESSION['user']['user_id'] ?? 0) === $userId) {
                $_SESSION['user']['email'] = $email;
                $_SESSION['user']['name'] = trim($firstName . ' ' . $lastName);
            }

            return ['ok' => true];
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not update profile. Email may already be used.'];
        }
    }
}

if (!function_exists('changeUserPassword')) {
    function changeUserPassword(int $userId, string $currentPassword, string $newPassword): array
    {
        if ($userId <= 0) {
            return ['ok' => false, 'error' => 'Invalid user.'];
        }

        if (!dbConnected()) {
            return ['ok' => false, 'error' => 'Database is not connected.'];
        }

        if (strlen($newPassword) < 8) {
            return ['ok' => false, 'error' => 'New password must be at least 8 characters.'];
        }

        $user = getUserById($userId);
        if ($user === null) {
            return ['ok' => false, 'error' => 'User not found.'];
        }

        if (!password_verify($currentPassword, (string) ($user['password_hash'] ?? ''))) {
            return ['ok' => false, 'error' => 'Current password is incorrect.'];
        }

        $ok = resetUserPassword($userId, $newPassword);
        if (!$ok) {
            return ['ok' => false, 'error' => 'Could not update password.'];
        }

        return ['ok' => true];
    }
}

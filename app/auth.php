<?php
declare(strict_types=1);

if (!function_exists('demoUsers')) {
    function demoUsers(): array
    {
        return [
            'admin@mobilis.ph' => [
                'name' => 'Alex Jose',
                'password' => 'admin123',
                'role' => 'admin',
            ],
            'staff@mobilis.ph' => [
                'name' => 'Sofia Cruz',
                'password' => 'staff123',
                'role' => 'staff',
            ],
            'customer@mobilis.ph' => [
                'name' => 'Maria Reyes',
                'password' => 'customer123',
                'role' => 'customer',
            ],
        ];
    }
}

if (!function_exists('attemptLogin')) {
    function attemptLogin(string $email, string $password): bool
    {
        $email = strtolower(trim($email));
        $users = demoUsers();

        if (!isset($users[$email]) || $users[$email]['password'] !== $password) {
            return false;
        }

        $_SESSION['user'] = [
            'email' => $email,
            'name' => $users[$email]['name'],
            'role' => $users[$email]['role'],
        ];

        return true;
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
            header('Location: index.php');
            exit;
        }

        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            http_response_code(403);
            echo 'Forbidden';
            exit;
        }
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

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
            header('Location: /login.php');
            exit;
        }

        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            $homePath = currentUserHomePath();
            $currentPath = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

            if ($homePath !== '' && $homePath !== $currentPath) {
                header('Location: ' . $homePath);
            } else {
                header('Location: /errors/403.php');
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

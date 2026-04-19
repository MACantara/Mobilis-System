<?php
declare(strict_types=1);

if (!function_exists('navSections')) {
    function navSections(string $role = 'staff'): array
    {
        $customerNav = [
            [
                'section' => 'My Rentals',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/Customer/dashboard.php', 'icon' => '📊'],
                    ['key' => 'bookings', 'label' => 'My bookings', 'href' => '/Customer/bookings.php', 'icon' => '🗓'],
                ],
            ],
            [
                'section' => 'Fleet',
                'items' => [
                    ['key' => 'tracking', 'label' => 'Live tracking', 'href' => '/Customer/tracking.php', 'icon' => '📍'],
                ],
            ],
            [
                'section' => 'Account',
                'items' => [
                    ['key' => 'payments', 'label' => 'Payments', 'href' => '/Customer/payments.php', 'icon' => '💳'],
                ],
            ],
        ];

        $staffNav = [
            [
                'section' => 'Overview',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => '/Staff/dashboard.php', 'icon' => '📊'],
                    ['key' => 'bookings', 'label' => 'Bookings', 'href' => '/Staff/bookings.php', 'icon' => '🗓', 'badge' => '12', 'badge_class' => 'ok'],
                ],
            ],
            [
                'section' => 'Fleet',
                'items' => [
                    ['key' => 'vehicles', 'label' => 'Vehicles', 'href' => '/Staff/vehicles.php', 'icon' => '🚙'],
                    ['key' => 'tracking', 'label' => 'Live tracking', 'href' => '/Staff/tracking.php', 'icon' => '📍'],
                    ['key' => 'maintenance', 'label' => 'Maintenance', 'href' => '/Staff/maintenance.php', 'icon' => '🔧', 'badge' => '3', 'badge_class' => 'warn'],
                ],
            ],
            [
                'section' => 'Business',
                'items' => [
                    ['key' => 'customers', 'label' => 'Customers', 'href' => '/Staff/customers.php', 'icon' => '👥'],
                    ['key' => 'payments', 'label' => 'Payments', 'href' => '/Staff/payments.php', 'icon' => '💳'],
                    ['key' => 'reports', 'label' => 'Reports', 'href' => '/Staff/reports.php', 'icon' => '📈'],
                ],
            ],
        ];

        $adminNav = [
            [
                'section' => 'Admin',
                'items' => [
                    ['key' => 'support', 'label' => 'Support inbox', 'href' => '/Admin/support-requests.php', 'icon' => '✉'],
                    ['key' => 'settings', 'label' => 'Settings', 'href' => '/Admin/settings.php', 'icon' => '⚙'],
                ],
            ],
        ];

        if ($role === 'customer') {
            return $customerNav;
        }

        if ($role === 'admin') {
            return array_merge($staffNav, $adminNav);
        }

        return $staffNav;
    }
}

if (!function_exists('renderPageTop')) {
    function renderPageTop(string $title, string $activeNav, array $options = []): void
    {
        $user = currentUser();
        $role = (string) ($options['role'] ?? ($user['role'] ?? 'staff'));
        $showSearch = (bool) ($options['show_search'] ?? true);
        $showPrimaryCta = (bool) ($options['show_primary_cta'] ?? true);
        $primaryCtaLabel = (string) ($options['primary_cta_label'] ?? '+ New booking');
        $initials = 'MB';
        if ($user && isset($user['name'])) {
            $parts = preg_split('/\s+/', trim($user['name']));
            $initials = '';
            foreach ($parts as $part) {
                $initials .= strtoupper(substr($part, 0, 1));
            }
            $initials = substr($initials, 0, 2);
        }

        echo '<!doctype html><html lang="en"><head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($title) . ' | Mobilis</title>';
        echo '<link rel="icon" type="image/png" href="/assets/images/favicon.png">';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
        echo '<link rel="stylesheet" href="/assets/styles.css">';
        echo '</head><body>';
        echo '<div class="app-shell">';
        echo '<aside class="sidebar">';
        echo '<div class="brand"><img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo"></div>';
        echo '<nav class="nav">';
        foreach (navSections($role) as $group) {
            echo '<section class="nav-group">';
            echo '<p class="nav-section-title">' . htmlspecialchars((string) $group['section']) . '</p>';
            foreach ($group['items'] as $item) {
                $isActive = $item['key'] === $activeNav ? 'active' : '';
                echo '<a class="nav-item ' . $isActive . '" href="' . htmlspecialchars((string) $item['href']) . '">';
                echo '<span class="nav-item-main">';
                echo '<span class="nav-icon">' . htmlspecialchars((string) $item['icon']) . '</span>';
                echo '<span class="nav-label">' . htmlspecialchars((string) $item['label']) . '</span>';
                echo '</span>';
                if (isset($item['badge']) && (string) $item['badge'] !== '') {
                    $badgeClass = isset($item['badge_class']) ? ' nav-badge-' . (string) $item['badge_class'] : '';
                    echo '<span class="nav-badge' . htmlspecialchars($badgeClass) . '">' . htmlspecialchars((string) $item['badge']) . '</span>';
                }
                echo '</a>';
            }
            echo '</section>';
        }
        echo '</nav>';
        echo '<div class="sidebar-footer">';
        echo '<div class="sidebar-footer-meta">';
        echo '<p class="sidebar-user-label">Signed in as</p>';
        echo '<p class="sidebar-user-role">' . htmlspecialchars(ucfirst($role)) . '</p>';
        echo '</div>';
        echo '<a class="sidebar-logout-btn" href="/logout.php">Sign out</a>';
        echo '</div>';
        echo '</aside>';

        echo '<main class="main">';
        echo '<header class="topbar">';
        echo '<div><h2>' . htmlspecialchars($title) . '</h2><p>' . date('l, F j, Y') . '</p></div>';
        echo '<div class="topbar-right">';
        if ($showSearch) {
            echo '<input type="search" placeholder="Search vehicles, customers...">';
        }
        if ($showPrimaryCta) {
            echo '<button class="primary-btn" type="button">' . htmlspecialchars($primaryCtaLabel) . '</button>';
        }
        echo '<span class="avatar">' . htmlspecialchars($initials) . '</span>';
        echo '</div>';
        echo '</header>';
    }
}

if (!function_exists('renderPageBottom')) {
    function renderPageBottom(): void
    {
        echo '</main></div>';
        echo '<script src="/assets/app.js"></script>';
        echo '</body></html>';
    }
}

if (!function_exists('renderAuthPageTop')) {
    function renderAuthPageTop(string $title, string $bodyClass = 'public-auth-body'): void
    {
        echo '<!doctype html><html lang="en"><head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($title) . ' | Mobilis</title>';
        echo '<link rel="icon" type="image/png" href="/assets/images/favicon.png">';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
        echo '<link rel="stylesheet" href="/assets/styles.css">';
        echo '</head><body class="' . htmlspecialchars($bodyClass) . '">';
        echo '<main class="auth-split-shell">';
    }
}

if (!function_exists('renderAuthPageBottom')) {
    function renderAuthPageBottom(): void
    {
        echo '</main>';
        echo '</body></html>';
    }
}

if (!function_exists('renderAuthBrandPanel')) {
    function renderAuthBrandPanel(string $heading, string $description): void
    {
        echo '<section class="auth-brand-panel">';
        echo '<a href="/index.php" class="brand hero-brand">';
        echo '<img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">';
        echo '</a>';
        echo '<div class="hero-copy">';
        echo '<h2>' . htmlspecialchars($heading) . '</h2>';
        echo '<p>' . htmlspecialchars($description) . '</p>';
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('renderAuthFormPanelStart')) {
    function renderAuthFormPanelStart(): void
    {
        echo '<section class="auth-form-panel">';
        echo '<div class="form-wrap">';
    }
}

if (!function_exists('renderAuthFormPanelEnd')) {
    function renderAuthFormPanelEnd(): void
    {
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('renderErrorPageTop')) {
    function renderErrorPageTop(int $code, string $title): void
    {
        echo '<!doctype html><html lang="en"><head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($code) . ' | ' . htmlspecialchars($title) . ' | Mobilis</title>';
        echo '<link rel="icon" type="image/png" href="/assets/images/favicon.png">';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
        echo '<link rel="stylesheet" href="/assets/styles.css">';
        echo '</head><body class="login-body">';
        echo '<main class="auth-split-shell">';
    }
}

if (!function_exists('renderErrorPageBottom')) {
    function renderErrorPageBottom(): void
    {
        echo '</main>';
        echo '</body></html>';
    }
}

if (!function_exists('renderErrorBrandPanel')) {
    function renderErrorBrandPanel(string $heading, string $description): void
    {
        echo '<section class="auth-brand-panel">';
        echo '<a href="/index.php" class="brand hero-brand">';
        echo '<img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">';
        echo '</a>';
        echo '<div class="hero-copy">';
        echo '<h2>' . htmlspecialchars($heading) . '</h2>';
        echo '<p>' . htmlspecialchars($description) . '</p>';
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('renderErrorFormPanel')) {
    function renderErrorFormPanel(int $code, string $title, string $message, string $backUrl = '/index.php'): void
    {
        echo '<section class="auth-form-panel">';
        echo '<div class="form-wrap">';
        echo '<h3>Error ' . htmlspecialchars($code) . '</h3>';
        echo '<p>' . htmlspecialchars($message) . '</p>';
        echo '<div style="margin-top: 24px;">';
        echo '<a href="' . htmlspecialchars($backUrl) . '" class="primary-btn full">Go to dashboard</a>';
        echo '<a href="/" class="ghost-link button-like" style="display: block; text-align: center; margin-top: 12px;">Go to home</a>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('renderLandingPageTop')) {
    function renderLandingPageTop(string $title): void
    {
        echo '<!doctype html><html lang="en"><head>';
        echo '<meta charset="UTF-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($title) . ' | Mobilis</title>';
        echo '<link rel="icon" type="image/png" href="/assets/images/favicon.png">';
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">';
        echo '<link rel="stylesheet" href="/assets/styles.css">';
        echo '</head><body class="landing-body">';
        echo '<main class="landing-shell">';
    }
}

if (!function_exists('renderLandingPageBottom')) {
    function renderLandingPageBottom(): void
    {
        echo '</main>';
        echo '</body></html>';
    }
}

<?php
declare(strict_types=1);

if (!function_exists('navSections')) {
    function navSections(): array
    {
        return [
            [
                'section' => 'Overview',
                'items' => [
                    ['key' => 'dashboard', 'label' => 'Dashboard', 'href' => 'dashboard.php', 'icon' => '📊'],
                    ['key' => 'bookings', 'label' => 'Bookings', 'href' => 'bookings.php', 'icon' => '🗓', 'badge' => '12', 'badge_class' => 'ok'],
                ],
            ],
            [
                'section' => 'Fleet',
                'items' => [
                    ['key' => 'vehicles', 'label' => 'Vehicles', 'href' => 'vehicles.php', 'icon' => '🚙'],
                    ['key' => 'tracking', 'label' => 'Live tracking', 'href' => 'tracking.php', 'icon' => '📍'],
                    ['key' => 'maintenance', 'label' => 'Maintenance', 'href' => 'maintenance.php', 'icon' => '🔧', 'badge' => '3', 'badge_class' => 'warn'],
                ],
            ],
            [
                'section' => 'Business',
                'items' => [
                    ['key' => 'customers', 'label' => 'Customers', 'href' => 'customers.php', 'icon' => '👥'],
                    ['key' => 'payments', 'label' => 'Payments', 'href' => 'payments.php', 'icon' => '💳'],
                    ['key' => 'reports', 'label' => 'Reports', 'href' => 'reports.php', 'icon' => '📈'],
                ],
            ],
            [
                'section' => 'Admin',
                'items' => [
                    ['key' => 'support', 'label' => 'Support inbox', 'href' => 'support-requests.php', 'icon' => '✉'],
                    ['key' => 'settings', 'label' => 'Settings', 'href' => 'settings.php', 'icon' => '⚙'],
                ],
            ],
        ];
    }
}

if (!function_exists('renderPageTop')) {
    function renderPageTop(string $title, string $activeNav, array $options = []): void
    {
        $user = currentUser();
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
        echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
        echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
        echo '<link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">';
        echo '<link rel="stylesheet" href="assets/styles.css">';
        echo '</head><body>';
        echo '<div class="app-shell">';
        echo '<aside class="sidebar">';
        echo '<div class="brand"><span class="brand-icon">🚗</span><div><h1>Mobilis</h1><p>Vehicle Rental</p></div></div>';
        echo '<nav class="nav">';
        foreach (navSections() as $group) {
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
        echo '<p class="sidebar-user-role">' . htmlspecialchars((string) ($user['role'] ?? 'guest')) . '</p>';
        echo '</div>';
        echo '<a class="sidebar-logout-btn" href="logout.php">Sign out</a>';
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
        echo '<script src="assets/app.js"></script>';
        echo '</body></html>';
    }
}

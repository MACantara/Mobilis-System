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
                    ['key' => 'vehicles', 'label' => 'Browse vehicles', 'href' => '/Customer/vehicles.php', 'icon' => '🚘'],
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

if (!function_exists('appLayoutData')) {
    function appLayoutData(string $title, string $activeNav, array $options = []): array
    {
        $user = currentUser();
        $role = (string) ($options['role'] ?? ($user['role'] ?? 'staff'));
        $showSearch = (bool) ($options['show_search'] ?? false);
        $showPrimaryCta = (bool) ($options['show_primary_cta'] ?? false);
        $primaryCtaLabel = (string) ($options['primary_cta_label'] ?? '+ New booking');
        $initials = 'MB';

        if ($user && isset($user['name'])) {
            $parts = preg_split('/\s+/', trim((string) $user['name']));
            $initials = '';
            foreach ($parts as $part) {
                $initials .= strtoupper(substr((string) $part, 0, 1));
            }
            $initials = substr($initials, 0, 2);
        }

        return [
            'title' => $title,
            'active_nav' => $activeNav,
            'role' => $role,
            'show_search' => $showSearch,
            'show_primary_cta' => $showPrimaryCta,
            'primary_cta_label' => $primaryCtaLabel,
            'initials' => $initials,
            'today' => date('l, F j, Y'),
        ];
    }
}

if (!function_exists('authLayoutData')) {
    function authLayoutData(string $title, string $bodyClass = 'public-auth-body'): array
    {
        return [
            'title' => $title,
            'body_class' => $bodyClass,
        ];
    }
}

if (!function_exists('viewAuthBrandPanel')) {
    function viewAuthBrandPanel(string $heading, string $description): void
    {
        echo '<section class="auth-brand-panel">';
        echo '<a href="' . baseUrl() . '/index.php" class="brand hero-brand">';
        echo '<img src="' . baseUrl() . '/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">';
        echo '</a>';
        echo '<div class="hero-copy">';
        echo '<h2>' . htmlspecialchars($heading) . '</h2>';
        echo '<p>' . htmlspecialchars($description) . '</p>';
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('viewAuthFormPanelStart')) {
    function viewAuthFormPanelStart(): void
    {
        echo '<section class="auth-form-panel">';
        echo '<div class="form-wrap">';
    }
}

if (!function_exists('viewAuthFormPanelEnd')) {
    function viewAuthFormPanelEnd(): void
    {
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('viewErrorBrandPanel')) {
    function viewErrorBrandPanel(string $heading, string $description): void
    {
        echo '<section class="auth-brand-panel">';
        echo '<a href="' . baseUrl() . '/index.php" class="brand hero-brand">';
        echo '<img src="' . baseUrl() . '/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">';
        echo '</a>';
        echo '<div class="hero-copy">';
        echo '<h2>' . htmlspecialchars($heading) . '</h2>';
        echo '<p>' . htmlspecialchars($description) . '</p>';
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('viewErrorFormPanel')) {
    function viewErrorFormPanel(int $code, string $title, string $message, string $backUrl = '/index.php', bool $showSignOut = false): void
    {
        unset($title);
        echo '<section class="auth-form-panel">';
        echo '<div class="form-wrap">';
        echo '<h3>Error ' . htmlspecialchars((string) $code) . '</h3>';
        echo '<p>' . htmlspecialchars($message) . '</p>';
        echo '<div style="margin-top: 24px;">';
        echo '<a href="' . htmlspecialchars($backUrl) . '" class="primary-btn full">Go back</a>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }
}

if (!function_exists('viewModalStart')) {
    function viewModalStart(string $id, string $title, array $options = []): void
    {
        $size = strtolower((string) ($options['size'] ?? 'md'));
        if (!in_array($size, ['sm', 'md', 'lg', 'xl'], true)) {
            $size = 'md';
        }

        $showClose = (bool) ($options['show_close'] ?? true);
        $closeLabel = (string) ($options['close_label'] ?? 'Close modal');

        echo '<div id="' . htmlspecialchars($id) . '" class="modal" data-modal data-modal-size="' . htmlspecialchars($size) . '" role="dialog" aria-modal="true" aria-hidden="true">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h4>' . htmlspecialchars($title) . '</h4>';

        if ($showClose) {
            echo '<button type="button" class="modal-close" data-modal-close aria-label="' . htmlspecialchars($closeLabel) . '">&times;</button>';
        }

        echo '</div>';
    }
}

if (!function_exists('viewModalEnd')) {
    function viewModalEnd(): void
    {
        echo '</div>';
        echo '</div>';
    }
}

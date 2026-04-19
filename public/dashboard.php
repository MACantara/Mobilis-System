<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff', 'customer']);

$metrics = getDashboardMetrics();
$vehicleStatus = getVehicleStatusList();
$upcomingBookings = getUpcomingBookings();
$fleetByCategory = getFleetByCategory();

if (!function_exists('vehicleEmoji')) {
    function vehicleEmoji(array $vehicle): string
    {
        if (!empty($vehicle['icon'])) {
            return (string) $vehicle['icon'];
        }

        $name = strtolower((string) ($vehicle['name'] ?? ''));
        if (str_contains($name, 'hiace') || str_contains($name, 'van')) {
            return '🚐';
        }
        if (str_contains($name, 'ranger') || str_contains($name, 'strada') || str_contains($name, 'pickup')) {
            return '🛻';
        }
        if (str_contains($name, 'fortuner') || str_contains($name, 'everest') || str_contains($name, 'montero')) {
            return '🚙';
        }
        return '🚗';
    }
}

if (!function_exists('customerInitials')) {
    function customerInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach ((array) $parts as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }
}

if (!function_exists('formatBookingDateRange')) {
    function formatBookingDateRange(string $pickupDate, string $returnDate): string
    {
        $pickup = date_create($pickupDate);
        $return = date_create($returnDate);
        if (!$pickup || !$return) {
            return $pickupDate . ' - ' . $returnDate;
        }

        if ($pickupDate === $returnDate) {
            return date_format($pickup, 'M j');
        }

        return date_format($pickup, 'M j') . ' - ' . date_format($return, 'j');
    }
}

renderPageTop('Dashboard', 'dashboard');
?>
<section class="content-grid metric-grid">
    <article class="card metric-card">
        <p>Total fleet</p>
        <h3><?= (int) $metrics['total_fleet'] ?></h3>
        <span>vehicles</span>
    </article>
    <article class="card metric-card">
        <p>Active rentals</p>
        <h3><?= (int) $metrics['active_rentals'] ?></h3>
        <span>running now</span>
    </article>
    <article class="card metric-card">
        <p>Revenue today</p>
        <h3>P<?= number_format((float) $metrics['revenue_today'], 2) ?></h3>
        <span>gross billings</span>
    </article>
    <article class="card metric-card">
        <p>Utilization rate</p>
        <h3><?= (int) $metrics['utilization_rate'] ?>%</h3>
        <span>of fleet booked</span>
    </article>
</section>

<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h3>Vehicle status</h3>
            <a href="vehicles.php" class="ghost-link">View all</a>
        </div>
        <ul class="list clean">
            <?php foreach ($vehicleStatus as $vehicle): ?>
                <?php $status = strtolower((string) ($vehicle['status'] ?? 'available')); ?>
                <li>
                    <div class="status-item-left">
                        <span class="status-emoji"><?= htmlspecialchars(vehicleEmoji($vehicle)) ?></span>
                        <div>
                        <strong><?= htmlspecialchars((string) $vehicle['name']) ?></strong>
                        <p><?= htmlspecialchars((string) $vehicle['plate']) ?></p>
                        </div>
                    </div>
                    <span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>

    <article class="card">
        <div class="card-header">
            <h3>Upcoming bookings</h3>
            <a href="bookings.php" class="ghost-link">Calendar</a>
        </div>
        <ul class="list clean">
            <?php foreach ($upcomingBookings as $booking): ?>
                <?php $status = strtolower((string) ($booking['status'] ?? 'pending')); ?>
                <li class="booking-item">
                    <div class="booking-item-left">
                        <span class="booking-avatar"><?= htmlspecialchars(customerInitials((string) $booking['customer_name'])) ?></span>
                        <div>
                        <strong><?= htmlspecialchars((string) $booking['customer_name']) ?></strong>
                        <p><?= htmlspecialchars((string) $booking['vehicle_name']) ?></p>
                        </div>
                    </div>
                    <div class="booking-item-meta">
                        <p class="booking-dates"><?= htmlspecialchars(formatBookingDateRange((string) $booking['pickup_date'], (string) $booking['return_date'])) ?></p>
                        <span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>

<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h3>Live fleet map</h3>
            <a href="tracking.php" class="ghost-link">Full map</a>
        </div>
        <div class="map-embed-wrap">
            <iframe
                title="Live fleet map"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=Metro+Manila,+Philippines&output=embed"></iframe>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h3>Fleet by category</h3>
            <a href="vehicles.php" class="ghost-link">Details</a>
        </div>
        <div class="progress-list">
            <?php foreach ($fleetByCategory as $row): ?>
                <?php $rate = (int) ($row['utilization'] ?? 0); ?>
                <div class="progress-row">
                    <span><?= htmlspecialchars((string) $row['category_name']) ?></span>
                    <div class="progress-track"><i style="width: <?= $rate ?>%"></i></div>
                    <strong><?= $rate ?>%</strong>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
</section>
<?php renderPageBottom(); ?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customerEmail = $user['email'] ?? '';

$customerBookings = getCustomerBookings($customerEmail, 50);
$activeBookings = array_filter($customerBookings, fn($b) => in_array(($b['status'] ?? ''), ['active', 'confirmed']));

if (!function_exists('vehicleEmoji')) {
    function vehicleEmoji(string $vehicleName): string
    {
        $name = strtolower($vehicleName);
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

renderPageTop('Live tracking', 'tracking', ['role' => 'customer']);
?>
<section class="page-content-head">
    <h3>My vehicle tracking</h3>
</section>

<?php if ($activeBookings === []): ?>
    <section class="card">
        <p class="muted">You have no active rentals to track. <a href="booking-create.php" class="text-link">Book a vehicle</a></p>
    </section>
<?php else: ?>
    <section class="content-grid split-grid">
        <article class="card">
            <div class="card-header">
                <h4>Live fleet map</h4>
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
                <h4>My rented vehicles</h4>
            </div>
            <ul class="list clean">
                <?php foreach ($activeBookings as $booking): ?>
                    <li>
                        <div class="status-item-left">
                            <span class="status-emoji"><?= htmlspecialchars(vehicleEmoji((string) $booking['vehicle'])) ?></span>
                            <div>
                            <strong><?= htmlspecialchars((string) $booking['vehicle']) ?></strong>
                            <p>Until <?= htmlspecialchars(date('M j, Y', strtotime((string) $booking['return_date']))) ?></p>
                            </div>
                        </div>
                        <span class="pill confirmed">Active</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>
<?php endif; ?>
<?php renderPageBottom(); ?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customerEmail = $user['email'] ?? '';

$customerBookings = getCustomerBookings($customerEmail, 50);
$activeBookings = array_filter($customerBookings, fn($b) => in_array(($b['status'] ?? ''), ['active', 'confirmed']));
$snapshot = getLiveTrackingSnapshot($user ?? [], 200, 5);
$trackedVehicles = (array) ($snapshot['vehicles'] ?? []);

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

viewBegin('app', appLayoutData('Live tracking', 'tracking', ['role' => 'customer']));
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
            <div
                id="customer-live-map"
                class="live-map-canvas"
                data-tracking-map
                data-tracking-endpoint="/api/tracking.php"
                data-tracking-list-target="customer-tracked-vehicles"
                data-tracking-status-target="customer-tracking-status"></div>
            <p id="customer-tracking-status" class="muted tracking-status-note">
                Simulated locations refresh every <?= (int) ($snapshot['step_seconds'] ?? 5) ?> seconds.
            </p>
            <div class="tracking-map-actions">
                <button type="button" class="ghost-link button-like" data-tracking-refresh="customer-live-map">Refresh now</button>
            </div>
        </article>

        <article class="card">
            <div class="card-header">
                <h4>My rented vehicles</h4>
            </div>
            <ul id="customer-tracked-vehicles" class="list clean">
                <?php foreach ($trackedVehicles as $vehicle): ?>
                    <?php $status = strtolower((string) ($vehicle['status'] ?? 'confirmed')); ?>
                    <li>
                        <div class="status-item-left">
                            <span class="status-emoji"><?= htmlspecialchars(vehicleEmoji((string) $vehicle['name'])) ?></span>
                            <div>
                            <strong><?= htmlspecialchars((string) $vehicle['name']) ?></strong>
                            <p><?= htmlspecialchars((string) $vehicle['plate']) ?></p>
                            <p class="muted tracking-coordinate"><?= number_format((float) $vehicle['lat'], 6) ?>, <?= number_format((float) $vehicle['lng'], 6) ?></p>
                            </div>
                        </div>
                        <span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </article>
    </section>
<?php endif; ?>
<?php viewEnd();
?>

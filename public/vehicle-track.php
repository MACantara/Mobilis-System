<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$vehicleId = (int) ($_GET['id'] ?? 0);
$vehicle = getVehicleById($vehicleId);

renderPageTop('Vehicle tracking', 'tracking', [
    'show_search' => false,
    'show_primary_cta' => false,
]);
?>
<section class="card customer-form-card">
    <?php if ($vehicle === null): ?>
        <h3>Vehicle not found</h3>
        <p class="muted">The selected vehicle could not be found.</p>
        <p><a class="ghost-link" href="vehicles.php">Back to vehicles</a></p>
    <?php else: ?>
        <div class="card-header">
            <h3>Tracking <?= htmlspecialchars((string) ($vehicle['name'] ?? 'Vehicle')) ?></h3>
            <a class="ghost-link" href="vehicles.php">Back</a>
        </div>
        <p class="muted" style="margin-bottom: 10px;">Plate: <?= htmlspecialchars((string) ($vehicle['plate'] ?? 'N/A')) ?> · Status: <?= htmlspecialchars(ucfirst((string) ($vehicle['status'] ?? 'available'))) ?></p>
        <div class="map-embed-wrap">
            <iframe
                title="Vehicle location"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=Metro+Manila,+Philippines&output=embed"></iframe>
        </div>
    <?php endif; ?>
</section>
<?php renderPageBottom(); ?>

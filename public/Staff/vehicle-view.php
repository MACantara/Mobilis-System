<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$vehicleId = (int) ($_GET['id'] ?? 0);
$vehicle = getVehicleById($vehicleId);

renderPageTop('Vehicle details', 'vehicles', [
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
            <h3><?= htmlspecialchars((string) ($vehicle['name'] ?? 'Vehicle')) ?></h3>
            <div class="customer-form-actions">
                <a class="ghost-link button-like" href="vehicles.php">Back</a>
                <a class="ghost-link button-like" href="vehicle-edit.php?id=<?= (int) ($vehicle['vehicle_id'] ?? 0) ?>">Edit</a>
            </div>
        </div>

        <dl class="customer-meta-list">
            <div><dt>Plate no.</dt><dd><?= htmlspecialchars((string) ($vehicle['plate'] ?? 'N/A')) ?></dd></div>
            <div><dt>Category</dt><dd><?= htmlspecialchars((string) ($vehicle['category_name'] ?? 'N/A')) ?></dd></div>
            <div><dt>Year</dt><dd><?= htmlspecialchars((string) ($vehicle['year'] ?? 'N/A')) ?></dd></div>
            <div><dt>Color</dt><dd><?= htmlspecialchars((string) ($vehicle['color'] ?? 'N/A')) ?></dd></div>
            <div><dt>Mileage</dt><dd><?= number_format((float) ($vehicle['mileage_km'] ?? 0)) ?> km</dd></div>
            <div><dt>Status</dt><dd><?= htmlspecialchars(ucfirst((string) ($vehicle['status'] ?? 'available'))) ?></dd></div>
            <div><dt>Rate/day</dt><dd>P<?= number_format((float) ($vehicle['daily_rate'] ?? 0), 2) ?></dd></div>
        </dl>
    <?php endif; ?>
</section>
<?php renderPageBottom(); ?>

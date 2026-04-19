<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$vehicles = getVehicles(18);
$metrics = getDashboardMetrics();

renderPageTop('Vehicles', 'vehicles');
?>
<section class="content-grid metric-grid">
    <article class="card metric-card"><p>Total fleet</p><h3><?= (int) $metrics['total_fleet'] ?></h3></article>
    <article class="card metric-card"><p>Currently rented</p><h3><?= (int) $metrics['active_rentals'] ?></h3></article>
    <article class="card metric-card"><p>Available now</p><h3><?= max((int) $metrics['total_fleet'] - (int) $metrics['active_rentals'], 0) ?></h3></article>
    <article class="card metric-card"><p>In maintenance</p><h3><?= count(array_filter($vehicles, static fn($v) => ($v['status'] ?? '') === 'maintenance')) ?></h3></article>
</section>

<section class="vehicle-grid">
    <?php foreach ($vehicles as $vehicle): ?>
        <?php $status = strtolower((string) ($vehicle['status'] ?? 'available')); ?>
        <article class="card vehicle-card">
            <div class="card-header">
                <h3><?= htmlspecialchars((string) $vehicle['name']) ?></h3>
                <span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
            </div>
            <p><?= htmlspecialchars((string) $vehicle['plate']) ?> · <?= htmlspecialchars((string) $vehicle['category_name']) ?></p>
            <div class="meta-grid">
                <div><span>Year</span><strong><?= htmlspecialchars((string) $vehicle['year']) ?></strong></div>
                <div><span>Mileage</span><strong><?= number_format((float) $vehicle['mileage_km']) ?> km</strong></div>
                <div><span>Rate/day</span><strong>P<?= number_format((float) $vehicle['daily_rate'], 2) ?></strong></div>
            </div>
            <div class="actions">
                <button class="ghost-link button-like">View</button>
                <button class="ghost-link button-like">Edit</button>
                <button class="primary-btn">Track</button>
            </div>
        </article>
    <?php endforeach; ?>
</section>
<?php renderPageBottom(); ?>

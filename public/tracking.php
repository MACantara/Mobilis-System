<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$vehicles = getVehicles(12);

renderPageTop('Live tracking', 'tracking');
?>
<section class="page-content-head">
    <h3>Live tracking overview</h3>
</section>

<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h3>Live fleet map</h3>
            <a href="vehicles.php" class="ghost-link">Full map</a>
        </div>
        <div class="map-stage">
            <span class="map-point" style="top: 32%; left: 22%;"></span>
            <span class="map-point" style="top: 20%; left: 44%;"></span>
            <span class="map-point" style="top: 42%; left: 61%;"></span>
            <span class="map-point" style="top: 58%; left: 77%;"></span>
            <span class="map-route"></span>
            <p>Metro Manila · 31 active trackers</p>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h3>Tracked vehicles</h3>
        </div>
        <ul class="list clean">
            <?php foreach ($vehicles as $vehicle): ?>
                <?php $status = strtolower((string) ($vehicle['status'] ?? 'available')); ?>
                <li>
                    <div>
                        <strong><?= htmlspecialchars((string) $vehicle['name']) ?></strong>
                        <p><?= htmlspecialchars((string) $vehicle['plate']) ?></p>
                    </div>
                    <span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>
<?php renderPageBottom(); ?>

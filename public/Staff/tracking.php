<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$snapshot = getLiveTrackingSnapshot(currentUser() ?? [], 200, 5);
$trackedVehicles = (array) ($snapshot['vehicles'] ?? []);
$trackedVehiclesPreview = array_slice($trackedVehicles, 0, 10);

viewBegin('app', appLayoutData('Live tracking', 'tracking'));
?>
<section class="page-content-head">
    <h3>Live tracking overview</h3>
</section>

<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h4>Live fleet map</h4>
            <a href="vehicles.php" class="ghost-link">Full map</a>
        </div>
        <div
            id="staff-live-map"
            class="live-map-canvas"
            data-tracking-map
            data-tracking-endpoint="/api/tracking.php"
            data-tracking-list-target="staff-tracked-vehicles"
            data-tracking-list-limit="10"
            data-tracking-status-target="staff-tracking-status"></div>
        <p id="staff-tracking-status" class="muted tracking-status-note">
            Simulated locations refresh every <?= (int) ($snapshot['step_seconds'] ?? 5) ?> seconds.
        </p>
        <div class="tracking-map-actions">
            <button type="button" class="ghost-link button-like" data-tracking-refresh="staff-live-map">Refresh now</button>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h4>Tracked vehicles</h4>
            <span class="muted">Showing first 10 (scroll for more)</span>
        </div>
        <ul id="staff-tracked-vehicles" class="list clean tracking-list-scroll">
            <?php foreach ($trackedVehiclesPreview as $vehicle): ?>
                <?php $status = strtolower((string) ($vehicle['status'] ?? 'available')); ?>
                <li>
                    <div>
                        <strong><?= htmlspecialchars((string) $vehicle['name']) ?></strong>
                        <p><?= htmlspecialchars((string) $vehicle['plate']) ?></p>
                        <p class="muted tracking-coordinate"><?= number_format((float) $vehicle['lat'], 6) ?>, <?= number_format((float) $vehicle['lng'], 6) ?></p>
                    </div>
                    <span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>
<?php viewEnd(); ?>

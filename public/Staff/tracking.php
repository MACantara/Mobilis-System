<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$vehicles = getVehicles(50);

// Build map markers for vehicles with GPS coordinates
$mapMarkers = [];
foreach ($vehicles as $vehicle) {
    $lat = $vehicle['latitude'] ?? null;
    $lng = $vehicle['longitude'] ?? null;
    if ($lat && $lng) {
        $mapMarkers[] = [
            'lat' => (float) $lat,
            'lng' => (float) $lng,
            'name' => $vehicle['name'] ?? 'Unknown',
            'plate' => $vehicle['plate'] ?? '',
            'status' => $vehicle['status'] ?? 'available',
        ];
    }
}

$mapCenter = count($mapMarkers) > 0 ? $mapMarkers[0] : ['lat' => 14.6091, 'lng' => 121.0223];

renderPageTop('Live tracking', 'tracking');
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
        <div class="map-embed-wrap">
            <iframe
                title="Live fleet map"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=<?= htmlspecialchars($mapCenter['lat']) ?>,<?= htmlspecialchars($mapCenter['lng']) ?>&output=embed&z=12"></iframe>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h4>Tracked vehicles</h4>
        </div>
        <ul class="list clean">
            <?php foreach ($vehicles as $vehicle): ?>
                <?php 
                    $status = strtolower((string) ($vehicle['status'] ?? 'available'));
                    $lat = $vehicle['latitude'] ?? null;
                    $lng = $vehicle['longitude'] ?? null;
                ?>
                <li>
                    <div>
                        <strong><?= htmlspecialchars((string) $vehicle['name']) ?></strong>
                        <p><?= htmlspecialchars((string) $vehicle['plate']) ?></p>
                        <?php if ($lat && $lng): ?>
                            <p class="muted"><?= number_format((float) $lat, 6) ?>, <?= number_format((float) $lng, 6) ?></p>
                        <?php else: ?>
                            <p class="muted">No GPS data</p>
                        <?php endif; ?>
                    </div>
                    <span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>
<?php renderPageBottom(); ?>

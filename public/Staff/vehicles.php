<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

if (!function_exists('vehicleCategoryKeyPage')) {
    function vehicleCategoryKeyPage(string $category): string
    {
        $normalized = strtolower(trim($category));
        if (str_contains($normalized, 'suv')) {
            return 'suv';
        }
        if (str_contains($normalized, 'sedan')) {
            return 'sedan';
        }
        if (str_contains($normalized, 'van')) {
            return 'van';
        }
        if (str_contains($normalized, 'pickup')) {
            return 'pickup';
        }
        return 'other';
    }
}

if (!function_exists('vehicleIconPage')) {
    function vehicleIconPage(array $vehicle): string
    {
        $category = strtolower((string) ($vehicle['category_name'] ?? ''));
        if (str_contains($category, 'motorcycle')) {
            return '🏍️';
        }
        if (str_contains($category, 'pickup')) {
            return '🛻';
        }
        if (str_contains($category, 'van')) {
            return '🚐';
        }
        if (str_contains($category, 'suv')) {
            return '🚙';
        }
        return '🚗';
    }
}

if (!function_exists('vehicleFuelTypePage')) {
    function vehicleFuelTypePage(array $vehicle): string
    {
        $name = strtolower((string) ($vehicle['name'] ?? ''));
        $category = strtolower((string) ($vehicle['category_name'] ?? ''));
        if (str_contains($name, 'fortuner') || str_contains($name, 'ranger') || str_contains($name, 'strada') || str_contains($category, 'pickup') || str_contains($category, 'van')) {
            return 'Diesel';
        }
        return 'Gasoline';
    }
}

$notice = (string) ($_GET['notice'] ?? '');
$noticeMessage = '';
if ($notice === 'vehicle_created') {
    $noticeMessage = 'Vehicle was added successfully.';
} elseif ($notice === 'vehicle_updated') {
    $noticeMessage = 'Vehicle profile was updated successfully.';
}

$allVehicles = getVehicles(500);
$statusFilter = strtolower((string) ($_GET['status'] ?? 'all'));
$categoryFilter = strtolower((string) ($_GET['category'] ?? 'all'));
$searchTerm = trim((string) ($_GET['q'] ?? ''));

$statusCounts = ['all' => count($allVehicles), 'available' => 0, 'rented' => 0, 'maintenance' => 0];
$categoryCounts = ['all' => count($allVehicles), 'suv' => 0, 'sedan' => 0, 'van' => 0, 'pickup' => 0];

foreach ($allVehicles as $vehicle) {
    $status = strtolower((string) ($vehicle['status'] ?? 'available'));
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }

    $catKey = vehicleCategoryKeyPage((string) ($vehicle['category_name'] ?? ''));
    if (isset($categoryCounts[$catKey])) {
        $categoryCounts[$catKey]++;
    }
}

$vehicles = [];
foreach ($allVehicles as $vehicle) {
    $status = strtolower((string) ($vehicle['status'] ?? 'available'));
    $catKey = vehicleCategoryKeyPage((string) ($vehicle['category_name'] ?? ''));

    if ($statusFilter !== 'all' && $status !== $statusFilter) {
        continue;
    }
    if ($categoryFilter !== 'all' && $catKey !== $categoryFilter) {
        continue;
    }

    if ($searchTerm !== '') {
        $haystack = strtolower(
            (string) ($vehicle['name'] ?? '') . ' ' .
            (string) ($vehicle['plate'] ?? '') . ' ' .
            (string) ($vehicle['category_name'] ?? '')
        );

        if (!str_contains($haystack, strtolower($searchTerm))) {
            continue;
        }
    }

    $vehicles[] = $vehicle;
}

if (!function_exists('vehiclesQuery')) {
    function vehiclesQuery(array $overrides = []): string
    {
        $params = [
            'status' => strtolower((string) ($_GET['status'] ?? 'all')),
            'category' => strtolower((string) ($_GET['category'] ?? 'all')),
            'q' => trim((string) ($_GET['q'] ?? '')),
        ];

        foreach ($overrides as $key => $value) {
            $params[$key] = (string) $value;
        }

        if (($params['status'] ?? 'all') === 'all') {
            unset($params['status']);
        }
        if (($params['category'] ?? 'all') === 'all') {
            unset($params['category']);
        }
        if (($params['q'] ?? '') === '') {
            unset($params['q']);
        }

        return http_build_query($params);
    }
}

$totalFleet = count($allVehicles);
$currentlyRented = (int) ($statusCounts['rented'] ?? 0);
$availableNow = (int) ($statusCounts['available'] ?? 0);
$inMaintenance = (int) ($statusCounts['maintenance'] ?? 0);

renderPageTop('Vehicles', 'vehicles', [
    'show_search' => false,
    'show_primary_cta' => false,
]);
?>
<?php if ($noticeMessage !== ''): ?>
    <div class="alert-success customers-alert"><?= htmlspecialchars($noticeMessage) ?></div>
<?php endif; ?>

<section class="vehicles-page-header">
    <h3>All vehicles</h3>
    <form class="vehicles-toolbar" method="get" action="vehicles.php">
        <div class="vehicles-search-wrap">
            <span aria-hidden="true">🔍</span>
            <input type="search" name="q" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Search vehicles...">
        </div>
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
        <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
        <a class="ghost-link button-like" href="vehicles-export.php?<?= htmlspecialchars(vehiclesQuery()) ?>">Export</a>
        <a class="primary-btn" href="vehicle-create.php">+ Add vehicle</a>
    </form>
</section>

<section class="vehicles-kpi-grid">
    <article class="card vehicles-kpi-card fleet">
        <span class="vehicles-kpi-icon">🚙</span>
        <div><p>Total fleet</p><h3><?= $totalFleet ?></h3></div>
    </article>
    <article class="card vehicles-kpi-card rented">
        <span class="vehicles-kpi-icon">🔑</span>
        <div><p>Currently rented</p><h3><?= $currentlyRented ?></h3></div>
    </article>
    <article class="card vehicles-kpi-card available">
        <span class="vehicles-kpi-icon">✅</span>
        <div><p>Available now</p><h3><?= $availableNow ?></h3></div>
    </article>
    <article class="card vehicles-kpi-card maintenance">
        <span class="vehicles-kpi-icon">🔧</span>
        <div><p>In maintenance</p><h3><?= $inMaintenance ?></h3></div>
    </article>
</section>

<section class="vehicles-filter-row">
    <?php $statusLabels = ['all' => 'All', 'available' => 'Available', 'rented' => 'Rented', 'maintenance' => 'Maintenance']; ?>
    <?php foreach ($statusLabels as $key => $label): ?>
        <a class="vehicles-chip<?= $statusFilter === $key ? ' active' : '' ?>" href="vehicles.php?<?= htmlspecialchars(vehiclesQuery(['status' => $key])) ?>">
            <?= htmlspecialchars($label) ?> (<?= (int) ($statusCounts[$key] ?? 0) ?>)
        </a>
    <?php endforeach; ?>

    <span class="vehicles-divider" aria-hidden="true"></span>

    <?php $catLabels = ['all' => 'All types', 'suv' => 'SUVs', 'sedan' => 'Sedans', 'van' => 'Vans', 'pickup' => 'Pick-ups']; ?>
    <?php foreach ($catLabels as $key => $label): ?>
        <a class="vehicles-chip secondary<?= $categoryFilter === $key ? ' active' : '' ?>" href="vehicles.php?<?= htmlspecialchars(vehiclesQuery(['category' => $key])) ?>">
            <?= htmlspecialchars($label) ?><?= $key !== 'all' ? ' (' . (int) ($categoryCounts[$key] ?? 0) . ')' : '' ?>
        </a>
    <?php endforeach; ?>
</section>

<section class="vehicle-grid">
    <?php foreach ($vehicles as $vehicle): ?>
        <?php $status = strtolower((string) ($vehicle['status'] ?? 'available')); ?>
        <article class="card vehicle-card">
            <div class="vehicle-card-top">
                <span class="vehicle-card-icon"><?= htmlspecialchars(vehicleIconPage($vehicle)) ?></span>
                <span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst($status)) ?></span>
            </div>
            <h3 class="vehicle-card-title"><?= htmlspecialchars((string) $vehicle['name']) ?></h3>
            <p class="vehicle-card-subtitle"><?= htmlspecialchars((string) $vehicle['plate']) ?> · <?= htmlspecialchars((string) $vehicle['category_name']) ?></p>

            <div class="vehicle-meta-grid">
                <div class="vehicle-meta-item"><span>Year</span><strong><?= htmlspecialchars((string) $vehicle['year']) ?></strong></div>
                <div class="vehicle-meta-item"><span>Mileage</span><strong><?= number_format((float) $vehicle['mileage_km']) ?> km</strong></div>
                <div class="vehicle-meta-item"><span>Rate/day</span><strong>P<?= number_format((float) $vehicle['daily_rate'], 0) ?></strong></div>
                <div class="vehicle-meta-item"><span>Fuel</span><strong><?= htmlspecialchars(vehicleFuelTypePage($vehicle)) ?></strong></div>
            </div>

            <div class="vehicle-actions">
                <a class="ghost-link button-like" href="vehicle-view.php?id=<?= (int) $vehicle['vehicle_id'] ?>">View</a>
                <a class="ghost-link button-like" href="vehicle-edit.php?id=<?= (int) $vehicle['vehicle_id'] ?>">Edit</a>
                <?php if ($status === 'rented'): ?>
                    <a class="primary-btn" href="vehicle-track.php?id=<?= (int) $vehicle['vehicle_id'] ?>">Track</a>
                <?php elseif ($status === 'maintenance'): ?>
                    <a class="primary-btn warning" href="maintenance.php?vehicle_id=<?= (int) $vehicle['vehicle_id'] ?>">PMS due</a>
                <?php else: ?>
                    <a class="primary-btn" href="booking-create.php?vehicle_id=<?= (int) $vehicle['vehicle_id'] ?>">Book now</a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if ($vehicles === []): ?>
        <article class="card"><p class="muted">No vehicles found for the selected filters.</p></article>
    <?php endif; ?>
</section>
<?php renderPageBottom(); ?>

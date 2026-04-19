<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customer = resolveCustomerForUser($user);
$customerId = (int) ($customer['customer_id'] ?? 0);
$accountLinked = $customerId > 0;

$search = trim((string) ($_GET['q'] ?? ''));
$activeCategory = trim((string) ($_GET['category'] ?? 'all'));

$vehicles = getAvailableVehicles(300);

$categories = ['all'];
foreach ($vehicles as $vehicle) {
    $category = trim((string) ($vehicle['category_name'] ?? ''));
    if ($category !== '' && !in_array($category, $categories, true)) {
        $categories[] = $category;
    }
}

$filteredVehicles = array_values(array_filter($vehicles, static function (array $vehicle) use ($search, $activeCategory): bool {
    $name = strtolower((string) ($vehicle['name'] ?? ''));
    $plate = strtolower((string) ($vehicle['plate'] ?? ''));
    $category = (string) ($vehicle['category_name'] ?? '');

    if ($activeCategory !== 'all' && $category !== $activeCategory) {
        return false;
    }

    if ($search === '') {
        return true;
    }

    $needle = strtolower($search);
    return str_contains($name, $needle) || str_contains($plate, $needle);
}));

usort($filteredVehicles, static function (array $a, array $b): int {
    return (float) ($a['daily_rate'] ?? 0) <=> (float) ($b['daily_rate'] ?? 0);
});

viewBegin('app', appLayoutData('Browse vehicles', 'vehicles', ['role' => 'customer']));
?>
<section class="bookings-page-head">
    <div class="bookings-page-titlebar">
        <h3>Browse available vehicles</h3>
        <a class="ghost-link" href="booking-create.php">Open booking form</a>
    </div>

    <form method="get" class="bookings-toolbar">
        <input type="search" name="q" placeholder="Search vehicle or plate" value="<?= htmlspecialchars($search) ?>">
        <select name="category">
            <?php foreach ($categories as $category): ?>
                <option value="<?= htmlspecialchars($category) ?>" <?= $activeCategory === $category ? 'selected' : '' ?>>
                    <?= htmlspecialchars($category === 'all' ? 'All categories' : $category) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="ghost-link button-like">Filter</button>
    </form>
</section>

<?php if (!$accountLinked): ?>
    <section class="card">
        <div class="alert-info">Your account is not yet linked to a customer profile. Vehicle browsing is available, but booking requests are disabled until linked.</div>
    </section>
<?php endif; ?>

<section class="card bookings-table-card">
    <div class="table-wrap bookings-table-wrap">
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Category</th>
                    <th>Plate</th>
                    <th>Rate/day</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($filteredVehicles === []): ?>
                <tr>
                    <td colspan="5" class="muted">No available vehicles match your filters.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($filteredVehicles as $vehicle): ?>
                    <?php $vehicleId = (int) ($vehicle['vehicle_id'] ?? 0); ?>
                    <tr>
                        <td><strong><?= htmlspecialchars((string) ($vehicle['name'] ?? '')) ?></strong></td>
                        <td><?= htmlspecialchars((string) ($vehicle['category_name'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string) ($vehicle['plate'] ?? '')) ?></td>
                        <td>P<?= number_format((float) ($vehicle['daily_rate'] ?? 0), 0) ?></td>
                        <td>
                            <?php if ($accountLinked): ?>
                                <a class="primary-btn booking-mini-btn" href="booking-create.php?vehicle_id=<?= $vehicleId ?>">Book now</a>
                            <?php else: ?>
                                <span class="muted">Unavailable</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php viewEnd();
?>
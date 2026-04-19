<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customer = resolveCustomerForUser($user);
$customerId = (int) ($customer['user_id'] ?? 0);
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
        <?php if ($accountLinked): ?>
            <button type="button" class="ghost-link button-like" data-modal-open="bookVehicleModal">Open booking form</button>
        <?php else: ?>
            <span class="muted">Open booking form</span>
        <?php endif; ?>
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
                                <button
                                    type="button"
                                    class="primary-btn booking-mini-btn"
                                    data-modal-open="bookVehicleModal"
                                    data-book-vehicle-id="<?= $vehicleId ?>"
                                    data-book-vehicle-name="<?= htmlspecialchars((string) ($vehicle['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-book-vehicle-plate="<?= htmlspecialchars((string) ($vehicle['plate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-book-vehicle-rate="<?= number_format((float) ($vehicle['daily_rate'] ?? 0), 0) ?>"
                                >Book now</button>
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

<?php viewModalStart('bookVehicleModal', 'Book a vehicle', ['size' => 'md']); ?>
    <form method="post" action="booking-create.php" class="modal-body" id="bookVehicleForm">
        <input type="hidden" name="vehicle_id" id="bookVehicleId">

        <div class="user-info">
            <div class="user-info-item">
                <span class="user-info-label">Vehicle:</span>
                <span class="user-info-value" id="bookVehicleName">Select a vehicle</span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">Plate:</span>
                <span class="user-info-value" id="bookVehiclePlate">N/A</span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">Rate/day:</span>
                <span class="user-info-value" id="bookVehicleRate">P0</span>
            </div>
        </div>

        <label for="bookPickupDate">Pickup date</label>
        <input id="bookPickupDate" type="date" name="pickup_date" required <?= $accountLinked ? '' : 'disabled' ?>>

        <label for="bookReturnDate">Return date</label>
        <input id="bookReturnDate" type="date" name="return_date" required <?= $accountLinked ? '' : 'disabled' ?>>

        <label for="bookVehicleNotes">Notes (optional)</label>
        <textarea id="bookVehicleNotes" name="notes" rows="3" placeholder="Any special requests or notes..." <?= $accountLinked ? '' : 'disabled' ?>></textarea>

        <div class="modal-footer">
            <button type="button" class="ghost-btn" data-modal-close>Cancel</button>
            <button type="submit" class="primary-btn" id="bookVehicleSubmit" <?= $accountLinked ? '' : 'disabled' ?>>Book vehicle</button>
        </div>
    </form>
<?php viewModalEnd(); ?>

<script>
(function () {
    const bookForm = document.getElementById('bookVehicleForm');
    const vehicleIdInput = document.getElementById('bookVehicleId');
    const vehicleName = document.getElementById('bookVehicleName');
    const vehiclePlate = document.getElementById('bookVehiclePlate');
    const vehicleRate = document.getElementById('bookVehicleRate');
    const pickupDate = document.getElementById('bookPickupDate');
    const returnDate = document.getElementById('bookReturnDate');
    const bookSubmit = document.getElementById('bookVehicleSubmit');

    if (!bookForm || !pickupDate || !returnDate) {
        return;
    }

    function formatDate(dateObj) {
        const year = dateObj.getFullYear();
        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
        const day = String(dateObj.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function setDefaultDates() {
        const now = new Date();
        const tomorrow = new Date(now);
        tomorrow.setDate(now.getDate() + 1);

        const todayStr = formatDate(now);
        const tomorrowStr = formatDate(tomorrow);

        pickupDate.min = todayStr;
        returnDate.min = tomorrowStr;

        if (!pickupDate.value || pickupDate.value < todayStr) {
            pickupDate.value = todayStr;
        }
        if (!returnDate.value || returnDate.value < tomorrowStr) {
            returnDate.value = tomorrowStr;
        }
    }

    function applyVehicle(trigger) {
        if (vehicleIdInput) {
            vehicleIdInput.value = trigger.dataset.bookVehicleId || '';
        }
        if (vehicleName) {
            vehicleName.textContent = trigger.dataset.bookVehicleName || 'Select a vehicle';
        }
        if (vehiclePlate) {
            vehiclePlate.textContent = trigger.dataset.bookVehiclePlate || 'N/A';
        }
        if (vehicleRate) {
            vehicleRate.textContent = 'P' + (trigger.dataset.bookVehicleRate || '0');
        }
    }

    document.querySelectorAll('[data-modal-open="bookVehicleModal"]').forEach((trigger) => {
        trigger.addEventListener('click', function () {
            setDefaultDates();
            if (trigger.dataset.bookVehicleId) {
                applyVehicle(trigger);
            }
        });
    });

    pickupDate.addEventListener('change', function () {
        if (!pickupDate.value) {
            return;
        }
        const nextDay = new Date(pickupDate.value + 'T00:00:00');
        nextDay.setDate(nextDay.getDate() + 1);
        const minReturn = formatDate(nextDay);
        returnDate.min = minReturn;
        if (!returnDate.value || returnDate.value < minReturn) {
            returnDate.value = minReturn;
        }
    });

    bookForm.addEventListener('submit', function () {
        if (bookSubmit) {
            bookSubmit.disabled = true;
            bookSubmit.textContent = 'Booking...';
        }
    });

    setDefaultDates();
})();
</script>
<?php viewEnd();
?>
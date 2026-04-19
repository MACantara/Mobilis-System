<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$vehicleId = (int) ($_GET['id'] ?? 0);
$vehicle = getVehicleById($vehicleId);
$categories = getVehicleCategories();
$errors = [];

if ($vehicle === null) {
    renderPageTop('Edit vehicle', 'vehicles', [
        'show_search' => false,
        'show_primary_cta' => false,
    ]);
    echo '<section class="card customer-form-card"><h3>Vehicle not found</h3><p class="muted">The selected vehicle record does not exist.</p><p><a class="ghost-link" href="vehicles.php">Back to vehicles</a></p></section>';
    renderPageBottom();
    exit;
}

$form = [
    'name' => (string) ($vehicle['name'] ?? ''),
    'category_id' => (string) ((int) ($vehicle['category_id'] ?? 0)),
    'plate_number' => (string) ($vehicle['plate'] ?? ''),
    'year' => (string) ($vehicle['year'] ?? ''),
    'color' => (string) ($vehicle['color'] ?? ''),
    'mileage_km' => (string) ((int) ($vehicle['mileage_km'] ?? 0)),
    'status' => (string) ($vehicle['status'] ?? 'available'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if ($form['name'] === '') {
        $errors[] = 'Vehicle name is required.';
    }
    if ((int) $form['category_id'] <= 0) {
        $errors[] = 'Please select a category.';
    }
    if ($form['plate_number'] === '') {
        $errors[] = 'Plate number is required.';
    }
    if ((int) $form['year'] <= 0) {
        $errors[] = 'Please provide a valid year.';
    }

    if ($errors === []) {
        $result = updateVehicleRecord($vehicleId, $form);
        if (($result['ok'] ?? false) === true) {
            header('Location: vehicles.php?notice=vehicle_updated');
            exit;
        }
        $errors[] = (string) ($result['error'] ?? 'Unable to update vehicle.');
    }
}

renderPageTop('Edit vehicle', 'vehicles', [
    'show_search' => false,
    'show_primary_cta' => false,
]);
?>
<section class="card customer-form-card">
    <div class="card-header">
        <h3>Edit vehicle</h3>
        <a class="ghost-link" href="vehicles.php">Back</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="customer-form-grid">
        <label class="full">Vehicle name (Brand Model)
            <input type="text" name="name" value="<?= htmlspecialchars($form['name']) ?>" required>
        </label>

        <label>Category
            <select name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $category): ?>
                    <?php $id = (int) ($category['category_id'] ?? 0); ?>
                    <option value="<?= $id ?>" <?= (string) $id === $form['category_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($category['category_name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Plate number
            <input type="text" name="plate_number" value="<?= htmlspecialchars($form['plate_number']) ?>" required>
        </label>

        <label>Year
            <input type="number" name="year" min="1990" max="2099" value="<?= htmlspecialchars($form['year']) ?>" required>
        </label>

        <label>Color
            <input type="text" name="color" value="<?= htmlspecialchars($form['color']) ?>" required>
        </label>

        <label>Mileage (km)
            <input type="number" name="mileage_km" min="0" value="<?= htmlspecialchars($form['mileage_km']) ?>" required>
        </label>

        <label>Status
            <select name="status" required>
                <?php $statusOptions = ['available', 'rented', 'maintenance']; ?>
                <?php foreach ($statusOptions as $option): ?>
                    <option value="<?= $option ?>" <?= strtolower($form['status']) === $option ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="customer-form-actions full">
            <a class="ghost-link button-like" href="vehicles.php">Cancel</a>
            <button type="submit" class="primary-btn">Save changes</button>
        </div>
    </form>
</section>
<?php renderPageBottom(); ?>

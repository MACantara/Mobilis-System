<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$categories = getVehicleCategories();
$errors = [];
$form = [
    'name' => '',
    'category_id' => '',
    'plate_number' => '',
    'year' => '',
    'color' => '',
    'mileage_km' => '0',
    'status' => 'available',
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
        $result = createVehicleRecord($form);
        if (($result['ok'] ?? false) === true) {
            header('Location: vehicles.php?notice=vehicle_created');
            exit;
        }
        $errors[] = (string) ($result['error'] ?? 'Unable to add vehicle.');
    }
}

renderPageTop('Add vehicle', 'vehicles', [
    'show_search' => false,
    'show_primary_cta' => false,
]);
?>
<section class="card customer-form-card">
    <div class="card-header">
        <h3>New vehicle</h3>
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
            <input type="text" name="name" value="<?= htmlspecialchars($form['name']) ?>" placeholder="Toyota Fortuner" required>
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
            <input type="text" name="plate_number" value="<?= htmlspecialchars($form['plate_number']) ?>" placeholder="ABC 1234" required>
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
                    <option value="<?= $option ?>" <?= $form['status'] === $option ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="customer-form-actions full">
            <a class="ghost-link button-like" href="vehicles.php">Cancel</a>
            <button type="submit" class="primary-btn">Create vehicle</button>
        </div>
    </form>
</section>
<?php renderPageBottom(); ?>

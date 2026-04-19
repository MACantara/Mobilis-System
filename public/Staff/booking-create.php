<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$customers = getCustomers(200);
$vehicles = getVehicles(200);

$errors = [];
$success = '';
$form = [
    'user_id' => (string) ((int) ($_GET['user_id'] ?? 0)),
    'vehicle_id' => (string) ((int) ($_GET['vehicle_id'] ?? 0)),
    'pickup_date' => date('Y-m-d'),
    'return_date' => date('Y-m-d', strtotime('+1 day')),
    'notes' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['user_id'] = (string) ((int) ($_POST['user_id'] ?? 0));
    $form['vehicle_id'] = (string) ((int) ($_POST['vehicle_id'] ?? 0));
    $form['pickup_date'] = trim((string) ($_POST['pickup_date'] ?? ''));
    $form['return_date'] = trim((string) ($_POST['return_date'] ?? ''));
    $form['notes'] = trim((string) ($_POST['notes'] ?? ''));

    $result = createRentalBooking(
        (int) $form['user_id'],
        (int) $form['vehicle_id'],
        $form['pickup_date'],
        $form['return_date'],
        $form['notes']
    );

    if (($result['ok'] ?? false) === true) {
        header('Location: customers.php?notice=booking_created');
        exit;
    }

    $errors[] = (string) ($result['error'] ?? 'Could not create booking.');
}

viewBegin('app', appLayoutData('New booking', 'bookings', [
    'show_search' => false,
    'show_primary_cta' => false,
]));
?>
<section class="card customer-form-card">
    <div class="card-header">
        <h3>Create booking</h3>
        <a class="ghost-link" href="customers.php">Back</a>
    </div>

    <?php if ($success !== ''): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <?php if ($errors !== []): ?>
        <div class="alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="customer-form-grid">
        <label>Customer
            <select name="user_id" required>
                <option value="">Select customer</option>
                <?php foreach ($customers as $customer): ?>
                    <?php $id = (int) ($customer['user_id'] ?? 0); ?>
                    <option value="<?= $id ?>" <?= (string) $id === $form['user_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($customer['name'] ?? '')) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Vehicle
            <select name="vehicle_id" required>
                <option value="">Select vehicle</option>
                <?php foreach ($vehicles as $vehicle): ?>
                    <?php $id = (int) ($vehicle['vehicle_id'] ?? 0); ?>
                    <option value="<?= $id ?>" <?= (string) $id === $form['vehicle_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($vehicle['name'] ?? '')) ?> (<?= htmlspecialchars((string) ($vehicle['plate'] ?? '')) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>Pickup date
            <input type="date" name="pickup_date" value="<?= htmlspecialchars($form['pickup_date']) ?>" required>
        </label>

        <label>Return date
            <input type="date" name="return_date" value="<?= htmlspecialchars($form['return_date']) ?>" required>
        </label>

        <label class="full">Notes
            <textarea name="notes" rows="3"><?= htmlspecialchars($form['notes']) ?></textarea>
        </label>

        <div class="customer-form-actions full">
            <a class="ghost-link button-like" href="customers.php">Cancel</a>
            <button type="submit" class="primary-btn">Create booking</button>
        </div>
    </form>
</section>
<?php viewEnd();
?>

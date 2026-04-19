<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$rentalId = (int) ($_GET['id'] ?? 0);
$booking = getBookingById($rentalId);
$errors = [];

if ($booking === null) {
    renderPageTop('Edit booking', 'bookings', [
        'show_search' => false,
        'show_primary_cta' => false,
    ]);
    echo '<section class="card customer-form-card"><h3>Booking not found</h3><p class="muted">The selected booking record does not exist.</p><p><a class="ghost-link" href="bookings.php">Back to bookings</a></p></section>';
    renderPageBottom();
    exit;
}

$form = [
    'pickup_date' => (string) ($booking['pickup_date'] ?? ''),
    'return_date' => (string) ($booking['return_date'] ?? ''),
    'status' => (string) ($booking['status'] ?? 'confirmed'),
    'notes' => (string) ($booking['notes'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['pickup_date'] = trim((string) ($_POST['pickup_date'] ?? ''));
    $form['return_date'] = trim((string) ($_POST['return_date'] ?? ''));
    $form['status'] = trim((string) ($_POST['status'] ?? 'confirmed'));
    $form['notes'] = trim((string) ($_POST['notes'] ?? ''));

    $result = updateBookingRecord(
        $rentalId,
        $form['pickup_date'],
        $form['return_date'],
        $form['status'],
        $form['notes']
    );

    if (($result['ok'] ?? false) === true) {
        header('Location: bookings.php?notice=updated');
        exit;
    }

    $errors[] = (string) ($result['error'] ?? 'Unable to update booking.');
}

renderPageTop('Edit booking', 'bookings', [
    'show_search' => false,
    'show_primary_cta' => false,
]);
?>
<section class="card customer-form-card">
    <div class="card-header">
        <h3>Edit booking #BK-<?= str_pad((string) ((int) $booking['rental_id']), 4, '0', STR_PAD_LEFT) ?></h3>
        <a class="ghost-link" href="bookings.php">Back</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="customer-form-grid">
        <label>Pickup date
            <input type="date" name="pickup_date" value="<?= htmlspecialchars($form['pickup_date']) ?>" required>
        </label>

        <label>Return date
            <input type="date" name="return_date" value="<?= htmlspecialchars($form['return_date']) ?>" required>
        </label>

        <label>Status
            <select name="status" required>
                <?php $statusOptions = ['pending', 'confirmed', 'completed', 'cancelled']; ?>
                <?php foreach ($statusOptions as $option): ?>
                    <option value="<?= $option ?>" <?= strtolower($form['status']) === $option ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($option)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="full">Notes
            <textarea name="notes" rows="3"><?= htmlspecialchars($form['notes']) ?></textarea>
        </label>

        <div class="customer-form-actions full">
            <a class="ghost-link button-like" href="bookings.php">Cancel</a>
            <button type="submit" class="primary-btn">Save changes</button>
        </div>
    </form>
</section>
<?php renderPageBottom(); ?>

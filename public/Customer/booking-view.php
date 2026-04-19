<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customer = resolveCustomerForUser($user);
$customerId = (int) ($customer['user_id'] ?? 0);
$accountLinked = $customerId > 0;

$rentalId = (int) ($_GET['id'] ?? 0);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim((string) ($_POST['action'] ?? '')));
    if ($action === 'cancel') {
        if (!$accountLinked) {
            $errors[] = 'Your account is not linked to a customer profile yet.';
        } else {
            $reason = trim((string) ($_POST['reason'] ?? ''));
            $result = cancelCustomerBooking($customerId, $rentalId, $reason);
            if (($result['ok'] ?? false) === true) {
                header('Location: bookings.php?notice=booking_cancelled');
                exit;
            }
            $errors[] = (string) ($result['error'] ?? 'Could not cancel booking.');
        }
    }
}

$booking = $accountLinked ? getCustomerBookingById($customerId, $rentalId) : null;
$statusRaw = strtolower((string) ($booking['status'] ?? 'pending'));
$statusKey = $statusRaw === 'active' ? 'confirmed' : $statusRaw;
$canCancel = in_array($statusKey, ['pending', 'confirmed'], true);

viewBegin('app', appLayoutData('Booking details', 'bookings', ['role' => 'customer']));
?>
<section class="card customer-form-card">
    <?php if (!$accountLinked): ?>
        <h3>Booking unavailable</h3>
        <p class="muted">Your account is not yet linked to a customer profile.</p>
        <p><a class="ghost-link" href="bookings.php">Back to bookings</a></p>
    <?php elseif ($booking === null): ?>
        <h3>Booking not found</h3>
        <p class="muted">The selected booking does not exist or does not belong to your account.</p>
        <p><a class="ghost-link" href="bookings.php">Back to bookings</a></p>
    <?php else: ?>
        <div class="card-header">
            <h3>Booking #BK-<?= str_pad((string) ((int) $booking['rental_id']), 4, '0', STR_PAD_LEFT) ?></h3>
            <div class="customer-form-actions">
                <a class="ghost-link button-like" href="bookings.php">Back</a>
                <?php if ((int) ($booking['vehicle_id'] ?? 0) > 0): ?>
                    <a class="ghost-link button-like" href="booking-create.php?vehicle_id=<?= (int) $booking['vehicle_id'] ?>">Book similar</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($errors !== []): ?>
            <div class="alert-error">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <dl class="customer-meta-list">
            <div><dt>Vehicle</dt><dd><?= htmlspecialchars((string) ($booking['vehicle'] ?? 'N/A')) ?></dd></div>
            <div><dt>Pickup date</dt><dd><?= htmlspecialchars((string) ($booking['pickup_date'] ?? 'N/A')) ?></dd></div>
            <div><dt>Return date</dt><dd><?= htmlspecialchars((string) ($booking['return_date'] ?? 'N/A')) ?></dd></div>
            <div><dt>Trip days</dt><dd><?= (int) ($booking['days'] ?? 0) ?></dd></div>
            <div><dt>Status</dt><dd><span class="pill <?= htmlspecialchars($statusKey) ?>"><?= htmlspecialchars(ucfirst($statusKey)) ?></span></dd></div>
            <div><dt>Payment</dt><dd><span class="pill <?= htmlspecialchars((string) ($booking['payment_status'] ?? 'unpaid')) ?>"><?= htmlspecialchars(ucfirst((string) ($booking['payment_status'] ?? 'unpaid'))) ?></span></dd></div>
            <div><dt>Method</dt><dd><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($booking['payment_method'] ?? 'pending')))) ?></dd></div>
            <div><dt>Total</dt><dd><strong>P<?= number_format((float) ($booking['total'] ?? 0), 2) ?></strong></dd></div>
            <div><dt>Invoice</dt><dd>INV-<?= str_pad((string) ((int) ($booking['invoice_id'] ?? 0)), 4, '0', STR_PAD_LEFT) ?></dd></div>
        </dl>

        <div class="booking-actions" style="margin-top: 12px; justify-content: flex-start;">
            <?php if (in_array((string) ($booking['payment_status'] ?? 'unpaid'), ['unpaid', 'partial'], true)): ?>
                <a class="primary-btn booking-mini-btn" href="payments.php">Pay invoice</a>
            <?php endif; ?>

            <?php if ($canCancel): ?>
                <form
                    method="post"
                    class="booking-actions"
                    data-confirm-submit
                    data-confirm-title="Cancel booking"
                    data-confirm-message="This booking will be cancelled and cannot be auto-reversed. Continue?"
                    data-confirm-label="Cancel booking"
                    data-cancel-label="Keep booking"
                    data-confirm-danger="1"
                >
                    <input type="hidden" name="action" value="cancel">
                    <input type="text" name="reason" placeholder="Reason for cancellation (optional)" maxlength="200">
                    <button type="submit" class="ghost-link button-like booking-mini-btn">Cancel booking</button>
                </form>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
<?php viewEnd();
?>
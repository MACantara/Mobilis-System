<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$rentalId = (int) ($_GET['id'] ?? 0);
$showReceipt = isset($_GET['receipt']);
$booking = getBookingById($rentalId);

renderPageTop('Booking details', 'bookings', [
    'show_search' => false,
    'show_primary_cta' => false,
]);
?>
<section class="card customer-form-card">
    <?php if ($booking === null): ?>
        <h3>Booking not found</h3>
        <p class="muted">The selected booking could not be found.</p>
        <p><a class="ghost-link" href="bookings.php">Back to bookings</a></p>
    <?php else: ?>
        <div class="card-header">
            <h3>Booking #BK-<?= str_pad((string) ((int) $booking['rental_id']), 4, '0', STR_PAD_LEFT) ?></h3>
            <div class="customer-form-actions">
                <a class="ghost-link button-like" href="bookings.php">Back</a>
                <a class="ghost-link button-like" href="booking-edit.php?id=<?= (int) $booking['rental_id'] ?>">Edit</a>
            </div>
        </div>

        <dl class="customer-meta-list">
            <div><dt>Customer</dt><dd><?= htmlspecialchars((string) ($booking['customer'] ?? 'N/A')) ?></dd></div>
            <div><dt>Vehicle</dt><dd><?= htmlspecialchars((string) ($booking['vehicle'] ?? 'N/A')) ?></dd></div>
            <div><dt>Pickup date</dt><dd><?= htmlspecialchars((string) ($booking['pickup_date'] ?? 'N/A')) ?></dd></div>
            <div><dt>Return date</dt><dd><?= htmlspecialchars((string) ($booking['return_date'] ?? 'N/A')) ?></dd></div>
            <div><dt>Status</dt><dd><?= htmlspecialchars(ucfirst((string) ($booking['status'] ?? 'pending'))) ?></dd></div>
            <div><dt>Payment</dt><dd><?= htmlspecialchars(ucfirst((string) ($booking['payment_status'] ?? 'unpaid'))) ?></dd></div>
            <div><dt>Total</dt><dd>P<?= number_format((float) ($booking['total'] ?? 0), 2) ?></dd></div>
        </dl>

        <?php if ($showReceipt): ?>
            <div class="mini-stats" style="margin-top: 12px;">
                <div>
                    <span>Receipt status</span>
                    <strong><?= htmlspecialchars(strtoupper((string) ($booking['payment_status'] ?? 'UNPAID'))) ?></strong>
                </div>
                <div>
                    <span>Receipt amount</span>
                    <strong>P<?= number_format((float) ($booking['total'] ?? 0), 2) ?></strong>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</section>
<?php renderPageBottom(); ?>

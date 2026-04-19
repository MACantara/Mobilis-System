<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customer = resolveCustomerForUser($user);
$customerId = (int) ($customer['user_id'] ?? 0);
$accountLinked = $customerId > 0;

// Get customer-specific data
$customerBookings = $accountLinked ? getCustomerBookingsByCustomerId($customerId, 10) : [];
$availableVehicles = getAvailableVehicles(6);
$customerPayments = $accountLinked ? getCustomerPaymentsByCustomerId($customerId, 5) : [];

if (!function_exists('vehicleEmoji')) {
    function vehicleEmoji(array $vehicle): string
    {
        if (!empty($vehicle['icon'])) {
            return (string) $vehicle['icon'];
        }

        $name = strtolower((string) ($vehicle['name'] ?? ''));
        if (str_contains($name, 'hiace') || str_contains($name, 'van')) {
            return '🚐';
        }
        if (str_contains($name, 'ranger') || str_contains($name, 'strada') || str_contains($name, 'pickup')) {
            return '🛻';
        }
        if (str_contains($name, 'fortuner') || str_contains($name, 'everest') || str_contains($name, 'montero')) {
            return '🚙';
        }
        return '🚗';
    }
}

if (!function_exists('formatBookingDateRange')) {
    function formatBookingDateRange(string $pickupDate, string $returnDate): string
    {
        $pickup = date_create($pickupDate);
        $return = date_create($returnDate);
        if (!$pickup || !$return) {
            return $pickupDate . ' - ' . $returnDate;
        }

        if ($pickupDate === $returnDate) {
            return date_format($pickup, 'M j');
        }

        return date_format($pickup, 'M j') . ' - ' . date_format($return, 'j');
    }
}

if (!function_exists('bookingStatusKey')) {
    function bookingStatusKey(array $booking): string
    {
        $status = strtolower((string) ($booking['status'] ?? 'pending'));
        $paymentStatus = strtolower((string) ($booking['payment_status'] ?? ''));

        if ($status === 'active' || $status === 'confirmed') {
            if ($paymentStatus === 'unpaid' || $paymentStatus === 'partial') {
                return 'awaiting-payment';
            }
            return 'confirmed';
        }

        if ($status === 'completed') {
            return 'completed';
        }

        if ($status === 'cancelled') {
            return 'cancelled';
        }

        if ($status === 'pending') {
            return 'pending';
        }

        return $status;
    }
}

viewBegin('app', appLayoutData('Dashboard', 'dashboard', ['role' => 'customer']));
?>
<section class="page-content-head">
    <h3>Welcome, <?= htmlspecialchars((string) ($user['name'] ?? 'Customer')) ?></h3>
</section>

<?php if (!$accountLinked): ?>
    <section class="card">
        <div class="alert-info">Your account is authenticated but not yet linked to a customer profile record. Booking and payment history will appear once linked by the admin team.</div>
    </section>
<?php endif; ?>

<section class="content-grid metric-grid">
    <article class="card metric-card">
        <p>Active bookings</p>
        <h4><?= count(array_filter($customerBookings, fn($b) => in_array(($b['status'] ?? ''), ['active', 'confirmed']))) ?></h4>
        <span>rentals in progress</span>
    </article>
    <article class="card metric-card">
        <p>Total spent</p>
        <h4>P<?= number_format(array_sum(array_map(static fn(array $payment): float => (float) ($payment['total_amount'] ?? 0), $customerPayments)), 2) ?></h4>
        <span>lifetime payments</span>
    </article>
    <article class="card metric-card">
        <p>Completed trips</p>
        <h4><?= count(array_filter($customerBookings, fn($b) => ($b['status'] ?? '') === 'completed')) ?></h4>
        <span>successful rentals</span>
    </article>
    <article class="card metric-card">
        <p>Pending payments</p>
        <h4><?= count(array_filter($customerPayments, fn($p) => in_array(($p['payment_status'] ?? ''), ['unpaid', 'partial']))) ?></h4>
        <span>invoices due</span>
    </article>
</section>

<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h4>My bookings</h4>
            <a href="bookings.php" class="ghost-link">View all</a>
        </div>
        <ul class="list clean">
            <?php if ($customerBookings === []): ?>
                <p class="muted">No bookings yet. <a href="booking-create.php" class="text-link">Book a vehicle</a></p>
            <?php else: ?>
                <?php foreach (array_slice($customerBookings, 0, 5) as $booking): ?>
                    <?php $statusKey = bookingStatusKey($booking); ?>
                    <li class="booking-item">
                        <div class="booking-item-left">
                            <span class="booking-avatar"><?= htmlspecialchars(vehicleEmoji($booking)) ?></span>
                            <div>
                            <strong><?= htmlspecialchars((string) $booking['vehicle']) ?></strong>
                            <p><?= htmlspecialchars(formatBookingDateRange((string) $booking['pickup_date'], (string) $booking['return_date'])) ?></p>
                            </div>
                        </div>
                        <span class="pill <?= htmlspecialchars($statusKey) ?>"><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $statusKey))) ?></span>
                    </li>
                <?php endforeach; ?>
            <?php endif; ?>
        </ul>
    </article>

    <article class="card">
        <div class="card-header">
            <h4>Available vehicles</h4>
            <a href="vehicles.php" class="ghost-link">View fleet</a>
        </div>
        <ul class="list clean">
            <?php foreach ($availableVehicles as $vehicle): ?>
                <li>
                    <div class="status-item-left">
                        <span class="status-emoji"><?= htmlspecialchars(vehicleEmoji($vehicle)) ?></span>
                        <div>
                        <strong><?= htmlspecialchars((string) $vehicle['name']) ?></strong>
                        <p><?= htmlspecialchars((string) $vehicle['plate']) ?></p>
                        </div>
                    </div>
                    <span class="pill available">P<?= number_format((float) ($vehicle['daily_rate'] ?? 0), 0) ?>/day</span>
                </li>
            <?php endforeach; ?>
        </ul>
    </article>
</section>

<section class="card">
    <div class="card-header">
        <h4>Recent payments</h4>
        <a href="payments.php" class="ghost-link">View all</a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Vehicle</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($customerPayments === []): ?>
                <tr><td colspan="5" class="muted">No payments yet.</td></tr>
            <?php else: ?>
                <?php foreach (array_slice($customerPayments, 0, 5) as $payment): ?>
                    <?php $status = strtolower(str_replace(' ', '-', (string) ($payment['payment_status'] ?? 'unpaid'))); ?>
                    <tr>
                        <td>INV-<?= str_pad((string) ((int) $payment['invoice_id']), 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars((string) $payment['vehicle']) ?></td>
                        <td><?= htmlspecialchars((string) $payment['issued_at']) ?></td>
                        <td><strong>P<?= number_format((float) $payment['total_amount'], 2) ?></strong></td>
                        <td><span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst((string) $payment['payment_status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php viewEnd();
?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customer = resolveCustomerForUser($user);
$customerId = (int) ($customer['user_id'] ?? 0);
$accountLinked = $customerId > 0;

$notice = (string) ($_GET['notice'] ?? '');
$noticeMessage = '';
if ($notice === 'booking_created') {
    $noticeMessage = 'Your booking has been created successfully.';
} elseif ($notice === 'booking_cancelled') {
    $noticeMessage = 'Your booking was cancelled successfully.';
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

if (!function_exists('bookingStatusLabel')) {
    function bookingStatusLabel(string $statusKey): string
    {
        return match ($statusKey) {
            'awaiting-payment' => 'Awaiting payment',
            'confirmed' => 'Confirmed',
            'pending' => 'Pending',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => ucfirst(str_replace('-', ' ', $statusKey)),
        };
    }
}

if (!function_exists('bookingDateLabel')) {
    function bookingDateLabel(string $pickupDate, string $returnDate): string
    {
        $pickup = strtotime($pickupDate);
        $return = strtotime($returnDate);
        if ($pickup === false || $return === false) {
            return $pickupDate . ' - ' . $returnDate;
        }

        if (date('Y-m-d', $pickup) === date('Y-m-d', $return)) {
            return date('M j, Y', $pickup);
        }

        if (date('Y', $pickup) === date('Y', $return)) {
            return date('M j', $pickup) . ' - ' . date('j, Y', $return);
        }

        return date('M j, Y', $pickup) . ' - ' . date('M j, Y', $return);
    }
}

$activeStatus = strtolower((string) ($_GET['status'] ?? 'all'));
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$allBookings = $accountLinked ? getCustomerBookingsByCustomerId($customerId, 500) : [];
$baseFiltered = [];

foreach ($allBookings as $booking) {
    $booking['status_key'] = bookingStatusKey($booking);
    $booking['status_label'] = bookingStatusLabel($booking['status_key']);
    $baseFiltered[] = $booking;
}

$statusFiltered = $baseFiltered;
if ($activeStatus !== 'all') {
    $statusFiltered = array_values(array_filter(
        $baseFiltered,
        static fn(array $booking): bool => (string) ($booking['status_key'] ?? '') === $activeStatus
    ));
}

$totalFiltered = count($statusFiltered);
$totalPages = max(1, (int) ceil($totalFiltered / $perPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $perPage;
$pagedBookings = array_slice($statusFiltered, $offset, $perPage);
$startItem = $totalFiltered > 0 ? $offset + 1 : 0;
$endItem = min($offset + $perPage, $totalFiltered);

$tabs = [
    'all' => 'All bookings',
    'confirmed' => 'Confirmed',
    'pending' => 'Pending',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];

if (!function_exists('bookingsQuery')) {
    function bookingsQuery(array $overrides = []): string
    {
        $params = [
            'status' => strtolower((string) ($_GET['status'] ?? 'all')),
            'page' => (string) max(1, (int) ($_GET['page'] ?? 1)),
        ];

        foreach ($overrides as $key => $value) {
            $params[$key] = (string) $value;
        }

        if (($params['status'] ?? 'all') === 'all') {
            unset($params['status']);
        }
        if (($params['page'] ?? '1') === '1') {
            unset($params['page']);
        }

        return http_build_query($params);
    }
}

if (!function_exists('bookingsPaginationItems')) {
    function bookingsPaginationItems(int $currentPage, int $totalPages): array
    {
        if ($totalPages <= 1) {
            return [1];
        }

        $pages = [1, $totalPages];
        for ($i = 2; $i <= min(3, $totalPages - 1); $i++) {
            $pages[] = $i;
        }
        for ($i = max(1, $currentPage - 1); $i <= min($totalPages, $currentPage + 1); $i++) {
            $pages[] = $i;
        }

        $pages = array_values(array_unique($pages));
        sort($pages);

        $items = [];
        $previous = null;
        foreach ($pages as $page) {
            if ($previous !== null && $page - $previous > 1) {
                $items[] = '...';
            }
            $items[] = $page;
            $previous = $page;
        }

        return $items;
    }
}

$paginationItems = bookingsPaginationItems($currentPage, $totalPages);

viewBegin('app', appLayoutData('My Bookings', 'bookings', ['role' => 'customer']));
?>
<section class="bookings-page-head">
    <div class="bookings-page-titlebar">
        <h3>My bookings</h3>
        <form class="bookings-toolbar" method="get" action="bookings.php">
            <a class="primary-btn" href="booking-create.php">+ New booking</a>
        </form>
    </div>

    <nav class="bookings-tabs" aria-label="Booking status tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <?php $isActiveTab = $activeStatus === $key; ?>
            <a class="bookings-tab<?= $isActiveTab ? ' active' : '' ?>" href="bookings.php?<?= htmlspecialchars(bookingsQuery(['status' => $key, 'page' => 1])) ?>">
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</section>

<?php if ($noticeMessage !== ''): ?>
    <section class="card">
        <div class="alert-success"><?= htmlspecialchars($noticeMessage) ?></div>
    </section>
<?php endif; ?>

<?php if (!$accountLinked): ?>
    <section class="card">
        <div class="alert-info">Your account is not yet linked to a customer profile. Please contact support to enable booking history.</div>
    </section>
<?php endif; ?>

<section class="card bookings-table-card">
    <div class="bookings-table-wrap table-wrap">
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Vehicle</th>
                    <th>Dates</th>
                    <th>Days</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pagedBookings as $booking): ?>
                <?php
                $statusKey = (string) ($booking['status_key'] ?? 'pending');
                $statusClass = $statusKey === 'awaiting-payment' ? 'awaiting payment' : $statusKey;
                ?>
                <tr>
                    <td class="booking-id-cell">BK-<?= str_pad((string) ((int) $booking['rental_id']), 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars((string) $booking['vehicle']) ?></td>
                    <td><?= htmlspecialchars(bookingDateLabel((string) $booking['pickup_date'], (string) $booking['return_date'])) ?></td>
                    <td><?= (int) $booking['days'] ?></td>
                    <td><strong>P<?= number_format((float) $booking['total'], 0) ?></strong></td>
                    <td><span class="pill <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars((string) ($booking['status_label'] ?? 'Pending')) ?></span></td>
                    <td>
                        <div class="booking-actions">
                            <?php if ($statusKey === 'completed'): ?>
                                <a class="ghost-link button-like booking-mini-btn" href="booking-view.php?id=<?= (int) $booking['rental_id'] ?>">Receipt</a>
                            <?php elseif ($statusKey === 'cancelled'): ?>
                                <a class="ghost-link button-like booking-mini-btn" href="booking-create.php">Rebook</a>
                            <?php else: ?>
                                <a class="ghost-link button-like booking-mini-btn" href="booking-view.php?id=<?= (int) $booking['rental_id'] ?>">View</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pagedBookings === []): ?>
            <p class="muted">No bookings found. <a href="booking-create.php" class="text-link">Book a vehicle</a></p>
        <?php endif; ?>
    </div>

    <?php if ($pagedBookings !== []): ?>
        <div class="bookings-footer-row">
            <p>Showing <?= $startItem ?>-<?= $endItem ?> of <?= $totalFiltered ?> bookings</p>

            <div class="bookings-pagination">
                <a class="ghost-link button-like page-btn<?= $currentPage <= 1 ? ' disabled' : '' ?>" href="bookings.php?<?= htmlspecialchars(bookingsQuery(['page' => max(1, $currentPage - 1)])) ?>">&lsaquo;</a>
                <?php foreach ($paginationItems as $item): ?>
                    <?php if ($item === '...'): ?>
                        <span class="page-ellipsis">...</span>
                    <?php else: ?>
                        <a class="ghost-link button-like page-btn<?= $currentPage === (int) $item ? ' active' : '' ?>" href="bookings.php?<?= htmlspecialchars(bookingsQuery(['page' => (int) $item])) ?>"><?= (int) $item ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
                <a class="ghost-link button-like page-btn<?= $currentPage >= $totalPages ? ' disabled' : '' ?>" href="bookings.php?<?= htmlspecialchars(bookingsQuery(['page' => min($totalPages, $currentPage + 1)])) ?>">&rsaquo;</a>
            </div>
        </div>
    <?php endif; ?>
</section>
<?php viewEnd();
?>

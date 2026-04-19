<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

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

if (!function_exists('bookingInitials')) {
    function bookingInitials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach ((array) $parts as $part) {
            if ($part !== '') {
                $initials .= strtoupper(substr($part, 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }
}

$activeStatus = strtolower((string) ($_GET['status'] ?? 'all'));
$searchTerm = trim((string) ($_GET['q'] ?? ''));
$fromDate = trim((string) ($_GET['from'] ?? ''));
$toDate = trim((string) ($_GET['to'] ?? ''));
$currentPage = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 6;

$notice = (string) ($_GET['notice'] ?? '');
$noticeMessage = '';
$noticeClass = 'alert-success';
if ($notice === 'action_success') {
    $noticeMessage = 'Booking action completed successfully.';
} elseif ($notice === 'updated') {
    $noticeMessage = 'Booking updated successfully.';
} elseif ($notice === 'action_error') {
    $noticeMessage = 'Booking action failed. Please try again.';
    $noticeClass = 'alert-error';
}

$allBookings = getBookings(500);
$baseFiltered = [];

foreach ($allBookings as $booking) {
    $booking['status_key'] = bookingStatusKey($booking);
    $booking['status_label'] = bookingStatusLabel($booking['status_key']);

    $bookingIdText = 'BK-' . str_pad((string) ((int) ($booking['rental_id'] ?? 0)), 4, '0', STR_PAD_LEFT);
    $haystack = strtolower($bookingIdText . ' ' . (string) ($booking['customer'] ?? '') . ' ' . (string) ($booking['vehicle'] ?? ''));

    if ($searchTerm !== '' && !str_contains($haystack, strtolower($searchTerm))) {
        continue;
    }

    $pickupDate = (string) ($booking['pickup_date'] ?? '');
    if ($fromDate !== '' && $pickupDate < $fromDate) {
        continue;
    }
    if ($toDate !== '' && $pickupDate > $toDate) {
        continue;
    }

    $baseFiltered[] = $booking;
}

$tabCounts = [
    'all' => count($baseFiltered),
    'confirmed' => 0,
    'pending' => 0,
    'completed' => 0,
    'cancelled' => 0,
];

foreach ($baseFiltered as $booking) {
    $key = (string) ($booking['status_key'] ?? '');
    if (isset($tabCounts[$key])) {
        $tabCounts[$key]++;
    }
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
            'q' => trim((string) ($_GET['q'] ?? '')),
            'from' => trim((string) ($_GET['from'] ?? '')),
            'to' => trim((string) ($_GET['to'] ?? '')),
            'page' => (string) max(1, (int) ($_GET['page'] ?? 1)),
        ];

        foreach ($overrides as $key => $value) {
            $params[$key] = (string) $value;
        }

        if (($params['status'] ?? 'all') === 'all') {
            unset($params['status']);
        }
        if (($params['q'] ?? '') === '') {
            unset($params['q']);
        }
        if (($params['from'] ?? '') === '') {
            unset($params['from']);
        }
        if (($params['to'] ?? '') === '') {
            unset($params['to']);
        }
        if (($params['page'] ?? '1') === '1') {
            unset($params['page']);
        }

        return http_build_query($params);
    }
}

renderPageTop('Bookings', 'bookings', [
    'show_search' => false,
    'show_primary_cta' => false,
]);
?>
<?php if ($noticeMessage !== ''): ?>
    <div class="<?= htmlspecialchars($noticeClass) ?> customers-alert"><?= htmlspecialchars($noticeMessage) ?></div>
<?php endif; ?>

<section class="card bookings-page-card">
    <div class="card-header bookings-head-row">
        <h3>All bookings</h3>
        <form class="bookings-toolbar" method="get" action="bookings.php">
            <input type="search" name="q" placeholder="Search bookings..." value="<?= htmlspecialchars($searchTerm) ?>">
            <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
            <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">
            <input type="hidden" name="status" value="<?= htmlspecialchars($activeStatus) ?>">
            <button class="ghost-link button-like" type="submit">Date range</button>
            <a class="ghost-link button-like" href="bookings-export.php?<?= htmlspecialchars(bookingsQuery(['page' => 1])) ?>">Export</a>
            <a class="primary-btn" href="booking-create.php">+ New booking</a>
        </form>
    </div>

    <nav class="bookings-tabs" aria-label="Booking status tabs">
        <?php foreach ($tabs as $key => $label): ?>
            <?php $isActiveTab = $activeStatus === $key; ?>
            <a class="bookings-tab<?= $isActiveTab ? ' active' : '' ?>" href="bookings.php?<?= htmlspecialchars(bookingsQuery(['status' => $key, 'page' => 1])) ?>">
                <?= htmlspecialchars($label) ?>
                <?php if ($key === 'all' || ($tabCounts[$key] ?? 0) > 0): ?>
                    <span class="bookings-tab-count"><?= (int) ($tabCounts[$key] ?? 0) ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="bookings-table-wrap table-wrap">
        <table class="bookings-table">
            <thead>
                <tr>
                    <th>Booking ID</th>
                    <th>Customer</th>
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
                    <td>BK-<?= str_pad((string) ((int) $booking['rental_id']), 4, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <div class="booking-customer-cell">
                            <span class="booking-avatar"><?= htmlspecialchars(bookingInitials((string) $booking['customer'])) ?></span>
                            <strong><?= htmlspecialchars((string) $booking['customer']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars((string) $booking['vehicle']) ?></td>
                    <td><?= htmlspecialchars(bookingDateLabel((string) $booking['pickup_date'], (string) $booking['return_date'])) ?></td>
                    <td><?= (int) $booking['days'] ?></td>
                    <td>P<?= number_format((float) $booking['total'], 2) ?></td>
                    <td><span class="pill <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars((string) ($booking['status_label'] ?? 'Pending')) ?></span></td>
                    <td>
                        <div class="booking-actions">
                            <a class="ghost-link button-like" href="booking-view.php?id=<?= (int) $booking['rental_id'] ?>">View</a>

                            <?php if ($statusKey === 'pending'): ?>
                                <form method="post" action="booking-action.php">
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?= (int) $booking['rental_id'] ?>">
                                    <input type="hidden" name="redirect" value="bookings.php?<?= htmlspecialchars(bookingsQuery()) ?>">
                                    <button class="ghost-link button-like" type="submit">Approve</button>
                                </form>
                            <?php elseif ($statusKey === 'awaiting-payment'): ?>
                                <form method="post" action="booking-action.php">
                                    <input type="hidden" name="action" value="remind">
                                    <input type="hidden" name="id" value="<?= (int) $booking['rental_id'] ?>">
                                    <input type="hidden" name="redirect" value="bookings.php?<?= htmlspecialchars(bookingsQuery()) ?>">
                                    <button class="ghost-link button-like" type="submit">Remind</button>
                                </form>
                            <?php elseif ($statusKey === 'completed'): ?>
                                <a class="ghost-link button-like" href="booking-view.php?id=<?= (int) $booking['rental_id'] ?>&receipt=1">Receipt</a>
                            <?php elseif ($statusKey === 'cancelled'): ?>
                                <a class="ghost-link button-like" href="booking-create.php?customer_id=<?= (int) ($booking['customer_id'] ?? 0) ?>">Rebook</a>
                            <?php else: ?>
                                <a class="ghost-link button-like" href="booking-edit.php?id=<?= (int) $booking['rental_id'] ?>">Edit</a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($pagedBookings === []): ?>
            <p class="muted">No bookings found for the selected filters.</p>
        <?php endif; ?>
    </div>

    <div class="bookings-footer-row">
        <p>Showing <?= $startItem ?>-<?= $endItem ?> of <?= $totalFiltered ?> bookings</p>

        <div class="bookings-pagination">
            <a class="ghost-link button-like<?= $currentPage <= 1 ? ' disabled' : '' ?>" href="bookings.php?<?= htmlspecialchars(bookingsQuery(['page' => max(1, $currentPage - 1)])) ?>">&lsaquo;</a>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a class="ghost-link button-like<?= $currentPage === $i ? ' active' : '' ?>" href="bookings.php?<?= htmlspecialchars(bookingsQuery(['page' => $i])) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <a class="ghost-link button-like<?= $currentPage >= $totalPages ? ' disabled' : '' ?>" href="bookings.php?<?= htmlspecialchars(bookingsQuery(['page' => min($totalPages, $currentPage + 1)])) ?>">&rsaquo;</a>
        </div>
    </div>
</section>
<?php renderPageBottom(); ?>

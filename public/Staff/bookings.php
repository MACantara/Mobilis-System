<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
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

viewBegin('app', appLayoutData('Bookings', 'bookings', [
    'show_search' => false,
    'show_primary_cta' => false,
]));
?>
<?php if ($noticeMessage !== ''): ?>
    <div class="<?= htmlspecialchars($noticeClass) ?> customers-alert"><?= htmlspecialchars($noticeMessage) ?></div>
<?php endif; ?>

<section class="bookings-page-head">
    <div class="bookings-page-titlebar">
        <h3>All bookings</h3>
        <form class="bookings-toolbar" method="get" action="bookings.php">
            <div class="bookings-search-wrap">
                <span aria-hidden="true">🔍</span>
                <input type="search" name="q" placeholder="Search bookings..." value="<?= htmlspecialchars($searchTerm) ?>">
            </div>

            <input type="hidden" name="status" value="<?= htmlspecialchars($activeStatus) ?>">

            <details class="bookings-date-range">
                <summary class="ghost-link button-like">📅 Date range</summary>
                <div class="bookings-date-panel">
                    <label>
                        <span>From</span>
                        <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
                    </label>
                    <label>
                        <span>To</span>
                        <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">
                    </label>
                    <button class="ghost-link button-like" type="submit">Apply</button>
                </div>
            </details>

            <a class="ghost-link button-like" href="bookings-export.php?<?= htmlspecialchars(bookingsQuery(['page' => 1])) ?>">Export</a>
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

<section class="card bookings-table-card">
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
                    <td class="booking-id-cell">BK-<?= str_pad((string) ((int) $booking['rental_id']), 4, '0', STR_PAD_LEFT) ?></td>
                    <td>
                        <div class="booking-customer-cell">
                            <span class="booking-avatar"><?= htmlspecialchars(bookingInitials((string) $booking['customer'])) ?></span>
                            <strong><?= htmlspecialchars((string) $booking['customer']) ?></strong>
                        </div>
                    </td>
                    <td><?= htmlspecialchars((string) $booking['vehicle']) ?></td>
                    <td><?= htmlspecialchars(bookingDateLabel((string) $booking['pickup_date'], (string) $booking['return_date'])) ?></td>
                    <td><?= (int) $booking['days'] ?></td>
                    <td><strong>P<?= number_format((float) $booking['total'], 0) ?></strong></td>
                    <td><span class="pill <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars((string) ($booking['status_label'] ?? 'Pending')) ?></span></td>
                    <td>
                        <div class="booking-actions">
                            <a class="ghost-link button-like booking-mini-btn" href="booking-view.php?id=<?= (int) $booking['rental_id'] ?>">View</a>

                            <?php if ($statusKey === 'pending'): ?>
                                <form
                                    method="post"
                                    action="booking-action.php"
                                    data-confirm-submit
                                    data-confirm-title="Approve booking"
                                    data-confirm-message="Approve this booking request now?"
                                    data-confirm-label="Approve"
                                    data-cancel-label="Not yet"
                                >
                                    <input type="hidden" name="action" value="approve">
                                    <input type="hidden" name="id" value="<?= (int) $booking['rental_id'] ?>">
                                    <input type="hidden" name="redirect" value="bookings.php?<?= htmlspecialchars(bookingsQuery()) ?>">
                                    <button class="ghost-link button-like booking-mini-btn" type="submit">Approve</button>
                                </form>
                            <?php elseif ($statusKey === 'awaiting-payment'): ?>
                                <form
                                    method="post"
                                    action="booking-action.php"
                                    data-confirm-submit
                                    data-confirm-title="Send payment reminder"
                                    data-confirm-message="Send a payment reminder for this booking?"
                                    data-confirm-label="Send reminder"
                                    data-cancel-label="Cancel"
                                >
                                    <input type="hidden" name="action" value="remind">
                                    <input type="hidden" name="id" value="<?= (int) $booking['rental_id'] ?>">
                                    <input type="hidden" name="redirect" value="bookings.php?<?= htmlspecialchars(bookingsQuery()) ?>">
                                    <button class="ghost-link button-like booking-mini-btn" type="submit">Remind</button>
                                </form>
                            <?php elseif ($statusKey === 'completed'): ?>
                                <a class="ghost-link button-like booking-mini-btn" href="booking-view.php?id=<?= (int) $booking['rental_id'] ?>&receipt=1">Receipt</a>
                            <?php elseif ($statusKey === 'cancelled'): ?>
                            <?php else: ?>
                                <a class="ghost-link button-like booking-mini-btn" href="booking-edit.php?id=<?= (int) $booking['rental_id'] ?>">Edit</a>
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
</section>
<?php viewEnd();
?>

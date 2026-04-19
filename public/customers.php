<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$notice = (string) ($_GET['notice'] ?? '');
$noticeMessage = '';
if ($notice === 'customer_created') {
    $noticeMessage = 'Customer was created successfully.';
} elseif ($notice === 'customer_updated') {
    $noticeMessage = 'Customer profile was updated successfully.';
} elseif ($notice === 'booking_created') {
    $noticeMessage = 'New booking was created successfully.';
}

$customers = getCustomers(20);

if (!function_exists('customerInitialsForPage')) {
    function customerInitialsForPage(string $name): string
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

if (!function_exists('customerJoinedLabel')) {
    function customerJoinedLabel(string $createdAt): string
    {
        $timestamp = strtotime($createdAt);
        if ($timestamp === false) {
            return 'N/A';
        }
        return date('M Y', $timestamp);
    }
}

$customerProfiles = [];
foreach ($customers as $customer) {
    $id = (int) ($customer['user_id'] ?? 0);
    if ($id <= 0) {
        continue;
    }
    $customer['recent_bookings'] = getCustomerRecentBookings($id, 3);
    $customerProfiles[$id] = $customer;
}

$selected = $customerProfiles[array_key_first($customerProfiles)] ?? null;
$customerProfilesJson = htmlspecialchars(
    json_encode($customerProfiles, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ENT_QUOTES,
    'UTF-8'
);

viewBegin('app', appLayoutData('Customers', 'customers', [
    'show_search' => false,
    'show_primary_cta' => false,
]));
?>
<?php if ($noticeMessage !== ''): ?>
    <div class="alert-success customers-alert"><?= htmlspecialchars($noticeMessage) ?></div>
<?php endif; ?>
<section class="page-content-head">
    <h3>All customers</h3>
</section>

<section class="content-grid customers-grid">
    <article class="card customers-table-card">
        <div class="card-header customers-table-head">
            <h4>Customer directory</h4>
            <div class="customers-toolbar">
                <input type="search" placeholder="Search customers..." aria-label="Search customers" data-customer-search>
                <a class="ghost-link button-like" href="customers-export.php">Export</a>
                <a class="primary-btn" href="customer-create.php">+ Add customer</a>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Tier</th>
                        <th>Total bookings</th>
                        <th>Total spent</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $customer): ?>
                    <?php
                    $customerId = (int) ($customer['user_id'] ?? 0);
                    $isActive = $selected !== null && (int) ($selected['user_id'] ?? 0) === $customerId;
                    ?>
                    <tr class="customer-row<?= $isActive ? ' is-active' : '' ?>" data-user-id="<?= $customerId ?>">
                        <td>
                            <div class="customer-cell-main">
                                <span class="customer-avatar"><?= htmlspecialchars(customerInitialsForPage((string) $customer['name'])) ?></span>
                                <div>
                                    <strong><?= htmlspecialchars((string) $customer['name']) ?></strong>
                                    <p><?= htmlspecialchars((string) $customer['email']) ?></p>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars((string) $customer['phone']) ?></td>
                        <td>
                            <span class="pill customer-tier <?= strtolower((string) ($customer['tier'] ?? 'regular')) ?>">
                                <?= htmlspecialchars((string) ($customer['tier'] ?? 'Regular')) ?>
                            </span>
                        </td>
                        <td><?= (int) $customer['bookings'] ?></td>
                        <td>P<?= number_format((float) $customer['spent'], 2) ?></td>
                        <td><button class="ghost-link button-like customer-view-btn" type="button" data-customer-id="<?= $customerId ?>">View</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card side-panel customer-profile-card" id="customer-profile-panel">
        <?php if ($selected): ?>
            <div class="customer-profile-header">
                <span class="customer-avatar large" id="profile-avatar"><?= htmlspecialchars(customerInitialsForPage((string) $selected['name'])) ?></span>
                <div>
                    <h4 id="profile-name"><?= htmlspecialchars((string) $selected['name']) ?></h4>
                    <p id="profile-tier"><?= htmlspecialchars((string) ($selected['tier'] ?? 'Regular')) ?> Customer · since <?= htmlspecialchars(customerJoinedLabel((string) ($selected['created_at'] ?? ''))) ?></p>
                </div>
            </div>

            <dl class="customer-meta-list">
                <div><dt>Email</dt><dd id="profile-email"><?= htmlspecialchars((string) $selected['email']) ?></dd></div>
                <div><dt>Phone</dt><dd id="profile-phone"><?= htmlspecialchars((string) $selected['phone']) ?></dd></div>
                <div><dt>License no.</dt><dd id="profile-license"><?= htmlspecialchars((string) ($selected['license_number'] ?? 'N/A')) ?></dd></div>
                <div><dt>License exp.</dt><dd id="profile-license-exp"><?= htmlspecialchars((string) ($selected['license_expiry'] ?? 'N/A')) ?></dd></div>
                <div><dt>Address</dt><dd id="profile-address"><?= htmlspecialchars((string) ($selected['address'] ?? 'N/A')) ?></dd></div>
            </dl>

            <div class="mini-stats customer-kpi-grid">
                <div><span>Total bookings</span><strong id="profile-bookings"><?= (int) $selected['bookings'] ?></strong></div>
                <div><span>Total spent</span><strong id="profile-spent">P<?= number_format((float) $selected['spent'], 2) ?></strong></div>
                <div><span>Avg. rental</span><strong id="profile-avg-rental"><?= number_format((float) ($selected['avg_rental_days'] ?? 0), 1) ?> days</strong></div>
                <div><span>No-shows</span><strong id="profile-no-shows"><?= (int) ($selected['no_shows'] ?? 0) ?></strong></div>
            </div>

            <div class="customer-recent-wrap">
                <h4>Recent bookings</h4>
                <ul class="customer-recent-list" id="profile-recent-bookings">
                    <?php foreach ((array) ($selected['recent_bookings'] ?? []) as $booking): ?>
                        <li>
                            <span><?= htmlspecialchars((string) ($booking['label'] ?? 'N/A')) ?></span>
                            <span class="pill <?= htmlspecialchars(strtolower((string) ($booking['status'] ?? 'pending'))) ?>">
                                <?= htmlspecialchars(ucfirst((string) ($booking['status'] ?? 'Pending'))) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="customer-profile-actions">
                <a class="ghost-link button-like" id="profile-message-btn" href="mailto:<?= htmlspecialchars((string) ($selected['email'] ?? '')) ?>">Message</a>
                <a class="ghost-link button-like" id="profile-edit-btn" href="customer-edit.php?id=<?= (int) ($selected['user_id'] ?? 0) ?>">Edit</a>
                <a class="primary-btn" id="profile-booking-btn" href="booking-create.php?user_id=<?= (int) ($selected['user_id'] ?? 0) ?>">New booking</a>
            </div>
        <?php else: ?>
            <p>No customer data available.</p>
        <?php endif; ?>
    </article>
</section>

<div id="customer-profile-data" data-customers="<?= $customerProfilesJson ?>"></div>
<?php viewEnd(); ?>

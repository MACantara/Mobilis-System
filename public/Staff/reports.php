<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$analytics = getAnalyticsSummary();
$maintenanceAlerts = getOverdueMaintenanceAlerts();
$revenueTrends = getRevenueByPeriod('month');
$bookingTrends = getBookingTrends('month');

renderPageTop('Reports', 'reports');
?>
<section class="page-content-head">
    <h3>Analytics Dashboard</h3>
</section>

<section class="content-grid">
    <article class="card">
        <div class="card-header">
            <h4>Fleet Health</h4>
        </div>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total Fleet</span>
                <strong class="stat-value"><?= number_format((int) ($analytics['fleet_health']['total_fleet'] ?? 0)) ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-label">Active Rentals</span>
                <strong class="stat-value"><?= number_format((int) ($analytics['fleet_health']['active_rentals'] ?? 0)) ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-label">Utilization Rate</span>
                <strong class="stat-value"><?= (int) ($analytics['fleet_health']['utilization_rate'] ?? 0) ?>%</strong>
            </div>
        </div>
        <div class="status-breakdown">
            <h5>Status Breakdown</h5>
            <ul class="list clean">
                <?php foreach (($analytics['fleet_health']['status_breakdown'] ?? []) as $status => $count): ?>
                    <li>
                        <span><?= htmlspecialchars(ucfirst($status)) ?></span>
                        <strong><?= number_format($count) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h4>Booking Behavior</h4>
        </div>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Total Bookings</span>
                <strong class="stat-value"><?= number_format((int) ($analytics['booking_behavior']['observed_bookings'] ?? 0)) ?></strong>
            </div>
            <div class="stat-item">
                <span class="stat-label">Avg Rental Days</span>
                <strong class="stat-value"><?= number_format((float) ($analytics['booking_behavior']['average_rental_days'] ?? 0), 1) ?></strong>
            </div>
        </div>
        <div class="top-demand">
            <h5>Top Vehicle Demand</h5>
            <ul class="list clean">
                <?php foreach (($analytics['booking_behavior']['top_vehicle_demand'] ?? []) as $item): ?>
                    <li>
                        <span><?= htmlspecialchars($item['vehicle'] ?? 'Unknown') ?></span>
                        <strong><?= number_format($item['count'] ?? 0) ?></strong>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h4>Financial Snapshot</h4>
        </div>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-label">Revenue Today</span>
                <strong class="stat-value"><?= formatCurrency((float) ($analytics['financial_snapshot']['revenue_today'] ?? 0)) ?></strong>
            </div>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h4>Maintenance Alerts</h4>
        </div>
        <?php if (empty($maintenanceAlerts)): ?>
            <p class="muted">No overdue maintenance alerts.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Vehicle</th>
                            <th>Mileage</th>
                            <th>Days Since Service</th>
                            <th>Recent Work</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($maintenanceAlerts as $alert): ?>
                            <tr>
                                <td><?= htmlspecialchars($alert['vehicle'] ?? 'Unknown') ?></td>
                                <td><?= number_format($alert['mileage_km'] ?? 0) ?> km</td>
                                <td><?= number_format($alert['days_since_service'] ?? 0) ?> days</td>
                                <td><?= htmlspecialchars($alert['recent_work'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>

    <article class="card full">
        <div class="card-header">
            <h4>Recommendations</h4>
        </div>
        <ul class="list clean">
            <?php foreach (($analytics['recommendations'] ?? []) as $recommendation): ?>
                <li>
                    <span class="recommendation-icon">💡</span>
                    <span><?= htmlspecialchars($recommendation) ?></span>
                </li>
            <?php endforeach; ?>
            <?php if (empty($analytics['recommendations'])): ?>
                <li class="muted">No recommendations at this time.</li>
            <?php endif; ?>
        </ul>
    </article>
</section>

<section class="page-content-head" style="margin-top: 32px;">
    <h3>Revenue Trends (Last 30 Days)</h3>
</section>
<section class="content-grid">
    <article class="card full">
        <div class="card-header">
            <h4>Daily Revenue</h4>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($revenueTrends as $trend): ?>
                        <tr>
                            <td><?= htmlspecialchars($trend['date'] ?? 'N/A') ?></td>
                            <td><?= formatCurrency($trend['total'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($revenueTrends)): ?>
                        <tr><td colspan="2" class="muted">No revenue data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<section class="page-content-head" style="margin-top: 32px;">
    <h3>Booking Trends (Last 30 Days)</h3>
</section>
<section class="content-grid">
    <article class="card full">
        <div class="card-header">
            <h4>Daily Bookings</h4>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Count</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookingTrends as $trend): ?>
                        <tr>
                            <td><?= htmlspecialchars($trend['date'] ?? 'N/A') ?></td>
                            <td><?= number_format($trend['count'] ?? 0) ?></td>
                            <td><?= formatCurrency($trend['revenue'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($bookingTrends)): ?>
                        <tr><td colspan="3" class="muted">No booking data available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php renderPageBottom(); ?>

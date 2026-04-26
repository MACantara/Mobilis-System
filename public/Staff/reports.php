<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

// Helper function to call Python analytics script
function fetchFromPythonAnalytics(string $function, string $arg = '')
{
    $pythonScript = __DIR__ . '/../../python-scripts/analytics.py';
    $command = "python \"$pythonScript\" \"$function\"";
    if ($arg !== '') {
        $command .= " \"$arg\"";
    }
    exec($command, $output, $returnCode);
    
    if ($returnCode !== 0 || empty($output)) {
        return $function === 'average_revenue' ? 0 : [];
    }
    
    $json = implode('', $output);
    $data = json_decode($json, true);
    
    if ($function === 'average_revenue') {
        return is_float($data) ? $data : ($data['average_revenue'] ?? 0);
    }
    
    return $data ?? [];
}

// Try to use Python analytics, fallback to PHP functions
$analytics = fetchFromPythonAnalytics('summary');
$maintenanceAlerts = fetchFromPythonAnalytics('maintenance_alerts');
$revenueTrends = fetchFromPythonAnalytics('revenue_trends', 'month');
$bookingTrends = fetchFromPythonAnalytics('booking_trends', 'month');
$topCustomers = fetchFromPythonAnalytics('top_customers', '10');
$vehiclePerformance = fetchFromPythonAnalytics('vehicle_performance');
$paymentStatusBreakdown = fetchFromPythonAnalytics('payment_status_breakdown');
$bookingStatusBreakdown = fetchFromPythonAnalytics('booking_status_breakdown');
$avgRevenueData = fetchFromPythonAnalytics('average_revenue');
$avgRevenuePerBooking = is_float($avgRevenueData) ? $avgRevenueData : ($avgRevenueData['average_revenue'] ?? 0);
$revenueByVehicleType = fetchFromPythonAnalytics('revenue_by_vehicle_type');

// Fallback to PHP functions if Python returns empty data
if (empty($analytics)) {
    $analytics = getAnalyticsSummary();
}
if (empty($maintenanceAlerts)) {
    $maintenanceAlerts = getOverdueMaintenanceAlerts();
}
if (empty($revenueTrends)) {
    $revenueTrends = getRevenueByPeriod('month');
}
if (empty($bookingTrends)) {
    $bookingTrends = getBookingTrends('month');
}
if (empty($topCustomers)) {
    $topCustomers = getTopCustomersByRevenue(10);
}
if (empty($vehiclePerformance)) {
    $vehiclePerformance = getVehiclePerformance();
}
if (empty($paymentStatusBreakdown)) {
    $paymentStatusBreakdown = getPaymentStatusBreakdown();
}
if (empty($bookingStatusBreakdown)) {
    $bookingStatusBreakdown = getBookingStatusBreakdown();
}
if ($avgRevenuePerBooking === 0) {
    $avgRevenuePerBooking = getAverageRevenuePerBooking();
}
if (empty($revenueByVehicleType)) {
    $revenueByVehicleType = getRevenueByVehicleType();
}

$fleetHealth = (array) ($analytics['fleet_health'] ?? []);
$bookingBehavior = (array) ($analytics['booking_behavior'] ?? []);
$financialSnapshot = (array) ($analytics['financial_snapshot'] ?? []);
$recommendations = (array) ($analytics['recommendations'] ?? []);

$revenueTrendRows = array_values(array_reverse($revenueTrends));
$bookingTrendRows = array_values(array_reverse($bookingTrends));

$revenueLabels = array_map(static fn(array $row): string => (string) ($row['date'] ?? 'N/A'), $revenueTrendRows);
$revenueValues = array_map(static fn(array $row): float => (float) ($row['total'] ?? 0), $revenueTrendRows);

$bookingLabels = array_map(static fn(array $row): string => (string) ($row['date'] ?? 'N/A'), $bookingTrendRows);
$bookingCounts = array_map(static fn(array $row): int => (int) ($row['count'] ?? 0), $bookingTrendRows);
$bookingRevenue = array_map(static fn(array $row): float => (float) ($row['revenue'] ?? 0), $bookingTrendRows);

$paymentLabels = array_map(static fn(array $row): string => ucfirst((string) ($row['status'] ?? 'Unknown')), $paymentStatusBreakdown);
$paymentCounts = array_map(static fn(array $row): int => (int) ($row['count'] ?? 0), $paymentStatusBreakdown);

$bookingStatusLabels = array_map(static fn(array $row): string => ucfirst((string) ($row['status'] ?? 'Unknown')), $bookingStatusBreakdown);
$bookingStatusCounts = array_map(static fn(array $row): int => (int) ($row['count'] ?? 0), $bookingStatusBreakdown);

$vehicleTypeLabels = array_map(static fn(array $row): string => (string) ($row['type'] ?? 'Unknown'), $revenueByVehicleType);
$vehicleTypeRevenue = array_map(static fn(array $row): float => (float) ($row['total_revenue'] ?? 0), $revenueByVehicleType);

$topCustomerSubset = array_slice($topCustomers, 0, 7);
$topCustomerLabels = array_map(static fn(array $row): string => (string) ($row['name'] ?? 'Unknown'), $topCustomerSubset);
$topCustomerRevenue = array_map(static fn(array $row): float => (float) ($row['total_revenue'] ?? 0), $topCustomerSubset);

$totalRevenueWindow = (float) array_sum($revenueValues);
$totalBookingsWindow = (int) array_sum($bookingCounts);

$peakRevenueAmount = 0.0;
$peakRevenueDate = 'N/A';
foreach ($revenueTrendRows as $row) {
    $candidate = (float) ($row['total'] ?? 0);
    if ($candidate > $peakRevenueAmount) {
        $peakRevenueAmount = $candidate;
        $peakRevenueDate = (string) ($row['date'] ?? 'N/A');
    }
}

$chartPayload = [
    'revenueTrend' => ['labels' => $revenueLabels, 'values' => $revenueValues],
    'bookingTrend' => ['labels' => $bookingLabels, 'counts' => $bookingCounts, 'revenue' => $bookingRevenue],
    'paymentStatus' => ['labels' => $paymentLabels, 'counts' => $paymentCounts],
    'bookingStatus' => ['labels' => $bookingStatusLabels, 'counts' => $bookingStatusCounts],
    'vehicleTypeRevenue' => ['labels' => $vehicleTypeLabels, 'values' => $vehicleTypeRevenue],
    'topCustomers' => ['labels' => $topCustomerLabels, 'values' => $topCustomerRevenue],
];

$chartPayloadJson = json_encode($chartPayload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
if (!is_string($chartPayloadJson) || $chartPayloadJson === '') {
    $chartPayloadJson = '{}';
}

viewBegin('app', appLayoutData('Reports', 'reports'));
?>
<section class="page-content-head reports-head">
    <h3>Reports and Insights</h3>
    <p class="muted">Visualize revenue, bookings, customer value, and operational signals in one place.</p>
</section>

<section class="reports-kpi-grid">
    <article class="card reports-kpi-card revenue">
        <span class="reports-kpi-label">Revenue (Last 30 Days)</span>
        <strong class="reports-kpi-value"><?= formatCurrency($totalRevenueWindow) ?></strong>
        <p>Peak day: <?= htmlspecialchars($peakRevenueDate) ?> (<?= formatCurrency($peakRevenueAmount) ?>)</p>
    </article>
    <article class="card reports-kpi-card bookings">
        <span class="reports-kpi-label">Bookings (Last 30 Days)</span>
        <strong class="reports-kpi-value"><?= number_format($totalBookingsWindow) ?></strong>
        <p>Avg rental days: <?= number_format((float) ($bookingBehavior['average_rental_days'] ?? 0), 1) ?></p>
    </article>
    <article class="card reports-kpi-card utilization">
        <span class="reports-kpi-label">Fleet Utilization</span>
        <strong class="reports-kpi-value"><?= (int) ($fleetHealth['utilization_rate'] ?? 0) ?>%</strong>
        <p><?= number_format((int) ($fleetHealth['active_rentals'] ?? 0)) ?> active of <?= number_format((int) ($fleetHealth['total_fleet'] ?? 0)) ?> vehicles</p>
    </article>
    <article class="card reports-kpi-card finance">
        <span class="reports-kpi-label">Revenue Today</span>
        <strong class="reports-kpi-value"><?= formatCurrency((float) ($financialSnapshot['revenue_today'] ?? 0)) ?></strong>
        <p>Average revenue per booking: <?= formatCurrency($avgRevenuePerBooking) ?></p>
    </article>
</section>

<section class="reports-chart-grid">
    <article class="card reports-chart-card reports-chart-wide reports-chart-hero">
        <div class="card-header">
            <h4>Revenue Trend</h4>
            <span class="muted">Last 30 days</span>
        </div>
        <div class="chart-container">
            <canvas id="reports-revenue-chart"></canvas>
        </div>
    </article>

    <article class="card reports-chart-card reports-chart-standard">
        <div class="card-header">
            <h4>Daily Bookings</h4>
            <span class="muted">Volume trend</span>
        </div>
        <div class="chart-container">
            <canvas id="reports-bookings-chart"></canvas>
        </div>
    </article>

    <article class="card reports-chart-card reports-chart-compact">
        <div class="card-header">
            <h4>Payment Status</h4>
            <span class="muted">Collection mix</span>
        </div>
        <div class="chart-container">
            <canvas id="reports-payment-chart"></canvas>
        </div>
    </article>

    <article class="card reports-chart-card reports-chart-compact">
        <div class="card-header">
            <h4>Booking Status</h4>
            <span class="muted">Pipeline health</span>
        </div>
        <div class="chart-container">
            <canvas id="reports-booking-status-chart"></canvas>
        </div>
    </article>

    <article class="card reports-chart-card reports-chart-compact">
        <div class="card-header">
            <h4>Top Customers by Revenue</h4>
            <span class="muted">Highest value customers</span>
        </div>
        <div class="chart-container">
            <canvas id="reports-customers-chart"></canvas>
        </div>
    </article>

    <article class="card reports-chart-card reports-chart-wide reports-chart-wide-mid">
        <div class="card-header">
            <h4>Revenue by Vehicle Type</h4>
            <span class="muted">Category contribution</span>
        </div>
        <div class="chart-container">
            <canvas id="reports-vehicle-type-chart"></canvas>
        </div>
    </article>
</section>

<section class="reports-insight-grid">
    <article class="card">
        <div class="card-header">
            <h4>Operational Recommendations</h4>
        </div>
        <ul class="reports-recommendation-list">
            <?php foreach ($recommendations as $recommendation): ?>
                <li><?= htmlspecialchars((string) $recommendation) ?></li>
            <?php endforeach; ?>
            <?php if (empty($recommendations)): ?>
                <li>No recommendations at this time.</li>
            <?php endif; ?>
        </ul>
    </article>

    <article class="card">
        <div class="card-header">
            <h4>Maintenance Alerts</h4>
            <span class="muted"><?= number_format(count($maintenanceAlerts)) ?> flagged</span>
        </div>
        <?php if (empty($maintenanceAlerts)): ?>
            <p class="muted">No overdue maintenance alerts.</p>
        <?php else: ?>
            <div class="table-wrap reports-table-wrap">
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
                                <td><?= htmlspecialchars((string) ($alert['vehicle'] ?? 'Unknown')) ?></td>
                                <td><?= number_format((int) ($alert['mileage_km'] ?? 0)) ?> km</td>
                                <td><?= number_format((int) ($alert['days_since_service'] ?? 0)) ?> days</td>
                                <td><?= htmlspecialchars((string) ($alert['recent_work'] ?? 'N/A')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="reports-detail-panels">
    <details class="card reports-detail-card" open>
        <summary>Detailed Revenue and Booking Tables</summary>
        <div class="reports-detail-grid">
            <article>
                <h5>Revenue Trend</h5>
                <div class="table-wrap reports-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($revenueTrendRows as $trend): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($trend['date'] ?? 'N/A')) ?></td>
                                    <td><?= formatCurrency((float) ($trend['total'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($revenueTrendRows)): ?>
                                <tr><td colspan="2" class="muted">No revenue data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
            <article>
                <h5>Booking Trend</h5>
                <div class="table-wrap reports-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookingTrendRows as $trend): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($trend['date'] ?? 'N/A')) ?></td>
                                    <td><?= number_format((int) ($trend['count'] ?? 0)) ?></td>
                                    <td><?= formatCurrency((float) ($trend['revenue'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookingTrendRows)): ?>
                                <tr><td colspan="3" class="muted">No booking data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </details>

    <details class="card reports-detail-card">
        <summary>Customer, Vehicle, and Status Breakdown</summary>
        <div class="reports-detail-grid">
            <article>
                <h5>Top Customers</h5>
                <div class="table-wrap reports-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Bookings</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topCustomers as $customer): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($customer['name'] ?? 'Unknown')) ?></td>
                                    <td><?= number_format((int) ($customer['bookings'] ?? 0)) ?></td>
                                    <td><?= formatCurrency((float) ($customer['total_revenue'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($topCustomers)): ?>
                                <tr><td colspan="3" class="muted">No customer data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
            <article>
                <h5>Vehicle Performance</h5>
                <div class="table-wrap reports-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th>Type</th>
                                <th>Rentals</th>
                                <th>Total Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehiclePerformance as $vehicle): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string) ($vehicle['name'] ?? 'Unknown')) ?></td>
                                    <td><?= htmlspecialchars((string) ($vehicle['type'] ?? 'Unknown')) ?></td>
                                    <td><?= number_format((int) ($vehicle['rentals'] ?? 0)) ?></td>
                                    <td><?= formatCurrency((float) ($vehicle['revenue'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($vehiclePerformance)): ?>
                                <tr><td colspan="4" class="muted">No vehicle performance data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
            <article>
                <h5>Payment Status</h5>
                <div class="table-wrap reports-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($paymentStatusBreakdown as $status): ?>
                                <tr>
                                    <td><?= htmlspecialchars(ucfirst((string) ($status['status'] ?? 'Unknown'))) ?></td>
                                    <td><?= number_format((int) ($status['count'] ?? 0)) ?></td>
                                    <td><?= formatCurrency((float) ($status['total'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($paymentStatusBreakdown)): ?>
                                <tr><td colspan="3" class="muted">No payment data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
            <article>
                <h5>Booking Status</h5>
                <div class="table-wrap reports-table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Total Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookingStatusBreakdown as $status): ?>
                                <tr>
                                    <td><?= htmlspecialchars(ucfirst((string) ($status['status'] ?? 'Unknown'))) ?></td>
                                    <td><?= number_format((int) ($status['count'] ?? 0)) ?></td>
                                    <td><?= formatCurrency((float) ($status['total'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($bookingStatusBreakdown)): ?>
                                <tr><td colspan="3" class="muted">No booking data available.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </details>
</section>

<script id="reports-chart-data" type="application/json"><?= $chartPayloadJson ?></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?php viewEnd();
?>

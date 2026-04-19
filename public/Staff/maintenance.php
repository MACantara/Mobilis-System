<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$maintenance = getMaintenanceBacklog();

renderPageTop('Maintenance', 'maintenance');
?>
<section class="page-content-head">
    <h3>All maintenance jobs</h3>
</section>

<section class="card">
    <div class="card-header">
        <h4>Service queue</h4>
        <button type="button" class="primary-btn">+ Add work order</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Vehicle</th>
                    <th>Mileage</th>
                    <th>Last service</th>
                    <th>Recent work</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($maintenance as $row): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $row['vehicle']) ?></td>
                    <td><?= number_format((float) $row['mileage_km']) ?> km</td>
                    <td><?= htmlspecialchars((string) ($row['last_service'] ?? 'N/A')) ?></td>
                    <td><?= htmlspecialchars((string) ($row['service_type'] ?? 'N/A')) ?></td>
                    <td><button type="button" class="ghost-link button-like">Schedule</button></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php renderPageBottom(); ?>

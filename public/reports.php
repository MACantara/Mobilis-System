<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$maintenance = getMaintenanceBacklog();

renderPageTop('Reports', 'reports');
?>
<section class="page-content-head">
    <h3>All reports</h3>
</section>

<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h3>Python analysis output</h3>
            <button type="button" class="primary-btn" data-refresh-insights>Generate report</button>
        </div>
        <p class="muted">This uses PHP to gather MySQL data and calls Python for deeper processing.</p>
        <pre class="code-block" id="insights-output">Click "Generate report" to run analytics.</pre>
    </article>

    <article class="card">
        <div class="card-header">
            <h3>Maintenance backlog</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Mileage</th>
                        <th>Last service</th>
                        <th>Recent work</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($maintenance as $row): ?>
                    <tr>
                        <td><?= htmlspecialchars((string) $row['vehicle']) ?></td>
                        <td><?= number_format((float) $row['mileage_km']) ?> km</td>
                        <td><?= htmlspecialchars((string) ($row['last_service'] ?? 'N/A')) ?></td>
                        <td><?= htmlspecialchars((string) ($row['service_type'] ?? 'N/A')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php renderPageBottom(); ?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$notice = '';
$errors = [];
$form = [
    'vehicle_id' => '',
    'service_date' => date('Y-m-d'),
    'service_type' => '',
    'odometer_km' => '',
    'performed_by' => '',
    'cost' => '',
    'remarks' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    $result = createMaintenanceWorkOrder($form);
    if (($result['ok'] ?? false) === true) {
        $notice = 'Work order added successfully.';
        $form = [
            'vehicle_id' => '',
            'service_date' => date('Y-m-d'),
            'service_type' => '',
            'odometer_km' => '',
            'performed_by' => '',
            'cost' => '',
            'remarks' => '',
        ];
    } else {
        $errors[] = (string) ($result['error'] ?? 'Could not add work order.');
    }
}

$maintenanceVehicles = getMaintenanceVehicleOptions();
$maintenance = getMaintenanceBacklog();

viewBegin('app', appLayoutData('Maintenance', 'maintenance'));
?>
<section class="page-content-head">
    <h3>All maintenance jobs</h3>
    <?php if ($notice !== ''): ?>
        <div class="alert-success"><?= htmlspecialchars($notice) ?></div>
    <?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="card customer-form-card" style="margin-bottom: 12px;">
    <div class="card-header">
        <h4>Add work order</h4>
    </div>
    <form method="post" class="customer-form-grid">
        <label>Vehicle
            <select name="vehicle_id" required>
                <option value="">Select vehicle</option>
                <?php foreach ($maintenanceVehicles as $vehicle): ?>
                    <?php $id = (int) ($vehicle['vehicle_id'] ?? 0); ?>
                    <option value="<?= $id ?>" <?= (string) $id === $form['vehicle_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($vehicle['name'] ?? 'Unknown')) ?> (<?= htmlspecialchars((string) ($vehicle['plate'] ?? '')) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Service date
            <input type="date" name="service_date" value="<?= htmlspecialchars($form['service_date']) ?>" required>
        </label>
        <label>Service type
            <input type="text" name="service_type" value="<?= htmlspecialchars($form['service_type']) ?>" placeholder="Oil change" required>
        </label>
        <label>Odometer (km)
            <input type="number" name="odometer_km" value="<?= htmlspecialchars($form['odometer_km']) ?>" min="1" required>
        </label>
        <label>Performed by
            <input type="text" name="performed_by" value="<?= htmlspecialchars($form['performed_by']) ?>" placeholder="Service center or technician">
        </label>
        <label>Estimated/actual cost
            <input type="number" name="cost" value="<?= htmlspecialchars($form['cost']) ?>" min="0" step="0.01" placeholder="0.00">
        </label>
        <label class="full">Remarks
            <textarea name="remarks" rows="2" placeholder="Optional notes"><?= htmlspecialchars($form['remarks']) ?></textarea>
        </label>
        <div class="customer-form-actions full">
            <button type="submit" class="primary-btn">Add work order</button>
        </div>
    </form>
</section>

<section class="card">
    <div class="card-header">
        <h4>Service queue</h4>
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
<?php viewEnd();
?>

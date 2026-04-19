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
    <div style="margin-top: 12px;">
        <button type="button" class="primary-btn" data-modal-open="addWorkOrderModal">+ Add work order</button>
    </div>
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

<?php viewModalStart('addWorkOrderModal', 'Add work order', ['size' => 'lg']); ?>
    <form method="post" class="modal-body" id="addWorkOrderForm">
        <label for="wo_vehicle_id">Vehicle</label>
        <select id="wo_vehicle_id" name="vehicle_id" required>
            <option value="">Select vehicle</option>
            <?php foreach ($maintenanceVehicles as $vehicle): ?>
                <?php $id = (int) ($vehicle['vehicle_id'] ?? 0); ?>
                <option value="<?= $id ?>" <?= (string) $id === $form['vehicle_id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($vehicle['name'] ?? 'Unknown')) ?> (<?= htmlspecialchars((string) ($vehicle['plate'] ?? '')) ?>)
                </option>
            <?php endforeach; ?>
        </select>

        <label for="wo_service_date">Service date</label>
        <input id="wo_service_date" type="date" name="service_date" value="<?= htmlspecialchars($form['service_date']) ?>" required>

        <label for="wo_service_type">Service type</label>
        <input id="wo_service_type" type="text" name="service_type" value="<?= htmlspecialchars($form['service_type']) ?>" placeholder="Oil change" required>

        <label for="wo_odometer_km">Odometer (km)</label>
        <input id="wo_odometer_km" type="number" name="odometer_km" value="<?= htmlspecialchars($form['odometer_km']) ?>" min="1" required>

        <label for="wo_performed_by">Performed by</label>
        <input id="wo_performed_by" type="text" name="performed_by" value="<?= htmlspecialchars($form['performed_by']) ?>" placeholder="Service center or technician">

        <label for="wo_cost">Estimated/actual cost</label>
        <input id="wo_cost" type="number" name="cost" value="<?= htmlspecialchars($form['cost']) ?>" min="0" step="0.01" placeholder="0.00">

        <label for="wo_remarks">Remarks</label>
        <textarea id="wo_remarks" name="remarks" rows="3" placeholder="Optional notes"><?= htmlspecialchars($form['remarks']) ?></textarea>

        <div class="modal-footer">
            <button type="button" class="ghost-btn" data-modal-close>Cancel</button>
            <button type="submit" class="primary-btn" id="addWorkOrderSubmit">Add work order</button>
        </div>
    </form>
<?php viewModalEnd(); ?>

<script>
(function () {
    const addWorkOrderForm = document.getElementById('addWorkOrderForm');
    const addWorkOrderSubmit = document.getElementById('addWorkOrderSubmit');

    if (!addWorkOrderForm) {
        return;
    }

    addWorkOrderForm.addEventListener('submit', function () {
        if (addWorkOrderSubmit) {
            addWorkOrderSubmit.disabled = true;
            addWorkOrderSubmit.textContent = 'Saving...';
        }
    });

    const shouldOpen = <?= $errors !== [] ? 'true' : 'false' ?>;
    if (shouldOpen && window.MobilisModal) {
        window.MobilisModal.open('addWorkOrderModal');
    }
})();
</script>
<?php viewEnd();
?>

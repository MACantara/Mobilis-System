<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$payments = getPayments(25);

renderPageTop('Payments', 'payments');
?>
<section class="card">
    <div class="card-header">
        <h3>Payment ledger</h3>
        <button type="button" class="primary-btn">Export</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Customer</th>
                    <th>Vehicle</th>
                    <th>Issued</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $payment): ?>
                <?php $status = strtolower(str_replace(' ', '-', (string) ($payment['payment_status'] ?? 'unpaid'))); ?>
                <tr>
                    <td>INV-<?= str_pad((string) ((int) $payment['invoice_id']), 4, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars((string) $payment['customer']) ?></td>
                    <td><?= htmlspecialchars((string) $payment['vehicle']) ?></td>
                    <td><?= htmlspecialchars((string) $payment['issued_at']) ?></td>
                    <td>P<?= number_format((float) $payment['total_amount'], 2) ?></td>
                    <td><span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst((string) $payment['payment_status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php renderPageBottom(); ?>

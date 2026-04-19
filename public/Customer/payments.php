<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customerEmail = $user['email'] ?? '';

$payments = getCustomerPayments($customerEmail, 50);

renderPageTop('Payments', 'payments', ['role' => 'customer']);
?>
<section class="page-content-head">
    <h3>My payments</h3>
</section>

<section class="card">
    <div class="card-header">
        <h4>Payment history</h4>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Vehicle</th>
                    <th>Issued</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($payments === []): ?>
                <tr><td colspan="5" class="muted">No payments yet.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <?php $status = strtolower(str_replace(' ', '-', (string) ($payment['payment_status'] ?? 'unpaid'))); ?>
                    <tr>
                        <td>INV-<?= str_pad((string) ((int) $payment['invoice_id']), 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars((string) $payment['vehicle']) ?></td>
                        <td><?= htmlspecialchars((string) $payment['issued_at']) ?></td>
                        <td><strong>P<?= number_format((float) $payment['total_amount'], 2) ?></strong></td>
                        <td><span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst((string) $payment['payment_status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php renderPageBottom(); ?>

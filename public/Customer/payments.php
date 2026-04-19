<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['customer']);

$user = currentUser();
$customer = resolveCustomerForUser($user);
$customerId = (int) ($customer['user_id'] ?? 0);
$accountLinked = $customerId > 0;

$errors = [];
$notice = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim((string) ($_POST['action'] ?? '')));
    $invoiceId = (int) ($_POST['invoice_id'] ?? 0);

    if ($action === 'pay') {
        if (!$accountLinked) {
            $errors[] = 'Your account is not linked to a customer profile yet.';
        } else {
            $paymentMethod = strtolower(trim((string) ($_POST['payment_method'] ?? 'cash')));
            $result = markCustomerInvoicePaid($customerId, $invoiceId, $paymentMethod);
            if (($result['ok'] ?? false) === true) {
                $notice = (string) ($result['notice'] ?? 'Payment recorded successfully.');
            } else {
                $errors[] = (string) ($result['error'] ?? 'Payment could not be processed.');
            }
        }
    }
}

$payments = $accountLinked ? getCustomerPaymentsByCustomerId($customerId, 50) : [];

viewBegin('app', appLayoutData('Payments', 'payments', ['role' => 'customer']));
?>
<section class="page-content-head">
    <h3>My payments</h3>
</section>

<?php if ($notice !== ''): ?>
    <section class="card">
        <div class="alert-success"><?= htmlspecialchars($notice) ?></div>
    </section>
<?php endif; ?>

<?php if ($errors !== []): ?>
    <section class="card">
        <div class="alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<?php if (!$accountLinked): ?>
    <section class="card">
        <div class="alert-info">Your account is not yet linked to a customer profile. Payment history will appear once linked.</div>
    </section>
<?php endif; ?>

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
                    <th>Method</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($payments === []): ?>
                <tr><td colspan="7" class="muted">No payments yet.</td></tr>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <?php $status = strtolower(str_replace(' ', '-', (string) ($payment['payment_status'] ?? 'unpaid'))); ?>
                    <tr>
                        <td>INV-<?= str_pad((string) ((int) $payment['invoice_id']), 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= htmlspecialchars((string) $payment['vehicle']) ?></td>
                        <td><?= htmlspecialchars((string) $payment['issued_at']) ?></td>
                        <td><strong>P<?= number_format((float) $payment['total_amount'], 2) ?></strong></td>
                        <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($payment['payment_method'] ?? 'pending')))) ?></td>
                        <td><span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst((string) $payment['payment_status'])) ?></span></td>
                        <td>
                            <?php if (in_array($status, ['unpaid', 'partial'], true)): ?>
                                <form
                                    method="post"
                                    class="booking-actions"
                                    style="justify-content:flex-start;"
                                    data-confirm-submit
                                    data-confirm-title="Confirm payment"
                                    data-confirm-message="Record this invoice as paid using the selected method?"
                                    data-confirm-label="Record payment"
                                    data-cancel-label="Review first"
                                >
                                    <input type="hidden" name="action" value="pay">
                                    <input type="hidden" name="invoice_id" value="<?= (int) ($payment['invoice_id'] ?? 0) ?>">
                                    <select name="payment_method" required>
                                        <option value="cash">Cash</option>
                                        <option value="gcash">GCash</option>
                                        <option value="card">Card</option>
                                        <option value="bank_transfer">Bank transfer</option>
                                    </select>
                                    <button type="submit" class="primary-btn booking-mini-btn">Pay now</button>
                                </form>
                            <?php else: ?>
                                <span class="muted">Settled</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php viewEnd();
?>

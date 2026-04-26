<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$payments = getPayments(25);

viewBegin('app', appLayoutData('Payments', 'payments'));
?>
<section class="page-content-head">
    <h3>All payments</h3>
</section>

<section class="card">
    <div class="card-header">
        <h4>Payment ledger</h4>
        <button type="button" class="primary-btn" data-export-modal="payments">Export</button>
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
                    <th>Method</th>
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
                    <td><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string) ($payment['payment_method'] ?? 'pending')))) ?></td>
                    <td><span class="pill <?= htmlspecialchars($status) ?>"><?= htmlspecialchars(ucfirst((string) $payment['payment_status'])) ?></span></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- Export Format Selection Modal -->
<div id="export-modal" class="modal" aria-hidden="true" data-modal>
    <div class="modal-backdrop" data-modal-close></div>
    <div class="modal-content">
        <div class="modal-header">
            <h3>Export Data</h3>
            <button type="button" class="modal-close" data-modal-close aria-label="Close modal">&times;</button>
        </div>
        <div class="modal-body">
            <p>Select export format:</p>
            <div class="export-format-cards">
                <label class="export-card selected">
                    <input type="radio" name="export-format" value="csv" checked>
                    <div class="export-card-icon">📄</div>
                    <div class="export-card-label">CSV</div>
                    <div class="export-card-desc">Spreadsheet-compatible</div>
                </label>
                <label class="export-card">
                    <input type="radio" name="export-format" value="xlsx">
                    <div class="export-card-icon">📊</div>
                    <div class="export-card-label">Excel</div>
                    <div class="export-card-desc">Microsoft Excel format</div>
                </label>
                <label class="export-card">
                    <input type="radio" name="export-format" value="pdf">
                    <div class="export-card-icon">📑</div>
                    <div class="export-card-label">PDF</div>
                    <div class="export-card-desc">Print-ready document</div>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="ghost-link" data-modal-close>Cancel</button>
            <button type="button" class="primary-btn" id="export-confirm">Export</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const exportButtons = document.querySelectorAll('[data-export-modal]');
    const exportModal = document.getElementById('export-modal');
    const exportConfirmBtn = document.getElementById('export-confirm');
    const exportCards = document.querySelectorAll('.export-card');
    let currentExportType = '';

    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            currentExportType = this.dataset.exportModal;
            const query = this.dataset.exportQuery || '';
            exportModal.dataset.exportQuery = query;
            MobilisModal.open('export-modal');
        });
    });

    exportCards.forEach(card => {
        card.addEventListener('click', function() {
            exportCards.forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            const radio = this.querySelector('input[type="radio"]');
            if (radio) {
                radio.checked = true;
            }
        });
    });

    exportConfirmBtn.addEventListener('click', function() {
        const format = document.querySelector('input[name="export-format"]:checked').value;
        const query = exportModal.dataset.exportQuery || '';

        let url = `Staff/${currentExportType}-export.php?format=${format}`;
        if (query) {
            url += '&' + query;
        }

        window.location.href = url;
        MobilisModal.close('export-modal');
    });
});
</script>
<?php viewEnd();
?>

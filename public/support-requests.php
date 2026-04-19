<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$contactMessages = getAdminContactMessages(30);
$resetRequests = getPasswordResetRequests(30);

renderPageTop('Support inbox', 'support');
?>
<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h3>Contact admin submissions</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($contactMessages as $msg): ?>
                    <tr>
                        <td>#<?= (int) $msg['message_id'] ?></td>
                        <td><?= htmlspecialchars((string) $msg['full_name']) ?></td>
                        <td><?= htmlspecialchars((string) $msg['email']) ?></td>
                        <td><?= htmlspecialchars((string) $msg['subject']) ?></td>
                        <td><span class="pill support-status-<?= htmlspecialchars((string) $msg['status']) ?>"><?= htmlspecialchars(ucfirst((string) $msg['status'])) ?></span></td>
                        <td><?= htmlspecialchars((string) $msg['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h3>Password reset requests</h3>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>License no.</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($resetRequests as $request): ?>
                    <tr>
                        <td>#<?= (int) $request['request_id'] ?></td>
                        <td><?= htmlspecialchars((string) $request['email']) ?></td>
                        <td><?= htmlspecialchars((string) ($request['license_number'] ?? 'N/A')) ?></td>
                        <td><span class="pill support-status-<?= htmlspecialchars((string) $request['status']) ?>"><?= htmlspecialchars(ucfirst((string) $request['status'])) ?></span></td>
                        <td><?= htmlspecialchars((string) $request['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>
<?php renderPageBottom(); ?>

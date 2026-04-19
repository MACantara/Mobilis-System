<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$user = currentUser();

renderPageTop('Settings', 'settings');
?>
<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h3>System settings</h3>
        </div>
        <div class="mini-stats">
            <div>
                <span>Application</span>
                <strong>Mobilis Prototype</strong>
            </div>
            <div>
                <span>Database status</span>
                <strong><?= dbConnected() ? 'Connected' : 'Fallback mode' ?></strong>
            </div>
            <div>
                <span>Analytics engine</span>
                <strong>Python bridge enabled</strong>
            </div>
        </div>
    </article>

    <article class="card side-panel">
        <h3>Account</h3>
        <h4><?= htmlspecialchars((string) ($user['name'] ?? 'Unknown user')) ?></h4>
        <p><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></p>
        <div class="mini-stats">
            <div>
                <span>Role</span>
                <strong><?= htmlspecialchars((string) ($user['role'] ?? 'guest')) ?></strong>
            </div>
            <div>
                <span>Preferences</span>
                <strong>Notifications: Enabled</strong>
            </div>
        </div>
    </article>
</section>
<?php renderPageBottom(); ?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin']);

$user = currentUser();
$userId = (int) ($user['user_id'] ?? 0);

$errors = [];
$success = '';

$account = $userId > 0 ? getUserById($userId) : null;
$profileForm = [
    'first_name' => (string) ($account['first_name'] ?? ''),
    'last_name' => (string) ($account['last_name'] ?? ''),
    'email' => (string) ($account['email'] ?? ((string) ($user['email'] ?? ''))),
    'phone' => (string) ($account['phone'] ?? ''),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = strtolower(trim((string) ($_POST['action'] ?? '')));

    if ($action === 'update_profile') {
        $profileForm['first_name'] = trim((string) ($_POST['first_name'] ?? ''));
        $profileForm['last_name'] = trim((string) ($_POST['last_name'] ?? ''));
        $profileForm['email'] = trim((string) ($_POST['email'] ?? ''));
        $profileForm['phone'] = trim((string) ($_POST['phone'] ?? ''));

        $result = updateUserProfile($userId, $profileForm);
        if (($result['ok'] ?? false) === true) {
            $success = 'Profile details updated successfully.';
            $account = getUserById($userId);
            if (is_array($account)) {
                $profileForm['first_name'] = (string) ($account['first_name'] ?? '');
                $profileForm['last_name'] = (string) ($account['last_name'] ?? '');
                $profileForm['email'] = (string) ($account['email'] ?? $profileForm['email']);
                $profileForm['phone'] = (string) ($account['phone'] ?? $profileForm['phone']);
            }
        } else {
            $errors[] = (string) ($result['error'] ?? 'Could not update profile settings.');
        }
    } elseif ($action === 'change_password') {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'New password and confirmation do not match.';
        } else {
            $result = changeUserPassword($userId, $currentPassword, $newPassword);
            if (($result['ok'] ?? false) === true) {
                $success = 'Password updated successfully.';
            } else {
                $errors[] = (string) ($result['error'] ?? 'Could not update password.');
            }
        }
    }
}

viewBegin('app', appLayoutData('Settings', 'settings', ['role' => 'admin']));
?>
<section class="page-content-head">
    <h3>System settings</h3>
    <?php if ($success !== ''): ?>
        <div class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors !== []): ?>
        <div class="alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="content-grid split-grid">
    <article class="card">
        <div class="card-header">
            <h4>Profile settings</h4>
        </div>
        <form method="post" class="customer-form-grid">
            <input type="hidden" name="action" value="update_profile">
            <label>First name
                <input type="text" name="first_name" value="<?= htmlspecialchars($profileForm['first_name']) ?>" required>
            </label>
            <label>Last name
                <input type="text" name="last_name" value="<?= htmlspecialchars($profileForm['last_name']) ?>" required>
            </label>
            <label>Email address
                <input type="email" name="email" value="<?= htmlspecialchars($profileForm['email']) ?>" required>
            </label>
            <label>Phone
                <input type="tel" name="phone" value="<?= htmlspecialchars($profileForm['phone']) ?>" placeholder="+63 9xx xxx xxxx">
            </label>
            <div class="customer-form-actions full">
                <button type="submit" class="primary-btn">Save profile</button>
            </div>
        </form>
    </article>

    <article class="card side-panel">
        <h4>Security and access</h4>
        <p class="settings-account-name"><?= htmlspecialchars((string) ($user['name'] ?? 'Unknown user')) ?></p>
        <p><?= htmlspecialchars((string) ($user['email'] ?? '')) ?></p>

        <form method="post" class="customer-form-grid" style="margin-top:10px;">
            <input type="hidden" name="action" value="change_password">
            <label>Current password
                <input type="password" name="current_password" minlength="8" required>
            </label>
            <label>New password
                <input type="password" name="new_password" minlength="8" required>
            </label>
            <label>Confirm new password
                <input type="password" name="confirm_password" minlength="8" required>
            </label>
            <div class="customer-form-actions full">
                <button type="submit" class="primary-btn">Change password</button>
            </div>
        </form>

        <div class="mini-stats">
            <div>
                <span>Role</span>
                <strong><?= htmlspecialchars((string) ($user['role'] ?? 'guest')) ?></strong>
            </div>
            <div>
                <span>Database status</span>
                <strong><?= dbConnected() ? 'Connected' : 'Fallback mode' ?></strong>
            </div>
            <div>
                <span>Auth mode</span>
                <strong><?= dbConnected() ? 'Database users' : 'Demo users' ?></strong>
            </div>
        </div>
    </article>
</section>
<?php viewEnd();
?>

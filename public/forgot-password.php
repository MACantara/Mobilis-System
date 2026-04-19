<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $licenseNumber = trim((string) ($_POST['license_number'] ?? ''));
    $reason = trim((string) ($_POST['reason'] ?? ''));

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if ($reason === '') {
        $errors[] = 'Please provide a short reason for the reset request.';
    }

    if (strlen($reason) > 500) {
        $errors[] = 'Reason must be 500 characters or fewer.';
    }

    if ($errors === []) {
        $saved = submitPasswordResetRequest(
            $email,
            $licenseNumber !== '' ? $licenseNumber : null,
            $reason
        );

        if ($saved) {
            $success = 'Your reset request was submitted and saved. The admin team will review it shortly.';
        } else {
            $errors[] = 'Could not save your request right now. Check database connection and table setup.';
        }
    }
}
?>
<?php
renderAuthPageTop('Forgot Password');
?>
    <section class="auth-brand-panel">
        <a href="/index.php" class="brand hero-brand">
            <img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">
        </a>
        <div class="hero-copy">
            <h2>We'll help you regain access</h2>
            <p>Send a reset request and the Mobilis admin team will manually review and process your account recovery details.</p>
            <ul class="auth-benefits">
                <li><span class="auth-check">✓</span><span>Manual identity checks for account safety</span></li>
                <li><span class="auth-check">✓</span><span>Fast follow-up through your registered contact info</span></li>
                <li><span class="auth-check">✓</span><span>Status updates from our support team</span></li>
            </ul>
        </div>
    </section>

    <?php renderAuthFormPanelStart(); ?>
        <h3>Password assistance</h3>
        <p>Provide your account details and reason for the request.</p>

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

        <form method="post" class="auth-form-grid">
            <label for="forgot-email">Email address
                <input id="forgot-email" type="email" name="email" placeholder="you@mobilis.ph" required>
            </label>
            <label for="forgot-license">License number (optional)
                <input id="forgot-license" type="text" name="license_number" placeholder="N01-23-456789">
            </label>
            <label for="forgot-reason" class="full">Reason
                <textarea id="forgot-reason" name="reason" rows="4" maxlength="500" placeholder="Lost access to account credentials" required></textarea>
            </label>
            <button type="submit" class="primary-btn full">Submit request</button>
        </form>

        <div class="auth-form-footer-links">
            <a href="/login.php">Back to sign in</a>
            <a href="/contact-admin.php">Contact admin</a>
        </div>
    <?php renderAuthFormPanelEnd(); ?>
<?php renderAuthPageBottom(); ?>

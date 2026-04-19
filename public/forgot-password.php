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
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | Mobilis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<main class="auth-helper-wrap">
    <section class="auth-helper-card">
        <h2>Password assistance</h2>
        <p>Submit a password reset request and it will be stored for the admin team to verify and process.</p>
        <h3>Submit reset details</h3>

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

        <form method="post" class="auth-helper-form">
            <label>Email address
                <input type="email" name="email" placeholder="you@mobilis.ph" required>
            </label>
            <label>License number (optional)
                <input type="text" name="license_number" placeholder="N01-23-456789">
            </label>
            <label>Reason
                <textarea name="reason" rows="4" maxlength="500" placeholder="Lost access to account credentials" required></textarea>
            </label>
            <button type="submit" class="primary-btn">Submit request</button>
        </form>

        <p>Default admin demo account: <strong>admin@mobilis.ph</strong></p>
        <div class="auth-helper-actions">
            <a href="/login.php" class="primary-btn">Back to sign in</a>
            <a href="/contact-admin.php" class="ghost-btn">Contact admin</a>
        </div>
    </section>
</main>
</body>
</html>

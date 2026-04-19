<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $licenseNumber = trim((string) ($_POST['license_number'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));

    if ($fullName === '') {
        $errors[] = 'Please provide your full name.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if ($phone === '') {
        $errors[] = 'Please provide a contact number.';
    }

    if ($licenseNumber === '') {
        $errors[] = 'Please provide your license number.';
    }

    if ($errors === []) {
        $subject = 'Account registration request';
        $message = "A new customer account request was submitted.\n"
            . "Name: {$fullName}\n"
            . "Email: {$email}\n"
            . "Phone: {$phone}\n"
            . "License number: {$licenseNumber}\n"
            . "Address: " . ($address !== '' ? $address : 'N/A');

        $saved = submitAdminContactMessage($fullName, $email, $phone, $subject, $message);
        if ($saved) {
            $success = 'Registration request submitted. The Mobilis team will review and activate your account.';
        } else {
            $errors[] = 'Could not save your request right now. Please try again later.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Mobilis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<main class="auth-helper-wrap">
    <section class="auth-helper-card">
        <h2>Create your Mobilis account</h2>
        <p>Sign up to start booking vehicles, track rentals, and manage your trip history in one platform.</p>
        <h3>Registration details</h3>

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
            <label>Full name
                <input type="text" name="full_name" placeholder="Maria Reyes" required>
            </label>
            <label>Email address
                <input type="email" name="email" placeholder="maria@email.com" required>
            </label>
            <label>Phone number
                <input type="tel" name="phone" placeholder="+63 917 123 4567" required>
            </label>
            <label>Driver's license number
                <input type="text" name="license_number" placeholder="N01-23-456789" required>
            </label>
            <label>Address (optional)
                <textarea name="address" rows="3" placeholder="Makati City, Metro Manila"></textarea>
            </label>
            <button type="submit" class="primary-btn">Submit registration</button>
        </form>

        <div class="auth-helper-actions">
            <a href="/login.php" class="primary-btn">Go to sign in</a>
            <a href="/contact-admin.php" class="ghost-btn">Talk to admin</a>
        </div>
    </section>
</main>
</body>
</html>

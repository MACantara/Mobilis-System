<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($fullName === '') {
        $errors[] = 'Please provide your full name.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if ($subject === '') {
        $errors[] = 'Please provide a subject.';
    }

    if ($message === '') {
        $errors[] = 'Please provide your message.';
    }

    if (strlen($message) > 1000) {
        $errors[] = 'Message must be 1000 characters or fewer.';
    }

    if ($errors === []) {
        $saved = submitAdminContactMessage($fullName, $email, $phone, $subject, $message);
        if ($saved) {
            $success = 'Your message was submitted and saved for the admin team.';
        } else {
            $errors[] = 'Could not save your message right now. Check database connection and table setup.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Admin | Mobilis</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<main class="auth-helper-wrap">
    <section class="auth-helper-card">
        <h2>Contact your admin</h2>
        <p>For account creation, role updates, billing questions, or password support, send a message below.</p>
        <h3>Submit a support message</h3>

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
            <label>Phone (optional)
                <input type="tel" name="phone" placeholder="+63 917 123 4567">
            </label>
            <label>Subject
                <input type="text" name="subject" placeholder="Account access support" required>
            </label>
            <label>Message
                <textarea name="message" rows="4" maxlength="1000" placeholder="Please help reset my account access" required></textarea>
            </label>
            <button type="submit" class="primary-btn">Send to admin</button>
        </form>

        <p>Email: <strong>admin@mobilis.ph</strong><br>Support line: <strong>+63 917 000 0000</strong></p>
        <div class="auth-helper-actions">
            <a href="/Customer/login.php" class="primary-btn">Back to sign in</a>
            <a href="/Customer/forgot-password.php" class="ghost-btn">Forgot password</a>
        </div>
    </section>
</main>
</body>
</html>

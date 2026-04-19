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
<?php
renderAuthPageTop('Create Account');
?>
    <section class="auth-brand-panel">
        <a href="/index.php" class="brand hero-brand">
            <img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">
        </a>
        <div class="hero-copy">
            <h2>Join Mobilis, start renting in minutes</h2>
            <p>Create your account request and our team will activate your profile for secure booking, tracking, and payment visibility.</p>
            <ul class="auth-benefits">
                <li><span class="auth-check">✓</span><span>Online booking</span></li>
                <li><span class="auth-check">✓</span><span>Real-time tracking</span></li>
                <li><span class="auth-check">✓</span><span>Transparent billing</span></li>
            </ul>
        </div>
    </section>

    <?php renderAuthFormPanelStart(); ?>
        <h3>Create your Mobilis account</h3>
        <p>Submit your details below and wait for admin approval.</p>

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
            <label for="register-full-name">Full name
                <input id="register-full-name" type="text" name="full_name" placeholder="Maria Reyes" required>
            </label>
            <label for="register-email">Email address
                <input id="register-email" type="email" name="email" placeholder="maria@email.com" required>
            </label>
            <label for="register-phone">Phone number
                <input id="register-phone" type="tel" name="phone" placeholder="+63 917 123 4567" required>
            </label>
            <label for="register-license">Driver's license number
                <input id="register-license" type="text" name="license_number" placeholder="N01-23-456789" required>
            </label>
            <label for="register-address" class="full">Address (optional)
                <textarea id="register-address" name="address" rows="3" placeholder="Makati City, Metro Manila"></textarea>
            </label>
            <button type="submit" class="primary-btn full">Submit registration</button>
        </form>

        <p class="auth-footnote">Already approved? <a href="/login.php" class="text-link">Sign in</a></p>
    <?php renderAuthFormPanelEnd(); ?>
<?php renderAuthPageBottom(); ?>

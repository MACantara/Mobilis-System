<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim((string) ($_POST['full_name'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $licenseNumber = trim((string) ($_POST['license_number'] ?? ''));
    $licenseExpiry = trim((string) ($_POST['license_expiry'] ?? ''));
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

    if ($password === '' || strlen($password) < 6) {
        $errors[] = 'Please provide a password with at least 6 characters.';
    }

    if ($errors === []) {
        if (!dbConnected()) {
            $errors[] = 'Database connection not available. Please try again later.';
        } else {
            try {
                // Check if email already exists
                $checkStmt = db()->prepare("SELECT COUNT(*) FROM User WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetchColumn() > 0) {
                    $errors[] = 'An account with this email already exists.';
                } else {
                    // Split full name into first and last name
                    $nameParts = explode(' ', $fullName, 2);
                    $firstName = $nameParts[0] ?? '';
                    $lastName = $nameParts[1] ?? '';

                    // Hash password
                    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

                    // Insert new user
                    $stmt = db()->prepare(
                        "INSERT INTO User (first_name, last_name, email, phone, license_number, license_expiry, address, role, password_hash)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'customer', ?)"
                    );
                    $stmt->execute([
                        $firstName,
                        $lastName,
                        $email,
                        $phone,
                        $licenseNumber !== '' ? $licenseNumber : null,
                        $licenseExpiry !== '' ? $licenseExpiry : null,
                        $address !== '' ? $address : null,
                        $passwordHash
                    ]);

                    $success = 'Account created successfully! You can now sign in with your credentials.';
                }
            } catch (Throwable $e) {
                $errors[] = 'Could not create account. Please try again later.';
            }
        }
    }
}
?>
<?php
viewBegin('auth', authLayoutData('Create Account'));
?>
    <section class="auth-brand-panel">
        <a href="/index.php" class="brand hero-brand">
            <img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">
        </a>
        <div class="hero-copy">
            <h2>Join Mobilis, start renting in minutes</h2>
            <p>Create your account and start booking vehicles immediately with real-time tracking and transparent billing.</p>
            <ul class="auth-benefits">
                <li><span class="auth-check">✓</span><span>Online booking</span></li>
                <li><span class="auth-check">✓</span><span>Real-time tracking</span></li>
                <li><span class="auth-check">✓</span><span>Transparent billing</span></li>
            </ul>
        </div>
    </section>

    <?php viewAuthFormPanelStart(); ?>
        <h3>Create your Mobilis account</h3>
        <p>Fill in your details below to create your customer account.</p>

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
            <label for="register-password">Password
                <input id="register-password" type="password" name="password" placeholder="At least 6 characters" required minlength="6">
            </label>
            <label for="register-license">Driver's license number (optional)
                <input id="register-license" type="text" name="license_number" placeholder="N01-23-456789">
            </label>
            <label for="register-expiry">License expiry date (optional)
                <input id="register-expiry" type="date" name="license_expiry">
            </label>
            <label for="register-address" class="full">Address (optional)
                <textarea id="register-address" name="address" rows="3" placeholder="Makati City, Metro Manila"></textarea>
            </label>
            <button type="submit" class="primary-btn full">Create account</button>
        </form>

        <p class="auth-footnote">Already have an account? <a href="/login.php" class="text-link">Sign in</a></p>
    <?php viewAuthFormPanelEnd(); ?>
<?php viewEnd();
?>

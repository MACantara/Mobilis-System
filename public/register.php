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
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Please provide your full name.';
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please provide a valid email address.';
    }

    if ($phone === '') {
        $errors[] = 'Please provide a contact number.';
    }

    if ($password === '' || strlen($password) < 8) {
        $errors[] = 'Please provide a password with at least 8 characters.';
    }

    if ($confirmPassword === '' || $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
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
                        VALUES (?, ?, ?, ?, NULL, NULL, NULL, 'customer', ?)"
                    );
                    $stmt->execute([
                        $firstName,
                        $lastName,
                        $email,
                        $phone,
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
                <div class="password-field-wrapper">
                    <input id="register-password" type="password" name="password" placeholder="At least 8 characters" required minlength="8">
                    <button type="button" class="password-toggle-btn" data-target="register-password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon" style="display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 5.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </label>
            <label for="register-confirm-password">Confirm password
                <div class="password-field-wrapper">
                    <input id="register-confirm-password" type="password" name="confirm_password" placeholder="Confirm your password" required minlength="8">
                    <button type="button" class="password-toggle-btn" data-target="register-confirm-password">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-off-icon" style="display: none;">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 5.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                            <line x1="1" y1="1" x2="23" y2="23"></line>
                        </svg>
                    </button>
                </div>
            </label>
            <button type="submit" class="primary-btn full">Create account</button>
        </form>

        <p class="auth-footnote">Already have an account? <a href="/login.php" class="text-link">Sign in</a></p>
    <?php viewAuthFormPanelEnd(); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordToggleButtons = document.querySelectorAll('.password-toggle-btn');

    passwordToggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const eyeIcon = this.querySelector('.eye-icon');
            const eyeOffIcon = this.querySelector('.eye-off-icon');

            if (input.type === 'password') {
                input.type = 'text';
                eyeIcon.style.display = 'none';
                eyeOffIcon.style.display = 'block';
            } else {
                input.type = 'password';
                eyeIcon.style.display = 'block';
                eyeOffIcon.style.display = 'none';
            }
        });
    });
});
</script>
<?php viewEnd();
?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

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
<?php
renderAuthPageTop('Contact Admin');
?>
    <section class="auth-brand-panel">
        <a href="/index.php" class="brand hero-brand">
            <img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">
        </a>
        <div class="hero-copy">
            <h2>Reach the Mobilis team</h2>
            <p>For account concerns, billing clarifications, and support requests, send us a message and we will follow up as soon as possible.</p>
            <ul class="auth-channel-list">
                <li>
                    <span class="auth-channel-icon">✉️</span>
                    <div>
                        <strong>Email support</strong>
                        <span>admin@mobilis.ph</span>
                    </div>
                </li>
                <li>
                    <span class="auth-channel-icon">📞</span>
                    <div>
                        <strong>Support line</strong>
                        <span>+63 917 000 0000</span>
                    </div>
                </li>
                <li>
                    <span class="auth-channel-icon">🕒</span>
                    <div>
                        <strong>Response window</strong>
                        <span>Mon-Sat, 8:00 AM to 6:00 PM</span>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    <?php renderAuthFormPanelStart(); ?>
        <h3>Contact your admin</h3>
        <p>Submit a support message and we will route it to the right team.</p>

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
            <label for="contact-full-name">Full name
                <input id="contact-full-name" type="text" name="full_name" placeholder="Maria Reyes" required>
            </label>
            <label for="contact-email">Email address
                <input id="contact-email" type="email" name="email" placeholder="maria@email.com" required>
            </label>
            <label for="contact-phone">Phone (optional)
                <input id="contact-phone" type="tel" name="phone" placeholder="+63 917 123 4567">
            </label>
            <label for="contact-subject">Subject
                <input id="contact-subject" type="text" name="subject" placeholder="Account access support" required>
            </label>
            <label for="contact-message" class="full">Message
                <textarea id="contact-message" name="message" rows="4" maxlength="1000" placeholder="Please help reset my account access" required></textarea>
            </label>
            <button type="submit" class="primary-btn full">Send to admin</button>
        </form>

        <div class="auth-form-footer-links">
            <a href="/login.php">Back to sign in</a>
            <a href="/forgot-password.php">Forgot password</a>
        </div>
    <?php renderAuthFormPanelEnd(); ?>
<?php renderAuthPageBottom(); ?>

<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$errors = [];
$success = '';
$contactHistoryEmail = '';
$contactHistory = [];

$form = [
    'full_name' => '',
    'email' => '',
    'phone' => '',
    'subject' => '',
    'message' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
    $form['email'] = strtolower(trim((string) ($_POST['email'] ?? '')));
    $form['phone'] = trim((string) ($_POST['phone'] ?? ''));
    $form['subject'] = trim((string) ($_POST['subject'] ?? ''));
    $form['message'] = trim((string) ($_POST['message'] ?? ''));

    $fullName = $form['full_name'];
    $email = $form['email'];
    $phone = $form['phone'];
    $subject = $form['subject'];
    $message = $form['message'];

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
            $contactHistoryEmail = $email;
        } else {
            $errors[] = 'Could not save your message right now. Check database connection and table setup.';
        }
    }
}

if ($contactHistoryEmail === '' && $form['email'] !== '') {
    $contactHistoryEmail = $form['email'];
}

if ($contactHistoryEmail !== '') {
    $contactHistory = getAdminContactMessagesByEmail($contactHistoryEmail, 5);
}
?>
<?php
viewBegin('auth', authLayoutData('Contact Admin'));
?>
    <section class="auth-brand-panel">
        <a href="<?= baseUrl() ?>/index.php" class="brand hero-brand">
            <img src="<?= baseUrl() ?>/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">
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

    <?php viewAuthFormPanelStart(); ?>
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

        <form method="post" class="auth-form-grid" id="contactAdminForm">
            <label for="contact-full-name" class="full">Full name
                <input id="contact-full-name" type="text" name="full_name" value="<?= htmlspecialchars($form['full_name']) ?>" placeholder="Maria Reyes" required>
            </label>

            <label for="contact-email" class="full">Email address
                <input id="contact-email" type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" placeholder="maria@email.com" required>
            </label>

            <label for="contact-phone" class="full">Phone (optional)
                <input id="contact-phone" type="tel" name="phone" value="<?= htmlspecialchars($form['phone']) ?>" placeholder="+63 917 123 4567">
            </label>

            <label for="contact-subject" class="full">Subject
                <input id="contact-subject" type="text" name="subject" value="<?= htmlspecialchars($form['subject']) ?>" placeholder="Account access support" required>
            </label>

            <label for="contact-message" class="full">Message
                <textarea id="contact-message" name="message" rows="5" maxlength="1000" placeholder="Please help reset my account access" required><?= htmlspecialchars($form['message']) ?></textarea>
            </label>

            <button type="submit" class="primary-btn full" id="contactAdminSubmit">Send to admin</button>
        </form>

        <?php if ($contactHistory !== []): ?>
            <div class="table-wrap" style="margin-top: 12px;">
                <table>
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Admin response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contactHistory as $item): ?>
                            <tr>
                                <td>#<?= (int) ($item['message_id'] ?? 0) ?></td>
                                <td>
                                    <?= htmlspecialchars((string) ($item['subject'] ?? '')) ?>
                                    <p class="muted"><?= htmlspecialchars((string) ($item['created_at'] ?? '')) ?></p>
                                </td>
                                <td><span class="pill support-status-<?= htmlspecialchars((string) ($item['status'] ?? 'new')) ?>"><?= htmlspecialchars(ucfirst((string) ($item['status'] ?? 'new'))) ?></span></td>
                                <td>
                                    <?php if (trim((string) ($item['admin_response'] ?? '')) !== ''): ?>
                                        <?= nl2br(htmlspecialchars((string) ($item['admin_response'] ?? ''))) ?>
                                        <p class="muted">Updated <?= htmlspecialchars((string) ($item['responded_at'] ?? '')) ?></p>
                                    <?php else: ?>
                                        <span class="muted">Awaiting admin response</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <div class="auth-form-footer-links">
            <a href="<?= baseUrl() ?>/login.php">Back to sign in</a>
            <a href="<?= baseUrl() ?>/forgot-password.php">Forgot password</a>
        </div>
    <?php viewAuthFormPanelEnd(); ?>

    <script>
    (function () {
        const contactForm = document.getElementById('contactAdminForm');
        const submitBtn = document.getElementById('contactAdminSubmit');

        if (!contactForm) {
            return;
        }

        contactForm.addEventListener('submit', function () {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';
            }
        });
    })();
    </script>
<?php viewEnd();
?>

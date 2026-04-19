<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin']);

$success = '';
$errors = [];
$user = currentUser() ?? [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');
    $requestId = (int) ($_POST['request_id'] ?? 0);

    if ($action === 'respond_contact' && $requestId > 0) {
        $responseStatus = (string) ($_POST['response_status'] ?? 'read');
        $responseMessage = trim((string) ($_POST['response_message'] ?? ''));

        $result = respondToAdminContactMessage(
            $requestId,
            (int) ($user['user_id'] ?? 0),
            $responseStatus,
            $responseMessage
        );

        if (($result['ok'] ?? false) === true) {
            $success = 'Response sent successfully.';
        } else {
            $errors[] = (string) ($result['error'] ?? 'Could not save response.');
        }
    } elseif ($action === 'reset_password' && $requestId > 0) {
        $newPassword = trim((string) ($_POST['new_password'] ?? ''));
        $confirmPassword = trim((string) ($_POST['confirm_password'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($newPassword === '' || strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'Passwords do not match.';
        } else {
            $resetRequests = getPasswordResetRequests(200);
            $request = null;
            foreach ($resetRequests as $r) {
                if ((int) $r['request_id'] === $requestId) {
                    $request = $r;
                    break;
                }
            }

            if ($request === null) {
                $errors[] = 'Password reset request not found.';
            } else {
                $user = findUserByEmail($request['email']);
                if ($user === null) {
                    $errors[] = 'User not found for this email address.';
                } else {
                    if (resetUserPassword((int) $user['user_id'], $newPassword)) {
                        updatePasswordResetRequestStatus($requestId, 'completed');
                        $success = 'Password reset successfully for ' . htmlspecialchars($request['email']);
                    } else {
                        $errors[] = 'Failed to reset password. Please try again.';
                    }
                }
            }
        }
    } elseif ($action === 'reject_request' && $requestId > 0) {
        updatePasswordResetRequestStatus($requestId, 'rejected');
        $success = 'Password reset request rejected.';
    }
}

$contactMessages = getAdminContactMessages(30);
$resetRequests = getPasswordResetRequests(30);

viewBegin('app', appLayoutData('Support inbox', 'support', ['role' => 'admin']));
?>
<section class="page-content-head">
    <h3>All support requests</h3>
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
            <h4>Contact admin submissions</h4>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Subject and message</th>
                        <th>Admin response</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Respond</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($contactMessages === []): ?>
                    <tr>
                        <td colspan="7" class="muted">No contact submissions yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($contactMessages as $msg): ?>
                        <tr>
                            <td>#<?= (int) $msg['message_id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars((string) $msg['full_name']) ?></strong>
                                <p class="muted"><?= htmlspecialchars((string) $msg['email']) ?></p>
                                <p class="muted"><?= htmlspecialchars((string) ($msg['phone'] ?? '')) ?></p>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars((string) $msg['subject']) ?></strong>
                                <p><?= nl2br(htmlspecialchars((string) ($msg['message'] ?? ''))) ?></p>
                            </td>
                            <td>
                                <?php if (trim((string) ($msg['admin_response'] ?? '')) !== ''): ?>
                                    <p><?= nl2br(htmlspecialchars((string) $msg['admin_response'])) ?></p>
                                    <p class="muted">Updated: <?= htmlspecialchars((string) ($msg['responded_at'] ?? 'N/A')) ?></p>
                                <?php else: ?>
                                    <p class="muted">No response yet.</p>
                                <?php endif; ?>
                            </td>
                            <td><span class="pill support-status-<?= htmlspecialchars((string) $msg['status']) ?>"><?= htmlspecialchars(ucfirst((string) $msg['status'])) ?></span></td>
                            <td><?= htmlspecialchars((string) $msg['created_at']) ?></td>
                            <td>
                                <button
                                    type="button"
                                    class="primary-btn"
                                    data-modal-open="contactResponseModal"
                                    data-contact-request-id="<?= (int) $msg['message_id'] ?>"
                                    data-contact-full-name="<?= htmlspecialchars((string) ($msg['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-contact-email="<?= htmlspecialchars((string) ($msg['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-contact-subject="<?= htmlspecialchars((string) ($msg['subject'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-contact-status="<?= htmlspecialchars((string) ($msg['status'] ?? 'read'), ENT_QUOTES, 'UTF-8') ?>"
                                    data-contact-response="<?= htmlspecialchars((string) ($msg['admin_response'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                >Respond</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card">
        <div class="card-header">
            <h4>Password reset requests</h4>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>License no.</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($resetRequests as $request): ?>
                    <tr>
                        <td>#<?= (int) $request['request_id'] ?></td>
                        <td><?= htmlspecialchars((string) $request['email']) ?></td>
                        <td><?= htmlspecialchars((string) ($request['license_number'] ?? 'N/A')) ?></td>
                        <td><?= htmlspecialchars((string) ($request['reason'] ?? '-')) ?></td>
                        <td><span class="pill support-status-<?= htmlspecialchars((string) $request['status']) ?>"><?= htmlspecialchars(ucfirst((string) $request['status'])) ?></span></td>
                        <td><?= htmlspecialchars((string) $request['created_at']) ?></td>
                        <td>
                            <?php if ($request['status'] === 'pending'): ?>
                                <button
                                    type="button"
                                    class="ghost-btn"
                                    data-modal-open="resetModal"
                                    data-reset-request-id="<?= (int) $request['request_id'] ?>"
                                    data-reset-email="<?= htmlspecialchars((string) $request['email'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-reset-license="<?= htmlspecialchars((string) ($request['license_number'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-reset-reason="<?= htmlspecialchars((string) ($request['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                >Reset</button>
                                <form
                                    method="post"
                                    style="display:inline;"
                                    data-confirm-submit
                                    data-confirm-title="Reject reset request"
                                    data-confirm-message="This request will be marked as rejected. Continue?"
                                    data-confirm-label="Reject request"
                                    data-cancel-label="Cancel"
                                    data-confirm-danger="1"
                                >
                                    <input type="hidden" name="action" value="reject_request">
                                    <input type="hidden" name="request_id" value="<?= (int) $request['request_id'] ?>">
                                    <button type="submit" class="ghost-btn text-error">Reject</button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted"><?= htmlspecialchars(ucfirst((string) $request['status'])) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </article>
</section>

<?php viewModalStart('contactResponseModal', 'Respond to customer message', ['size' => 'lg']); ?>
    <form method="post" class="modal-body" id="contactResponseForm">
        <input type="hidden" name="action" value="respond_contact">
        <input type="hidden" name="request_id" id="contactResponseRequestId">

        <div class="user-info">
            <div class="user-info-item">
                <span class="user-info-label">Customer:</span>
                <span class="user-info-value" id="contactResponseCustomer">N/A</span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">Email:</span>
                <span class="user-info-value" id="contactResponseEmail">N/A</span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">Subject:</span>
                <span class="user-info-value" id="contactResponseSubject">N/A</span>
            </div>
        </div>

        <label for="contactResponseStatus">Status update</label>
        <select id="contactResponseStatus" name="response_status" required>
            <option value="read">Mark as read</option>
            <option value="resolved">Mark as resolved</option>
        </select>

        <label for="contactResponseMessage">Admin response</label>
        <textarea id="contactResponseMessage" name="response_message" rows="4" maxlength="1200" placeholder="Write response to this request..." required></textarea>

        <div class="modal-footer">
            <button type="button" class="ghost-btn" data-modal-close>Cancel</button>
            <button type="submit" class="primary-btn" id="contactResponseSubmit">Save response</button>
        </div>
    </form>
<?php viewModalEnd(); ?>

<?php viewModalStart('resetModal', 'Reset User Password', ['size' => 'md']); ?>
    <form method="post" class="modal-body" id="resetForm">
        <input type="hidden" name="action" value="reset_password">
        <input type="hidden" name="request_id" id="modalRequestId">
        <input type="hidden" name="email" id="modalEmail">

        <div class="user-info" id="userInfo">
            <div class="user-info-item">
                <span class="user-info-label">Email:</span>
                <span class="user-info-value" id="modalEmailDisplay"></span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">License:</span>
                <span class="user-info-value" id="modalLicenseDisplay">N/A</span>
            </div>
            <div class="user-info-item">
                <span class="user-info-label">Reason:</span>
                <span class="user-info-value" id="modalReasonDisplay">N/A</span>
            </div>
        </div>

        <div class="password-field-wrapper">
            <label for="new_password">New password</label>
            <input id="new_password" type="password" name="new_password" required minlength="8" placeholder="At least 8 characters">
            <button type="button" class="password-toggle" data-password-toggle data-target="new_password" aria-label="Toggle new password visibility">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>

        <div class="password-strength">
            <div class="strength-meter">
                <div class="strength-meter-fill" id="strengthMeter"></div>
            </div>
            <div class="strength-text" id="strengthText">Enter a password</div>
        </div>

        <div class="password-field-wrapper">
            <label for="confirm_password">Confirm password</label>
            <input id="confirm_password" type="password" name="confirm_password" required minlength="8" placeholder="Re-enter password">
            <button type="button" class="password-toggle" data-password-toggle data-target="confirm_password" aria-label="Toggle confirm password visibility">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>

        <p class="form-hint">Password must be at least 8 characters long.</p>

        <div class="modal-footer">
            <button type="button" class="ghost-btn" data-modal-close>Cancel</button>
            <button type="submit" class="primary-btn" id="resetBtn">Reset Password</button>
        </div>
    </form>
<?php viewModalEnd(); ?>

<script>
(function () {
    const resetModal = document.getElementById('resetModal');
    const resetForm = document.getElementById('resetForm');
    const requestIdInput = document.getElementById('modalRequestId');
    const emailInput = document.getElementById('modalEmail');
    const emailDisplay = document.getElementById('modalEmailDisplay');
    const licenseDisplay = document.getElementById('modalLicenseDisplay');
    const reasonDisplay = document.getElementById('modalReasonDisplay');
    const newPasswordInput = document.getElementById('new_password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const strengthMeter = document.getElementById('strengthMeter');
    const strengthText = document.getElementById('strengthText');
    const resetBtn = document.getElementById('resetBtn');

    if (!resetModal || !resetForm) {
        return;
    }

    const hiddenEye = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
    const visibleEye = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 5.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

    function resetStrengthMeter() {
        if (!strengthMeter || !strengthText) {
            return;
        }

        strengthMeter.className = 'strength-meter-fill';
        strengthMeter.style.width = '0%';
        strengthText.textContent = 'Enter a password';
    }

    function updateStrength() {
        if (!newPasswordInput || !strengthMeter || !strengthText) {
            return;
        }

        const password = newPasswordInput.value;
        let strength = 0;

        if (password.length >= 8) strength++;
        if (password.length >= 12) strength++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
        if (/\d/.test(password)) strength++;
        if (/[^a-zA-Z0-9]/.test(password)) strength++;

        strengthMeter.className = 'strength-meter-fill';

        if (strength <= 1) {
            strengthMeter.classList.add('weak');
            strengthText.textContent = 'Weak password';
        } else if (strength <= 3) {
            strengthMeter.classList.add('medium');
            strengthText.textContent = 'Medium password';
        } else {
            strengthMeter.classList.add('strong');
            strengthText.textContent = 'Strong password';
        }
    }

    function prepareModal(trigger) {
        if (!requestIdInput || !emailInput || !emailDisplay || !licenseDisplay || !reasonDisplay) {
            return;
        }

        requestIdInput.value = trigger.dataset.resetRequestId || '';
        emailInput.value = trigger.dataset.resetEmail || '';
        emailDisplay.textContent = trigger.dataset.resetEmail || '';
        licenseDisplay.textContent = trigger.dataset.resetLicense || 'N/A';
        reasonDisplay.textContent = trigger.dataset.resetReason || 'N/A';

        resetForm.reset();
        if (confirmPasswordInput) {
            confirmPasswordInput.setCustomValidity('');
        }
        resetStrengthMeter();
        if (resetBtn) {
            resetBtn.disabled = false;
            resetBtn.textContent = 'Reset Password';
        }

        resetForm.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.innerHTML = hiddenEye;
        });
    }

    document.querySelectorAll('[data-modal-open="resetModal"][data-reset-request-id]').forEach((button) => {
        button.addEventListener('click', function () {
            prepareModal(button);
        });
    });

    resetForm.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', function () {
            const inputId = button.dataset.target || '';
            const input = inputId ? document.getElementById(inputId) : null;
            if (!input) {
                return;
            }

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            button.innerHTML = isPassword ? visibleEye : hiddenEye;
        });
    });

    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', updateStrength);
    }

    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('input', function () {
            confirmPasswordInput.setCustomValidity('');
        });
    }

    resetForm.addEventListener('submit', function (event) {
        const password = newPasswordInput ? newPasswordInput.value : '';
        const confirmPassword = confirmPasswordInput ? confirmPasswordInput.value : '';

        if (password !== confirmPassword) {
            event.preventDefault();
            if (confirmPasswordInput) {
                confirmPasswordInput.setCustomValidity('Passwords do not match.');
                confirmPasswordInput.reportValidity();
            }
            return;
        }

        if (resetBtn) {
            resetBtn.disabled = true;
            resetBtn.textContent = 'Resetting...';
        }
    });

    resetModal.addEventListener('click', function (event) {
        if (event.target.closest('[data-modal-close]')) {
            resetForm.reset();
            resetStrengthMeter();
            if (resetBtn) {
                resetBtn.disabled = false;
                resetBtn.textContent = 'Reset Password';
            }
        }
    });
})();

(function () {
    const responseForm = document.getElementById('contactResponseForm');
    const requestIdInput = document.getElementById('contactResponseRequestId');
    const customerEl = document.getElementById('contactResponseCustomer');
    const emailEl = document.getElementById('contactResponseEmail');
    const subjectEl = document.getElementById('contactResponseSubject');
    const statusInput = document.getElementById('contactResponseStatus');
    const messageInput = document.getElementById('contactResponseMessage');
    const submitBtn = document.getElementById('contactResponseSubmit');

    if (!responseForm || !requestIdInput || !statusInput || !messageInput) {
        return;
    }

    function fillFromTrigger(trigger) {
        requestIdInput.value = trigger.dataset.contactRequestId || '';
        if (customerEl) {
            customerEl.textContent = trigger.dataset.contactFullName || 'N/A';
        }
        if (emailEl) {
            emailEl.textContent = trigger.dataset.contactEmail || 'N/A';
        }
        if (subjectEl) {
            subjectEl.textContent = trigger.dataset.contactSubject || 'N/A';
        }

        const nextStatus = (trigger.dataset.contactStatus || 'read').toLowerCase();
        statusInput.value = nextStatus === 'resolved' ? 'resolved' : 'read';
        messageInput.value = trigger.dataset.contactResponse || '';

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save response';
        }
    }

    document.querySelectorAll('[data-modal-open="contactResponseModal"][data-contact-request-id]').forEach((button) => {
        button.addEventListener('click', function () {
            fillFromTrigger(button);
        });
    });

    responseForm.addEventListener('submit', function () {
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }
    });
})();
</script>

<?php viewEnd();
?>

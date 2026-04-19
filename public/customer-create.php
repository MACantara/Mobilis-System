<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$errors = [];
$form = [
    'first_name' => '',
    'last_name' => '',
    'email' => '',
    'phone' => '',
    'license_number' => '',
    'license_expiry' => '',
    'address' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if ($form['first_name'] === '' || $form['last_name'] === '') {
        $errors[] = 'First name and last name are required.';
    }
    if ($form['email'] === '' || !filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'A valid email is required.';
    }
    if ($form['phone'] === '') {
        $errors[] = 'Phone is required.';
    }
    if ($form['license_number'] === '' || $form['license_expiry'] === '') {
        $errors[] = 'License number and expiry date are required.';
    }

    if ($errors === []) {
        $result = createCustomer($form);
        if (($result['ok'] ?? false) === true) {
            header('Location: customers.php?notice=customer_created');
            exit;
        }

        $errors[] = (string) ($result['error'] ?? 'Unable to create customer.');
    }
}

renderPageTop('Add customer', 'customers', [
    'show_search' => false,
    'show_primary_cta' => false,
]);
?>
<section class="card customer-form-card">
    <div class="card-header">
        <h3>New customer</h3>
        <a class="ghost-link" href="customers.php">Back</a>
    </div>

    <?php if ($errors !== []): ?>
        <div class="alert-error">
            <?php foreach ($errors as $error): ?>
                <p><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="post" class="customer-form-grid">
        <label>First name
            <input type="text" name="first_name" value="<?= htmlspecialchars($form['first_name']) ?>" required>
        </label>
        <label>Last name
            <input type="text" name="last_name" value="<?= htmlspecialchars($form['last_name']) ?>" required>
        </label>
        <label>Email
            <input type="email" name="email" value="<?= htmlspecialchars($form['email']) ?>" required>
        </label>
        <label>Phone
            <input type="tel" name="phone" value="<?= htmlspecialchars($form['phone']) ?>" required>
        </label>
        <label>License number
            <input type="text" name="license_number" value="<?= htmlspecialchars($form['license_number']) ?>" required>
        </label>
        <label>License expiry
            <input type="date" name="license_expiry" value="<?= htmlspecialchars($form['license_expiry']) ?>" required>
        </label>
        <label class="full">Address
            <textarea name="address" rows="3"><?= htmlspecialchars($form['address']) ?></textarea>
        </label>

        <div class="customer-form-actions full">
            <a class="ghost-link button-like" href="customers.php">Cancel</a>
            <button type="submit" class="primary-btn">Create customer</button>
        </div>
    </form>
</section>
<?php renderPageBottom(); ?>

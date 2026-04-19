<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (isAuthenticated()) {
    header('Location: ' . currentUserHomePath());
    exit;
}

$error = '';
$info = '';

if (isset($_GET['notice'])) {
    if ($_GET['notice'] === 'google') {
        $info = 'Google sign-in is not enabled in this prototype yet. Use demo credentials below.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = (string) ($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if (attemptLogin($email, $password)) {
        header('Location: ' . currentUserHomePath());
        exit;
    }

    $error = 'Invalid credentials.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Mobilis</title>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="login-body">
<div class="login-shell">
    <section class="login-brand-panel">
        <a href="/index.php" class="brand hero-brand">
            <img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">
        </a>
        <div class="hero-copy">
            <h2>Rent a vehicle and manage a fleet, all from one place</h2>
            <p>Track vehicles in real time, handle bookings, monitor maintenance, and grow your rental business with confidence.</p>
        </div>
    </section>

    <section class="login-form-panel">
        <div class="form-wrap">
            <h3>Welcome back</h3>
            <p>Sign in to your Mobilis account</p>

            <?php if ($info !== ''): ?>
                <div class="alert-info"><?= htmlspecialchars($info) ?></div>
            <?php endif; ?>

            <?php if ($error !== ''): ?>
                <div class="alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <label for="login-email">Email address
                    <input id="login-email" type="email" name="email" placeholder="you@mobilis.ph" required>
                </label>
                <label for="login-password">Password
                    <input id="login-password" type="password" name="password" placeholder="Enter password" required>
                </label>

                <div class="form-inline-row">
                    <label for="remember-me" class="checkbox-line">
                        <input id="remember-me" type="checkbox" name="remember_me" value="1">
                        <span>Remember me</span>
                    </label>
                    <a href="/forgot-password.php" class="text-link">Forgot password?</a>
                </div>

                <button type="submit" class="primary-btn full">Sign in</button>

                <div class="or-divider"><span>or</span></div>

                <a class="ghost-btn" href="/login.php?notice=google">Continue with Google</a>
            </form>

            <p class="auth-footnote">Don't have an account? <a href="/register.php" class="text-link">Create an account</a></p>

            <a href="/index.php" class="ghost-link button-like" style="display: block; text-align: center; margin-top: 16px;">← Back to home</a>
        </div>
    </section>
</div>
</body>
</html>

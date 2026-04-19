<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

if (isAuthenticated()) {
    header('Location: /Staff/dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobilis | Smarter Vehicle Rental</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="landing-body">
<main class="landing-shell">
    <section class="landing-hero">
        <div class="brand hero-brand">
            <span class="brand-icon">🚗</span>
            <div>
                <h1>Mobilis</h1>
                <p>Vehicle Rental & Fleet Management</p>
            </div>
        </div>

        <div class="landing-hero-copy">
            <h2>Book faster, travel smarter, and stay in control of every ride.</h2>
            <p>Mobilis helps customers reserve trusted vehicles, monitor booking status in real time, and get transparent billing from pickup to return.</p>
        </div>

        <div class="landing-hero-actions">
            <a href="/register.php" class="primary-btn">Get started</a>
            <a href="/login.php" class="ghost-btn">I already have an account</a>
        </div>

        <div class="landing-proof-grid">
            <article>
                <strong>48+</strong>
                <span>Vehicles managed daily</span>
            </article>
            <article>
                <strong>31</strong>
                <span>Active rentals in progress</span>
            </article>
            <article>
                <strong>99%</strong>
                <span>On-time booking updates</span>
            </article>
        </div>
    </section>

    <section class="landing-section">
        <div class="landing-section-head">
            <h3>Why customers choose Mobilis</h3>
            <p>Everything you need before, during, and after a rental.</p>
        </div>
        <div class="landing-feature-grid">
            <article class="card landing-feature-card">
                <h4>Instant booking visibility</h4>
                <p>Track confirmation, payment, and schedule updates with clear status indicators.</p>
            </article>
            <article class="card landing-feature-card">
                <h4>Reliable vehicle options</h4>
                <p>Choose from sedans, SUVs, vans, and pickups with transparent rates and availability.</p>
            </article>
            <article class="card landing-feature-card">
                <h4>Transparent billing flow</h4>
                <p>See charges, invoices, and payment status in one place with no hidden surprises.</p>
            </article>
        </div>
    </section>

    <section class="landing-section">
        <div class="landing-section-head">
            <h3>Meet Team Mobilis</h3>
            <p>The people building your smarter rental experience.</p>
        </div>

        <div class="team-grid">
            <article class="team-card">
                <img src="/assets/images/Team-Mobilis/DAWINAN.png" alt="Anton Sebastian C. Dawinan">
                <h4>DAWINAN, ANTON SEBASTIAN C.</h4>
                <p>Web/PHP</p>
            </article>
            <article class="team-card">
                <img src="/assets/images/Team-Mobilis/MANGAO.png" alt="Alexander John M. Mangao">
                <h4>MANGAO, ALEXANDER JOHN M.</h4>
                <p>Frontend/UI</p>
            </article>
            <article class="team-card">
                <img src="/assets/images/Team-Mobilis/SADICON.png" alt="Jhane Rose U. Sadicon">
                <h4>SADICON, JHANE ROSE U.</h4>
                <p>Project Manager/QA Tester</p>
            </article>
            <article class="team-card">
                <img src="/assets/images/Team-Mobilis/SY.png" alt="Kenneth A. Sy">
                <h4>SY, KENNETH A.</h4>
                <p>Backend/Python</p>
            </article>
            <article class="team-card">
                <img src="/assets/images/Team-Mobilis/TENORIA.png" alt="Johan Jaiser Tenoria">
                <h4>TENORIA, JOHAN JAISER</h4>
                <p>Database Lead</p>
            </article>
        </div>
    </section>

    <section class="landing-cta card">
        <h3>Ready to book your next vehicle?</h3>
        <p>Create your account today and get access to real-time booking and payment updates.</p>
        <div class="landing-hero-actions">
            <a href="/register.php" class="primary-btn">Create account</a>
            <a href="/login.php" class="ghost-btn">Sign in</a>
        </div>
    </section>
</main>
</body>
</html>

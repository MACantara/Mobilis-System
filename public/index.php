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
    <nav class="landing-nav">
        <a href="/index.php" class="brand">
            <img src="/assets/images/logo.png" alt="Mobilis logo" class="brand-logo">
        </a>
        <div class="landing-nav-center">
            <a href="#platform" class="landing-nav-tab active">Platform</a>
            <a href="#why" class="landing-nav-tab">Solutions</a>
            <a href="#team" class="landing-nav-tab">Team</a>
        </div>
        <div class="landing-nav-links">
            <a href="/login.php" class="landing-nav-link subtle">Sign in</a>
            <a href="/register.php" class="landing-nav-link primary">Register</a>
        </div>
    </nav>

    <section class="landing-hero reveal reveal-delay-1" id="platform">
        <div class="landing-hero-layout">
            <div class="landing-hero-copy">
                <span class="landing-kicker">Redefining Mobility</span>
                <h2>Book faster.<br>Travel <span class="landing-accent">smarter.</span></h2>
                <p>Mobilis helps customers reserve trusted vehicles, monitor booking status in real time, and get transparent billing from pickup to return.</p>
                <div class="landing-hero-actions">
                    <a href="/register.php" class="primary-btn">Start your journey</a>
                    <a href="/login.php" class="ghost-btn">I already have an account</a>
                </div>
            </div>

            <aside class="landing-hero-visual">
                <div class="landing-hero-panel">
                    <p class="landing-panel-title">Live Fleet Snapshot</p>
                    <ul class="landing-mini-list">
                        <li><span>Vehicles online</span><strong>48</strong></li>
                        <li><span>Active rentals</span><strong>31</strong></li>
                        <li><span>On-time updates</span><strong>99%</strong></li>
                    </ul>
                </div>
            </aside>
        </div>
    </section>

    <section class="landing-stat-section reveal reveal-delay-2">
        <article class="landing-stat-card">
            <span class="landing-proof-icon">🚙</span>
            <div class="landing-proof-content">
                <strong>48+</strong>
                <span>Vehicles managed daily</span>
            </div>
        </article>
        <article class="landing-stat-card shift">
            <span class="landing-proof-icon">🧭</span>
            <div class="landing-proof-content">
                <strong>31</strong>
                <span>Active rentals in progress</span>
            </div>
        </article>
        <article class="landing-stat-card">
            <span class="landing-proof-icon">📊</span>
            <div class="landing-proof-content">
                <strong>99%</strong>
                <span>On-time booking updates</span>
            </div>
        </article>
    </section>

    <section class="landing-section reveal reveal-delay-2" id="why">
        <div class="landing-section-head">
            <span class="landing-section-kicker">Our Advantage</span>
            <h3>Precision in every mile.</h3>
            <p>Everything you need before, during, and after a rental.</p>
        </div>

        <div class="landing-bento-grid">
            <article class="landing-bento-card wide">
                <span class="landing-feature-icon">🚙</span>
                <h4>Instant booking visibility</h4>
                <p>Track confirmation, payment, and schedule updates with clear status indicators from reservation to return.</p>
            </article>

            <article class="landing-bento-card accent">
                <span class="landing-feature-icon">🛡️</span>
                <h4>Reliable vehicle options</h4>
                <p>Choose sedans, SUVs, vans, and pickups with transparent rates and real availability.</p>
            </article>

            <article class="landing-bento-card compact">
                <span class="landing-feature-icon">💳</span>
                <h4>Transparent billing flow</h4>
                <p>View charges, invoices, and payment status in one place.</p>
            </article>

            <article class="landing-bento-card split">
                <span class="landing-feature-icon">🕒</span>
                <h4>24/7 support routing</h4>
                <p>Our support workflows keep customers informed with timely updates and clear turnaround expectations.</p>
            </article>
        </div>
    </section>

    <section class="landing-section reveal reveal-delay-3" id="team">
        <div class="landing-section-head">
            <span class="landing-section-kicker">The Team</span>
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

    <footer class="landing-footer">
        <p>&copy; 2026 Mobilis. Built for reliable, transparent vehicle rentals.</p>
    </footer>
</main>
</body>
</html>

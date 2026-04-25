<?php
declare(strict_types=1);

$title = (string) ($title ?? 'Mobilis');
$activeNav = (string) ($active_nav ?? '');
$role = (string) ($role ?? 'staff');
$showSearch = (bool) ($show_search ?? false);
$showPrimaryCta = (bool) ($show_primary_cta ?? false);
$primaryCtaLabel = (string) ($primary_cta_label ?? '+ New booking');
$initials = (string) ($initials ?? 'MB');
$today = (string) ($today ?? date('l, F j, Y'));
$content = (string) ($content ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= baseUrl() ?>/">
    <title><?= htmlspecialchars($title) ?> | Mobilis</title>
    <link rel="icon" type="image/png" href="<?= baseUrl() ?>/assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= baseUrl() ?>/assets/styles.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand"><img src="<?= baseUrl() ?>/assets/images/logo.png" alt="Mobilis logo" class="brand-logo"></div>
        <nav class="nav">
            <?php foreach (navSections($role) as $group): ?>
                <section class="nav-group">
                    <p class="nav-section-title"><?= htmlspecialchars((string) $group['section']) ?></p>
                    <?php foreach ($group['items'] as $item): ?>
                        <?php $isActive = ((string) $item['key'] === $activeNav) ? 'active' : ''; ?>
                        <a class="nav-item <?= $isActive ?>" href="<?= htmlspecialchars((string) $item['href']) ?>">
                            <span class="nav-item-main">
                                <span class="nav-icon"><?= htmlspecialchars((string) $item['icon']) ?></span>
                                <span class="nav-label"><?= htmlspecialchars((string) $item['label']) ?></span>
                            </span>
                            <?php if (isset($item['badge']) && (string) $item['badge'] !== ''): ?>
                                <?php $badgeClass = isset($item['badge_class']) ? ' nav-badge-' . (string) $item['badge_class'] : ''; ?>
                                <span class="nav-badge<?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars((string) $item['badge']) ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-footer-meta">
                <p class="sidebar-user-label">Signed in as</p>
                <p class="sidebar-user-role"><?= htmlspecialchars(ucfirst($role)) ?></p>
            </div>
            <a class="sidebar-logout-btn" href="<?= baseUrl() ?>/logout.php">Sign out</a>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div><h2><?= htmlspecialchars($title) ?></h2><p><?= htmlspecialchars($today) ?></p></div>
            <div class="topbar-right">
                <?php if ($showSearch): ?>
                    <input type="search" placeholder="Search vehicles, customers...">
                <?php endif; ?>
                <?php if ($showPrimaryCta): ?>
                    <button class="primary-btn" type="button"><?= htmlspecialchars($primaryCtaLabel) ?></button>
                <?php endif; ?>
                <span class="avatar"><?= htmlspecialchars($initials) ?></span>
            </div>
        </header>

        <?= $content ?>
    </main>
</div>
<script src="<?= baseUrl() ?>/assets/app.js"></script>
</body>
</html>

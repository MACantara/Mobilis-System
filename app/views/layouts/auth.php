<?php
declare(strict_types=1);

$title = (string) ($title ?? 'Mobilis');
$bodyClass = (string) ($body_class ?? 'public-auth-body');
$content = (string) ($content ?? '');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> | Mobilis</title>
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="<?= htmlspecialchars($bodyClass) ?>">
<main class="auth-split-shell">
    <?= $content ?>
</main>
<script src="/assets/app.js"></script>
</body>
</html>

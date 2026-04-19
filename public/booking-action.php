<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: bookings.php');
    exit;
}

$rentalId = (int) ($_POST['id'] ?? 0);
$action = trim((string) ($_POST['action'] ?? ''));
$redirect = trim((string) ($_POST['redirect'] ?? 'bookings.php'));

$target = 'bookings.php';
if ($redirect !== '' && str_starts_with($redirect, 'bookings.php')) {
    $target = $redirect;
}

$result = applyBookingAction($rentalId, $action);
$notice = ($result['ok'] ?? false) ? 'action_success' : 'action_error';

$separator = str_contains($target, '?') ? '&' : '?';
header('Location: ' . $target . $separator . 'notice=' . urlencode($notice));
exit;

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff', 'customer']);

header('Content-Type: application/json; charset=utf-8');

$user = currentUser() ?? [];
$snapshot = getLiveTrackingSnapshot($user, 200, 5);

echo json_encode([
    'ok' => true,
    'source' => dbConnected() ? 'mysql' : 'fallback',
    'role' => (string) ($user['role'] ?? 'staff'),
    'generated_at' => $snapshot['generated_at'],
    'step_seconds' => $snapshot['step_seconds'],
    'center' => $snapshot['center'],
    'vehicles' => $snapshot['vehicles'],
], JSON_UNESCAPED_UNICODE);

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff', 'customer']);

header('Content-Type: application/json; charset=utf-8');

$payload = [
    'generated_at' => date('c'),
    'metrics' => getDashboardMetrics(),
    'vehicles' => getVehicles(25),
    'bookings' => getBookings(30),
    'maintenance' => getMaintenanceBacklog(),
];

$insights = generatePythonInsights($payload);

echo json_encode([
    'ok' => true,
    'source' => dbConnected() ? 'mysql' : 'fallback',
    'payload' => $payload,
    'insights' => $insights,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

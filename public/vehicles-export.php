<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
requireAuth(['admin', 'staff']);

function vehicleCategoryKeyExport(string $category): string
{
    $normalized = strtolower(trim($category));
    if (str_contains($normalized, 'suv')) {
        return 'suv';
    }
    if (str_contains($normalized, 'sedan')) {
        return 'sedan';
    }
    if (str_contains($normalized, 'van')) {
        return 'van';
    }
    if (str_contains($normalized, 'pickup')) {
        return 'pickup';
    }
    return 'other';
}

$status = strtolower((string) ($_GET['status'] ?? 'all'));
$category = strtolower((string) ($_GET['category'] ?? 'all'));
$q = trim((string) ($_GET['q'] ?? ''));

$rows = [];
foreach (getVehicles(2000) as $vehicle) {
    $vehicleStatus = strtolower((string) ($vehicle['status'] ?? 'available'));
    $vehicleCategory = vehicleCategoryKeyExport((string) ($vehicle['category_name'] ?? ''));

    if ($status !== 'all' && $vehicleStatus !== $status) {
        continue;
    }
    if ($category !== 'all' && $vehicleCategory !== $category) {
        continue;
    }

    if ($q !== '') {
        $haystack = strtolower(
            (string) ($vehicle['name'] ?? '') . ' ' .
            (string) ($vehicle['plate'] ?? '') . ' ' .
            (string) ($vehicle['category_name'] ?? '')
        );

        if (!str_contains($haystack, strtolower($q))) {
            continue;
        }
    }

    $rows[] = $vehicle;
}

$filename = 'mobilis-vehicles-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'wb');
if ($out === false) {
    http_response_code(500);
    echo 'Failed to generate export file.';
    exit;
}

fputcsv($out, ['Vehicle ID', 'Name', 'Plate', 'Category', 'Year', 'Color', 'Mileage (km)', 'Status', 'Rate/day']);

foreach ($rows as $vehicle) {
    fputcsv($out, [
        (int) ($vehicle['vehicle_id'] ?? 0),
        (string) ($vehicle['name'] ?? ''),
        (string) ($vehicle['plate'] ?? ''),
        (string) ($vehicle['category_name'] ?? ''),
        (string) ($vehicle['year'] ?? ''),
        (string) ($vehicle['color'] ?? ''),
        (int) ($vehicle['mileage_km'] ?? 0),
        (string) ($vehicle['status'] ?? ''),
        (float) ($vehicle['daily_rate'] ?? 0),
    ]);
}

fclose($out);
exit;

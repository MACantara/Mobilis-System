<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

$customers = getCustomers(1000);

$filename = 'mobilis-customers-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'wb');
if ($output === false) {
    http_response_code(500);
    echo 'Failed to generate export file.';
    exit;
}

fputcsv($output, [
    'Customer ID',
    'Name',
    'Email',
    'Phone',
    'Tier',
    'Total Bookings',
    'Total Spent',
    'License Number',
    'License Expiry',
    'Address',
]);

foreach ($customers as $customer) {
    fputcsv($output, [
        (int) ($customer['customer_id'] ?? 0),
        (string) ($customer['name'] ?? ''),
        (string) ($customer['email'] ?? ''),
        (string) ($customer['phone'] ?? ''),
        (string) ($customer['tier'] ?? 'Regular'),
        (int) ($customer['bookings'] ?? 0),
        (float) ($customer['spent'] ?? 0),
        (string) ($customer['license_number'] ?? ''),
        (string) ($customer['license_expiry'] ?? ''),
        (string) ($customer['address'] ?? ''),
    ]);
}

fclose($output);
exit;

<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

function bookingStatusKeyExport(array $booking): string
{
    $status = strtolower((string) ($booking['status'] ?? 'pending'));
    $paymentStatus = strtolower((string) ($booking['payment_status'] ?? ''));

    if ($status === 'active' || $status === 'confirmed') {
        if ($paymentStatus === 'unpaid' || $paymentStatus === 'partial') {
            return 'awaiting-payment';
        }
        return 'confirmed';
    }

    return $status;
}

$q = trim((string) ($_GET['q'] ?? ''));
$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
$status = strtolower((string) ($_GET['status'] ?? 'all'));

$rows = [];
foreach (getBookings(2000) as $booking) {
    $booking['status_key'] = bookingStatusKeyExport($booking);

    $bookingIdText = 'BK-' . str_pad((string) ((int) ($booking['rental_id'] ?? 0)), 4, '0', STR_PAD_LEFT);
    $haystack = strtolower($bookingIdText . ' ' . (string) ($booking['customer'] ?? '') . ' ' . (string) ($booking['vehicle'] ?? ''));

    if ($q !== '' && !str_contains($haystack, strtolower($q))) {
        continue;
    }

    $pickupDate = (string) ($booking['pickup_date'] ?? '');
    if ($from !== '' && $pickupDate < $from) {
        continue;
    }
    if ($to !== '' && $pickupDate > $to) {
        continue;
    }

    if ($status !== 'all' && (string) ($booking['status_key'] ?? '') !== $status) {
        continue;
    }

    $rows[] = $booking;
}

$filename = 'mobilis-bookings-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'wb');
if ($out === false) {
    http_response_code(500);
    echo 'Failed to generate export file.';
    exit;
}

fputcsv($out, ['Booking ID', 'Customer', 'Vehicle', 'Pickup Date', 'Return Date', 'Days', 'Total', 'Status', 'Payment Status']);

foreach ($rows as $booking) {
    fputcsv($out, [
        'BK-' . str_pad((string) ((int) ($booking['rental_id'] ?? 0)), 4, '0', STR_PAD_LEFT),
        (string) ($booking['customer'] ?? ''),
        (string) ($booking['vehicle'] ?? ''),
        (string) ($booking['pickup_date'] ?? ''),
        (string) ($booking['return_date'] ?? ''),
        (int) ($booking['days'] ?? 0),
        (float) ($booking['total'] ?? 0),
        (string) ($booking['status_key'] ?? ''),
        (string) ($booking['payment_status'] ?? ''),
    ]);
}

fclose($out);
exit;

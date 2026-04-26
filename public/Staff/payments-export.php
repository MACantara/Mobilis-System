<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';
requireAuth(['admin', 'staff']);

// Configuration for Python scripts
$pythonScriptPath = __DIR__ . '/../../python-scripts';

// Get export format (default to CSV)
$format = $_GET['format'] ?? 'csv';
if (!in_array($format, ['csv', 'xlsx', 'pdf'])) {
    $format = 'csv';
}

// Get filters
$status = $_GET['status'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$search = $_GET['q'] ?? '';

// Generate temp filename
$tempDir = sys_get_temp_dir();
$filename = 'mobilis-payments-' . date('Ymd-His') . ".{$format}";
$tempFile = $tempDir . DIRECTORY_SEPARATOR . $filename;

// Call Python script
$pythonCmd = escapeshellcmd("python {$pythonScriptPath}/export_payments.py " . 
    escapeshellarg($status) . ' ' . 
    escapeshellarg($from) . ' ' . 
    escapeshellarg($to) . ' ' . 
    escapeshellarg($search) . ' ' . 
    escapeshellarg($format) . ' ' . 
    escapeshellarg($tempFile));

exec($pythonCmd, $output, $returnCode);

if ($returnCode === 0 && file_exists($tempFile)) {
    // Set content type based on format
    $contentTypes = [
        'csv' => 'text/csv; charset=utf-8',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pdf' => 'application/pdf'
    ];
    
    header('Content-Type: ' . ($contentTypes[$format] ?? 'text/csv'));
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($tempFile));
    readfile($tempFile);
    unlink($tempFile);
    exit;
}

// Fallback to PHP CSV export
$status = strtolower((string) ($_GET['status'] ?? 'all'));
$from = trim((string) ($_GET['from'] ?? ''));
$to = trim((string) ($_GET['to'] ?? ''));
$q = trim((string) ($_GET['q'] ?? ''));

$payments = getInvoices(2000);

$rows = [];
foreach ($payments as $payment) {
    $paymentStatus = strtolower((string) ($payment['payment_status'] ?? ''));
    
    if ($status !== 'all' && $paymentStatus !== $status) {
        continue;
    }
    
    $issuedAt = (string) ($payment['issued_at'] ?? '');
    if ($from !== '' && $issuedAt < $from) {
        continue;
    }
    if ($to !== '' && $issuedAt > $to) {
        continue;
    }
    
    if ($q !== '') {
        $haystack = strtolower(
            (string) ($payment['invoice_id'] ?? '') . ' ' .
            (string) ($payment['rental_id'] ?? '') . ' ' .
            (string) ($payment['customer'] ?? '') . ' ' .
            (string) ($payment['vehicle'] ?? '')
        );
        
        if (!str_contains($haystack, strtolower($q))) {
            continue;
        }
    }
    
    $rows[] = $payment;
}

$filename = 'mobilis-payments-' . date('Ymd-His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'wb');
if ($out === false) {
    http_response_code(500);
    echo 'Failed to generate export file.';
    exit;
}

fputcsv($out, ['Invoice ID', 'Rental ID', 'Customer', 'Vehicle', 'Base Amount', 'Late Fee', 'Damage Fee', 'Total', 'Payment Status', 'Payment Method', 'Issued At']);

foreach ($rows as $payment) {
    fputcsv($out, [
        (int) ($payment['invoice_id'] ?? 0),
        (int) ($payment['rental_id'] ?? 0),
        (string) ($payment['customer'] ?? ''),
        (string) ($payment['vehicle'] ?? ''),
        (float) ($payment['base_amount'] ?? 0),
        (float) ($payment['late_fee'] ?? 0),
        (float) ($payment['damage_fee'] ?? 0),
        (float) ($payment['total_amount'] ?? 0),
        (string) ($payment['payment_status'] ?? ''),
        (string) ($payment['payment_method'] ?? ''),
        (string) ($payment['issued_at'] ?? ''),
    ]);
}

fclose($out);
exit;

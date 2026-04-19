<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

$code = (int) ($_GET['code'] ?? 500);
$title = (string) ($_GET['title'] ?? 'Server Error');
$message = (string) ($_GET['message'] ?? 'Something went wrong. Please try again later.');

$validCodes = [400, 403, 404, 500, 503];
if (!in_array($code, $validCodes, true)) {
    $code = 500;
}

http_response_code($code);
?>
<?php
renderErrorPageTop($code, $title);
renderErrorBrandPanel($title, $message);
renderErrorFormPanel($code, $title, 'An error occurred while processing your request.');
renderErrorPageBottom();

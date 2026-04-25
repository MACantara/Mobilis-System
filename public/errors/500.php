<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

$backUrl = isAuthenticated() ? currentUserHomePath() : baseUrl() . '/index.php';

http_response_code(500);
?>
<?php
viewBegin('error', ['code' => 500, 'title' => 'Server Error']);
viewErrorBrandPanel('Server Error', 'Something went wrong on our end. Please try again later or contact support if the problem persists.');
viewErrorFormPanel(500, 'Server Error', 'An unexpected error occurred while processing your request. Our team has been notified.', $backUrl, false);
viewEnd();
?>

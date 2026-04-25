<?php
declare(strict_types=1);

require_once __DIR__ . '/../../app/bootstrap.php';

$backUrl = isAuthenticated() ? currentUserHomePath() : baseUrl() . '/index.php';

http_response_code(403);
?>
<?php
viewBegin('error', ['code' => 403, 'title' => 'Forbidden']);
viewErrorBrandPanel('Access Denied', 'You don\'t have permission to access this page. Please contact your administrator if you believe this is an error.');
viewErrorFormPanel(403, 'Forbidden', 'The page you\'re trying to access requires special permissions.', $backUrl, false);
viewEnd();
?>

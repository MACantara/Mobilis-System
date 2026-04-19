<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

http_response_code(403);
?>
<?php
renderErrorPageTop(403, 'Forbidden');
renderErrorBrandPanel('Access Denied', 'You don\'t have permission to access this page. Please contact your administrator if you believe this is an error.');
renderErrorFormPanel(403, 'Forbidden', 'The page you\'re trying to access requires special permissions.', '/index.php');
?>
            <a href="/logout.php" class="ghost-link button-like" style="display: block; text-align: center; margin-top: 12px;">Sign out</a>
<?php renderErrorPageBottom(); ?>

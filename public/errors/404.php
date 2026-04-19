<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

http_response_code(404);
?>
<?php
renderErrorPageTop(404, 'Not Found');
renderErrorBrandPanel('Page Not Found', 'The page you\'re looking for doesn\'t exist or has been moved. Please check the URL and try again.');
renderErrorFormPanel(404, 'Not Found', 'We couldn\'t find the page you\'re looking for.');
renderErrorPageBottom();

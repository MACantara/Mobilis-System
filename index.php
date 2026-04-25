<?php
declare(strict_types=1);

// Router to proxy requests from /Mobilis-System to public/ folder for XAMPP

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Extract the path after /Mobilis-System/
$basePath = '/Mobilis-System';
$path = $requestUri;

// If mod_rewrite is active, use the rewritten path
if (isset($_SERVER['REDIRECT_URL'])) {
    $path = $_SERVER['REDIRECT_URL'];
}

// Remove query string
if (($pos = strpos($path, '?')) !== false) {
    $path = substr($path, 0, $pos);
}

// Remove base path if present
if (strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Normalize path - remove leading slash and ensure it's not empty
$path = ltrim($path, '/');
if ($path === '' || $path === '/') {
    $path = 'index.php';
}

$publicDir = __DIR__ . '/public';
$requestedFile = $publicDir . '/' . $path;

// Debug: log the path resolution
error_log("Router: path='$path', requestedFile='$requestedFile', exists=" . (file_exists($requestedFile) ? 'yes' : 'no'));

// Security: prevent directory traversal
$realRequested = realpath($requestedFile);
$realPublic = realpath($publicDir);
if ($realRequested === false || strpos($realRequested, $realPublic) !== 0) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

// Check if file exists
if (!file_exists($requestedFile)) {
    // Try adding .php if it's a directory request without extension
    if (is_dir($requestedFile)) {
        $requestedFile = rtrim($requestedFile, '/') . '/index.php';
    }
    
    if (!file_exists($requestedFile)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }
}

// Determine file extension
$extension = strtolower(pathinfo($requestedFile, PATHINFO_EXTENSION));

// Static asset handling
$staticExtensions = ['css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'ico', 'woff', 'woff2', 'ttf', 'eot'];
if (in_array($extension, $staticExtensions, true)) {
    // Set correct content type
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];
    
    $contentType = $mimeTypes[$extension] ?? 'application/octet-stream';
    header('Content-Type: ' . $contentType);
    
    // Enable caching for static assets
    header('Cache-Control: public, max-age=31536000');
    
    // Serve the file
    readfile($requestedFile);
    exit;
}

// PHP file handling
if ($extension === 'php' || is_file($requestedFile)) {
    // Change to public directory so relative includes work correctly
    chdir($publicDir);
    
    // Set the script filename to the actual file being executed
    $_SERVER['SCRIPT_FILENAME'] = $requestedFile;
    $_SERVER['SCRIPT_NAME'] = $basePath . '/' . $path;
    $_SERVER['PHP_SELF'] = $basePath . '/' . $path;
    
    // Include the PHP file
    include $requestedFile;
    exit;
}

// If we get here, it's a file we can't handle
http_response_code(403);
echo 'Forbidden';
exit;

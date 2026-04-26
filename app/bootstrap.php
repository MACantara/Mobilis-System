<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (!function_exists('appConfig')) {
    function appConfig(): array
    {
        static $config = null;

        if ($config === null) {
            $config = require __DIR__ . '/config.php';
        }

        return $config;
    }
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/view_helpers.php';

if (!function_exists('baseUrl')) {
    function baseUrl(): string
    {
        // Detect if we're running via XAMPP router (localhost/Mobilis-System)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        if (strpos($scriptName, '/Mobilis-System') !== false) {
            return '/Mobilis-System';
        }
        return '';
    }
}

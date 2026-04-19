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
require_once __DIR__ . '/python_bridge.php';
require_once __DIR__ . '/layout.php';

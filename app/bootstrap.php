<?php
declare(strict_types=1);

if (!function_exists('loadProjectEnv')) {
    function loadProjectEnv(): void
    {
        $envPath = dirname(__DIR__) . '/.env';
        if (!is_file($envPath) || !is_readable($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            $separatorPos = strpos($line, '=');
            if ($separatorPos === false) {
                continue;
            }

            $name = trim(substr($line, 0, $separatorPos));
            $value = trim(substr($line, $separatorPos + 1));

            if ($name === '') {
                continue;
            }

            $quoted = strlen($value) >= 2
                && (($value[0] === '"' && $value[strlen($value) - 1] === '"')
                    || ($value[0] === "'" && $value[strlen($value) - 1] === "'"));

            if ($quoted) {
                $value = substr($value, 1, -1);
            }

            $current = getenv($name);
            if ($current === false || $current === '') {
                putenv($name . '=' . $value);
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

loadProjectEnv();

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
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/view.php';
require_once __DIR__ . '/view_helpers.php';

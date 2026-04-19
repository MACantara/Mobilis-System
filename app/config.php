<?php
declare(strict_types=1);

return [
    'app_name' => 'Mobilis Vehicle Rental',
    'db' => [
        'host' => getenv('MOBILIS_DB_HOST') ?: '127.0.0.1',
        'port' => getenv('MOBILIS_DB_PORT') ?: '3306',
        'name' => getenv('MOBILIS_DB_NAME') ?: 'mobilis_db',
        'user' => getenv('MOBILIS_DB_USER') ?: 'root',
        'pass' => getenv('MOBILIS_DB_PASS') ?: '',
    ],
    'python_bin' => getenv('MOBILIS_PYTHON_BIN') ?: 'python3',
    'mileage_alert_threshold' => 80000,
];

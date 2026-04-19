<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';
logoutUser();
header('Location: /Customer/login.php');
exit;

<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

\App\Support\DotEnv::load(__DIR__ . '/../');

// set database creds
\App\Database\Database::setCreds(
    $_SERVER['DB_HOST'],
    $_SERVER['DB_NAME'],
    $_SERVER['DB_USER'],
    $_SERVER['DB_PASS'],
    (int)$_SERVER['DB_PORT']
);

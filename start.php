<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

$server = new \App\Network\Server();
$server->start();

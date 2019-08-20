<?php
declare(strict_types=1);

require_once 'vendor/autoload.php';

$server = new \App\Network\Server();
$server->start();

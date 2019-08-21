<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

$pdo = \App\Database\Database::getPdo();

$pdo->beginTransaction();

$sql = <<<SQL
CREATE TABLE IF NOT EXISTS users (
    id SERIAL NOT NULL,
    email text NOT NULL UNIQUE,
    name text NOT NULL UNIQUE,
    password text NOT NULL
)
SQL;

$pdo->exec($sql);

$pdo->commit();
<?php
declare(strict_types=1);

namespace App\Database;

use App\Database\Repository\UserRepository;

class Database
{
    private static ?\PDO $pdo = null;

    private static ?UserRepository $userRepository = null;

    public static function getPdo(): \PDO
    {
        if (null === static::$pdo) {
            static::$pdo = new \PDO(
                'pgsql:host=127.0.0.1;dbname=ussr_poker',
                'ussr_poker',
                '2668210h',
            );
            static::$pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
            static::$pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
            static::$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }

        return static::$pdo;
    }

    public static function getUserRepository(): UserRepository
    {
        $pdo = static::getPdo();

        if (null === static::$userRepository) {
            static::$userRepository = new UserRepository($pdo);
        }

        return static::$userRepository;
    }
}
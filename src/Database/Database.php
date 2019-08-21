<?php
declare(strict_types=1);

namespace App\Database;

use App\Database\Repository\UserRepository;

class Database
{
    private static ?\PDO $pdo = null;

    private static ?UserRepository $userRepository = null;

    private static string $host = '127.0.0.1';
    private static string $name = 'ussr_poker';
    private static string $user = '';
    private static string $pass = '';
    private static int $port = 5432;

    public static function setCreds(string $host, string $name, string $user, string $pass, int $port = 5432): void
    {
        static::$host = $host;
        static::$name = $name;
        static::$user = $user;
        static::$pass = $pass;
        static::$port = $port;
    }

    public static function getPdo(): \PDO
    {
        if (null === static::$pdo) {
            $host = static::$host;
            $name = static::$name;
            $user = static::$user;
            $pass = static::$pass;
            $port = static::$port;

            static::$pdo = new \PDO(
                "pgsql:host={$host};dbname={$name};port={$port}",
                $user,
                $pass,
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

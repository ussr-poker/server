<?php
declare(strict_types=1);

namespace App\Support;

class DotEnv
{
    /**
     * Load config from ENV vars/CLI or ".env" files
     *
     * @param string $path
     */
    public static function load(string $path): void
    {
        $dotenv = \Dotenv\Dotenv::create($path);
        $dotenv->safeLoad();

        $dotenv->required('DB_HOST')->notEmpty();
        $dotenv->required('DB_NAME')->notEmpty();
        $dotenv->required('DB_USER')->notEmpty();
        $dotenv->required('DB_PASS');
        $dotenv->required('DB_PORT')->isInteger();
    }
}

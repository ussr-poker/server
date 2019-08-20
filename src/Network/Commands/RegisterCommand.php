<?php
declare(strict_types=1);

namespace App\Network\Commands;

use App\Database\Database;
use App\Database\Entity\User;
use App\Network\Client;

class RegisterCommand implements CommandInterface
{
    public function handle(Client $client, $data): User
    {
        [$email, $password, $username] = [$data['email'] ?? null, $data['password'] ?? null, $data['name'] ?? null];
        if (empty($email) || empty($password) || empty($username)) {
            throw new \InvalidArgumentException('Specify "email", "password", "name"');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Specify valid email');
        }

        $repository = Database::getUserRepository();
        if ($repository->isEmailTaken($email)) {
            throw new \InvalidArgumentException('This email already taken');
        }
        if ($repository->isNameTaken($username)) {
            throw new \InvalidArgumentException('This username already taken');
        }

        $hash = \password_hash($password, PASSWORD_BCRYPT);

        return $repository->create($email, $hash, $username);
    }
}
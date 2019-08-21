<?php
declare(strict_types=1);

namespace App\Database\Repository;

use App\Database\Entity\User;

class UserRepository
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function create(string $email, string $password, string $name): User
    {
        $sql = <<<SQL
INSERT INTO users (email, password, name) VALUES (:email, :password, :name)
RETURNING *
SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $password);
        $stmt->bindParam(':name', $name);
        $stmt->execute();

        return $stmt->fetchObject(User::class);
    }

    public function getByEmailOrName(string $value): ?User
    {
        $sql = <<<SQL
SELECT * FROM users
WHERE name = :value OR email = :value
LIMIT 1
SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':value', $value);
        $stmt->execute();

        $res = $stmt->fetchObject(User::class);
        if (false === $res) {
            return null;
        }

        return $res;
    }

    public function isEmailTaken(string $email): bool
    {
        $sql = <<<SQL
SELECT 1 FROM users
WHERE email = ?
LIMIT 1
SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $email);
        $stmt->execute();

        return 0 !== $stmt->rowCount();
    }

    public function isNameTaken(string $name): bool
    {
        $sql = <<<SQL
SELECT 1 FROM users
WHERE name = ?
LIMIT 1
SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, $name);
        $stmt->execute();

        return 0 !== $stmt->rowCount();
    }
}

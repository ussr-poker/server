<?php
declare(strict_types=1);

namespace App\Database\Entity;

class User implements \JsonSerializable
{
    public int $id;

    public string $name;

    public string $email;

    public string $password;

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
        ];
    }
}
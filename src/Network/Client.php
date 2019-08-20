<?php
declare(strict_types=1);

namespace App\Network;

use App\Database\Entity\User;

class Client
{
    private int $fd;

    private ?User $user = null;

    public function __construct(int $fd)
    {
        $this->fd = $fd;
    }

    public function getFd(): int
    {
        return $this->fd;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }
}
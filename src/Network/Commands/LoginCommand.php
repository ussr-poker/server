<?php
declare(strict_types=1);

namespace App\Network\Commands;

use App\Database\Database;
use App\Database\Entity\User;
use App\Network\Client;
use App\Network\Server;

class LoginCommand implements CommandInterface
{
    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle(Client $client, $data): User
    {
        [$username, $password] = [$data['username'] ?? null, $data['password'] ?? null];
        if (empty($username) || empty($password)) {
            throw new \InvalidArgumentException('Specify "username", "password"');
        }

        $repository = Database::getUserRepository();
        $user = $repository->getByEmailOrName($username);
        if (null === $user) {
            throw new \InvalidArgumentException('User not found');
        }

        if (!\password_verify($password, $user->password)) {
            throw new \InvalidArgumentException('Invalid password');
        }

        $client->setUser($user);

        // update client reference in "player"
        foreach ($this->server->getRooms() as $room) {
            foreach ($room->getPlayers() as $player) {
                if ($player->id === $client->getUser()->id) {
                    $player->setClient($client);
                    break 2;
                }
            }
        }

        return $user;
    }
}
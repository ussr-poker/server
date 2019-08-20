<?php
declare(strict_types=1);

namespace App\Network\Commands;

use App\Game\Player;
use App\Network\Client;
use App\Network\Commands\Traits\ClientAuthenticated;
use App\Network\Server;

class JoinRoomCommand implements CommandInterface
{
    use ClientAuthenticated;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle(Client $client, $data): void
    {
        $this->checkClientIsAuthenticated($client);

        $roomId = (int)($data['roomId'] ?? 0);
        if (0 === $roomId) {
            throw new \InvalidArgumentException('Specify "roomId"');
        }

        $room = $this->server->getRoom($roomId);
        $room->joinPlayer(new Player($client));
    }
}
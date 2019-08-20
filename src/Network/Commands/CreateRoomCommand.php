<?php
declare(strict_types=1);

namespace App\Network\Commands;

use App\Game\Player;
use App\Network\Client;
use App\Network\Commands\Traits\ClientAuthenticated;
use App\Network\Server;

class CreateRoomCommand implements CommandInterface
{
    use ClientAuthenticated;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle(Client $client, $data): array
    {
        $this->checkClientIsAuthenticated($client);

        [$players, $deckSize] = [(int)($data['players'] ?? 0), (int)($data['deckSize'] ?? 0)];

        $room = $this->server->createRoom($players, $deckSize);
        $room->joinPlayer(new Player($client));

        return ['id' => $room->getId()];
    }
}
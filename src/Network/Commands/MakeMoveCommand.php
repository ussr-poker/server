<?php
declare(strict_types=1);

namespace App\Network\Commands;

use App\Game\Cards\CardSuit;
use App\Game\Round\JokerMove;
use App\Network\Client;
use App\Network\Commands\Traits\ClientAuthenticated;
use App\Network\Server;

class MakeMoveCommand implements CommandInterface
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

        [$roomId, $cardId, $jokerMoveRaw] = [
            (int)($data['roomId'] ?? 0),
            (int)($data['cardId'] ?? 0),
            $data['jokerMove'] ?? null
        ];
        if (0 === $roomId) {
            throw new \InvalidArgumentException('Specify "roomId"');
        }

        if (null !== $jokerMoveRaw) {
            if (!isset($jokerMoveRaw['mode'], $jokerMoveRaw['suit'])) {
                throw new \InvalidArgumentException('Specify jokerMove "mode", "suit"');
            }

            $jokerMoveMode = (int)$jokerMoveRaw['mode'];
            $jokerMoveSuit = (int)$jokerMoveRaw['suit'];

            $jokerMove = new JokerMove($jokerMoveMode, CardSuit::from($jokerMoveSuit));
        } else {
            $jokerMove = null;
        }

        $room = $this->server->getRoom($roomId);
        $player = $room->getPlayerById($client->getUser()->id);

        $room->getGame()->makeMove($player, $cardId, $jokerMove);
    }
}
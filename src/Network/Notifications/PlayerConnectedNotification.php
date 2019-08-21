<?php
declare(strict_types=1);

namespace App\Network\Notifications;

use App\Network\Server;

class PlayerConnectedNotification implements NotificationInterface
{
    public const ID = 1011;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle($playerId): void
    {
        foreach ($this->server->getRooms() as $room) {
            foreach ($room->getPlayers() as $roomPlayer) {
                if ($roomPlayer->id === $playerId) {
                    $this->server->broadcast($room, self::ID, ['playerId' => $roomPlayer->id], $roomPlayer->id);
                    break;
                }
            }
        }
    }

    public function getId(): int
    {
        return self::ID;
    }
}

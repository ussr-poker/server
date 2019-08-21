<?php
declare(strict_types=1);

namespace App\Network\Notifications;

use App\Game\Events\PlayerJoinedEvent;
use App\Network\Server;

class PlayerJoinedNotification implements NotificationInterface
{
    public const ID = 1000;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle($event): void
    {
        if (!$event instanceof PlayerJoinedEvent) {
            throw new \InvalidArgumentException('PlayerJoinedEvent must be passed');
        }

        $player = $event->getPlayer();
        $data = [
            'id' => $player->id,
            'name' => $player->name
        ];

        $this->server->broadcast($event->getRoom(), self::ID, $data, $player->id);
    }

    public function getId(): int
    {
        return self::ID;
    }
}

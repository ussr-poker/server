<?php
declare(strict_types=1);

namespace App\Network\Notifications;

use App\Game\Events\PlayerStakeEvent;
use App\Network\Server;

class PlayerStakeNotification implements NotificationInterface
{
    public const ID = 1003;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle($event): void
    {
        if (!$event instanceof PlayerStakeEvent) {
            throw new \InvalidArgumentException('PlayerStakeEvent must be passed');
        }

        $playerStake = $event->getPlayerStake();

        $data = [
            'playerId' => $playerStake->getPlayer()->id,
            'stake' => $playerStake->getStake(),
            'awaitedPlayerId' => $event->getRound()->getPlayerAwaited()->id ?? null
        ];

        $this->server->broadcast($event->getRound()->getGame()->getRoom(), self::ID, $data);
    }

    public function getId(): int
    {
        return self::ID;
    }
}

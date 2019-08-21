<?php
declare(strict_types=1);

namespace App\Network\Notifications;

use App\Game\Events\SubRoundStartedEvent;
use App\Network\Server;

class SubRoundStartedNotification implements NotificationInterface
{
    public const ID = 1005;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle($event): void
    {
        if (!$event instanceof SubRoundStartedEvent) {
            throw new \InvalidArgumentException('SubRoundStartedEvent must be passed');
        }

        $subRound = $event->getSubRound();

        $data = [
            'number' => $subRound->getNumber(),
            'awaitedPlayerId' => $subRound->getRound()->getPlayerAwaited()->id ?? null
        ];

        $this->server->broadcast($subRound->getRound()->getGame()->getRoom(), self::ID, $data);
    }

    public function getId(): int
    {
        return self::ID;
    }
}

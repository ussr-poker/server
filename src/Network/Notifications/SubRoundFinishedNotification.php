<?php
declare(strict_types=1);

namespace App\Network\Notifications;

use App\Game\Events\SubRoundFinishedEvent;
use App\Network\Server;

class SubRoundFinishedNotification implements NotificationInterface
{
    public const ID = 1006;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle($event): void
    {
        if (!$event instanceof SubRoundFinishedEvent) {
            throw new \InvalidArgumentException('SubRoundFinishedEvent must be passed');
        }

        $subRound = $event->getSubRound();

        $data = [
            'winnerId' => $event->getWinner()->id,
            'number' => $subRound->getNumber()
        ];

        $this->server->broadcast($subRound->getRound()->getGame()->getRoom(), self::ID, $data);
    }

    public function getId(): int
    {
        return self::ID;
    }
}

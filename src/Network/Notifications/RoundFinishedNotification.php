<?php
declare(strict_types=1);

namespace App\Network\Notifications;

use App\Game\Events\RoundFinishedEvent;
use App\Game\Round\RoundResult;
use App\Network\Server;

class RoundFinishedNotification implements NotificationInterface
{
    public const ID = 1002;

    private Server $server;

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function handle($event): void
    {
        if (!$event instanceof RoundFinishedEvent) {
            throw new \InvalidArgumentException('RoundFinishedEvent must be passed');
        }

        $round = $event->getRound();

        $results = \array_map(static function (RoundResult $result) {
            $playerStake = $result->getPlayerStake();

            return [
                'playerId' => $playerStake->getPlayer()->id,
                'stake' => $playerStake->getStake(),
                'wins' => $result->getWins(),
                'score' => $result->getScoreResult()
            ];
        }, $event->getRoundResults());

        $data = [
            'number' => $round->getNumber(),
            'winnerId' => $event->getWinner()->id,
            'results' => $results
        ];

        $this->server->broadcast($round->getGame()->getRoom(), self::ID, $data);
    }

    public function getId(): int
    {
        return self::ID;
    }
}
